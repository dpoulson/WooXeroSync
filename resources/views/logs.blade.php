<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Logs') }}
        </h2>
    </x-slot>

    <div class="flex flex-col">
    
        <div class="flex-grow bg-white p-6 gap-4 mb-4 rounded-lg shadow-md overflow-y-auto">
            @livewire('sync-logs-component')
        </div>
    
        <div class="flex-grow bg-white p-6 gap-4 mb-4 rounded-lg shadow-md overflow-y-auto">
            @livewire('sync-details-component')
        </div>
    </div>
</x-app-layout>