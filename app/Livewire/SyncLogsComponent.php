<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class SyncLogsComponent extends Component
{
    use WithPagination;

    public $perPage = 5;
    public $perPageOptions = [5, 10, 25, 50, 100];

    // Property to highlight the currently selected row
    public $selectedRunId = null;

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    /**
     * Fetches the error details for a specific run and dispatches an event.
     */
    public function selectRun($syncRunId)
    {
        // Highlight the selected row
        $this->selectedRunId = $syncRunId;

        // Fetch the specific SyncRun to get the error_details
        $syncRun = SyncRun::findOrFail($syncRunId);

        // Dispatch the event to the SyncDetailsComponent, passing the error_details
        // The second argument is the data payload for the listener.
        $this->dispatch('sync-run-selected', $syncRun->error_details ?? 'No details available.');
    }

    public function render()
    {
        // Get the current user's team
        $team = Auth::user()->currentTeam;

        // Fetch paginated SyncRun models belonging to the current team
        $syncRuns = SyncRun::where('team_id', $team->id)
            ->latest() 
            ->paginate($this->perPage); 

        return view('livewire.sync-logs-component', [
            'syncRuns' => $syncRuns,
        ]);
    }
}