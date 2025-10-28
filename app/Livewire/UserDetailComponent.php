<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\User; // Assuming your User model is here

class UserDetailComponent extends Component
{
    // Holds the user model object or null
    public $user = null;

    /**
     * Listens for the 'user-selected' event dispatched from the TeamMembersComponent.
     * @param int $id The ID of the selected User.
     */
    #[On('user-selected')]
    public function loadUser($id)
    {
        // Fetch the user model
        $this->user = User::find($id);
    }
    
    public function render()
    {
        return view('livewire.user-detail-component');
    }
}