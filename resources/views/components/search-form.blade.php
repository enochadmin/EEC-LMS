@props(['route', 'placeholder' => 'Search...'])

<div class="mb-4">
    <form method="GET" action="{{ $route }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="{{ $placeholder }}" 
               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">
            Search
        </button>
        @if(request('search'))
            <a href="{{ $route }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Clear
            </a>
        @endif
    </form>
</div>
