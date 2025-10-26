<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\WoocommerceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class WoocommerceConnectionStatus extends Component
{
    // Properties for the API connection form
    public $woocommerce_url = '';
    public $woocommerce_consumer_key = '';
    public $woocommerce_consumer_secret = '';

    // Property to hold the status data from the service
    public array $wcStatus = [
        'connected' => false,
        'url' => null,
        'key_status' => 'N/A'
    ];

    // Property to control the visibility of the configuration form
    public $showConfigForm = false;

    // NOTE: Removed the '$user' property definition. We will fetch the user
    // locally in methods using Auth::user() to prevent hydration issues.

    public function mount()
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        $this->fetchWoocommerceStatus();
        
        // Load existing data if available for pre-filling
        $this->woocommerce_url = $user->woocommerce_url ?? '';
        
        // Determine initial form visibility state
        $this->showConfigForm = !$this->wcStatus['connected'];
    }

    /**
     * Retrieves the latest status from the WooCommerce Service and updates component state.
     */
    protected function fetchWoocommerceStatus()
    {
        // CRITICAL FIX: Get user instance locally
        $user = Auth::user(); 

        if (!$user) {
            // If user is somehow lost, return a default disconnected status
            return $this->wcStatus;
        }

        // Use the existing service command with the local $user
        $this->wcStatus = WoocommerceService::getConnectionStatus($user);
    }

    /**
     * Saves the WooCommerce credentials and attempts to verify the connection.
     */
    public function saveConnection()
    {
        // CRITICAL FIX: Re-authenticate and check the user object immediately
        $user = Auth::user();
        if (!$user) {
            $this->dispatch('banner-message', style: 'danger', message: 'Authentication required. Please refresh and log in again.');
            return;
        }
        
        $this->validate([
            'woocommerce_url' => 'required|url',
            'woocommerce_consumer_key' => 'required|string', 
            'woocommerce_consumer_secret' => 'required|string', 
        ]);
        
        // 1. Persist the credentials to the user model/database.
        // We are now calling forceFill() on the local $user variable, which is guaranteed not to be null.
        try {
            $user->forceFill([
                'woocommerce_url' => $this->woocommerce_url,
                'woocommerce_consumer_key' => Crypt::encryptString($this->woocommerce_consumer_key),
                'woocommerce_consumer_secret' => Crypt::encryptString($this->woocommerce_consumer_secret),
            ])->save();
        } catch (\Exception $e) {
            // Handle encryption or database save error gracefully
            $this->dispatch('banner-message', style: 'danger', message: 'Error saving credentials: ' . $e->getMessage());
            Log::error('WooCommerce Save Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return;
        }

        
        // Clear sensitive inputs after save attempt
        $this->woocommerce_consumer_key = '';
        $this->woocommerce_consumer_secret = '';

        // 3. Refresh the status
        $this->fetchWoocommerceStatus();
        
        // 4. Update form visibility
        $this->showConfigForm = !$this->wcStatus['connected'];

        // 5. Provide feedback
        if ($this->wcStatus['connected']) {
            $this->dispatch('banner-message', style: 'success', message: 'WooCommerce connected successfully!');
        } else {
            $this->dispatch('banner-message', style: 'danger', message: 'Credentials saved, but connection failed. Check your keys and URL.');
        }
    }

    /**
     * Toggles the visibility of the configuration form.
     */
    public function toggleConfigForm()
    {
        $this->showConfigForm = !$this->showConfigForm;
    }

    #[On('woocommerceStatusUpdated')]
    public function refreshStatus()
    {
        $this->fetchWoocommerceStatus();
    }
    
    public function render()
    {
        return view('livewire.woocommerce-connection-status');
    }
}
