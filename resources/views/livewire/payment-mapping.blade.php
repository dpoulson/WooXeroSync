<div class="bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">WooCommerce Account Mapping</h2>
        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
    </div>

    @if (!data_get($xeroStatus, 'connected'))
        <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm">
            Please connect to Xero first to load the list of available bank accounts.
        </div>
    @else
        <form wire:submit.prevent="savePaymentMapping">
            <div class="space-y-4">
                @foreach ($wcPaymentTypes as $gatewayId => $gatewayName)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="col-span-1">
                            <label for="map_{{ $gatewayId }}" class="block text-sm font-medium text-gray-700">
                                {{ $gatewayName }} (<span class="text-xs text-gray-500">{{ $gatewayId }}</span>)
                            </label>
                        </div>
                        
                        <div class="col-span-2">
                            <select 
                                wire:model.live="mapping.{{ $gatewayId }}" 
                                id="map_{{ $gatewayId }}" 
                                class="w-full p-2 bg-black border text-white border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">-- Select Xero Account Code --</option>
                                @foreach ($xeroBankAccounts as $account)
                                    <option value="{{ $account['Code'] }}">
                                        {{ $account['Code'] }} - {{ $account['Name'] }} ({{ $account['Currency'] }})
                                    </option>
                                @endforeach
                            </select>
                            @error("mapping.{$gatewayId}") <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endforeach
            </div>
            
            <button 
                type="submit" 
                class="mt-6 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="savePaymentMapping">Save Payment Mappings</span>
                <span wire:loading wire:target="savePaymentMapping">Saving...</span>
            </button>
        </form>
    @endif
</div>