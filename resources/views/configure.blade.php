<x-layouts.app :title="__('Configure')">

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    
        
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="card col-span-3">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Payment Mapping Configuration</h2>
                <p class="text-sm text-gray-500 mb-6">Map WooCommerce payment gateways to the corresponding Xero Bank Account Code where the funds are deposited.</p>
        
                @if (!$xeroStatus['connected'])
                    <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm">
                        Please connect to Xero first to load the list of available bank accounts.
                    </div>
                @else
                    <form action="{{ route('wc.map.payments') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            @foreach ($mockWCPayments as $gatewayId => $gatewayName)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <!-- WC Gateway Name -->
                                    <div class="col-span-1">
                                        <label for="map_{{ $gatewayId }}" class="block text-sm font-medium text-gray-700">
                                            {{ $gatewayName }} (<span class="text-xs text-gray-500">{{ $gatewayId }}</span>)
                                        </label>
                                    </div>
                                    
                                    <!-- Xero Account Dropdown -->
                                    <div class="col-span-2">
                                        <select name="mapping[{{ $gatewayId }}]" id="map_{{ $gatewayId }}" class="w-full p-2 bg-black border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">-- Select Xero Account Code --</option>
                                            @foreach ($xeroBankAccounts as $account)
                                                <option 
                                                    value="{{ $account['Code'] }}"
                                                    {{ ($wcPaymentMap[$gatewayId] ?? null) === $account['Code'] ? 'selected' : '' }}
                                                >
                                                    {{ $account['Code'] }} - {{ $account['Name'] }} ({{ $account['Currency'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <button type="submit" class="mt-6 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md">
                            Save Payment Mappings
                        </button>
                    </form>
                @endif
            </div>
            
            <!-- Hidden Form for Sync Button -->
            <form id="sync-form" action="{{ route('wc.sync') }}" method="POST" style="display: none;">
                @csrf
            </form>

        </div>
    </div>


</x-layouts.app>