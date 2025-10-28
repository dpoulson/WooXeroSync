<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-md dark:bg-gray-800 dark:border-gray-700 h-full">

    <h2 class="text-xl font-semibold text-gray-700 mb-4 dark:text-gray-200">User Details</h2>
    
    @if ($user)
        <div class="space-y-4">
            <div class="flex items-center space-x-4 border-b pb-4 dark:border-gray-700">
                {{-- Larger Avatar --}}
                <div class="h-16 w-16 rounded-full bg-indigo-500 flex items-center justify-center text-2xl font-bold text-white shadow-lg">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                </div>
            </div>

            <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <p><strong>ID:</strong> {{ $user->id }}</p>
                <p><strong>Joined:</strong> {{ $user->created_at->diffForHumans() }}</p>
                
                {{-- You can add more details here, such as role/permissions/etc. --}}
                <p><strong>Role:</strong> 
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $user->hasRole('Admin') ? 'Administrator' : 'Standard User' }} 
                        {{-- (Assuming you have an is_admin flag or similar) --}}
                    </span>
                </p>
            </div>
        </div>

    @else
        <div class="p-4 bg-blue-100 text-blue-700 rounded-lg text-sm dark:bg-blue-900 dark:text-blue-100">
            Select a member from the list to view their detailed information here.
        </div>
    @endif
</div>