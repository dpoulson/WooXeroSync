<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configure') }}
        </h2>
    </x-slot>

    <div class="flex flex-col">
    
        <div class="flex-grow bg-white p-4 gap-2 mb-2 rounded-lg shadow-md overflow-y-auto">
            <livewire:team-list />
        </div>

        <div class="h-1/3 grid grid-cols-2 gap-2 mb-2"> 
            <div class="bg-white p-4 rounded-lg shadow-md">
                <livewire:team-members-component />
            </div>
    
            <div class="bg-white p-4 rounded-lg shadow-md">
                <livewire:user-detail-component />
            </div>
        </div>
    
    </div>
</x-app-layout>