<?php

namespace App\Services;

use App\Models\Team;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;
use Carbon\Carbon;

/**
 * Handles the business logic for syncing data from WooCommerce to Xero.
 */
class XeroIntegrationService
{
    private const BATCH_SIZE = 100; 
    private const DEFAULT_ACCOUNT_CODE = '200'; // Standard Revenue Account
    private const SKU_BATCH_SIZE = 25; 
    private const DEFAULT_TAX_TYPE = 'NONE'; 

    /**
     * Finds the correct Xero Account Code for a payment method based on user config.
     *
     * @param Team $team
     * @param string $paymentMethodId The ID (slug) of the WooCommerce payment method.
     * @return string|null
     */
    private static function getPaymentAccountCode(Team $team, string $paymentMethodId): ?string
    {
        // NOTE: Assuming $team->wc_payment_account_map is stored as a JSON string in the DB.
        $map = $team->woocommerceConnection->payment_account_map ? json_decode($team->woocommerceConnection->payment_account_map, true) : [];

        if (empty($map)) {
            Log::warning("No Xero payment map configured for user {$team->id}. Payment will fail without a default.");
            return null; 
        }

        return $map[$paymentMethodId] ?? null;
    }

    /**
     * Retrieves a list of Bank Accounts from Xero for mapping purposes.
     *
     * @param Team $team
     * @return array
     * @throws Exception
     */
    public static function getAccounts(Team $team, string $type = "BANK"): array
    {
        Log::info("Fetching Xero Bank Accounts for team {$team->id}, type {$type}");
        
        // Xero API filter to only retrieve accounts of type 'BANK'
        $response = XeroTokenService::makeApiCall($team, 'GET', 'Accounts', ['where' => 'Type=="'.$type.'"']);

        // Safely map the accounts, providing fallbacks for missing keys like 'Code'
        $accounts = collect($response['Accounts'] ?? [])
            ->map(fn($account) => [
                'Code' => $account['Code'] ?? $account['AccountID'] ?? '', 
                'Name' => $account['Name'],
                'Currency' => $account['CurrencyCode'] ?? 'N/A',
                // We'll filter out accounts that don't have a valid Code or ID later.
            ])
            ->filter(fn($account) => !empty($account['Code'])) // Filter out accounts with no useful identifier
            ->toArray();

        return $accounts;
    }

