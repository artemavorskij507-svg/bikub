<div>
    <div class="flex space-x-4 mb-4">
        <input type="text" wire:model.debounce.500ms="search"
               placeholder="Поиск…" class="border rounded px-2 py-1">

        <select wire:model="status" class="border rounded px-2 py-1">
            <option value="">Все статусы</option>
            <option value="active">Активные</option>
            <option value="draft">Черновики</option>
        </select>

        <select wire:model="category" class="border rounded px-2 py-1">
            <option value="">Все категории</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->title }}</option>
            @endforeach
        </select>
    </div>

    <table class="min-w-full bg-white">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2">Заголовок</th>
                <th class="px-4 py-2">Категория</th>
                <th class="px-4 py-2">Статус</th>
                <th class="px-4 py-2">Дата</th>
                <th class="px-4 py-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ads as $ad)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $ad->title }}</td>
                    <td class="px-4 py-2">{{ $ad->category->title ?? '-' }}</td>
                    <td class="px-4 py-2">{{ ucfirst($ad->status) }}</td>
                    <td class="px-4 py-2">{{ $ad->created_at->format('d.m.Y') }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('account.classifieds.show', $ad) }}"
                           class="text-blue-600 hover:underline">Просмотр</a>
                        <a href="{{ route('account.classifieds.edit', $ad) }}"
                           class="ml-2 text-indigo-600 hover:underline">Редактировать</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">Объявлений нет.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $ads->links() }}
    </div>
</div>
