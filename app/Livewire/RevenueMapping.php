<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\XeroTokenService;
use App\Services\XeroIntegrationService;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class RevenueMapping extends Component
{

        // These public properties will hold the data displayed in the view.
    // They are automatically passed to the view.
    public array $xeroStatus = [];
    public array $xeroAccounts = [];
    public array $wcRevenueMap = [];
    public array $wcRevenueTypes = [
        'default' => 'Default Sales Account',
        'postage' => 'Default Postage Account',
    ];
    public array $mapping = [];
    public $currentTeam;

    // You might also use a dedicated Xero service or repository 
    // to fetch the initial data instead of relying on the parent view.

    // A mount method is a good place to load initial data.
    public function mount()
    {

        if (auth()->check() && auth()->user()->currentTeam) {
            // Get the user model instance
            $this->currentTeam = auth()->user()->currentTeam;
        } else {
            // Handle guest user case (e.g., redirect or set default state)
            abort(403, 'You must be logged in and have an active team.');
        }
        $this->xeroStatus = XeroTokenService::getConnectionStatus($this->currentTeam);

        if ($this->xeroStatus['connected']) {
            try {
                $this->xeroAccounts = XeroIntegrationService::getAccounts($this->currentTeam, "REVENUE");
            } catch (\Exception $e) {
                Log::error("Unable to get list of Bank Accounts".$e);
            }
        }

        $this->wcRevenueMap = !empty($this->currentTeam->woocommerceConnection->revenue_account_map) ? json_decode($this->currentTeam->woocommerceConnection->revenue_account_map, true) : [];
        $this->mapping = $this->wcRevenueMap;
    }

    public function saveRevenueMapping()
    {

        $rules = [];
        foreach ($this->wcRevenueTypes as $gatewayId => $gatewayName) {
            // Only validate if a selection has been made (adjust as needed)
            $rules["mapping.{$gatewayId}"] = 'nullable|string'; 
        }
        //$this->validate($rules);
        $finalMapping = array_filter($this->mapping, function($value) {
            return !empty($value);
        });
        $this->mapping = $finalMapping;
        /*
        $mappingData = $request->input('mapping', []);
        
        // Clean and validate the mapping data
        $cleanMap = [];
        foreach ($mappingData as $wcGateway => $xeroCode) {
            if (is_string($wcGateway) && !empty($xeroCode)) {
                $cleanMap[$wcGateway] = $xeroCode;
            }
        }
            */
        Log::info("Revenue Map: ". json_encode($finalMapping));

        try {
            $this->currentTeam->woocommerceConnection()->update([
                'revenue_account_map' => json_encode($finalMapping),
            ]);
            Log::info("Saved user revenue map");
            return redirect()->route('configure')->with('flash.banner', 'Revenue mapping successfully updated and saved.');
        } catch (Exception $e) {
             Log::error('Failed to save revenue mapping: ' . $e->getMessage());
             return redirect()->route('configure')->with('flash.banner', 'Failed to save revenue mapping.')->with('flash.bannerStyle', 'danger');
        }
    }

    public function render()
    {
        return view('livewire.revenue-mapping');
    }
}
