<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Exception;

/**
 * Handles all WooCommerce API interactions, primarily connection and order retrieval.
 */
class WoocommerceService
{
    // --- Connection Methods ---

    /**
     * Creates and configures the HTTP client for WooCommerce API calls.
     * @param string $key The Consumer Key
     * @param string $secret The Consumer Secret
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private static function buildHttpClient(string $key, string $secret): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($key, $secret)
                   ->timeout(15)
                   ->withHeaders(['Accept' => 'application/json']);
    }


    /**
     * Tests the connection to the WooCommerce store using Basic Auth.
     */
    public static function testConnection(string $url, string $key, string $secret): array
    {
        $endpoint = rtrim($url, '/') . '/wp-json/wc/v3/system_status';
        
        try {
            $response = self::buildHttpClient($key, $secret)->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['environment'])) {
                    return [
                        'success' => true,
                        'message' => 'Connection successful! Store Name: ' . ($data['environment']['site_name'] ?? 'N/A'),
                        'store_url' => $url
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Connection succeeded, but response format was unexpected. Ensure keys have Read access.',
                    'store_url' => $url
                ];

            } else {
                $errorMsg = match($response->status()) {
                    401 => 'Unauthorized: Check your Consumer Key/Secret and ensure they have Read permissions.',
                    404 => 'Not Found: The WooCommerce API endpoint (wp-json/wc/v3) could not be reached. Check the store URL.',
                    default => 'HTTP Error ' . $response->status() . ': ' . ($response->json()['message'] ?? 'Unknown error.')
                };

                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'store_url' => $url
                ];
            }
        } catch (Exception $e) {
            Log::error("WooCommerce Test Connection failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Network error or timeout. Check the URL and firewall settings.',
                'store_url' => $url
            ];
        }
    }

    /**
     * Gets the current WooCommerce connection status for the authenticated user.
     */
    public static function getConnectionStatus(Team $team): array
    {
        $isConnected = !empty($team->WoocommerceConnection->store_url) && !empty($team->WoocommerceConnection->consumer_key);

        return [
            'connected' => $isConnected,
            'url' => !empty($team->WoocommerceConnection->store_url) ? $team->WoocommerceConnection->store_url : null,
            'key_status' => $isConnected ? 'Set' : 'Missing',
            'message' => $isConnected ? "Ready to sync with {$team->WoocommerceConnection->store_url}." : "Not connected to WooCommerce.",
        ];
    }

    // --- Order Retrieval Method (NEW) ---

    /**
     * Fetches recent WooCommerce orders ready for invoicing (processing or completed).
     *
     * @param User $user The authenticated user model.
     * @param int $days How many days back to look for orders.
     * @param int $maxOrders Maximum orders to return.
     * @return array An array of decoded order objects.
     * @throws Exception if connection data is missing or API call fails.
     */
    public static function getRecentOrders(Team $team, int $days = 2, int $maxOrders = 100): array
    {
        if (empty($team->WoocommerceConnection->store_url) || empty($team->WoocommerceConnection->consumer_key) || empty($team->WoocommerceConnection->consumer_secret)) {
            throw new Exception("WooCommerce credentials are not configured for this user.");
        }

        $url = rtrim($team->WoocommerceConnection->store_url, '/');
        $key = $team->WoocommerceConnection->consumer_key;
        $secret = $team->WoocommerceConnection->consumer_secret;

        // Calculate the date range
        $afterDate = Carbon::now()->subDays($days)->toIso8601String();

        $endpoint = "{$url}/wp-json/wc/v3/orders";
        try {
            // Build the authenticated request
            $client = self::buildHttpClient($key, $secret);

            $response = $client->get($endpoint, [
                'status' => ['processing', 'completed'], // Only get orders ready for accounting
                'after' => $afterDate,                   // Orders after this date
                'per_page' => $maxOrders,                // Max orders to retrieve
                'orderby' => 'date',
                'order' => 'asc',                        // Oldest first for syncing
            ]);

            if ($response->failed()) {
                $error = $response->json()['message'] ?? "WooCommerce API error.";
                Log::error("WooCommerce Order Fetch Failed: " . $error, ['team_id' => $team->id, 'status' => $response->status(), 'key' => $key, 'secret' => $secret]);
                throw new Exception("Failed to fetch orders from WooCommerce: [HTTP {$response->status()}] {$error}");
            }

            $orders = $response->json();
            Log::info("Successfully retrieved " . count($orders) . " orders from WooCommerce for team {$team->id}.");

            return $orders;

        } catch (Exception $e) {
            // Re-throw the exception to be caught in the controller
            throw $e;
        }
    }

    /**
     * Initiates the synchronization process by retrieving recent WooCommerce orders.
     */
    public static function syncOrders(Team $team)
    {

        if (!$team->WoocommerceConnection->store_url) {
            return redirect()->route('dashboard')->with('error', 'Please configure WooCommerce credentials first.');
        }

        if (!$team->XeroConnection->tenant_id) {
            return redirect()->route('dashboard')->with('error', 'Please connect to Xero first.');
        }
        
        $wcStatus = WoocommerceService::getConnectionStatus($team);
        if (!$wcStatus['connected']) {
             return redirect()->route('dashboard')->with('error', 'Sync Failed: WooCommerce is not configured. Please enter and save your WC credentials.');
        }


        try {
            // The service method now handles the batching internally
            XeroIntegrationService::syncOrdersToXero($team);

        } catch (Exception $e) {
            Log::error("Manual Sync Failed: " . $e->getMessage());
        }
    }
}