    /**
     * Executes the synchronization of recent WooCommerce orders to Xero Invoices and Payments.
     *
     * @param Team $team
     * @throws Exception
     */
    public static function syncOrdersToXero(Team $team, int $days = 2): void
    {
        // 1. Initialize SyncRun record
        $syncRun = SyncRun::create([
            'team_id' => $team->id,
            'status' => 'Running',
            'start_time' => Carbon::now(),
        ]);
        
        // Initialize metrics to track during the run
        $metrics = [
            'successful_invoices' => 0,
            'failed_invoices' => 0,
        ];

        try {
            Log::info("Starting TWO-PASS batched sync (rate-limit optimized) for team {$team->id}...");
            
            $orders = WoocommerceService::getRecentOrders($team, $days);
            if (empty($orders)) {
                Log::info("No recent WooCommerce orders found to sync.");
                // Update log record and exit
                $syncRun->update([
                    'status' => 'Success',
                    'end_time' => Carbon::now(),
                    'total_orders' => 0,
                ]);
                return;
            }

            $allOrders = collect($orders);
            $syncRun->update(['total_orders' => $allOrders->count()]); // Update total orders
            
            // --- PASS 0: Batch check for existing invoices ---
            $initialInvoiceMap = self::batchFindExistingInvoices($team, $allOrders);

            // Filter orders to only those that need processing (not already synced and paid)
            $ordersToProcess = $allOrders->filter(function($order) use ($initialInvoiceMap) {
                $wcOrderRef = (string) $order['id'];
                $existingInvoice = $initialInvoiceMap[$wcOrderRef] ?? null;
                
                $isInvoicePaid = ($existingInvoice['Status'] ?? null) === 'PAID';
                
                if ($isInvoicePaid) {
                    Log::info("WC Order {$order['id']} already synced and PAID in Xero. Skipping all processing.");
                    return false;
                }
                // Order needs processing if invoice doesn't exist or is not paid
                return true;
            });

            if ($ordersToProcess->isEmpty()) {
                Log::info("All recent orders are already synced and paid in Xero. No further processing needed.");
                 $syncRun->update([
                    'status' => 'Success',
                    'end_time' => Carbon::now(),
                ]);
                return;
            }
            
            // =========================================================================
            // Item Lookup & Creation
            // =========================================================================
            
            // 1. Collect all necessary SKU data for potential creation
            $skuData = [];
            $ordersToProcess->each(function ($order) use (&$skuData) {
                collect($order['line_items'] ?? [])->each(function ($item) use (&$skuData) {
                    $sku = $item['sku'] ?? null;
                    if ($sku) {
                        if (!isset($skuData[$sku])) {
                             $skuData[$sku] = [
                                'name' => $item['name'],
                                'price' => floatval($item['price']),
                                'tax_status' => $item['tax_status'] ?? 'none',
                            ];
                        }
                    }
                });
            });

            $allSkus = array_keys($skuData);
            
            // 2. Batch retrieve existing Xero Items
            $xeroItemMap = self::batchGetXeroItemsBySku($team, $allSkus);

            // 3. Determine missing items and prepare creation payloads
            $missingItemPayloads = [];
            $defaultSalesAccountCode = $team->xero_sales_account_code ?? self::DEFAULT_ACCOUNT_CODE;

            foreach ($skuData as $sku => $data) {
                if (!isset($xeroItemMap[$sku])) {
                    $missingItemPayloads[] = self::mapWcItemToXeroItemPayload($sku, $data, $defaultSalesAccountCode);
                }
            }

            // 4. Batch Create Missing Items
            if (!empty($missingItemPayloads)) {
                Log::info("Found " . count($missingItemPayloads) . " missing Xero Items. Batch creating them now...");
                // Pass $syncRun to log any batch creation errors
                self::batchCreateXeroItems($team, $missingItemPayloads, $syncRun); 
                
                // CRITICAL: Re-query all items, including the newly created ones
                $xeroItemMap = self::batchGetXeroItemsBySku($team, $allSkus);
                Log::info("Re-queried Xero Items. Final map contains " . count($xeroItemMap) . " items, including newly created ones.");
            }


            // --- PASS 1.1: Batch Create/Get Contacts ---
            $contactMap = self::batchFindOrCreateContacts($team, $ordersToProcess, $syncRun); // Pass $syncRun
            
            // --- PASS 1.2: Batch Create Invoices ---
            Log::info("Pass 1.2: Collecting new Invoices...");
            $invoicesToCreate = new Collection();
            
            foreach ($ordersToProcess as $order) {
                $wcOrderId = $order['id'];
                $wcOrderRef = (string) $wcOrderId;
                $contactNameKey = self::getContactKeyFromOrder($order);

                try {
                    if (isset($initialInvoiceMap[$wcOrderRef])) {
                        continue; 
                    }

                    $contactId = $contactMap[$contactNameKey] ?? null;

                    if (!$contactId) {
                         Log::error("Failed to find Xero Contact ID for key '{$contactNameKey}' for WC Order {$wcOrderId}. Skipping Invoice creation.");
                         // NOTE: We don't increment failed_invoices here, only after API response
                         continue;
                    }
                    
                    $invoicesToCreate->push(self::mapOrderToInvoicePayload($team, $order, $contactId, $xeroItemMap));
                    Log::debug("Prepared Invoice payload for WC Order {$wcOrderId}.");
                    
                } catch (Exception $e) {
                    Log::error("Failed to prepare WC Order {$wcOrderId} for Pass 1.2 (Invoice): " . $e->getMessage());
                    // NOTE: This is a preparation failure, not a batch API failure.
                }
            }
            
            // Execute Batch Create Invoices
            if ($invoicesToCreate->isNotEmpty()) {
                // Pass $syncRun to track batch errors and get the response
                $response = self::batchApiCallWithResponse($team, 'POST', 'Invoices', $invoicesToCreate, $syncRun); 
                
                // Update metrics based on response
                $invoiceCreationResponse = $response['Invoices'] ?? [];
                
                $successfulInvoices = collect($invoiceCreationResponse)
                    ->where('Status', 'AUTHORISED')
                    ->where('HasErrors', false)
                    ->count();
                
                $failedInvoices = collect($invoiceCreationResponse)
                    ->where('HasErrors', true)
                    ->count();
                
                $metrics['successful_invoices'] += $successfulInvoices;
                $metrics['failed_invoices'] += $failedInvoices;
                
                Log::info("Pass 1.2: Batched and sent {$invoicesToCreate->count()} Invoices. Successful: {$successfulInvoices}, Failed: {$failedInvoices}");
            }
            
            // --- PASS 2.1: Re-query all necessary invoices ---
            $finalInvoiceMap = self::batchFindExistingInvoices($team, $ordersToProcess);

            // --- PASS 2.2: Collect and Batch Payments ---
            Log::info("Pass 2.2: Collecting Payments...");
            $paymentsToCreate = new Collection();
            
            foreach ($ordersToProcess as $order) {
                $wcOrderId = $order['id'];
                $wcOrderRef = (string) $wcOrderId;
    
                try {
                    $isOrderPaid = in_array($order['status'], ['processing', 'completed']);
                    
                    // Use the final map which contains IDs for both old and newly created invoices
                    $existingInvoice = $finalInvoiceMap[$wcOrderRef] ?? null;
                    $invoiceId = $existingInvoice['InvoiceID'] ?? null;
                    $invoiceStatus = $existingInvoice['Status'] ?? null;
                    $isInvoicePaid = $invoiceStatus === 'PAID';
                    
                    if ($isOrderPaid && $invoiceId && !$isInvoicePaid) {
                        // We only create payment if the WC order is paid, the Xero invoice exists, and it's not already paid.
                        
                        $paymentMethodId = $order['payment_method'] ?? 'unknown';
                        $accountCode = self::getPaymentAccountCode($team, $paymentMethodId);
    
                        if (empty($accountCode)) {
                            Log::error("Skipping payment for {$wcOrderId}. No Xero Account Code configured for method '{$paymentMethodId}'.");
                            continue;
                        }
    
                        // Store payment payload for batch posting
                        $paymentsToCreate->push(self::mapOrderToPaymentPayload($order, $invoiceId, $accountCode)[0]);
                        Log::debug("Prepared Payment payload for WC Order {$wcOrderId} with InvoiceID: {$invoiceId}.");
                    } else if (!$invoiceId) {
                        Log::debug("WC Order {$wcOrderId} skipped payment: Invoice not found.");
                    } else if ($isInvoicePaid) {
                         Log::debug("WC Order {$wcOrderId} skipped payment: Invoice already paid.");
                    }
                    
                } catch (Exception $e) {
                    // Log and continue to the next order if a single order fails preparation
                    Log::error("Failed to prepare WC Order {$wcOrderId} for Pass 2 (Payment): " . $e->getMessage());
                }
            }

            // Execute Batch Create Payments
            if ($paymentsToCreate->isNotEmpty()) {
                self::batchApiCall($team, 'POST', 'Payments', $paymentsToCreate, $syncRun); // Pass $syncRun
                Log::info("Pass 2.2: Successfully batched and sent {$paymentsToCreate->count()} Payments to Xero.");
            }

            // 4. Final Success Update
            $syncRun->update([
                'status' => 'Success',
                'end_time' => Carbon::now(),
                'successful_invoices' => $metrics['successful_invoices'],
                'failed_invoices' => $metrics['failed_invoices'],
            ]);

            Log::info("TWO-PASS Batched sync finished for user {$team->id}.");

        } catch (Exception $e) {
            // 5. Final Failure Update
            $syncRun->update([
                'status' => 'Failure',
                'end_time' => Carbon::now(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    // Preserve existing batch errors if any occurred before critical failure
                    'batch_errors' => $syncRun->error_details['batch_errors'] ?? [],
                ],
                'successful_invoices' => $metrics['successful_invoices'],
                'failed_invoices' => $metrics['failed_invoices'],
            ]);
            Log::error("Critical sync failure for team {$team->id}: " . $e->getMessage());
            // Re-throw to ensure the calling controller knows the job failed
            throw $e; 
        }
    }

