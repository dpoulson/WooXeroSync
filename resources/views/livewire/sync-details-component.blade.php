<div class="bg-white p-4 rounded-xl border border-gray-200 shadow-md dark:bg-gray-800 dark:border-gray-700 h-full">

    <h2 class="text-xl font-semibold text-gray-700 mb-4 dark:text-gray-200">Error Details</h2>
    
    @if (is_null($errorDetailsJson))
        <div class="p-4 bg-blue-100 text-blue-700 rounded-lg text-sm dark:bg-blue-900 dark:text-blue-100">
            Click on a log entry in the table to view its detailed error information here.
        </div>
    @elseif (empty($errorDetailsJson) || $errorDetailsJson === '[]' || $errorDetailsJson === '{}' || !$details || !is_array($details) || (isset($details['message']) && str_contains($details['message'], 'No details available')))
        {{-- Handles empty JSON, 'No details available' fallback, or parsing failure --}}
        <div class="p-4 bg-green-100 text-green-700 rounded-lg text-sm dark:bg-green-900 dark:text-green-100">
            This synchronization run reported no specific error details or the details were empty.
        </div>
    @else
        {{-- Display structured error details --}}
        <div class="space-y-6">
            
            {{-- PRIMARY ERROR MESSAGE --}}
            @if (isset($details['message']))
                <div class="p-4 rounded-lg border-l-4 border-red-500 bg-red-50 dark:bg-gray-700 dark:border-red-400">
                    <p class="font-bold text-red-700 dark:text-red-400 mb-1">Error Message:</p>
                    <p class="text-gray-900 dark:text-gray-200 font-mono text-sm break-all">{{ $details['message'] }}</p>
                </div>
            @endif
    
            {{-- TECHNICAL DETAILS (File and Line) --}}
            <div class="bg-gray-50 p-4 rounded-lg dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <p class="font-bold text-gray-700 dark:text-gray-300 mb-2">Technical Context</p>
                
                <dl class="text-sm">
                    @if (isset($details['file']))
                        <div class="py-1 flex justify-between border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">File:</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs text-right break-all">{{ basename($details['file']) }}</dd>
                        </div>
                        <div class="py-1">
                            <dt class="text-gray-500 dark:text-gray-400">Full Path:</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all mt-1">{{ $details['file'] }}</dd>
                        </div>
                    @endif
                    @if (isset($details['line']))
                        <div class="py-1 flex justify-between border-t border-gray-100 dark:border-gray-700 mt-2">
                            <dt class="text-gray-500 dark:text-gray-400">Line Number:</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-semibold">{{ $details['line'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
    
            {{-- BATCH ERRORS (Handling the potentially empty array) --}}
            @if (isset($details['batch_errors']) && is_array($details['batch_errors']) && count($details['batch_errors']) > 0)
                <div class="bg-yellow-100 p-4 rounded-lg border-l-4 border-yellow-500 dark:bg-yellow-900 dark:border-yellow-400">
                    <p class="font-bold text-yellow-800 dark:text-yellow-200 mb-2">Batch Errors Found ({{ count($details['batch_errors']) }})</p>
                    {{-- You can iterate through batch_errors here if needed --}}
                    <pre class="whitespace-pre-wrap font-mono text-xs text-yellow-700 dark:text-yellow-100">{{ print_r($details['batch_errors'], true) }}</pre>
                </div>
            @else
                <div class="p-2 text-sm text-gray-500 dark:text-gray-400 border-t pt-4 border-gray-200 dark:border-gray-700">
                    No specific batch or item-level errors found ({{ isset($details['batch_errors']) ? 'Empty array' : 'Field missing' }}).
                </div>
            @endif
            
            {{-- RAW JSON FALLBACK (Optional but useful for debugging) --}}
            <div x-data="{ open: false }">
                <button @click="open = !open" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 underline">
                    <span x-show="!open">Show Raw JSON</span>
                    <span x-show="open">Hide Raw JSON</span>
                </button>
                <div x-show="open" x-collapse.duration.500ms class="mt-2 bg-gray-700 p-3 rounded-lg overflow-x-auto text-sm dark:bg-gray-900">
                    <pre class="whitespace-pre-wrap font-mono text-gray-300 dark:text-gray-400 text-xs">{{ $errorDetailsJson }}</pre>
                </div>
            </div>
            
        </div>
    @endif
    
    
    </div>