<x-action-section>
    <x-slot name="title">
        {{ __('Delete Organisation') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Permanently delete this organisation.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once an organisation is deleted, all of its resources and data will be permanently deleted. Before deleting this organisation, please download any data or information regarding this organisation that you wish to retain.') }}
        </div>

        <div class="mt-5">
            <x-danger-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                {{ __('Delete Organisation') }}
            </x-danger-button>
        </div>

        <!-- Delete Team Confirmation Modal -->
        <x-confirmation-modal wire:model.live="confirmingTeamDeletion">
            <x-slot name="title">
                {{ __('Delete Organisation') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Are you sure you want to delete this organisation? Once a organisation is deleted, all of its resources and data will be permanently deleted.') }}
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="deleteTeam" wire:loading.attr="disabled">
                    {{ __('Delete Organisation') }}
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>
    </x-slot>
</x-action-section>
