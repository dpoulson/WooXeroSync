<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\XeroTokenService;
use App\Services\WoocommerceService;
use App\Services\XeroIntegrationService;
use Illuminate\Support\Facades\Log;
use Exception;

class ConfigureController extends Controller
{
    /**
     * Displays the application dashboard and checks Xero connection status.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $xeroStatus = XeroTokenService::getConnectionStatus($user);
        $wcStatus = WoocommerceService::getConnectionStatus($user); // NEW: Get WC status

        $xeroBankAccounts = [];
        $wcPaymentMap = [];
        if ($xeroStatus['connected']) {
            try {
                $xeroBankAccounts = XeroIntegrationService::getBankAccounts($user);
            } catch (\Exception $e) {
                Log::error("Unable to get list of Bank Accounts");
            }
        }

        $wcPaymentMap = $user->wc_payment_account_map ? json_decode($user->wc_payment_account_map, true) : [];

        // Mock common WC payment methods to display in the mapping form
        $mockWCPayments = [
            'bacs' => 'BACS (Bank Transfer)',
            'cheque' => 'Cheque Payment',
            'cod' => 'Cash on Delivery',
            'ppcp-gateway' => 'PayPal Payments Pro',
            'stripe' => 'Stripe (Credit Card)',
        ];

        return view('configure', [
            'user' => $user,
            'xeroStatus' => $xeroStatus,
            'wcStatus' => $wcStatus, // Pass WC status to the view
            'xeroBankAccounts' => $xeroBankAccounts,
            'wcPaymentMap' => $wcPaymentMap,
            'mockWCPayments' => $mockWCPayments,
            'sessionMessage' => session('success') ?? session('warning'),
            'error' => session('error'),
        ]);
    }
}