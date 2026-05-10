<x-layouts.public>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Сертификат экологичной утилизации</h1>
        <div class="text-slate-500 mb-6">GLF Bikube</div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-4">
                <div class="border rounded-lg p-4">
                    <div class="text-sm text-slate-600">UID</div>
                    <div class="text-lg font-semibold">{{ $certificate->certificate_uid }}</div>
                </div>
                <div class="border rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-slate-600">Заказ</div>
                            <div class="font-medium">#{{ $summary['order_number'] ?? $certificate->order_id }}</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Клиент</div>
                            <div class="font-medium">{{ $certificate->customer_name }}</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Дата выполнения</div>
                            <div class="font-medium">{{ $summary['performed_at'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Дата выдачи</div>
                            <div class="font-medium">{{ optional($certificate->issued_at)->toDateTimeString() }}</div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-slate-600">Адрес</div>
                            <div class="font-medium">{{ $summary['address'] ?? '—' }}, {{ $summary['city'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
                <div class="border rounded-lg p-4">
                    <div class="font-semibold mb-2">Предметы</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="border-b text-slate-600">
                                <th class="text-left py-2 pr-3">Название</th>
                                <th class="text-left py-2 pr-3">Категория</th>
                                <th class="text-left py-2 pr-3">Путь</th>
                                <th class="text-right py-2 pr-3">Кол-во</th>
                                <th class="text-right py-2 pr-3">Объем (м³)</th>
                                <th class="text-right py-2 pr-3">Вес (кг)</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach(($summary['items'] ?? []) as $it)
                                <tr class="border-b">
                                    <td class="py-2 pr-3">{{ $it['name'] ?? '—' }}</td>
                                    <td class="py-2 pr-3">{{ $it['category'] ?? '—' }}</td>
                                    <td class="py-2 pr-3">{{ $it['disposal_path'] ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-right">{{ $it['quantity'] ?? 0 }}</td>
                                    <td class="py-2 pr-3 text-right">{{ number_format((float)($it['volume_m3'] ?? 0), 3, '.', ' ') }}</td>
                                    <td class="py-2 pr-3 text-right">{{ number_format((float)($it['weight_kg'] ?? 0), 3, '.', ' ') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div>
                <div class="border rounded-lg p-4 space-y-2">
                    <div class="font-semibold">Итоги</div>
                    @php $totals = $summary['totals'] ?? []; @endphp
                    <div class="text-sm text-slate-600">Общий объем</div>
                    <div class="font-medium">{{ number_format((float)($totals['total_volume_m3'] ?? 0), 3, '.', ' ') }} м³</div>
                    <div class="text-sm text-slate-600">Общий вес</div>
                    <div class="font-medium">{{ number_format((float)($totals['total_weight_kg'] ?? 0), 3, '.', ' ') }} кг</div>
                    <div class="text-sm text-slate-600">CO₂ сэкономлено</div>
                    <div class="font-medium">{{ number_format((float)($certificate->co2_saved_kg ?? 0), 3, '.', ' ') }} кг</div>
                    <div class="text-sm text-slate-600">Повторно использовано</div>
                    <div class="font-medium">{{ (int)($certificate->items_reused_count ?? 0) }}</div>
                    @if($certificate->pdf_path)
                        <a class="inline-flex items-center justify-center mt-3 w-full rounded bg-primary-600 text-white px-4 py-2 text-sm hover:bg-primary-700"
                           href="{{ \Illuminate\Support\Facades\Storage::url($certificate->pdf_path) }}" target="_blank">
                            Скачать PDF
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>


