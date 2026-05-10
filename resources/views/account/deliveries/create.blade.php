@extends('account.layout')

@section('title', 'РќРѕРІР°СЏ РґРѕСЃС‚Р°РІРєР°')
@section('header', 'РќРѕРІР°СЏ РґРѕСЃС‚Р°РІРєР°')

@section('content')
<div x-data="deliveryQuickOrder()" class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Р’С‹Р±РµСЂРёС‚Рµ С‚РёРї Рё Р·Р°РїРѕР»РЅРёС‚Рµ РґРµС‚Р°Р»Рё. Р—Р°РєР°Р· СЃРѕР·РґР°СЃС‚СЃСЏ Р±РµР· РїРµСЂРµР·Р°РіСЂСѓР·РєРё.</p>
        <a href="{{ route('account.deliveries.index') }}" class="btn btn-secondary">РњРѕРё РґРѕСЃС‚Р°РІРєРё</a>
    </div>

    <template x-if="error">
        <div class="alert alert-error" role="alert" aria-live="assertive">
            <div class="alert-content"><p class="alert-text" x-text="error"></p></div>
        </div>
    </template>

    <template x-if="successMessage">
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content"><p class="alert-text" x-text="successMessage"></p></div>
        </div>
    </template>

    <noscript>
        <div class="alert alert-warning" role="alert">
            <div class="alert-content">
                <p class="alert-text">Р”Р»СЏ РѕС„РѕСЂРјР»РµРЅРёСЏ РЅСѓР¶РµРЅ JavaScript. <a href="{{ route('account.new-order.delivery') }}" class="underline font-semibold">РћС‚РєСЂС‹С‚СЊ СЃС‚Р°РЅРґР°СЂС‚РЅСѓСЋ С„РѕСЂРјСѓ</a>.</p>
            </div>
        </div>
    </noscript>

    <section class="card" aria-labelledby="delivery-create-title">
        <div class="card-header">
            <h2 id="delivery-create-title" class="card-title">РџР°СЂР°РјРµС‚СЂС‹ РґРѕСЃС‚Р°РІРєРё</h2>
            <p class="card-subtitle">РџРѕР»СЏ, РѕС‚РјРµС‡РµРЅРЅС‹Рµ * , РѕР±СЏР·Р°С‚РµР»СЊРЅС‹ РґР»СЏ РѕС„РѕСЂРјР»РµРЅРёСЏ.</p>
        </div>

        <div class="card-body">
            <form @submit.prevent="submit" class="space-y-6" novalidate>
                <fieldset class="space-y-3">
                    <legend class="form-label">РўРёРї РґРѕСЃС‚Р°РІРєРё</legend>
                    <div class="grid gap-3 md:grid-cols-3" role="radiogroup" aria-label="РўРёРї РґРѕСЃС‚Р°РІРєРё">
                        <button type="button" @click="setType('grocery')"
                            :class="form.type === 'grocery' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700'"
                            class="min-h-[48px] rounded-lg border px-3 py-3 text-left text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            role="radio" :aria-checked="(form.type === 'grocery').toString()">
                            РџСЂРѕРґСѓРєС‚С‹
                            <div class="mt-1 text-xs text-slate-500">РџРѕРєСѓРїРєР° Рё РґРѕСЃС‚Р°РІРєР° РёР· РјР°РіР°Р·РёРЅР°</div>
                        </button>
                        <button type="button" @click="setType('bulky')"
                            :class="form.type === 'bulky' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700'"
                            class="min-h-[48px] rounded-lg border px-3 py-3 text-left text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            role="radio" :aria-checked="(form.type === 'bulky').toString()">
                            РљСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚
                            <div class="mt-1 text-xs text-slate-500">РњРµР±РµР»СЊ, С‚РµС…РЅРёРєР°, С‚СЏР¶С‘Р»С‹Рµ РІРµС‰Рё</div>
                        </button>
                        <button type="button" @click="setType('food')"
                            :class="form.type === 'food' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700'"
                            class="min-h-[48px] rounded-lg border px-3 py-3 text-left text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            role="radio" :aria-checked="(form.type === 'food').toString()">
                            Р“РѕС‚РѕРІР°СЏ РµРґР°
                            <div class="mt-1 text-xs text-slate-500">Р”РѕСЃС‚Р°РІРєР° РёР· СЂРµСЃС‚РѕСЂР°РЅРѕРІ</div>
                        </button>
                    </div>
                </fieldset>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-group mb-0">
                        <label for="pickup_address" class="form-label">РђРґСЂРµСЃ Р·Р°Р±РѕСЂР° / РјР°РіР°Р·РёРЅР° <span class="form-required">*</span></label>
                        <input type="text" id="pickup_address" name="pickup_address" x-model="form.pickup_address"
                               class="form-input" placeholder="РќР°РїСЂРёРјРµСЂ: Rema 1000 Narvik, Kongens gate..."
                               required :aria-invalid="hasError('pickup_address')" x-ref="pickupAddress">
                        <template x-if="hasError('pickup_address')"><p class="form-error" x-text="firstError('pickup_address')"></p></template>
                    </div>

                    <div class="form-group mb-0">
                        <label for="delivery_address" class="form-label">РђРґСЂРµСЃ РґРѕСЃС‚Р°РІРєРё <span class="form-required">*</span></label>
                        <input type="text" id="delivery_address" name="delivery_address" x-model="form.delivery_address"
                               class="form-input" placeholder="Р’Р°С€ Р°РґСЂРµСЃ РІ РќР°СЂРІРёРєРµ"
                               required :aria-invalid="hasError('delivery_address')">
                        <template x-if="hasError('delivery_address')"><p class="form-error" x-text="firstError('delivery_address')"></p></template>
                    </div>
                </div>

                <template x-if="form.type === 'grocery'">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">РџСЂРѕРґСѓРєС‚С‹</h3>
                        <div class="form-group mb-0">
                            <label for="store_id" class="form-label">РњР°РіР°Р·РёРЅ</label>
                            <select x-model="form.store_id" id="store_id" name="store_id" x-ref="groceryStore" class="form-select">
                                <option value="">Р’С‹Р±РµСЂРёС‚Рµ РјР°РіР°Р·РёРЅ...</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="grocery_notes" class="form-label">РЎРїРёСЃРѕРє РїРѕРєСѓРїРѕРє</label>
                            <textarea x-model="form.grocery_notes" id="grocery_notes" name="grocery_notes" class="form-textarea" rows="4" placeholder="РќР°РїРёС€РёС‚Рµ, С‡С‚Рѕ РЅСѓР¶РЅРѕ РєСѓРїРёС‚СЊ..."></textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input id="is_urgent_grocery" type="checkbox" x-model="form.is_urgent" class="form-checkbox">
                            РЎСЂРѕС‡РЅР°СЏ РґРѕСЃС‚Р°РІРєР°
                        </label>
                    </section>
                </template>

                <template x-if="form.type === 'bulky'">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">РљСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚</h3>
                        <div class="grid gap-3 md:grid-cols-4">
                            <div class="form-group mb-0">
                                <label for="dimension_length" class="form-label">Р”Р»РёРЅР° (СЃРј)</label>
                                <input type="number" min="0" x-model.number="form.dimensions.length" id="dimension_length" x-ref="bulkyLength" class="form-input">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">РЁРёСЂРёРЅР° (СЃРј)</label>
                                <input type="number" min="0" x-model.number="form.dimensions.width" class="form-input">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Р’С‹СЃРѕС‚Р° (СЃРј)</label>
                                <input type="number" min="0" x-model.number="form.dimensions.height" class="form-input">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Р’РµСЃ (РєРі)</label>
                                <input type="number" min="0" step="0.1" x-model.number="form.weight_kg" class="form-input">
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="form-group mb-0">
                                <label class="form-label">Р­С‚Р°Р¶</label>
                                <input type="number" min="0" x-model.number="form.floor_number" class="form-input">
                            </div>
                            <label class="inline-flex items-center gap-2 pt-7 text-sm text-slate-700">
                                <input type="checkbox" id="elevator_available" x-model="form.elevator_available" class="form-checkbox">
                                Р•СЃС‚СЊ Р»РёС„С‚
                            </label>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ СѓСЃР»СѓРіРё</label>
                            <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                @foreach(['assembly' => 'РЎР±РѕСЂРєР°', 'disassembly' => 'Р Р°Р·Р±РѕСЂРєР°', 'packaging' => 'РЈРїР°РєРѕРІРєР°', 'wrapping' => 'Р—Р°С‰РёС‚РЅР°СЏ РїР»С‘РЅРєР°'] as $serviceValue => $serviceLabel)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" value="{{ $serviceValue }}" x-model="form.services" class="form-checkbox">
                                        <span>{{ $serviceLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">РљРѕРјРјРµРЅС‚Р°СЂРёР№</label>
                            <textarea x-model="form.notes" class="form-textarea" rows="3" placeholder="РћРїРёС€РёС‚Рµ, С‡С‚Рѕ РЅСѓР¶РЅРѕ РїРµСЂРµРІРµР·С‚Рё..."></textarea>
                        </div>
                    </section>
                </template>

                <template x-if="form.type === 'food'">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">Р“РѕС‚РѕРІР°СЏ РµРґР°</h3>
                        <div class="form-group mb-0">
                            <label for="restaurant_id" class="form-label">Р РµСЃС‚РѕСЂР°РЅ</label>
                            <select x-model="form.restaurant_id" id="restaurant_id" name="restaurant_id" x-ref="foodRestaurant" class="form-select">
                                <option value="">Р’С‹Р±РµСЂРёС‚Рµ СЂРµСЃС‚РѕСЂР°РЅ...</option>
                                @foreach($restaurants as $restaurant)
                                    <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Р§С‚Рѕ Р·Р°РєР°Р·Р°С‚СЊ</label>
                            <textarea x-model="form.food_notes" class="form-textarea" rows="4" placeholder="РќР°РїРёС€РёС‚Рµ, С‡С‚Рѕ С…РѕС‚РёС‚Рµ Р·Р°РєР°Р·Р°С‚СЊ..."></textarea>
                        </div>
                    </section>
                </template>

                <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600" x-show="price">
                        РћСЂРёРµРЅС‚РёСЂРѕРІРѕС‡РЅР°СЏ СЃС‚РѕРёРјРѕСЃС‚СЊ: <strong x-text="priceDisplay()"></strong>
                    </p>
                    <button type="submit" :disabled="loading" class="btn btn-primary">
                        <svg x-show="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 00-8 8h4z"></path>
                        </svg>
                        <span x-text="loading ? 'РЎРѕР·РґР°С‘Рј Р·Р°РєР°Р·...' : 'РЎРѕР·РґР°С‚СЊ РґРѕСЃС‚Р°РІРєСѓ'"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
function deliveryQuickOrder() {
    return {
        loading: false,
        error: '',
        successMessage: '',
        price: null,
        validationErrors: {},
        form: {
            type: 'grocery',
            pickup_address: '',
            delivery_address: '',
            store_id: '',
            grocery_notes: '',
            is_urgent: false,
            dimensions: { length: null, width: null, height: null },
            weight_kg: null,
            floor_number: null,
            elevator_available: false,
            services: [],
            notes: '',
            restaurant_id: '',
            food_notes: '',
        },
        hasError(field) {
            return Array.isArray(this.validationErrors[field]) && this.validationErrors[field].length > 0;
        },
        firstError(field) {
            return this.hasError(field) ? this.validationErrors[field][0] : '';
        },
        setType(type) {
            if (this.form.type === type) {
                return;
            }

            this.form.type = type;

            this.$nextTick(() => {
                const focusTargets = {
                    grocery: this.$refs.groceryStore,
                    bulky: this.$refs.bulkyLength,
                    food: this.$refs.foodRestaurant,
                };
                focusTargets[type]?.focus();
            });
        },
        priceDisplay() {
            if (!this.price) {
                return '';
            }
            return `${(this.price / 100).toFixed(2)} NOK`;
        },
        async submit() {
            this.error = '';
            this.successMessage = '';
            this.validationErrors = {};
            this.loading = true;

            try {
                await fetch('/sanctum/csrf-cookie', {
                    headers: { 'Accept': 'application/json' },
                });

                const payload = this.buildPayload();

                const response = await fetch('/api/v1/delivery/quick-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (data?.errors && typeof data.errors === 'object') {
                        this.validationErrors = data.errors;
                    }
                    this.error = data.message || 'РћС€РёР±РєР° РїСЂРё СЃРѕР·РґР°РЅРёРё РґРѕСЃС‚Р°РІРєРё';
                    this.loading = false;
                    return;
                }

                this.price = data.data?.price ?? data.data?.pricing?.total ?? null;
                this.successMessage = 'Р—Р°РєР°Р· СѓСЃРїРµС€РЅРѕ СЃРѕР·РґР°РЅ, РѕС‚РєСЂС‹РІР°РµРј С‚СЂРµРєРёРЅРі...';

                const orderId = data.data?.order_id ?? data.order_id ?? null;
                if (orderId) {
                    window.location.href = `/account/deliveries/${orderId}`;
                    return;
                }

                this.loading = false;
            } catch (error) {
                console.error(error);
                this.error = 'РџСЂРѕРёР·РѕС€Р»Р° РѕС€РёР±РєР° РїСЂРё РѕС‚РїСЂР°РІРєРµ Р·Р°РїСЂРѕСЃР°. РџРѕРїСЂРѕР±СѓР№С‚Рµ РµС‰С‘ СЂР°Р·.';
                this.loading = false;
            }
        },
        buildPayload() {
            const base = {
                type: this.form.type,
                pickup_address: this.form.pickup_address,
                delivery_address: this.form.delivery_address,
            };

            if (this.form.type === 'grocery') {
                return {
                    ...base,
                    store_id: this.form.store_id || null,
                    items: [],
                    substitution_policy: 'ai',
                    is_urgent: this.form.is_urgent,
                    notes: this.form.grocery_notes,
                };
            }

            if (this.form.type === 'bulky') {
                return {
                    ...base,
                    dimensions: this.form.dimensions,
                    weight_kg: this.form.weight_kg,
                    services: this.form.services,
                    floor_number: this.form.floor_number,
                    elevator_available: this.form.elevator_available,
                    notes: this.form.notes,
                };
            }

            if (this.form.type === 'food') {
                return {
                    ...base,
                    restaurant_id: this.form.restaurant_id || null,
                    items: [],
                    special_instructions: this.form.food_notes,
                };
            }

            return base;
        },
    };
}
</script>
@endsection
