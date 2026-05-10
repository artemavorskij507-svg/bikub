@php
    /** @var \Illuminate\Support\Collection|\App\Models\DisposalItem[] $items */
    $grouped = $items->groupBy('category');
    $categoryLabels = [
        'furniture' => 'Мебель',
        'large_appliance' => 'Крупная техника',
        'small_appliance' => 'Мелкая техника',
        'electronics' => 'Электроника',
        'construction' => 'Стройматериалы',
        'textile' => 'Текстиль',
        'hazardous' => 'Опасные отходы',
        'other' => 'Другое',
    ];
@endphp

<x-layouts.public>
    <div class="container mx-auto px-4 py-8" x-data="ecoDisposal()">
        <h1 class="text-2xl md:text-3xl font-semibold mb-6">Эко-услуги и утилизация</h1>
        <p class="text-slate-600 mb-6">Выберите предметы для вывоза, укажите условия и получите предварительный расчет стоимости.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <div class="space-y-6">
                    @foreach($grouped as $cat => $catItems)
                        <div class="border rounded-lg p-4">
                            <h2 class="font-semibold mb-3">{{ $categoryLabels[$cat] ?? $cat }}</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($catItems as $item)
                                    <div class="border rounded-md p-3 flex items-start gap-3">
                                        <input type="checkbox"
                                               :value="{{ $item->id }}"
                                               @change="toggleItem({ id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', base_price_nok: {{ (float)($item->base_price_nok ?? 0) }} })"
                                               class="mt-1 border-gray-300 rounded">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $item->name }}</div>
                                            <div class="text-xs text-slate-500 mb-2">
                                                Путь: {{ $item->disposal_path ?? '—' }} · База: {{ $item->base_price_nok !== null ? number_format((float) $item->base_price_nok, 2, '.', ' ') . ' NOK' : '—' }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="text-sm text-slate-600">Количество</label>
                                                <input type="number" min="1" step="1" value="1"
                                                       @input="setQuantity({ id: {{ $item->id }}, qty: $event.target.valueAsNumber || 1 })"
                                                       class="w-20 border rounded px-2 py-1 text-sm">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border rounded-lg p-4">
                    <h2 class="font-semibold mb-3">Условия и адрес</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Адрес</label>
                            <input type="text" x-model="form.address_line" class="w-full border rounded px-3 py-2" placeholder="Улица, дом">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Город</label>
                            <input type="text" x-model="form.city" class="w-full border rounded px-3 py-2" placeholder="Oslo">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Почтовый индекс</label>
                            <input type="text" x-model="form.postal_code" class="w-full border rounded px-3 py-2" placeholder="0010">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Код зоны (опц.)</label>
                            <input type="text" x-model="form.zone_code" class="w-full border rounded px-3 py-2" placeholder="OSL-C1">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Этаж</label>
                            <input type="number" min="0" step="1" x-model.number="form.floor" class="w-full border rounded px-3 py-2" placeholder="0">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" x-model="form.has_elevator" class="border rounded">
                            <span class="text-sm text-slate-700">Есть лифт</span>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Расстояние от парковки (м)</label>
                            <input type="number" min="0" step="1" x-model.number="form.parking_distance_m" class="w-full border rounded px-3 py-2" placeholder="0">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" x-model="form.express_requested" class="border rounded">
                            <span class="text-sm text-slate-700">Срочный вывоз</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="border rounded-lg p-4 sticky top-6">
                    <h3 class="font-semibold mb-3">Ваш заказ</h3>
                    <template x-if="selected.length === 0">
                        <div class="text-sm text-slate-500">Выберите предметы слева.</div>
                    </template>
                    <template x-for="sel in selected" :key="sel.id">
                        <div class="flex items-center justify-between text-sm py-1">
                            <div class="truncate" x-text="sel.name"></div>
                            <div class="text-slate-600" x-text="`x${sel.quantity}`"></div>
                        </div>
                    </template>
                    <div class="mt-4 flex gap-2">
                        <button @click="estimate" class="flex-1 bg-slate-800 text-white rounded px-3 py-2 text-sm"
                                :disabled="loading || selected.length === 0">
                            <span x-show="!loading">Рассчитать стоимость</span>
                            <span x-show="loading">Считаем...</span>
                        </button>
                    </div>
                    <template x-if="estimateResult">
                        <div class="mt-4 space-y-1 text-sm">
                            <div class="text-slate-600">Объем: <span class="font-medium" x-text="estimateResult.estimated_volume_m3"></span> м³</div>
                            <div class="text-slate-600">Вес: <span class="font-medium" x-text="estimateResult.estimated_weight_kg"></span> кг</div>
                            <div class="text-slate-600">База: <span class="font-medium" x-text="formatMoney(estimateResult.base_price_nok)"></span></div>
                            <div class="text-slate-600">Надбавки: <span class="font-medium" x-text="formatMoney((estimateResult.express_surcharge_nok||0)+(estimateResult.distance_surcharge_nok||0))"></span></div>
                            <div class="text-lg font-semibold mt-2">Итого: <span x-text="formatMoney(estimateResult.total_price_nok)"></span></div>
                        </div>
                    </template>
                    <form class="mt-4 space-y-2" method="POST" action="{{ route('eco-disposal.order') }}">
                        @csrf
                        <template x-for="sel in selected" :key="`form-${sel.id}`">
                            <div class="hidden">
                                <input type="hidden" name="items[][disposal_item_id]" :value="sel.id">
                                <input type="hidden" name="items[][quantity]" :value="sel.quantity">
                            </div>
                        </template>
                        <input type="hidden" name="address_line" x-model="form.address_line">
                        <input type="hidden" name="city" x-model="form.city">
                        <input type="hidden" name="postal_code" x-model="form.postal_code">
                        <input type="hidden" name="zone_code" x-model="form.zone_code">
                        <input type="hidden" name="floor" x-model="form.floor">
                        <input type="hidden" name="has_elevator" x-model="form.has_elevator">
                        <input type="hidden" name="parking_distance_m" x-model="form.parking_distance_m">
                        <input type="hidden" name="express_requested" x-model="form.express_requested">
                        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded px-3 py-2 text-sm"
                                :disabled="selected.length === 0">
                            Оформить заказ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function ecoDisposal() {
            return {
                selected: [],
                form: {
                    address_line: '',
                    city: '',
                    postal_code: '',
                    zone_code: '',
                    floor: 0,
                    has_elevator: false,
                    parking_distance_m: 0,
                    express_requested: false,
                },
                loading: false,
                estimateResult: null,
                toggleItem(item) {
                    const i = this.selected.findIndex(x => x.id === item.id);
                    if (i >= 0) {
                        this.selected.splice(i, 1);
                    } else {
                        this.selected.push({ id: item.id, name: item.name, quantity: 1, base_price_nok: item.base_price_nok });
                    }
                },
                setQuantity({ id, qty }) {
                    const found = this.selected.find(x => x.id === id);
                    if (found) {
                        found.quantity = Math.max(1, parseInt(qty || 1, 10));
                    }
                },
                async estimate() {
                    this.loading = true;
                    this.estimateResult = null;
                    try {
                        const payload = {
                            items: this.selected.map(s => ({ disposal_item_id: s.id, quantity: s.quantity })),
                            floor: this.form.floor,
                            has_elevator: this.form.has_elevator,
                            parking_distance_m: this.form.parking_distance_m,
                            express_requested: this.form.express_requested,
                            zone_code: this.form.zone_code || null,
                        };
                        const res = await fetch('{{ route('eco-disposal.estimate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload),
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.estimateResult = json.data;
                        } else {
                            alert(json.message || 'Не удалось выполнить расчет');
                        }
                    } catch (e) {
                        alert('Ошибка запроса расчета');
                    } finally {
                        this.loading = false;
                    }
                },
                formatMoney(v) {
                    if (v == null) return '—';
                    const n = Number(v);
                    return n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' NOK';
                }
            }
        }
    </script>
</x-layouts.public>