    /**
     * Finds all existing Xero Invoices corresponding to a collection of WooCommerce orders using batch GET calls.
     * This avoids individual API calls that cause rate limiting.
     *
     * @param Team $team
     * @param Collection $orders Collection of WooCommerce Order data
     * @return array Map of WC Order ID (Reference) -> Xero Invoice data
     * @throws Exception
     */
    private static function batchFindExistingInvoices(Team $team, Collection $orders): array
    {
        Log::info("Batch querying existing Invoices for " . $orders->count() . " orders...");
        $references = $orders->pluck('id')->map(fn($id) => (string) $id);

        if ($references->isEmpty()) {
            return [];
        }

        $invoiceMap = [];
        // Xero's GET filter length is limited, so we chunk the references into groups of 50 and execute multiple batch calls.
        $references->chunk(50)->each(function ($chunk) use ($team, &$invoiceMap) {
            
            // Construct the 'where' clause: Reference=="ID1" || Reference=="ID2" || ...
            $filters = $chunk->map(fn($ref) => "Reference==\"{$ref}\"")->implode(' || ');

            $response = XeroTokenService::makeApiCall($team, 'GET', 'Invoices', [
                'where' => $filters,
                // Include all statuses to check for existence and payment status
                'Statuses' => ['DRAFT', 'SUBMITTED', 'AUTHORISED', 'PAID'], 
            ]);

            foreach ($response['Invoices'] ?? [] as $invoice) {
                if (isset($invoice['Reference'])) {
                    // Map the WooCommerce Order ID (Reference) to the full Invoice data
                    $invoiceMap[$invoice['Reference']] = $invoice;
                }
            }
        });

        Log::info("Completed batch query. Found " . count($invoiceMap) . " existing Invoices.");
        return $invoiceMap;
    }

