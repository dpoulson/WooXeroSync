<div class="bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">
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
        <div class="p-4 bg-white shadow-xl rounded-xl border-t-8 border-indigo-600 max-w-xl mx-auto font-sans" wire:init="loadLastSyncRun">

            {{-- Error Block --}}
            @if ($error)
                <div class="p-4 mb-4 bg-red-50 border border-red-300 rounded-lg text-red-700 flex items-center gap-2">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    <span>Critical Error: {{ $error }}</span>
                </div>
            @endif
            
            {{-- Loading State --}}
            @if ($loading)
                <div class="p-6 text-center text-gray-500">
                    <svg class="w-6 h-6 inline animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M12 2v10"/></svg>
                    Loading Sync Status...
                </div>
            {{-- No Sync Run Found --}}
            @elseif (!$lastSync)
                <div class="p-6 text-center border-2 border-dashed border-gray-300 rounded-xl text-gray-600">
                    <svg class="w-8 h-8 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13.5V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4.5"/><path d="M16 8h3"/><path d="M16 12h3"/><path d="M16 16h3"/><path d="M2 22h3"/><path d="M2 18h3"/><path d="M2 14h3"/><path d="M2 10h3"/><path d="M2 6h3"/></svg>
                    <p class="font-semibold">No Sync Runs Found</p>
                    <p class="text-sm">Run a sync job to see the history here.</p>
                </div>
            @else
                @php
                    $props = $this->statusProps;
                    $status = $lastSync->status ?? 'N/A';
                @endphp
            
                <div class="flex justify-between items-start border-b pb-3 mb-4">
                    <h3 class="text-2xl font-bold text-gray-800">Last Synchronization Status</h3>
                    <button wire:click="loadLastSyncRun" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition duration-150" title="Refresh Status">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M21.5 8a10 10 0 0 0-17.923-5.5"/><path d="M2.5 16a10 10 0 0 0 17.923 5.5"/></svg>
                    </button>
                </div>
            
                {{-- Status Card --}}
                <div class="p-4 rounded-lg border-2 {{ $props['borderColor'] }} {{ $props['bgColor'] }}">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 {{ $props['color'] }}">
                            {!! $props['icon_path'] !!}
                        </div>
                        <span class="text-lg font-extrabold {{ $props['color'] }}">{{ strtoupper($status) }}</span>
                        @if ($status === 'Running')
                            <span class="text-sm text-blue-600">Process is currently active.</span>
                        @endif
                    </div>
                </div>
            
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    
                    {{-- Timing Details --}}
                    <div class="col-span-2 md:col-span-1 p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-gray-700">Start Time (UTC)</p>
                        <p class="text-gray-600 font-mono text-xs">{{ $lastSync->start_time?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                    </div>
                    <div class="col-span-2 md:col-span-1 p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-gray-700">End Time (UTC)</p>
                        <p class="text-gray-600 font-mono text-xs">{{ $lastSync->end_time?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                    </div>
            
                    {{-- Metrics --}}
                    <div class="col-span-2 border-t pt-4 mt-2">
                        <h4 class="font-semibold text-gray-700 mb-2">Sync Metrics</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-white border border-gray-200 rounded-lg text-center">
                                <svg class="w-5 h-5 mx-auto mb-1 text-indigo-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3h8v4"/><path d="M12 3v10"/><path d="M2 13h20"/><path d="M2 13l4 4"/><path d="M20 13l-4 4"/></svg>
                                <p class="text-xl font-bold text-gray-800">{{ $lastSync->total_orders ?? 0 }}</p>
                                <p class="text-xs text-gray-500 font-medium">Total Orders</p>
                            </div>
                            <div class="p-3 bg-white border border-gray-200 rounded-lg text-center">
                                <svg class="w-5 h-5 mx-auto mb-1 text-green-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                <p class="text-xl font-bold text-gray-800">{{ $lastSync->successful_invoices ?? 0 }}</p>
                                <p class="text-xs text-gray-500 font-medium">Successful Invoices</p>
                            </div>
                            <div class="p-3 bg-white border border-gray-200 rounded-lg text-center">
                                <svg class="w-5 h-5 mx-auto mb-1 text-red-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                <p class="text-xl font-bold text-gray-800">{{ $lastSync->failed_invoices ?? 0 }}</p>
                                <p class="text-xs text-gray-500 font-medium">Failed Invoices</p>
                            </div>
                        </div>
                    </div>
            
                    {{-- Duration --}}
                    <div class="col-span-2 p-3 bg-gray-50 rounded-lg mt-2">
                        <p class="font-semibold text-gray-700">Duration</p>
                        <p class="text-gray-600 font-mono text-sm">{{ $this->durationInSeconds }}</p>
                    </div>
                </div>
            
                {{-- Error Details Section (Conditional) --}}
                @if ($lastSync->error_details)
                    @php
                        $errors = $lastSync->error_details;
                        $batchErrors = $errors['batch_errors'] ?? [];
                    @endphp
            
                    @if (!empty($errors['message']))
                        <div class="mt-4 p-3 bg-red-50 border-l-4 border-red-400 text-sm rounded-md">
                            <h4 class="font-semibold text-red-600">CRITICAL SYSTEM ERROR</h4>
                            <p class="font-mono text-xs mt-1 overflow-x-auto break-words">{{ $errors['message'] }}</p>
                        </div>
                    @endif
            
                    @if (!empty($batchErrors))
                        <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 max-h-40 overflow-y-auto rounded-md">
                            <h4 class="font-semibold text-yellow-800 flex items-center gap-2">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                                {{ count($batchErrors) }} Item(s) Failed to Process
                            </h4>
                            <ul class="text-sm space-y-2 mt-2">
                                @foreach (collect($batchErrors)->take(5) as $err)
                                    <li class="border-b border-yellow-100 pb-1 last:border-b-0">
                                        <span class="font-mono text-xs bg-yellow-100 px-1 py-0.5 rounded mr-2">{{ $err['endpoint'] }}</span>
                                        <span class="font-semibold text-gray-700">{{ $err['name'] ?? $err['reference'] }}</span>
                                        <p class="text-xs text-red-600 mt-0.5">{{ $err['errors'] }}</p>
                                    </li>
                                @endforeach
                                @if (count($batchErrors) > 5)
                                    <li class="text-xs text-gray-500 italic pt-1">
                                        ... and {{ count($batchErrors) - 5 }} more failures.
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                @endif
            @endif
            
            
            </div>
    
        {{-- Days to Sync Input --}}
        <div class="max-w-xl mx-auto">
        <div class="flex items-end space-x-6">

            <div class="mb-1">
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
            </div>
        
            <div class="mt-6 max-w-xl">
                <label for="syncDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Number of Days to Sync (Last X Days)
                </label>
                <input 
                    wire:model.live="syncDays" 
                    type="number" 
                    id="syncDays" 
                    name="syncDays"
                    min="1"
                    max="30"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    placeholder="2"
                >

                {{-- ADD THIS Livewire Error Display --}}

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Default is 2 days. The system will sync orders created in the last X days.</p>
            </div>
        </div>
    </div>
    @endif
</div>



