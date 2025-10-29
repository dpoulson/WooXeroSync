<div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
    @if ($syncRunId === null)
        <div class="text-center py-10 text-gray-500 dark:text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-3 10H6l1-17h11l-3 10z" />
            </svg>
            <p class="text-lg font-medium">Select a Sync Run to view details.</p>
            <p class="text-sm">Click on any row in the table to see its full execution history and metrics.</p>
        </div>
    @elseif ($syncRun === null)
         <div class="text-center py-10 text-red-500 dark:text-red-400">
            <p class="text-lg font-medium">Error: Sync Run not found.</p>
        </div>
    @else
        <h3 class="text-xl font-bold text-gray-900 mb-6 dark:text-gray-100">Details for Run #{{ $syncRun->id }}</h3>

        {{-- Summary Metrics --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            {{-- Status Card --}}
            @include('livewire.partials.metric-card', [
                'title' => 'Status', 
                'value' => $syncRun->status, 
                'color' => ($syncRun->status === 'Success' ? 'text-green-600' : ($syncRun->status === 'Failure' ? 'text-red-600' : 'text-blue-600'))
            ])
            
            {{-- Total Orders Card --}}
            @include('livewire.partials.metric-card', [
                'title' => 'Total Orders', 
                'value' => $syncRun->total_orders ?? 0, 
                'color' => 'text-indigo-600'
            ])
            
            {{-- Successful Invoices Card --}}
            @include('livewire.partials.metric-card', [
                'title' => 'Successful Invoices', 
                'value' => $syncRun->successful_invoices ?? 0, 
                'color' => 'text-green-600'
            ])
            
            {{-- Failed Invoices Card --}}
            @include('livewire.partials.metric-card', [
                'title' => 'Failed Invoices', 
                'value' => $syncRun->failed_invoices ?? 0, 
                'color' => 'text-red-600'
            ])
        </div>
        
        {{-- Error Details Section (Only displayed on Failure) --}}
        @if ($syncRun->status === 'Failure' && !empty($syncRun->error_details))
            <div class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded dark:bg-red-900 dark:border-red-600 dark:text-red-100 shadow-md">
                <p class="font-bold text-lg mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    Critical Error Details
                </p>
                <div class="text-sm space-y-1 ml-7">
                    <p><strong>Message:</strong> {{ $syncRun->error_details['message'] ?? 'N/A' }}</p>
                    <p>
                        <strong>Source:</strong> 
                        <span class="font-mono text-xs bg-red-100 px-1 py-0.5 rounded dark:bg-red-800 dark:text-red-300">
                            {{ basename($syncRun->error_details['file'] ?? 'N/A') }} (Line {{ $syncRun->error_details['line'] ?? 'N/A' }})
                        </span>
                    </p>
                </div>
                @if (!empty($syncRun->error_details['batch_errors']))
                    <p class="mt-4 text-sm font-semibold">Batch API Errors:</p>
                    <pre class="whitespace-pre-wrap text-xs mt-1 bg-red-100 p-3 rounded dark:bg-red-800 dark:text-red-100 overflow-x-auto">{{ json_encode($syncRun->error_details['batch_errors'], JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        @endif

        {{-- Log Entries Section --}}
        <h4 class="text-lg font-semibold text-gray-900 mt-6 mb-4 border-b pb-2 dark:text-gray-100 dark:border-gray-700">Execution Log ({{ $logs->count() }} Entries)</h4>
        
        <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
            @forelse ($logs as $log)
                <div class="p-3 rounded-lg flex space-x-3 text-sm items-start shadow-sm
                    @switch($log->level)
                        @case('error') bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 @break
                        @case('info') bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-700 @break
                        @case('debug') bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 @break
                        @default bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600
                    @endswitch
                ">
                    <span class="font-mono text-xs pt-0.5 w-14 uppercase font-bold flex-shrink-0 
                        @switch($log->level)
                            @case('error') text-red-700 dark:text-red-400 @break
                            @case('info') text-blue-700 dark:text-blue-400 @break
                            @case('debug') text-gray-700 dark:text-gray-400 @break
                            @default text-gray-700 dark:text-gray-400
                        @endswitch
                    ">
                        {{ $log->level }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">{{ $log->message }}</p>
                        @if (!empty($log->context))
                            <pre class="text-xs text-gray-600 mt-1 dark:text-gray-400 whitespace-pre-wrap bg-opacity-50 p-1 rounded">Context: {{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    </div>
                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap pt-0.5 flex-shrink-0">
                        {{ $log->created_at->format('H:i:s.u') }}
                    </span>
                </div>
            @empty
                <p class="text-center text-gray-500 text-sm py-8 dark:text-gray-400">No detailed log entries found for this run.</p>
            @endforelse
        </div>
    @endif
</div>
