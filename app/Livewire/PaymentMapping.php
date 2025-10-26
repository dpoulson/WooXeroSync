<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\XeroTokenService;
use App\Services\XeroIntegrationService;
use Illuminate\Support\Facades\Log;

class PaymentMapping extends Component
{

        // These public properties will hold the data displayed in the view.
    // They are automatically passed to the view.
    public array $xeroStatus = [];
    public array $xeroBankAccounts = [];
    public array $wcPaymentMap = [];
    public array $wcPaymentTypes = [
        'bacs' => 'BACS (Bank Transfer)',
        'cheque' => 'Cheque Payment',
        'cod' => 'Cash on Delivery',
        'ppcp-gateway' => 'PayPal Payments Pro',
        'stripe' => 'Stripe (Credit Card)',
    ];
    public array $mapping = [];
    public $user;

    // You might also use a dedicated Xero service or repository 
    // to fetch the initial data instead of relying on the parent view.

    // A mount method is a good place to load initial data.
    public function mount()
    {

        if (auth()->check()) {
            // Get the user model instance
            $this->user = auth()->user();
        } else {
            // Handle guest user case (e.g., redirect or set default state)
            abort(403, 'You must be logged in.');
        }
        $this->xeroStatus = XeroTokenService::getConnectionStatus($this->user);

        if ($this->xeroStatus['connected']) {
            try {
                $this->xeroBankAccounts = XeroIntegrationService::getBankAccounts($this->user);
            } catch (\Exception $e) {
                Log::error("Unable to get list of Bank Accounts".$e);
            }
        }

        $this->wcPaymentMap = $this->user->wc_payment_account_map ? json_decode($this->user->wc_payment_account_map, true) : [];
        $this->mapping = $this->wcPaymentMap;
    }

    public function savePaymentMapping()
    {

        $rules = [];
        foreach ($this->wcPaymentTypes as $gatewayId => $gatewayName) {
            // Only validate if a selection has been made (adjust as needed)
            $rules["mapping.{$gatewayId}"] = 'nullable|string'; 
        }
        //$this->validate($rules);
        $finalMapping = array_filter($this->mapping, function($value) {
            return !empty($value);
        });
        $this->mapping = $finalMapping;
        /*
        $mappingData = $request->input('mapping', []);
        
        // Clean and validate the mapping data
        $cleanMap = [];
        foreach ($mappingData as $wcGateway => $xeroCode) {
            if (is_string($wcGateway) && !empty($xeroCode)) {
                $cleanMap[$wcGateway] = $xeroCode;
            }
        }
            */
        Log::info("Payment Map: ". json_encode($finalMapping));

        try {
            $this->user->update([
                'wc_payment_account_map' => json_encode($finalMapping),
            ]);
            Log::info("Saved user payment map");
            return redirect()->route('configure')->with('success', 'Payment mapping successfully updated and saved.');
        } catch (Exception $e) {
             Log::error('Failed to save payment mapping: ' . $e->getMessage());
            return redirect()->route('configure')->with('error', 'Failed to save payment mapping.');
        }
    }

    public function render()
    {
        return view('livewire.payment-mapping');
    }
}
