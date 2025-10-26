<div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Run Manual Sync</h2>
        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
    </div>

    @if (!data_get($xeroStatus, 'connected'))
        <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm">
            Please connect to Xero first to load the list of available bank accounts.
        </div>
    @else
    
        {{-- Status Display --}}
        <p class="mb-4 text-sm text-gray-600">
            Current Status: 
            <span class="font-semibold text-blue-700">{{ $syncStatus }}</span>
        </p>
    
        @if ($lastSyncTime)
            <p class="text-xs text-gray-500 mb-6">
                Last successful run: **{{ $lastSyncTime }}**
            </p>
        @endif
    
        {{-- The Sync Button --}}
        <button
            wire:click="syncOrders"
            
            {{-- DISABLE BUTTON while the syncOrders method is running --}}
            wire:loading.attr="disabled"
            wire:target="syncOrders"
    
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150 disabled:opacity-75"
        >
            
            {{-- Button text when NOT loading --}}
            <span wire:loading.remove wire:target="syncOrders">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2H15"></path></svg>
                Run Sync Now
            </span>
    
            {{-- Spinner and text when LOADING --}}
            <span wire:loading wire:target="syncOrders">
                {{-- Simple SVG Spinner --}}
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Syncing...
            </span>
        </button>
    @endif
</div>