@props(['title', 'value', 'color'])

<div class="p-3 bg-white shadow-md rounded-lg text-center dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
<p class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ $title }}</p>
<p class="text-2xl font-bold mt-1 {{ $color }} dark:text-white">{{ $value }}</p>
</div>