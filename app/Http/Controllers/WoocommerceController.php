<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\WoocommerceService;
use App\Services\XeroTokenService;
use App\Services\XeroIntegrationService;
use Illuminate\Support\Facades\Log;
use Exception;

class WoocommerceController extends Controller
{
    /**
     * Handles the saving and testing of WooCommerce credentials.
     */
    public function configureWoocommerce(Request $request)
    {
        $request->validate([
            'woocommerce_url' => ['required', 'url', 'max:255'],
            'woocommerce_consumer_key' => ['required', 'string', 'max:100'],
            'woocommerce_consumer_secret' => ['required', 'string', 'max:100'],
        ]);

        $user = Auth::user();
        $url = rtrim($request->woocommerce_url, '/'); // Clean up URL
        $key = $request->woocommerce_consumer_key;
        $secret = $request->woocommerce_consumer_secret;

        try {
            // 1. Test the connection first
            $testResult = WoocommerceService::testConnection($url, $key, $secret);

            if (!$testResult['success']) {
                // If the test fails, do NOT save the tokens, just report the error
                return redirect()->route('dashboard')->with('error', 'WooCommerce Connection Test Failed: ' . $testResult['message']);
            }

            // 2. If test passes, save the credentials
            $user->woocommerce_url = $url;
            $user->woocommerce_consumer_key = $key;
            $user->woocommerce_consumer_secret = $secret;
            $user->save();

            return redirect()->route('dashboard')->with('success', $testResult['message'] . ' Credentials have been saved.');
            
        } catch (Exception $e) {
            // Catch any unexpected exceptions
            return redirect()->route('dashboard')->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Initiates the synchronization process by retrieving recent WooCommerce orders.
     */
    public function syncOrders()
    {
        $user = Auth::user();

        if (!$user->woocommerce_url) {
            return redirect()->route('dashboard')->with('error', 'Please configure WooCommerce credentials first.');
        }

        if (!$user->xero_tenant_id) {
            return redirect()->route('dashboard')->with('error', 'Please connect to Xero first.');
        }
        
        $wcStatus = WoocommerceService::getConnectionStatus($user);
        if (!$wcStatus['connected']) {
             return redirect()->route('dashboard')->with('error', 'Sync Failed: WooCommerce is not configured. Please enter and save your WC credentials.');
        }


        try {
            // The service method now handles the batching internally
            XeroIntegrationService::syncOrdersToXero($user);
            
            return redirect()->route('dashboard')->with('success', 'Synchronization initiated successfully! Check logs for results.');

        } catch (Exception $e) {
            Log::error("Manual Sync Failed: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', "Sync Failed: " . $e->getMessage());
        }
    }

    /**
     * Handles the submission of the WooCommerce Payment to Xero Account Code map.
     */
    public function savePaymentMapping(Request $request)
    {
        $user = Auth::user();
        $mappingData = $request->input('mapping', []);
        
        // Clean and validate the mapping data
        $cleanMap = [];
        foreach ($mappingData as $wcGateway => $xeroCode) {
            if (is_string($wcGateway) && !empty($xeroCode)) {
                $cleanMap[$wcGateway] = $xeroCode;
            }
        }
        Log::info("Payment Map: ". json_encode($cleanMap));

        try {
            $user->update([
                'wc_payment_account_map' => json_encode($cleanMap),
            ]);
            Log::info("Saved user payment map");
            return redirect()->route('configure')->with('success', 'Payment mapping successfully updated and saved.');
        } catch (Exception $e) {
             Log::error('Failed to save payment mapping: ' . $e->getMessage());
            return redirect()->route('configure')->with('error', 'Failed to save payment mapping.');
        }
    }
}
