<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\XeroTokenService;


class XeroConnectionStatus extends Component
{
    // These public properties will hold the data displayed in the view.
    // They are automatically passed to the view.
    public array $xeroStatus = [];
    public $currentTeam;

    // You might also use a dedicated Xero service or repository 
    // to fetch the initial data instead of relying on the parent view.

    // A mount method is a good place to load initial data.
    public function mount()
    {

        if (auth()->check() && auth()->user()->currentTeam) {
            // Get the user model instance
            $this->currentTeam = auth()->user()->currentTeam;
        } else {
            // Handle guest user case (e.g., redirect or set default state)
            abort(403, 'You must be logged in and have an active team.');
        }
        $this->xeroStatus = $this->fetchXeroStatus();

    }
    
    // Livewire method to handle the Xero disconnection
    public function disconnectXero()
    {
        
        // 2. Update the component state.
        XeroTokenService::revokeConnection($this->currentTeam);
        $this->xeroStatus = $this->fetchXeroStatus(); 
        $this->dispatch('xeroStatusUpdated');
        $this->dispatch('banner-message', style: 'success', message: 'Successfully disconnected from Xero!');
    }

    /**
     * Listens for the 'xeroStatusUpdated' event and refreshes the status.
     * The Livewire framework handles calling $refresh() implicitly here.
     */
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
        return XeroTokenService::getConnectionStatus($this->currentTeam);
    }

    // The render method remains the same, pointing to the view file.
    public function render()
    {
        return view('livewire.xero-connection-status');
    }
}