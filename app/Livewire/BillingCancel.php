<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Team;

class BillingCancel extends Component
{
    public Team $team;

    public function mount(Team $team)
    {
        $this->team = $team;
    }

    public function render()
    {
        return view('livewire.billing-cancel');
    }
}