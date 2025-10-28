<div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Sync Logs</h2>
        {{-- Icon related to teams/users --}}
        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
    </div>
    
    {{-- Pagination Controls and Status --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 space-y-3 sm:space-y-0">
        <div class="flex items-center space-x-3">
            <label for="perPage" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Items per page:
            </label>
            {{-- Use wire:model.live to update perPage property and re-render on change --}}
            <select wire:model.live="perPage" id="perPage" class="border-gray-300 focus:border-indigo-300 focus:ring-indigo-200 rounded-md shadow-sm text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 p-2">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
        
        {{-- Display current result range --}}
        @if ($syncRuns->total() > 0)
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span class="font-semibold">{{ $syncRuns->firstItem() }}</span> to <span class="font-semibold">{{ $syncRuns->lastItem() }}</span> of <span class="font-semibold">{{ $syncRuns->total() }}</span> results
            </p>
        @endif
    </div>
    
    {{-- The sync logs table, styled to fit the card's look --}}
    @if ($syncRuns->isEmpty() && $syncRuns->currentPage() === 1)
        <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm dark:bg-yellow-800 dark:text-yellow-100">
            There are currently no logs for this organisation.
        </div>
    @else
        <div class="bg-white shadow-lg sm:rounded-lg overflow-hidden border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Start
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            End
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Total Orders
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Successful
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Failed
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @foreach ($syncRuns as $syncRun)
                        {{-- Add wire:click to dispatch event and a dynamic class for visual feedback --}}
                        <tr 
                            wire:click="selectRun({{ $syncRun->id }})" 
                            class="cursor-pointer transition duration-100 
                                {{ $selectedRunId === $syncRun->id ? 'bg-indigo-100 dark:bg-indigo-900 ring-2 ring-indigo-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                {{ $syncRun->start_time }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right dark:text-gray-400">
                                {{ $syncRun->end_time }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right dark:text-gray-400">
                                {{ $syncRun->total_orders }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right dark:text-gray-400">
                                {{ $syncRun->successful_invoices }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right dark:text-gray-400">
                                {{ $syncRun->failed_invoices }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Livewire Pagination Links --}}
        <div class="mt-6">
            {{ $syncRuns->links() }}
        </div>
    @endif
    
    
    </div>