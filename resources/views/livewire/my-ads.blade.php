<x-account-layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Listings</h1>
        <div class="text-sm text-gray-500">Total Views: <strong>{{ number_format($stats['views'] ?? 0) }}</strong></div>
    </div>

    @if(session('message'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex border-b border-gray-200 mb-6 overflow-x-auto">
        @foreach(['all' => 'All', 'published' => 'Active', 'moderation' => 'Review', 'draft' => 'Drafts', 'sold' => 'Sold'] as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" 
                class="px-6 py-3 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $filter === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($ads->count() > 0)
            <div class="divide-y divide-gray-100">
                @foreach($ads as $ad)
                    <div class="p-6 flex flex-col md:flex-row gap-6 hover:bg-gray-50 transition">
                        <div class="w-full md:w-32 h-32 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden relative">
                            @if($ad->images && $ad->images->count() > 0)
                                <img src="{{ $ad->main_image_url }}" class="w-full h-full object-cover">
                            @elseif($ad->shop && $ad->shop->logo_path)
                                <img src="{{ asset('storage/'.$ad->shop->logo_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            @if($ad->vip_expires_at && $ad->vip_expires_at->isFuture())
                                <span class="absolute top-1 left-1 bg-yellow-400 text-yellow-900 text-xs font-bold px-1.5 py-0.5 rounded">VIP</span>
                            @endif
                            @if($ad->is_premium)
                                <span class="absolute top-1 right-1 bg-purple-500 text-white text-xs font-bold px-1.5 py-0.5 rounded">⭐</span>
                            @endif
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                                        <a href="{{ route('classifieds.show', $ad->slug) }}" class="hover:underline" target="_blank">{{ $ad->title }}</a>
                                    </h3>
                                    <p class="text-blue-600 font-bold mb-2">
                                        @if($ad->price_value)
                                            {{ number_format($ad->price_value / 100, 0, ',', ' ') }} NOK
                                        @else
                                            По договорённости
                                        @endif
                                    </p>
                                    <div class="text-sm text-gray-500 flex gap-3 flex-wrap">
                                        <span>📅 {{ $ad->created_at->format('d M, Y') }}</span>
                                        <span>👁️ {{ number_format($ad->views_count ?? 0, 0, ',', ' ') }} views</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ match($ad->status) {
                                            'published' => 'bg-green-100 text-green-800',
                                            'moderation' => 'bg-orange-100 text-orange-800',
                                            'sold' => 'bg-gray-100 text-gray-800',
                                            'draft' => 'bg-gray-100 text-gray-500',
                                            default => 'bg-gray-100 text-gray-500'
                                        } }}">
                                            {{ ucfirst($ad->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="hidden md:flex flex-col gap-2 items-end">
                                    @if($ad->status === 'published')
                                        <button wire:click="$dispatch('open-promotion-modal', { adId: {{ $ad->id }} })" 
                                                class="bg-gradient-to-r from-purple-600 to-blue-600 text-white text-sm font-bold px-4 py-2 rounded-lg shadow hover:opacity-90">
                                            ⚡ Promote
                                        </button>
                                    @endif
                                    <div class="flex gap-2 text-sm text-gray-500 mt-2">
                                        <a href="{{ route('account.classifieds.edit', $ad) }}" class="hover:text-blue-600">Edit</a>
                                        <button wire:click="delete({{ $ad->id }})" 
                                                wire:confirm="Are you sure?" 
                                                class="hover:text-red-600">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t">
                {{ $ads->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="inline-block p-4 rounded-full bg-gray-100 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No ads found</h3>
                <p class="text-gray-500 mt-1 mb-6">You haven't posted any ads in this category yet.</p>
                <a href="{{ route('account.classifieds.create') }}" class="text-blue-600 font-bold hover:underline">Start selling today &rarr;</a>
            </div>
        @endif
    </div>

    <livewire:ad-promotion-modal />
</x-account-layout>
