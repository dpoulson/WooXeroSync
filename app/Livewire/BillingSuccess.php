<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url; // <- IMPORTANT: Make sure this is imported!
use App\Models\Team; 
use Laravel\Cashier\Cashier;
use Livewire\Attributes\Layout; 
use Illuminate\Support\Facades\Log; // Good practice for debugging background tasks

#[Layout('layouts.app')]
class BillingSuccess extends Component
{
    // 1. Property Name MUST match the URL key ('session_id')
    // 2. #[Url] attribute MUST be present
    #[Url] 
    public string $session_id = ''; // Initialize to empty string or null

    public $status = 'processing';
    public $team;

    // Remove ALL parameters from the mount method!
    public function mount() 
    {
        // The property $this->session_id is now populated by Livewire *before* mount runs.
        
        if (empty($this->session_id)) {
            Log::warning('BillingSuccess accessed without session_id.');
            $this->status = 'error';
            return;
        }
        
        // Now call verifySession, which uses the populated property
        $this->verifySession();
    }
    
    public function verifySession()
    {
        try {
            // Retrieve the session from Stripe using the bound property
            $session = Cashier::stripe()->checkout->sessions->retrieve($this->session_id);
            
            // The team is the "client_reference_id" on the Stripe session
            $teamId = $session->client_reference_id;
            $this->team = Team::find($teamId);

            if ($session->payment_status === 'paid') {
                $this->status = 'success';
            } else {
                $this->status = 'failed';
            }

        } catch (\Exception $e) {
            Log::error('Stripe session verification failed: ' . $e->getMessage());
            $this->status = 'error';
        }
    }

    public function render()
    {
        return view('livewire.billing-success');
    }
}