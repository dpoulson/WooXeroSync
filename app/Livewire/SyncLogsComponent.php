<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination; // Import the trait

class SyncLogsComponent extends Component
{
    use WithPagination; // Use the trait

    // Public property to control pagination size
    public $perPage = 5;
    
    // Options for the per-page dropdown menu
    public $perPageOptions = [5, 10, 25, 50, 100];

    // Method runs when the 'perPage' property is updated
    public function updatedPerPage()
    {
        // Reset the current page to 1 whenever the limit changes
        $this->resetPage();
    }

    public function mount()
    {
        // Data fetching is moved to render() for pagination to work dynamically
    }

    public function render()
    {
        // Get the current user's team
        $team = Auth::user()->currentTeam;

        // Fetch paginated SyncRun models belonging to the current team
        $syncRuns = SyncRun::where('team_id', $team->id)
            ->latest() // Order by latest run (highly recommended for logs)
            ->paginate($this->perPage); // Use the perPage property for pagination

        return view('livewire.sync-logs-component', [
            'syncRuns' => $syncRuns, // Pass the paginated collection
        ]);
    }
}