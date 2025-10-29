<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Collection;

class SyncDetailsComponent extends Component
{
    // The ID of the currently selected SyncRun
    public $syncRunId = null;

    // The SyncRun model instance
    public ?SyncRun $syncRun = null;

    // The log entries for the selected run
    public Collection $logs;

    // Listener for the event dispatched by SyncLogsComponent
    protected $listeners = ['sync-run-selected' => 'loadRunDetails'];
    
    // Initialize the collection property
    public function mount()
    {
        // Use an empty collection to prevent errors if rendered before selection
        $this->logs = new Collection();
    }

    /**
     * Loads the full details and logs for a selected SyncRun ID.
     */
    public function loadRunDetails(int $id)
    {
        $this->syncRunId = $id;

        // Eager load the logs relationship for efficiency
        $this->syncRun = SyncRun::with('logs')->find($this->syncRunId);

        if ($this->syncRun) {
            // Assign logs, which are already sorted ASC by the relationship definition
            $this->logs = $this->syncRun->logs;
        } else {
            // Clear state if run is not found
            $this->logs = new Collection();
        }
    }

    public function render()
    {
        return view('livewire.sync-details-component');
    }
}