        /**
     * Finds all existing Xero Inventory Items corresponding to a list of SKUs.
     * The Xero 'Code' field is used to match the WooCommerce 'sku'.
     *
     * @param Team $team
     * @param array $skus Array of unique WooCommerce SKUs (mapped to Xero Item Code).
     * @return array Map of SKU (Item Code) -> Xero Item data
     * @throws Exception
     */
    private static function batchGetXeroItemsBySku(Team $team, array $skus): array
    {
        Log::info("Batch querying Xero Items for " . count($skus) . " unique SKUs...");

        if (empty($skus)) {
            return [];
        }

        $itemMap = [];
        // Chunk SKUs to respect URL length limits (25 is a safe number)
        collect($skus)->chunk(self::SKU_BATCH_SIZE)->each(function ($chunk) use ($team, &$itemMap) {
            
            // Construct the 'where' clause: Code=="SKU1" || Code=="SKU2" || ...
            // Xero API filter: 'Code' is the inventory item code.
            $filters = $chunk->map(fn($sku) => "Code==\"{$sku}\"")->implode(' || ');

            $response = XeroTokenService::makeApiCall($team, 'GET', 'Items', [
                'where' => $filters,
            ]);

            foreach ($response['Items'] ?? [] as $item) {
                // Map the Xero Item Code (which is our SKU) to the full Item data
                if (isset($item['Code']) && !empty($item['SalesDetails']['AccountCode'])) {
                    $itemMap[$item['Code']] = $item;
                } else if (isset($item['Code'])) {
                    // Item found, but sales details are incomplete
                    Log::warning("Xero Item '{$item['Code']}' found but missing SalesDetails.AccountCode or is archived. Skipping dynamic config for this SKU.");
                }
            }
        });

        Log::info("Completed batch query. Found " . count($itemMap) . " matching Xero Items with SalesDetails.");
        return $itemMap;
    }

