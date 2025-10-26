<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\XeroTokenService;
use App\Services\WoocommerceService;

class RunManualSync extends Component
{

    // These public properties will hold the data displayed in the view.
    // They are automatically passed to the view.
    public array $xeroStatus = [];
    public $user;
    /** @var string The user-facing status message. */
    public $syncStatus = 'Ready to start synchronization.';
    
    /** @var string|null The time of the last successful sync. */
    public $lastSyncTime = null;

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
        $this->xeroStatus = $this->fetchXeroStatus();

    }

    public function syncOrders()
    {
        // Set an initial status while the network request is happening
        // (The wire:loading indicator will also be active now).
        $this->syncStatus = 'Synchronization in progress... Please wait.';

        try {
            // Call your service method. This is the long-running operation.
            $orderCount = WoocommerceService::SyncOrders($this->user);

            // Update status upon success
            $this->syncStatus = "Sync Complete! Successfully synced $orderCount orders.";
            $this->lastSyncTime = now()->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Update status upon failure
            $this->syncStatus = 'Sync Failed: ' . $e->getMessage();
            
            // Log the error for debugging
            \Log::error('WooCommerce Sync Error: ' . $e->getMessage());
        }

        // The component will automatically re-render here, updating the displayed status.
    }

    #[On('xeroStatusUpdated')]
    public function refreshStatus()
    {
        // We re-run the status check logic to ensure we get the latest state 
        // from the database/service and update the view.
        $this->xeroStatus = $this->fetchXeroStatus();
    }

    protected function fetchXeroStatus() 
    {
        // Implement logic to read the current Xero connection status
        // return 'Connected' or 'Disconnected'
        return XeroTokenService::getConnectionStatus($this->user);
    }

    public function render()
    {
        return view('livewire.run-manual-sync');
    }
}
