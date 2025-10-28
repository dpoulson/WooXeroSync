<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; // Import the Livewire 3 attribute

class SyncDetailsComponent extends Component
{
    // Holds the raw JSON string or null if nothing is selected
    public $errorDetailsJson = null;

    // Holds the parsed details (for display)
    public $details = null;

    /**
     * Listens for the 'sync-run-selected' event dispatched from the logs table.
     * @param string $jsonErrorDetails The error_details JSON string from the selected SyncRun.
     */
    #[On('sync-run-selected')]
    public function showDetails($jsonErrorDetails)
    {
        $this->errorDetailsJson = $jsonErrorDetails;

        // Attempt to parse the JSON for better display
        if ($jsonErrorDetails) {
            try {
                $this->details = json_decode($jsonErrorDetails, true);
            } catch (\Exception $e) {
                // Fallback if parsing fails (e.g., if it's not valid JSON)
                $this->details = ['Error' => 'Failed to parse JSON details. Displaying raw data below.'];
            }
        } else {
            $this->details = null;
        }
    }

    public function render()
    {
        return view('livewire.sync-details-component');
    }
}