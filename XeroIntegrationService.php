<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

/**
 * Handles the business logic for syncing data from WooCommerce to Xero.
 */
class XeroIntegrationService
{
    // --- Configuration Placeholder (HARDCODED FOR DEMO) ---
    // NOTE: In a production app, these should be configurable settings 
    // that the user maps in the dashboard.
    private const DEFAULT_SALES_ACCOUNT_CODE = '200'; // Standard Revenue Account
    private const DEFAULT_SHIPPING_ACCOUNT_CODE = '200'; // Freight/Shipping Revenue Account
    private const DEFAULT_TAX_TYPE = 'NONE'; 


    /**
     * Main method to iterate through WooCommerce orders and create Xero invoices.
     *
     * @param User $user
     * @param array $orders Array of decoded WooCommerce order objects.
     * @return array Sync statistics
     */
    public static function syncOrdersToXero(User $user, array $orders): array
    {
        $successCount = 0;
        $failureCount = 0;
        
        Log::info("Starting sync of " . count($orders) . " WooCommerce orders to Xero.");

        foreach ($orders as $order) {
            try {
                $wcOrderId = $order['id'];
                $paidStatuses = ['processing', 'completed'];
                $isPaidInWc = in_array($order['status'], $paidStatuses);
                
                // 1. Check if the invoice already exists in Xero.
                $xeroInvoice = self::getExistingInvoice($user, $wcOrderId);

                if ($xeroInvoice) {
                    // --- UPDATE PATH ---
                    $xeroInvoiceId = $xeroInvoice['InvoiceID'];
                    Log::info("WC Order ID {$wcOrderId} found in Xero (Invoice ID: {$xeroInvoiceId}).");
                    
                    self::handlePayment($user, $order, $xeroInvoiceId, $isPaidInWc, $xeroInvoice['Status']);

                } else {
                    // --- CREATION PATH ---
                    
                    // 2. Find or Create Xero Contact
                    $contact = self::findOrCreateContact($user, $order);
                    
                    // 3. Map Order to Xero Invoice payload
                    $invoicePayload = self::mapOrderToInvoicePayload($order, $contact['ContactID']);
                    
                    // 4. Create Invoice in Xero
                    $newXeroInvoice = self::createInvoice($user, $invoicePayload);
                    $newXeroInvoiceId = $newXeroInvoice['InvoiceID'];

                    Log::info("Created new Xero Invoice ID {$newXeroInvoiceId} for WC Order {$wcOrderId}.");
                    
                    // 5. Apply Payment if the order is paid
                    self::handlePayment($user, $order, $newXeroInvoiceId, $isPaidInWc);
                }
                
                $successCount++;
            } catch (Exception $e) {
                $failureCount++;
                Log::error("Failed to sync WooCommerce Order ID {$wcOrderId}: " . $e->getMessage());
            }
        }

        Log::info("Xero sync completed. Successes: {$successCount}, Failures: {$failureCount}.");

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ];
    }

    /**
     * Finds the correct Xero Account Code for a payment method based on user config.
     *
     * @param User $user
     * @param string $paymentMethodId The ID (slug) of the WooCommerce payment method.
     * @return string|null
     */
    private static function getPaymentAccountCode(User $user, string $paymentMethodId): ?string
    {
        $map = $user->wc_payment_account_map;

        if (empty($map)) {
            Log::warning("No Xero payment map configured for user {$user->id}. Payment will fail without a default.");
            return null; // Force failure later to prevent incorrect posting
        }

        return $map[$paymentMethodId] ?? null;
    }

    /**
     * Retrieves a list of Bank Accounts from Xero for mapping purposes.
     *
     * @param User $user
     * @return array
     * @throws Exception
     */
    public static function getBankAccounts(User $user): array
    {
        Log::info("Fetching Xero Bank Accounts for user {$user->id}");
        
        // Xero API filter to only retrieve accounts of type 'BANK'
        $response = XeroTokenService::makeApiCall($user, 'GET', 'Accounts', ['where' => 'Type=="BANK"']);

        // Safely map the accounts, providing fallbacks for missing keys like 'Code'
        $accounts = collect($response['Accounts'] ?? [])
            ->map(fn($account) => [
                // FIX: Use null-coalescing operator to safely access 'Code'. 
                // If 'Code' is missing, use the AccountID, or an empty string.
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
     * Handles payment creation for both new and existing invoices.
     *
     * @param User $user
     * @param array $order WC Order data.
     * @param string $xeroInvoiceId Xero Invoice ID.
     * @param bool $isPaidInWc If the WC order is currently paid.
     * @param string|null $xeroInvoiceStatus Current status of the Xero invoice (only provided on update path).
     */
    private static function handlePayment(User $user, array $order, string $xeroInvoiceId, bool $isPaidInWc, ?string $xeroInvoiceStatus = null): void
    {
        if (!$isPaidInWc) {
            // WC order is not paid, no payment action needed.
            return;
        }

        if ($xeroInvoiceStatus === 'PAID') {
            // Xero invoice is already paid, no payment action needed.
            Log::info("Invoice {$xeroInvoiceId} is already paid. Skipping payment creation.");
            return;
        }

        $paymentMethod = $order['payment_method'];
        $bankAccountCode = self::getBankAccountCodeFromMap($user, $paymentMethod);

        if (!$bankAccountCode) {
            Log::error("Cannot create payment for WC Order {$order['id']}. Missing Xero account code for payment method '{$paymentMethod}'.");
            throw new Exception("Missing Xero account code for payment method '{$paymentMethod}'. Please check your Payment Mapping configuration.");
        }

        // Only proceed if the order is paid and the invoice status is not 'PAID'.
        try {
            $paymentPayload = self::mapOrderToPaymentPayload($order, $xeroInvoiceId, $bankAccountCode);
            self::createPayment($user, $paymentPayload);
            Log::info("Created Xero Payment for Invoice {$xeroInvoiceId} (WC Order {$order['id']}) via payment method '{$paymentMethod}'. Invoice is now Paid.");
        } catch (Exception $e) {
            // If payment fails, we log it but don't stop the overall sync.
            Log::error("Failed to create Xero Payment for Invoice {$xeroInvoiceId}: " . $e->getMessage());
        }
    }

    /**
     * Gets the correct Xero Account Code from the user's map based on the WC payment method.
     */
    private static function getBankAccountCodeFromMap(User $user, string $wcPaymentMethod): ?string
    {
        $map = $user->wc_payment_account_map ? json_decode($user->wc_payment_account_map, true) : [];
        return $map[$wcPaymentMethod] ?? null;
    }

    /**
     * Retrieves a full existing invoice object from Xero based on the WC Order ID.
     *
     * @param User $user
     * @param int $wcOrderId
     * @return array|null The Xero Invoice object or null if not found.
     */
    private static function getExistingInvoice(User $user, int $wcOrderId): ?array
    {
        try {
            // Filter by the InvoiceNumber field which stores the WC Order ID.
            $filter = 'InvoiceNumber=="' . $wcOrderId . '"';
            
            $response = XeroTokenService::makeApiCall($user, 'GET', 'Invoices', [
                'where' => $filter,
            ]);

            return !empty($response['Invoices']) ? $response['Invoices'][0] : null;

        } catch (Exception $e) {
            Log::warning("Failed to check for existing invoice for WC Order ID {$wcOrderId}: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Maps a WooCommerce Order object to the Xero Payment payload structure.
     *
     * @param array $order Decoded WooCommerce order object.
     * @param string $invoiceId The Xero InvoiceID this payment applies to.
     * @return array Xero Payment payload fragment (an array containing a single payment object).
     */
    private static function mapOrderToPaymentPayload(array $order, string $invoiceId, string $bankAccountCode): array
    {
        // Use the date paid if available, otherwise fall back to creation date
        $paymentDate = $order['date_paid_gmt'] 
            ? Carbon::parse($order['date_paid_gmt'])->format('Y-m-d') 
            : Carbon::parse($order['date_created_gmt'])->format('Y-m-d');

        $totalAmount = floatval($order['total']);

        $payload = [
            'Invoice' => [
                'InvoiceID' => $invoiceId,
            ],
            'Account' => [
                'Code' => $bankAccountCode,
            ],
            'Date' => $paymentDate,
            'Amount' => $totalAmount,
            'Reference' => "WC Payment: {$order['payment_method_title']}",
            'CurrencyCode' => $order['currency'], 
        ];

        // Xero Payment structure - array of payment objects
        return [
            $payload,
        ];
    }

    /**
     * Executes the API call to create the Payment in Xero.
     *
     * @param User $user
     * @param array $paymentPayload The full payment payload (an array containing a single payment object).
     * @return array Xero response
     * @throws Exception
     */
    private static function createPayment(User $user, array $paymentPayload): array
    {
        $response = XeroTokenService::makeApiCall($user, 'POST', 'Payments', $paymentPayload);
        Log::info("Response: " . json_encode($response));
        // Xero's Payments endpoint returns an array of payments under the 'Payments' key
        if (!empty($response['Payments']) && isset($response['Payments'][0]['PaymentID'])) {
            return $response['Payments'][0];
        }

        throw new Exception("Xero Payment creation failed, but no specific error returned.");
    }

    /**
     * Maps a WooCommerce Order object to the Xero Invoice payload structure.
     *
     * @param array $order Decoded WooCommerce order object.
     * @param string $contactId The Xero ContactID for this customer.
     * @return array Xero Invoice payload fragment.
     */
    private static function mapOrderToInvoicePayload(array $order, string $contactId): array
    {
        // Xero expects dates in YYYY-MM-DD format
        $date = Carbon::parse($order['date_created_gmt'])->format('Y-m-d');

        $lineItems = [];

        // 1. Map product line items
        foreach ($order['line_items'] as $item) {
            $lineItems[] = [
                'Description' => $item['name'] . " (SKU: {$item['sku']})",
                'Quantity' => $item['quantity'],
                'UnitAmount' => round($item['subtotal'] / $item['quantity'], 4), // Calculate unit price based on subtotal/quantity
                'AccountCode' => self::DEFAULT_SALES_ACCOUNT_CODE,
                'TaxType' => self::DEFAULT_TAX_TYPE,
            ];
        }

        // 2. Map Shipping lines (if present)
        foreach ($order['shipping_lines'] as $shipping) {
            $lineItems[] = [
                'Description' => 'Shipping: ' . $shipping['method_title'],
                'Quantity' => 1,
                'UnitAmount' => (float) $shipping['total'],
                'AccountCode' => self::DEFAULT_SHIPPING_ACCOUNT_CODE,
                'TaxType' => self::DEFAULT_TAX_TYPE,
            ];
        }

        // 3. Map Fee lines (if present) - e.g. Payment Gateway fees passed to customer
        foreach ($order['fee_lines'] as $fee) {
            $lineItems[] = [
                'Description' => 'Fee: ' . $fee['name'],
                'Quantity' => 1,
                'UnitAmount' => (float) $fee['total'],
                'AccountCode' => self::DEFAULT_SALES_ACCOUNT_CODE, // Use sales account for fees too
                'TaxType' => self::DEFAULT_TAX_TYPE,
            ];
        }

        // Xero Invoice structure
        return [
            [ // This is an array of Invoices
                'Type' => 'ACCREC', // Accounts Receivable (Sales Invoice)
                'Contact' => [
                    'ContactID' => $contactId
                ],
                'Date' => $date,
                'DueDate' => $date, // Simplification: set due date to creation date
                'InvoiceNumber' => $order['number'], // Use the WC Order ID as the unique Xero Invoice Number
                'Reference' => "{$order['id']}", // WC order number for reference
                'Status' => 'AUTHORISED', // Best to create as Draft first
                'LineItems' => $lineItems,
                // Total is calculated by Xero based on line items, but we need to ensure the TaxInclusive flag is set correctly
                'LineAmountTypes' => 'Exclusive', // Assuming WC totals are Tax Exclusive. Adjust if necessary.
                'CurrencyCode' => $order['currency'],
                'Total' => $order['total'],

            ]
        ];
    }
    

    /**
     * Attempts to find a Xero Contact by email address, or creates a new one if not found.
     *
     * @param User $user
     * @param array $order Decoded WooCommerce order object.
     * @return array The Xero Contact object (with ContactID).
     * @throws Exception
     */
    private static function findOrCreateContact(User $user, array $order): array
    {
        $billing = $order['billing'];
        $email = $billing['email'];
        $fullName = trim("{$billing['first_name']} {$billing['last_name']}");

        // 1. Try to find by Email
        try {
            $filter = 'EmailAddress=="' . $email . '"'; 
            
            $response = XeroTokenService::makeApiCall($user, 'GET', 'Contacts', ['where' => $filter]);

            if (!empty($response['Contacts'])) {
                Log::info("Found existing Xero Contact by email: {$email}");
                return $response['Contacts'][0]; // Return the first matching contact
            }
        } catch (Exception $e) {
            Log::warning("Failed to search Xero Contacts by email: " . $e->getMessage());
            // Fall through to create new contact
        }

        // 2. Create New Contact
        // Payload is now just an array of contact objects, no top-level 'Contacts' key.
        // This is the array that will be wrapped by the XeroTokenService with the 'Contacts' key.
        $newContactPayload = [
            [
                'Name' => $fullName,
                'FirstName' => $billing['first_name'],
                'LastName' => $billing['last_name'],
                'EmailAddress' => $email,
                'Phones' => [
                    [
                        'PhoneType' => 'DEFAULT',
                        'PhoneNumber' => $billing['phone'] ?? $order['shipping']['phone'] ?? null,
                    ]
                ],
                // Use shipping address as primary postal address
                'Addresses' => [
                    [
                        'AddressType' => 'STREET',
                        'AddressLine1' => $order['shipping']['address_1'] ?? $billing['address_1'],
                        'AddressLine2' => $order['shipping']['address_2'] ?? $billing['address_2'],
                        'City' => $order['shipping']['city'] ?? $billing['city'],
                        'Region' => $order['shipping']['state'] ?? $billing['state'],
                        'PostalCode' => $order['shipping']['postcode'] ?? $billing['postcode'],
                        'Country' => $order['shipping']['country'] ?? $billing['country'],
                    ]
                ]
            ]
        ];

        try {
            $response = XeroTokenService::makeApiCall($user, 'POST', 'Contacts', $newContactPayload);
            
            if (isset($response['Contacts'][0]['ContactID'])) {
                Log::info("Created new Xero Contact: {$fullName}");
                return $response['Contacts'][0];
            }
            throw new Exception("Xero API did not return a valid ContactID after creation.");

        } catch (Exception $e) {
            throw new Exception("Failed to create new Xero Contact for {$fullName}: " . $e->getMessage());
        }
    }
    
    /**
     * Executes the API call to create the Invoice in Xero.
     *
     * @param User $user
     * @param array $invoicePayload The full invoice payload (an array containing a single invoice object).
     * @return array Xero response
     * @throws Exception
     */
    private static function createInvoice(User $user, array $invoicePayload): array
    {
        $response = XeroTokenService::makeApiCall($user, 'POST', 'Invoices', $invoicePayload);
        
        if (!empty($response['Invoices']) && isset($response['Invoices'][0]['InvoiceID'])) {
            return $response['Invoices'][0];
        }

        throw new Exception("Xero Invoice creation failed, but no specific error returned.");
    }
}
