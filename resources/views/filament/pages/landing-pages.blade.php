<x-filament::page>
    <div class="rounded-lg border bg-white p-4 mb-4">
        <div class="grid md:grid-cols-3 gap-3">
            <input wire:model.defer="formSlug" placeholder="slug (delivery)" class="border rounded px-2 py-1">
            <input wire:model.defer="formTitle" placeholder="title" class="border rounded px-2 py-1">
            <input wire:model.defer="formContent" placeholder="hero text" class="border rounded px-2 py-1">
        </div>
        <button wire:click="save" class="mt-3 px-3 py-1 rounded bg-slate-900 text-white">Save</button>
    </div>

    <div class="overflow-x-auto rounded-lg border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Slug</th><th class="px-3 py-2 text-left">Title</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Updated</th></tr></thead>
            <tbody class="divide-y">
                @foreach($pages as $p)
                    <tr><td class="px-3 py-2">{{ $p['slug'] }}</td><td class="px-3 py-2">{{ $p['title'] }}</td><td class="px-3 py-2">{{ $p['status'] }}</td><td class="px-3 py-2">{{ $p['updated_at'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>
