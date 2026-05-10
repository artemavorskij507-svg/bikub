<x-filament::page>
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Дата</label>
            <input type="date" wire:model="date" class="fi-input w-auto" />
        </div>
        @foreach ($this->rows as $zone => $tasks)
            <div class="rounded-lg border p-4">
                <div class="font-semibold mb-2">Зона: {{ $zone }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pr-4">ID</th>
                                <th class="pr-4">Тип</th>
                                <th class="pr-4">Статус</th>
                                <th class="pr-4">Окно</th>
                                <th class="pr-4">Адрес</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $t)
                                <tr class="border-t">
                                    <td class="pr-4 py-1">{{ $t['id'] }}</td>
                                    <td class="pr-4 py-1">{{ $t['type'] }}</td>
                                    <td class="pr-4 py-1">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-gray-100">{{ $t['status'] }}</span>
                                    </td>
                                    <td class="pr-4 py-1">
                                        {{ $t['window_start'] ? \Carbon\Carbon::parse($t['window_start'])->format('H:i') : '—' }}
                                        —
                                        {{ $t['window_end'] ? \Carbon\Carbon::parse($t['window_end'])->format('H:i') : '—' }}
                                    </td>
                                    <td class="pr-4 py-1">{{ $t['address_text'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-filament::page>


