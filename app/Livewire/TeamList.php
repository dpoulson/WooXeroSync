<?php

namespace App\Livewire;

use Livewire\Component;
use Laravel\Jetstream\Jetstream; // Import Jetstream for the Team model

class TeamList extends Component
{
    // A property to hold the collection of teams
    public $teams;
    public function mount()
    {
        $teamModel = Jetstream::teamModel();

        // 1. Fetch all teams without the eager count
        $teamsCollection = $teamModel::all();

        $this->teams = $teamModel::withCount('users')->get();
    }
    
    /**
     * Render the component's view.
     */
    public function render()
    {
        return view('livewire.team-list');
    }
}