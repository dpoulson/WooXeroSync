<div class="bg-white p-4 rounded-xl border border-gray-200 shadow-md dark:bg-gray-800 dark:border-gray-700 h-full">

    <h2 class="text-xl font-semibold text-gray-700 mb-4 dark:text-gray-200">
        @if ($team)
            Members of <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $team->name }}</span>
        @else
            Team Members
        @endif
    </h2>
    
    {{-- Case 1: No team has been selected yet (Initial state) --}}
    @if (is_null($teamId))
        <div class="p-4 bg-blue-100 text-blue-700 rounded-lg text-sm dark:bg-blue-900 dark:text-blue-100">
            Click on an organisation in the list to view its members here.
        </div>
        
    {{-- Case 2: A team ID was selected, and we confirm the model was successfully loaded --}}
    @elseif ($team)
        
        {{-- HARD FIX: Check if allMembers is null OR if it is a collection that is empty. --}}
        @if ($members->isEmpty())
            <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm dark:bg-yellow-800 dark:text-yellow-100">
                This organisation currently has no members.
            </div>
        @else
            {{-- Member List --}}
            <div class="space-y-3">
                {{-- Sort members so the owner is usually first (or just ensure stable order) --}}
                @foreach ($members->sortByDesc(fn($member) => $member->id === $team->user_id) as $member)
                    <div
                        wire:click="selectUser({{ $member->id }})" 
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700 transition duration-150 ease-in-out hover:shadow-lg">
                        <div class="flex items-center space-x-3">
                            {{-- Simple Avatar/Initial --}}
                            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-semibold text-white">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                            </div>
                        </div>
    
                        {{-- Role / Owner Badge --}}
                        @if ($member->id === $team->user_id)
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-200 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100">
                                Owner
                            </span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                Member
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
    
    
    </div>