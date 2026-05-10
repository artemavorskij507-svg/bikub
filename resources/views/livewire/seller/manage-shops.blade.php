<x-account-layout>

    @if(session('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if($isCreating)
        <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
            <form wire:submit="save" class="divide-y divide-gray-200">
                <div class="px-4 py-5 sm:p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Shop Name</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Logo</label>
                            <input type="file" wire:model="logo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @if ($logo) 
                                <img src="{{ $logo->temporaryUrl() }}" class="mt-2 w-16 h-16 rounded-lg object-cover border">
                            @elseif($editingId && $shop && $shop->logo_path)
                                <img src="{{ asset('storage/'.$shop->logo_path) }}" class="mt-2 w-16 h-16 rounded-lg object-cover border">
                            @endif
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                            <input type="file" wire:model="cover" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @if ($cover) 
                                <img src="{{ $cover->temporaryUrl() }}" class="mt-2 w-full h-32 rounded-lg object-cover border">
                            @elseif($editingId && $shop && $shop->cover_path)
                                <img src="{{ asset('storage/'.$shop->cover_path) }}" class="mt-2 w-full h-32 rounded-lg object-cover border">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="px-4 py-4 sm:px-6 bg-gray-50 flex justify-end gap-2">
                    <button type="button" wire:click="cancel" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </div>
    @else
        @if($shop)
            <div class="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200">
                <div class="relative h-48 bg-gray-300">
                    @if($shop->cover_path) 
                        <img src="{{ asset('storage/'.$shop->cover_path) }}" class="w-full h-full object-cover">
                    @endif
                    <div class="absolute -bottom-8 left-8">
                        @if($shop->logo_path) 
                            <img src="{{ asset('storage/'.$shop->logo_path) }}" class="w-24 h-24 rounded-lg border-4 border-white shadow bg-white object-contain">
                        @else
                            <div class="w-24 h-24 rounded-lg border-4 border-white shadow bg-gray-200"></div>
                        @endif
                    </div>
                </div>
                <div class="px-4 py-5 sm:px-6 pt-12 flex justify-between items-start">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ $shop->name }} @if($shop->is_verified) ✅ @endif</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $shop->description }}</p>
                        <a href="{{ route('shops.show', $shop->slug) }}" class="text-blue-600 hover:underline text-sm mt-2 block">View Public Page &rarr;</a>
                    </div>
                    <button wire:click="edit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Edit Profile
                    </button>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <h3 class="mt-2 text-sm font-medium text-gray-900">No shop created</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new shop profile.</p>
                <div class="mt-6">
                    <button wire:click="create" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Create Shop
                    </button>
                </div>
            </div>
        @endif
    @endif
</x-account-layout>