    /**
     * Gets a unique key (Name or Email) for mapping the Contact.
     * @param array $order
     * @return string
     */
    private static function getContactKeyFromOrder(array $order): string
    {
        $billing = $order['billing'] ?? [];
        $email = $billing['email'] ?? null;
        $name = trim("{$billing['first_name']} {$billing['last_name']}");
        return $email ?: ($name ?: 'Unknown Customer');
    }

    /**
     * Collects unique contact payloads and performs a single batch POST to Xero.
     *
     * @param Team $team
     * @param Collection $ordersToProcess
     * @return array Map of contact keys (Name/Email) to Xero ContactID
     * @throws Exception
     */
    private static function batchFindOrCreateContacts(Team $team, Collection $ordersToProcess, ?SyncRun $syncRun = null): array
    {
        Log::info("Pass 1.1: Batch creating/updating Contacts...");
        $uniqueContacts = [];
        $contactMap = [];

        // 1. Collect unique contact payloads
        foreach ($ordersToProcess as $order) {
            $key = self::getContactKeyFromOrder($order);
            if (!isset($uniqueContacts[$key])) {
                 $uniqueContacts[$key] = self::mapOrderToContactPayload($order);
            }
        }

        $contactPayloads = array_values($uniqueContacts);

        if (empty($contactPayloads)) {
            Log::warning("No unique contacts found to create/update.");
            return [];
        }

        // 2. Execute Batch POST for Contacts (Xero handles de-duplication)
        $response = self::batchApiCallWithResponse($team, 'POST', 'Contacts', collect($contactPayloads), $syncRun);

        // 3. Map the response ContactIDs back to the original keys
        foreach ($response['Contacts'] ?? [] as $contact) {
            $key = null;
            $name = $contact['Name'] ?? null;
            $email = $contact['EmailAddress'] ?? null;
            
            // Try to match by Name or Email 
            foreach ($uniqueContacts as $originalKey => $payload) {
                if ($name && ($payload['Name'] ?? null) === $name) {
                    $key = $originalKey;
                    break;
                }
                if ($email && ($payload['EmailAddress'] ?? null) === $email) {
                    $key = $originalKey;
                    break;
                }
            }

            if ($key && ($contact['ContactID'] ?? null) && !($contact['HasErrors'] ?? false)) {
                $contactMap[$key] = $contact['ContactID'];
            }
        }
        
        Log::info("Pass 1.1: Successfully processed " . count($contactMap) . " unique Contacts.");

        return $contactMap;
    }

    /**
     * Executes a batch POST to create new Xero Inventory Items.
     *
     * @param Team $team
     * @param array $payloads Array of Xero Item payloads
     * @throws Exception
     */
    private static function batchCreateXeroItems(Team $team, array $payloads, ?SyncRun $syncRun = null): void
    {
        // Chunk and execute the batch creation call
        self::batchApiCall($team, 'POST', 'Items', collect($payloads), $syncRun);
    }

