<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\XeroTokenService;
use App\Services\WoocommerceService;
use App\Models\SyncRun;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RunManualSync extends Component
{
    /** @var \App\Models\SyncRun|null */
    public $lastSync = null;
    public bool $loading = true;
    public ?string $error = null;
    // These public properties will hold the data displayed in the view.
    // They are automatically passed to the view.
    public array $xeroStatus = [];
    public $currentTeam;
    /** @var string The user-facing status message. */
    public $syncStatus = 'Ready to start synchronization.';
    
    /** @var string|null The time of the last successful sync. */
    public $lastSyncTime = null;

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
        $this->loadLastSyncRun();

    }

    public function loadLastSyncRun(): void
    {
        $this->loading = true;
        $this->error = null;
        $teamId = $this->currentTeam->id;

        if (!$teamId) {
            $this->error = "User not authenticated. Cannot retrieve sync history.";
            $this->loading = false;
            return;
        }

        try {
            // Retrieve the latest SyncRun record
            $syncRun = SyncRun::where('team_id', $teamId)
                                     ->orderBy('start_time', 'desc')
                                     ->first();
            
            $this->lastSync = $syncRun;

            if ($this->lastSync && $this->lastSync->error_details) {
                // Ensure error_details is an array if the model cast failed or data is inconsistent
                if (is_string($this->lastSync->error_details)) {
                    $this->lastSync->error_details = json_decode($this->lastSync->error_details, true);
                }
            }

        } catch (\Exception $e) {
            $this->error = "Database Error: Failed to retrieve sync history. Please check logs.";
            Log::error("Livewire SyncStatusWidget DB Error: " . $e->getMessage());
        }

        $this->loading = false;
    }

    /**
     * Calculated property to get the duration in seconds.
     */
    public function getDurationInSecondsProperty(): string
    {
        if (!$this->lastSync || !$this->lastSync->start_time || !$this->lastSync->end_time) {
            return '--';
        }
        $start = Carbon::parse($this->lastSync->start_time);
        $end = Carbon::parse($this->lastSync->end_time);
        return $start->diffInSeconds($end) . ' seconds';
    }


    /**
     * Calculates the required color/icon properties for the status.
     */
    public function getStatusPropsProperty(): array
    {
        $status = $this->lastSync->status ?? 'N/A';
        
        return match ($status) {
            'Success' => [
                'icon_path' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
                'color' => 'text-green-500', 
                'bgColor' => 'bg-green-100', 
                'borderColor' => 'border-green-400'
            ],
            'Failure' => [
                'icon_path' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>',
                'color' => 'text-red-500', 
                'bgColor' => 'bg-red-100', 
                'borderColor' => 'border-red-400'
            ],
            'Running' => [
                'icon_path' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M12 2v10"/></svg>',
                'color' => 'text-blue-500 animate-spin', 
                'bgColor' => 'bg-blue-100', 
                'borderColor' => 'border-blue-400'
            ],
            default => [
                'icon_path' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
                'color' => 'text-gray-500', 
                'bgColor' => 'bg-gray-100', 
                'borderColor' => 'border-gray-400'
            ]
        };
    }

    public function syncOrders()
    {
        // Set an initial status while the network request is happening
        // (The wire:loading indicator will also be active now).
        $this->syncStatus = 'Synchronization in progress... Please wait.';

        try {
            // Call your service method. This is the long-running operation.
            $orderCount = WoocommerceService::SyncOrders($this->currentTeam);

            // Update status upon success
            $this->syncStatus = "Sync Complete! Successfully synced $orderCount orders.";
            $this->lastSyncTime = now()->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Update status upon failure
            $this->syncStatus = 'Sync Failed: ' . $e->getMessage();
            
            // Log the error for debugging
            Log::error('WooCommerce Sync Error for Team ID ' . $this->currentTeam->id . ': ' . $e->getMessage());
        }
        $this->loadLastSyncRun();
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
        return XeroTokenService::getConnectionStatus($this->currentTeam);
    }

    public function render()
    {
        return view('livewire.run-manual-sync');
    }
}
