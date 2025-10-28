<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class SyncDetailsComponent extends Component
{
    // Holds the raw JSON string or null if nothing is selected
    public $errorDetailsJson = null;

    // Holds the parsed details (for display)
    public $details = null;

    /**
     * Listens for the 'sync-run-selected' event dispatched from the logs table.
     * @param mixed $errorDetails The error_details data, which might be a string or an array/object due to model casting.
     */
    #[On('sync-run-selected')]
    public function showDetails($errorDetails)
    {
        $this->details = null;
        
        // 1. Check if Laravel has already cast the data to an array/object.
        if (is_array($errorDetails) || is_object($errorDetails)) {
            // If it's already parsed, use it directly for display and re-encode it for the raw JSON display.
            $this->details = (array) $errorDetails;
            $this->errorDetailsJson = json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        // 2. If it's a string (the expected case, or the 'No details available' fallback)
        if (is_string($errorDetails)) {
            $this->errorDetailsJson = $errorDetails; // Set the raw string
            
            // Line 28 (original error location) - now only runs if it's confirmed a string
            if ($errorDetails && $errorDetails !== 'No details available.') {
                try {
                    // Attempt to decode the string
                    $this->details = json_decode($errorDetails, true);
                } catch (\Exception $e) {
                    // Fallback if parsing fails (e.g., if it's malformed JSON)
                    $this->details = ['Error' => 'Failed to parse JSON string.', 'Raw Data' => $errorDetails];
                }
            }
        } else {
            // Handle any unexpected types
            $this->errorDetailsJson = 'Unknown data type received.';
            $this->details = null;
        }
    }

    public function render()
    {
        return view('livewire.sync-details-component');
    }
}
