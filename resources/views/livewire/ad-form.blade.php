<div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 max-w-4xl mx-auto my-8">
    <h2 class="text-3xl font-extrabold mb-8 text-gray-900 border-b pb-4">Sell Item</h2>
    
    <form wire:submit="save" class="space-y-4">
        <div class="mb-8">
            <label class="block text-sm font-bold text-gray-700 mb-2">Photos</label>
            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 bg-gray-50 text-center hover:bg-blue-50 hover:border-blue-400 transition cursor-pointer relative">
                <input type="file" wire:model="photos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*">
                @if(count($photos) > 0)
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($photos as $photo)
                            <div class="relative aspect-square rounded-lg overflow-hidden shadow-sm">
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                            </div>
                        @endforeach
                        <div class="flex items-center justify-center bg-gray-200 rounded-lg aspect-square text-gray-500">
                            + Add More
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-4">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 font-medium">Click to upload or drag photos here</p>
                        <p class="text-xs text-gray-400">Up to 10 photos</p>
                    </div>
                @endif
            </div>
            @error('photos.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Title *</label>
                <input wire:model="title" type="text" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. iPhone 13 Pro">
                @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Category *</label>
                <select wire:model="category_id" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Description *</label>
            <textarea wire:model="description" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Describe your item..."></textarea>
            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Price (NOK)</label>
                <input wire:model="price_value" type="number" step="0.01" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="0.00">
                @error('price_value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Address</label>
                <input wire:model="address" type="text" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="City, street">
                @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="pt-6 border-t flex justify-end gap-3">
            <button type="button" onclick="window.history.back()" class="px-6 py-2 rounded-lg border hover:bg-gray-50 font-semibold">Cancel</button>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg">
                Publish Ad
            </button>
        </div>
    </form>
</div>

