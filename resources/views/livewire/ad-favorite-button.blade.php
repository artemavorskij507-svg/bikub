<button wire:click="toggleFavorite" 
        class="w-full {{ $isFavorite ? 'bg-red-50 border-2 border-red-200 hover:border-red-300 text-red-700' : 'bg-gray-50 border-2 border-gray-200 hover:border-gray-300 text-gray-700' }} font-bold py-3.5 px-4 rounded-xl transition flex justify-center items-center gap-2">
    @if($isFavorite)
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
        </svg>
        В избранном
    @else
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        В избранное
    @endif
</button>

