<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SyncLogsComponent extends Component
{
    public $syncRuns;

    public function mount()
    {
        // Get the current user's team
        $team = Auth::user()->currentTeam;

        // Fetch all SyncRun models belonging to the current team
        $this->syncRuns = SyncRun::where('team_id', $team->id)->get();
    }

    public function render()
    {
        return view('livewire.sync-logs-component');
    }
}