    /**
     * Executes a batched API call, splitting the payload if necessary, and returns the full response array.
     *
     * @param Team $team
     * @param string $method
     * @param string $endpoint e.g., 'Invoices', 'Payments', 'Contacts'
     * @param Collection $payloads Collection of individual payloads
     * @param SyncRun|null $syncRun Optional SyncRun record for logging batch errors
     * @return array The aggregated response array
     * @throws Exception
     */
    private static function batchApiCallWithResponse(Team $team, string $method, string $endpoint, Collection $payloads, ?SyncRun $syncRun = null): array
    {
        $aggregatedResponse = [$endpoint => []]; 

        $payloads->chunk(self::BATCH_SIZE)->each(function ($chunk, $index) use ($team, $method, $endpoint, &$aggregatedResponse, $syncRun) {
            $response = XeroTokenService::makeApiCall($team, $method, $endpoint, $chunk->toArray());
            
            $responseKey = $endpoint;
            $elements = $response[$responseKey] ?? [];

            $batchErrors = [];

            foreach ($elements as $element) {
                // Check for errors in the individual batch elements
                if (($element['HasErrors'] ?? false) && !empty($element['ValidationErrors'])) {
                    $errors = collect($element['ValidationErrors'])->pluck('Message')->implode('; ');
                    $reference = $element['Reference'] ?? $element['Code'] ?? 'N/A';
                    $name = $element['Name'] ?? 'N/A';
                    $logMessage = "Batch {$endpoint} failure for {$name} / Ref: {$reference}. Errors: {$errors}";
                    Log::error($logMessage);
                    
                    // Log error detail to the SyncRun record's error_details
                    $batchErrors[] = [
                        'endpoint' => $endpoint,
                        'reference' => $reference,
                        'name' => $name,
                        'errors' => $errors,
                    ];
                }
            }
            
            // Merge the elements from the current chunk into the aggregated response
            $aggregatedResponse[$responseKey] = array_merge($aggregatedResponse[$responseKey], $elements);

            // Update error details on the SyncRun object if errors occurred
            if ($syncRun && !empty($batchErrors)) {
                $currentErrors = $syncRun->error_details ?? [];
                
                // Ensure 'batch_errors' is an array before merging
                if (!isset($currentErrors['batch_errors']) || !is_array($currentErrors['batch_errors'])) {
                    $currentErrors['batch_errors'] = [];
                }

                $currentErrors['batch_errors'] = array_merge($currentErrors['batch_errors'], $batchErrors);

                $syncRun->update(['error_details' => $currentErrors]);
            }
        });

        return $aggregatedResponse;
    }

    /**
     * Executes a batched POST API call, splitting the payload if necessary (wrapper for existing calls).
     *
     * @param Team $team
     * @param string $method
     * @param string $endpoint e.g., 'Invoices', 'Payments', 'Contacts'
     * @param Collection $payloads Collection of individual payloads
     * @param SyncRun|null $syncRun Optional SyncRun record for logging batch errors
     * @throws Exception
     */
    private static function batchApiCall(Team $team, string $method, string $endpoint, Collection $payloads, ?SyncRun $syncRun = null): void
    {
        self::batchApiCallWithResponse($team, $method, $endpoint, $payloads, $syncRun);
    }


    // --- Core Xero API Operations ---

    /**
     * Attempts to find a Xero Invoice using the WooCommerce Order ID as the Reference.
     * * @param Team $team
     * @param string $reference WooCommerce Order ID
     * @return array|null
     * @throws Exception
     */
    private static function findInvoiceByReference(Team $team, string $reference): ?array
    {
        // Xero API filter: find Invoice where Reference equals WC Order ID
        $response = XeroTokenService::makeApiCall($team, 'GET', 'Invoices', [
            'where' => "Reference==\"{$reference}\"",
            // Include all statuses to check for existence and payment status
            'Statuses' => ['DRAFT', 'SUBMITTED', 'AUTHORISED', 'PAID'], 
        ]);

        return $response['Invoices'][0] ?? null;
    }

