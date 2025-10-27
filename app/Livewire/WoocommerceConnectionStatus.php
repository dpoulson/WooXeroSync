<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Model\Teams;
use App\Services\WoocommerceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class WoocommerceConnectionStatus extends Component
{
    // Properties for the API connection form
    public $store_url = '';
    public $consumer_key = '';
    public $consumer_secret = '';

    // Property to hold the status data from the service
    public array $wcStatus = [
        'connected' => false,
        'url' => null,
        'key_status' => 'N/A'
    ];

    public $currentTeam;

    // Property to control the visibility of the configuration form
    public $showConfigForm = false;

    // NOTE: Removed the '$user' property definition. We will fetch the user
    // locally in methods using Auth::user() to prevent hydration issues.

    public function mount()
    {
        if (auth()->check() && auth()->user()->currentTeam) {
            // Get the user model instance
            $this->currentTeam = auth()->user()->currentTeam;
        } else {
            // Handle guest user case (e.g., redirect or set default state)
            abort(403, 'You must be logged in and have an active team.');
        }

        $this->fetchWoocommerceStatus();
        
        // Load existing data if available for pre-filling
        $this->store_url = $this->currentTeam->woocommerceConnection->store_url ?? '';
        
        // Determine initial form visibility state
        $this->showConfigForm = !$this->wcStatus['connected'];
    }

    /**
     * Retrieves the latest status from the WooCommerce Service and updates component state.
     */
    protected function fetchWoocommerceStatus()
    {

        if (!$this->currentTeam) {
            // If user is somehow lost, return a default disconnected status
            return $this->wcStatus;
        }

        // Use the existing service command with the local $team
        $this->wcStatus = WoocommerceService::getConnectionStatus($this->currentTeam);
    }

    /**
     * Saves the WooCommerce credentials and attempts to verify the connection.
     */
    public function saveConnection()
    {

        if (!$this->currentTeam) {
            $this->dispatch('banner-message', style: 'danger', message: 'Authentication required. Please refresh and log in again.');
            return;
        }
        
        $this->validate([
            'store_url' => 'required|url',
            'consumer_key' => 'required|string', 
            'consumer_secret' => 'required|string', 
        ]);
        
        
        // 1. Persist the credentials to the user model/database.
        // We are now calling forceFill() on the local $user variable, which is guaranteed not to be null.
        try {
            $this->currentTeam->woocommerceConnection()->updateOrCreate(
                ['team_id' => $this->currentTeam->id], // The attributes to search for
                [
                    'store_url' => $this->store_url,
                    'consumer_key' => $this->consumer_key,
                    'consumer_secret' => $this->consumer_secret,
                ]
            );
        } catch (\Exception $e) {
            // Handle encryption or database save error gracefully
            $this->dispatch('banner-message', style: 'danger', message: 'Error saving credentials: ' . $e->getMessage());
            Log::error('WooCommerce Save Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return;
        }

        
        // Clear sensitive inputs after save attempt
        $this->consumer_key = '';
        $this->consumer_secret = '';

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