    /**
     * Maps WooCommerce Item data to a minimal Xero Item creation payload (Sales Only).
     *
     * @param string $sku
     * @param array $data Contains name, price, and tax_status from the WC line item.
     * @param string $defaultSalesAccountCode The default account code for sales revenue.
     * @return array
     */
    private static function mapWcItemToXeroItemPayload(string $sku, array $data, string $defaultSalesAccountCode): array
    {
        // WooCommerce 'taxable' status roughly translates to 'TAX001' or similar standard tax rate
        // We default to 'NONE' (0%) if not taxable, or 'OUTPUT' (default sales tax) if taxable.
        // As you are in the UK, OUTPUT usually refers to the default sales VAT rate.
        $defaultTaxType = ($data['tax_status'] === 'taxable') ? 'OUTPUT' : 'NONE';

        return [
            'Code' => $sku,
            'Name' => $data['name'],
            'Description' => "Automated creation from WC for SKU: {$sku}",
            'IsSold' => true,
            'IsTrackedAsInventory' => false, // No inventory tracking requested
            // *** Removed IsPurchased and PurchaseDetails ***
            
            // Sales Details (for Accounts Receivable/Invoices)
            'SalesDetails' => [
                'UnitPrice' => $data['price'],
                'AccountCode' => $defaultSalesAccountCode,
                'TaxType' => $defaultTaxType,
            ],
        ];
    }

    /**
     * Maps WooCommerce Order data to a Xero Payment payload.
     * * @param array $order
     * @param string $invoiceId
     * @param string $accountCode The Xero Account Code for the bank/payment clearing account
     * @return array
     */
    private static function mapOrderToPaymentPayload(array $order, string $invoiceId, string $accountCode): array
    {
        // Use the date paid if available (GMT preferred for APIs), otherwise fall back to creation date
        $paymentDate = $order['date_paid_gmt'] 
            ? Carbon::parse($order['date_paid_gmt'])->format('Y-m-d') 
            : Carbon::parse($order['date_created_gmt'])->format('Y-m-d');

        $totalAmount = floatval($order['total']);

        $netTotal = floatval($order['total'] ?? 0);
        $taxTotal = floatval($order['total_tax'] ?? 0);
        $totalAmount = $netTotal + $taxTotal; 
        
        $payload = [
            'Invoice' => [
                'InvoiceID' => $invoiceId,
            ],
            'Account' => [
                'Code' => $accountCode, // Dynamically set from user mapping
            ],
            'Date' => $paymentDate, // Set payment date to WC date paid
            'Amount' => $totalAmount,
            'Reference' => "WC Payment: {$order['payment_method_title']}",
            'CurrencyCode' => $order['currency'], 
        ];

        // We return an array containing the single payment object, but the batcher handles the array wrapper
        return [
            $payload,
        ];
    }

    /**
     * Maps a WooCommerce Order object to the Xero Invoice payload structure.
     *
     * @param array $order Decoded WooCommerce order object.
     * @param string $contactId The Xero ContactID for this customer.
     * @return array Xero Invoice payload fragment.
     */
    /**
     * Maps WooCommerce Order data to a Xero Invoice payload.
     * * @param array $order
     * @param string $contactId
     * @return array
     */
    private static function mapOrderToInvoicePayload(Team $team, array $order, string $contactId, array $xeroItemMap): array
    {

        $defaultSalesAccountCode = $team->xero_sales_account_code ?? self::DEFAULT_ACCOUNT_CODE;
        // If shipping code isn't explicitly set, default it to the sales account code
        $shippingAccountCode = $team->xero_shipping_account_code ?? $defaultSalesAccountCode;

        $lineItems = collect($order['line_items'] ?? [])->map(function ($item) use ($defaultSalesAccountCode, $xeroItemMap) {
            
            $sku = $item['sku'] ?? null;
            $xeroItem = $sku ? ($xeroItemMap[$sku] ?? null) : null;

            // Start with defaults (which were previously configurable but are now fallbacks)
            $accountCode = $defaultSalesAccountCode;
            $taxType = self::DEFAULT_TAX_TYPE; // Default to 0% Tax if no item found
            
            // Override with Xero Item details if a match is found
            if ($xeroItem) {
                $itemDetails = $xeroItem['SalesDetails'] ?? [];
                
                // 1. Get Account Code from Xero Item
                if ($itemDetails['AccountCode'] ?? null) {
                    $accountCode = $itemDetails['AccountCode'];
                    Log::debug("Using specific Xero Account Code {$accountCode} from Item for SKU {$sku}.");
                }
                
                // 2. Get Tax Type from Xero Item
                if ($itemDetails['TaxType'] ?? null) {
                    $taxType = $itemDetails['TaxType'];
                    Log::debug("Using specific Xero TaxType {$taxType} from Item for SKU {$sku}.");
                }
            }


            $description = "{$item['name']}";
            return [
                'Description' => $description,
                'Quantity' => (float) $item['quantity'],
                'UnitAmount' => floatval($item['price']),
                'AccountCode' => $accountCode, // Dynamically set from Item or fallback
                'TaxType' => $taxType, // Dynamically set from Item or fallback ('NONE')
                'ItemCode' => $item['sku']
            ];
        })->toArray();

        // Map Shipping Line
        $shippingLines = collect($order['shipping_lines'] ?? [])->map(function ($item) use ($shippingAccountCode) {
            $description = "Shipping: {$item['method_title']}";
            return [
                'Description' => $description,
                'Quantity' => 1.0,
                'UnitAmount' => floatval($item['total']),
                'AccountCode' => $shippingAccountCode, // Uses the configurable shipping code
                // Shipping is usually a separate tax consideration, keeping it simple with 'NONE' (0%)
                'TaxType' => self::DEFAULT_TAX_TYPE, 
            ];
        })->toArray();
        
        $lineAmountTypes = 'Exclusive'; 

        return [
            'Type' => 'ACCREC', // Accounts Receivable (Sales Invoice)
            'Contact' => ['ContactID' => $contactId],
            'Date' => Carbon::parse($order['date_created_gmt'])->format('Y-m-d'),
            'DueDate' => Carbon::parse($order['date_created_gmt'])->addDays(7)->format('Y-m-d'), 
            'Reference' => (string) $order['id'], // WooCommerce Order ID
            'Status' => 'AUTHORISED', // Invoice must be Authorised for payment to be applied
            'LineItems' => array_merge($lineItems, $shippingLines),
            'InvoiceNumber' => $order['number'], // Use the WC Order ID as the unique Xero Invoice Number
            'CurrencyCode' => $order['currency'],
            'LineAmountTypes' => $lineAmountTypes,
        ];
    }
    
    /**
     * Maps WooCommerce Order data to a Xero Contact payload.
     * * @param array $order
     * @return array
     */
    private static function mapOrderToContactPayload(array $order): array
    {
        $billing = $order['billing'] ?? [];
        $shipping = $order['shipping'] ?? [];

        $address = $shipping['address_1'] ? $shipping : $billing;

        return [
            'Name' => trim("{$billing['first_name']} {$billing['last_name']}"),
            'FirstName' => $billing['first_name'] ?? null,
            'LastName' => $billing['last_name'] ?? null,
            'EmailAddress' => $billing['email'] ?? null,
            'Phones' => [
                [
                    'PhoneType' => 'DEFAULT',
                    'PhoneNumber' => $billing['phone'] ?? null,
                ],
            ],
            'Addresses' => [
                [
                    'AddressType' => 'STREET',
                    'AddressLine1' => $address['address_1'] ?? null,
                    'AddressLine2' => $address['address_2'] ?? null,
                    'City' => $address['city'] ?? null,
                    'Region' => $address['state'] ?? null,
                    'PostalCode' => $address['postcode'] ?? null,
                    'Country' => $address['country'] ?? null,
                ],
            ],
        ];
    }
}
