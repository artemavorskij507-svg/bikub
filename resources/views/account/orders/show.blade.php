@extends('account.layout')

@section('title', 'Р—Р°РєР°Р· #' . $order->order_number . ' вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'Р—Р°РєР°Р· #' . $order->order_number)

@section('content')
<div class="space-y-6">
    <a href="{{ route('account.orders.index') }}" class="btn btn-tertiary">← Р’СЃРµ Р·Р°РєР°Р·С‹</a>

    <!-- Order Header -->
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ $orderCard['title'] }}</h2>
                <div class="mt-2 text-sm text-slate-600">
                    <div>РќРѕРјРµСЂ Р·Р°РєР°Р·Р°: <span class="font-medium">{{ $order->order_number }}</span></div>
                    <div class="mt-1">РЎРѕР·РґР°РЅ: {{ $order->created_at->format('d.m.Y H:i') }}</div>
                    @if($order->scheduled_at)
                        <div class="mt-1">Р—Р°РїР»Р°РЅРёСЂРѕРІР°РЅ: {{ $order->scheduled_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($order->completed_at)
                        <div class="mt-1">Р—Р°РІРµСЂС€С‘РЅ: {{ $order->completed_at->format('d.m.Y H:i') }}</div>
                    @endif
                </div>
            </div>
            @php
                $statusClasses = [
                    'green' => 'bg-green-100 text-green-800',
                    'blue' => 'bg-blue-100 text-blue-800',
                    'amber' => 'bg-amber-100 text-amber-800',
                    'yellow' => 'bg-yellow-100 text-yellow-800',
                    'red' => 'bg-red-100 text-red-800',
                ][$orderCard['status_color']] ?? 'bg-slate-100 text-slate-800';
            @endphp
            <span class="px-4 py-2 text-sm font-medium rounded-full {{ $statusClasses }}">
                {{ $orderCard['status_label'] }}
            </span>
        </div>
    </div>

    <!-- Address -->
    @if($order->address)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РђРґСЂРµСЃ</h3>
            <div class="text-slate-700">
                {{ $order->address->formatted_address ?? $order->address->street_address }}
            </div>
        </div>
    @endif

    {{-- Р‘Р»РѕРє: DELIVERY --}}
    @if($order->deliveryOrder)
        @php $d = $order->deliveryOrder; @endphp
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                рџљљ Р”РѕСЃС‚Р°РІРєР° ({{ strtoupper($d->type ?? 'delivery') }})
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                @if($d->pickup_address)
                    <div class="space-y-1 text-sm">
                        <p class="text-slate-500">РћС‚РєСѓРґР°:</p>
                        <p class="font-medium text-slate-900">{{ $d->pickup_address }}</p>
                    </div>
                @endif
                @if($d->delivery_address)
                    <div class="space-y-1 text-sm">
                        <p class="text-slate-500">РљСѓРґР°:</p>
                        <p class="font-medium text-slate-900">{{ $d->delivery_address }}</p>
                    </div>
                @endif
                @if($d->eta)
                    <div class="space-y-1 text-sm">
                        <p class="text-slate-500">ETA:</p>
                        <p class="font-medium text-slate-900">
                            {{ $d->eta->timezone(config('app.timezone'))->format('d.m H:i') }}
                        </p>
                    </div>
                @endif
                <div class="space-y-1 text-sm">
                    <p class="text-slate-500">РЎС‚Р°С‚СѓСЃ РґРѕСЃС‚Р°РІРєРё:</p>
                    <p class="font-medium text-slate-900">{{ ucfirst($d->tracking_status ?? 'pending') }}</p>
                </div>
            </div>
            @if(view()->exists('components.delivery-tracking'))
                <div class="mt-4">
                    <x-delivery-tracking
                        :order-id="$order->id"
                        :delivery-order-id="$d->id"
                    />
                </div>
            @endif
        </div>
    @endif

    {{-- Р‘Р»РѕРє: Handyman --}}
    @if($order->handymanDetails || $order->handymanAssignments->isNotEmpty())
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">рџ› пёЏ РњР°СЃС‚РµСЂ РЅР° С‡Р°СЃ / СЂРµРјРѕРЅС‚</h3>
            @if($order->handymanDetails)
                <div class="space-y-2 text-sm text-slate-700">
                    @if($order->handymanDetails->service_type)
                        <div>
                            <span class="font-medium">РўРёРї СЂР°Р±РѕС‚:</span>
                            <span class="ml-2">{{ $order->handymanDetails->service_type }}</span>
                        </div>
                    @endif
                    @if($order->handymanDetails->address)
                        <div>
                            <span class="font-medium">РђРґСЂРµСЃ:</span>
                            <span class="ml-2">{{ $order->handymanDetails->address }}</span>
                        </div>
                    @endif
                </div>
            @endif
            @if($order->primaryHandymanAssignment)
                <div class="mt-4 text-sm">
                    <span class="font-medium text-slate-700">РСЃРїРѕР»РЅРёС‚РµР»СЊ:</span>
                    <span class="ml-2 text-slate-900">
                        {{ $order->primaryHandymanAssignment->executorProfile->user->name ?? 'РќРµ РЅР°Р·РЅР°С‡РµРЅ' }}
                    </span>
                </div>
            @endif
        </div>
    @endif

    {{-- Р‘Р»РѕРє: Р­РєРѕ-СѓСЃР»СѓРіРё --}}
    @if($order->disposalDetails || $order->ecoCertificate)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">в™»пёЏ Р­РєРѕ-СѓСЃР»СѓРіРё Рё СѓС‚РёР»РёР·Р°С†РёСЏ</h3>
            @if($order->disposalDetails)
                <div class="space-y-2 text-sm text-slate-700">
                    @if($order->disposalDetails->items)
                        <div>
                            <span class="font-medium">РћР±СЉРµРєС‚С‹ СѓС‚РёР»РёР·Р°С†РёРё:</span>
                            <span class="ml-2">{{ is_array($order->disposalDetails->items) ? implode(', ', $order->disposalDetails->items) : $order->disposalDetails->items }}</span>
                        </div>
                    @endif
                </div>
            @endif
            @if($order->ecoCertificate)
                <div class="mt-4 text-sm">
                    <span class="font-medium text-slate-700">РЎРµСЂС‚РёС„РёРєР°С‚:</span>
                    <span class="ml-2 text-slate-900">#{{ $order->ecoCertificate->id }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Р‘Р»РѕРє: РРЅРґРёРІРёРґСѓР°Р»СЊРЅРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ --}}
    @if($order->errandDetails)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">рџ§© РРЅРґРёРІРёРґСѓР°Р»СЊРЅРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ</h3>
            <div class="space-y-2 text-sm text-slate-700">
                @if($order->errandDetails->category)
                    <div>
                        <span class="font-medium">РљР°С‚РµРіРѕСЂРёСЏ:</span>
                        <span class="ml-2">{{ $order->errandDetails->category }}</span>
                    </div>
                @endif
                @if($order->errandDetails->from_address)
                    <div>
                        <span class="font-medium">РћС‚РєСѓРґР°:</span>
                        <span class="ml-2">{{ $order->errandDetails->from_address }}</span>
                    </div>
                @endif
                @if($order->errandDetails->to_address)
                    <div>
                        <span class="font-medium">РљСѓРґР°:</span>
                        <span class="ml-2">{{ $order->errandDetails->to_address }}</span>
                    </div>
                @endif
                @if($order->errandDetails->is_urgent)
                    <div>
                        <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">РЎСЂРѕС‡РЅРѕРµ</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Social Care Context -->
    @if($order->careContext)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РЎРѕС†РёР°Р»СЊРЅС‹Р№ РєРѕРЅС‚РµРєСЃС‚</h3>
            <div class="space-y-2 text-sm text-slate-700">
                @if($order->careContext->clientProfile)
                    <div>РљР»РёРµРЅС‚: <span class="font-medium">{{ $order->careContext->clientProfile->full_name }}</span></div>
                @endif
                @if($order->careContext->trustedContact)
                    <div>Р”РѕРІРµСЂРµРЅРЅРѕРµ Р»РёС†Рѕ: <span class="font-medium">{{ $order->careContext->trustedContact->full_name }}</span></div>
                @endif
            </div>
            <div class="mt-6 flex justify-end">
                @if($order->repairProject)
                    <a href="{{ route('account.repairs.show', $order->repairProject) }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        РћС‚РєСЂС‹С‚СЊ СЃС‚СЂР°РЅРёС†Сѓ В«РњРѕР№ СЂРµРјРѕРЅС‚В» в†’
                    </a>
                @else
                    <span class="text-sm text-slate-500">
                        РЎС‚СЂР°РЅРёС†Р° СЂРµРјРѕРЅС‚Р° Р±СѓРґРµС‚ РґРѕСЃС‚СѓРїРЅР° РїРѕСЃР»Рµ СЃРѕР·РґР°РЅРёСЏ РїСЂРѕРµРєС‚Р°.
                    </span>
                @endif
            </div>
        </div>
    @endif

    <!-- Care Details -->
    @if($order->careDetails)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Р”РµС‚Р°Р»Рё РІРёР·РёС‚Р°</h3>
            <div class="space-y-3 text-sm">
                @if($order->careDetails->careService)
                    <div>
                        <span class="font-medium text-slate-700">РЈСЃР»СѓРіР°:</span>
                        <span class="text-slate-900">{{ $order->careDetails->careService->name }}</span>
                    </div>
                @endif
                @if($order->careDetails->scheduled_start_at)
                    <div>
                        <span class="font-medium text-slate-700">Р—Р°РїР»Р°РЅРёСЂРѕРІР°РЅ РЅР°:</span>
                        <span class="text-slate-900">{{ $order->careDetails->scheduled_start_at->format('d.m.Y H:i') }}</span>
                    </div>
                @endif
                @if($order->careDetails->assignedHelper)
                    <div>
                        <span class="font-medium text-slate-700">РџРѕРјРѕС‰РЅРёРє:</span>
                        <span class="text-slate-900">{{ $order->careDetails->assignedHelper->user->name ?? 'РќРµ РЅР°Р·РЅР°С‡РµРЅ' }}</span>
                    </div>
                @endif
                <div>
                    <span class="font-medium text-slate-700">РЎС‚Р°С‚СѓСЃ РІРёР·РёС‚Р°:</span>
                    <span class="text-slate-900">{{ $order->careDetails->care_status }}</span>
                </div>
            </div>
            <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between">
                <a href="{{ route('account.care.visit.show', $order) }}" 
                   class="btn btn-tertiary">
                    РџРѕРґСЂРѕР±РЅРµРµ Рѕ РІРёР·РёС‚Рµ в†’
                </a>
                @if($order->receipt_url)
                    <a href="{{ $order->receipt_url }}" class="btn btn-tertiary" target="_blank" rel="noopener noreferrer">
                        РџРѕСЃРјРѕС‚СЂРµС‚СЊ С‡РµРє
                    </a>
                @endif
            </div>
        </div>
    @endif

    @php
        $reviewableTypes = [
            \App\Enums\ServiceType::HANDYMAN_HOURLY->value,
            \App\Enums\ServiceType::HANDYMAN_FIXED->value,
            \App\Enums\ServiceType::COMPLEX_REPAIR->value,
        ];
    @endphp

    <div class="card p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">РћС†РµРЅРєР° Р·Р°РєР°Р·Р°</h3>
        @if($order->review)
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600">Р’Р°С€Р° РѕС†РµРЅРєР°:</span>
                    <span class="text-lg font-semibold text-primary-600">{{ $order->review->rating }} / 5</span>
                </div>
                @if($order->review->comment)
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $order->review->comment }}</p>
                @endif
                <p class="text-xs text-slate-500">РћС‚Р·С‹РІ РѕСЃС‚Р°РІР»РµРЅ {{ $order->review->created_at->format('d.m.Y H:i') }}</p>
            </div>
        @elseif($order->status === 'completed' && in_array($order->service_type, $reviewableTypes, true))
            <p class="text-sm text-slate-600 mb-3">
                Р Р°СЃСЃРєР°Р¶РёС‚Рµ, РєР°Рє РїСЂРѕС€С‘Р» РІРёР·РёС‚ РјР°СЃС‚РµСЂР°. Р­С‚Рѕ РІР»РёСЏРµС‚ РЅР° СЂРµР№С‚РёРЅРі РёСЃРїРѕР»РЅРёС‚РµР»СЏ Рё СѓР»СѓС‡С€Р°РµС‚ СЃРµСЂРІРёСЃ.
            </p>
            <a href="{{ route('account.orders.review.create', $order) }}"
               class="btn btn-primary">
                РћСЃС‚Р°РІРёС‚СЊ РѕС‚Р·С‹РІ
            </a>
        @else
            <p class="text-sm text-slate-500">Р”Р»СЏ СЌС‚РѕРіРѕ Р·Р°РєР°Р·Р° РѕС‚Р·С‹РІ РЅРµРґРѕСЃС‚СѓРїРµРЅ.</p>
        @endif
    </div>

    <!-- Complex Repair Project -->
    @if($order->repairProject)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РљРѕРјРїР»РµРєСЃРЅС‹Р№ СЂРµРјРѕРЅС‚</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-slate-700">РЎС‚Р°С‚СѓСЃ РїСЂРѕРµРєС‚Р°:</span>
                        <span class="ml-2 text-slate-900">
                            @php
                                $statusLabels = [
                                    'draft' => 'Р§РµСЂРЅРѕРІРёРє',
                                    'assessment' => 'РћС†РµРЅРєР°',
                                    'estimating' => 'РЎРѕСЃС‚Р°РІР»РµРЅРёРµ СЃРјРµС‚С‹',
                                    'scheduled' => 'Р—Р°РїР»Р°РЅРёСЂРѕРІР°РЅ',
                                    'in_progress' => 'Р’ СЂР°Р±РѕС‚Рµ',
                                    'on_hold' => 'РџСЂРёРѕСЃС‚Р°РЅРѕРІР»РµРЅ',
                                    'completed' => 'Р—Р°РІРµСЂС€РµРЅ',
                                    'cancelled' => 'РћС‚РјРµРЅРµРЅ',
                                ];
                            @endphp
                            {{ $statusLabels[$order->repairProject->status] ?? $order->repairProject->status }}
                        </span>
                    </div>
                    @if($order->repairProject->planned_start_at)
                        <div>
                            <span class="font-medium text-slate-700">РџР»Р°РЅРёСЂСѓРµРјРѕРµ РЅР°С‡Р°Р»Рѕ:</span>
                            <span class="ml-2 text-slate-900">{{ $order->repairProject->planned_start_at->format('d.m.Y') }}</span>
                        </div>
                    @endif
                    @if($order->repairProject->planned_finish_at)
                        <div>
                            <span class="font-medium text-slate-700">РџР»Р°РЅРёСЂСѓРµРјРѕРµ РѕРєРѕРЅС‡Р°РЅРёРµ:</span>
                            <span class="ml-2 text-slate-900">{{ $order->repairProject->planned_finish_at->format('d.m.Y') }}</span>
                        </div>
                    @endif
                    @if($order->repairProject->actual_finish_at)
                        <div>
                            <span class="font-medium text-slate-700">Р¤Р°РєС‚РёС‡РµСЃРєРѕРµ РѕРєРѕРЅС‡Р°РЅРёРµ:</span>
                            <span class="ml-2 text-slate-900">{{ $order->repairProject->actual_finish_at->format('d.m.Y') }}</span>
                        </div>
                    @endif
                </div>
                @if($order->repairProject->address_line)
                    <div class="text-sm">
                        <span class="font-medium text-slate-700">РђРґСЂРµСЃ РѕР±СЉРµРєС‚Р°:</span>
                        <span class="ml-2 text-slate-900">
                            {{ $order->repairProject->address_line }}, 
                            {{ $order->repairProject->postal_code }} {{ $order->repairProject->city }}
                        </span>
                    </div>
                @endif
                @if($order->repairProject->description)
                    <div class="text-sm">
                        <span class="font-medium text-slate-700">РћРїРёСЃР°РЅРёРµ:</span>
                        <div class="mt-1 text-slate-900 whitespace-pre-wrap">{{ $order->repairProject->description }}</div>
                    </div>
                @endif
                
                <!-- Stages -->
                @php
                    $stages = $order->repairProject->stages()->orderBy('sequence')->get();
                @endphp
                @if($stages->isNotEmpty())
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-slate-900 mb-3">Р­С‚Р°РїС‹ РїСЂРѕРµРєС‚Р°</h4>
                        <div class="space-y-3">
                            @foreach($stages as $stage)
                                <div class="border border-slate-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium text-slate-500">#{{ $stage->sequence }}</span>
                                            <span class="text-sm font-semibold text-slate-900">{{ $stage->name }}</span>
                                        </div>
                                        @php
                                            $stageStatusLabels = [
                                                'planned' => 'Р—Р°РїР»Р°РЅРёСЂРѕРІР°РЅРѕ',
                                                'in_progress' => 'Р’ СЂР°Р±РѕС‚Рµ',
                                                'completed' => 'Р—Р°РІРµСЂС€РµРЅРѕ',
                                                'cancelled' => 'РћС‚РјРµРЅРµРЅРѕ',
                                            ];
                                            $stageStatusColors = [
                                                'planned' => 'bg-slate-100 text-slate-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ $stageStatusColors[$stage->status] ?? 'bg-slate-100 text-slate-800' }}">
                                            {{ $stageStatusLabels[$stage->status] ?? $stage->status }}
                                        </span>
                                    </div>
                                    @if($stage->description)
                                        <div class="text-sm text-slate-600 mt-2">{{ $stage->description }}</div>
                                    @endif
                                    @if($stage->progress_percent > 0)
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
                                                <span>РџСЂРѕРіСЂРµСЃСЃ</span>
                                                <span>{{ $stage->progress_percent }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stage->progress_percent }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Visit Reports -->
    @if($order->careDetails && $order->careDetails->visitReports->isNotEmpty())
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РћС‚С‡С‘С‚С‹ Рѕ РІРёР·РёС‚Р°С…</h3>
            <div class="space-y-4">
                @foreach($order->careDetails->visitReports as $report)
                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-slate-900">
                                {{ $report->created_at->format('d.m.Y H:i') }}
                            </div>
                            @if($report->helperProfile)
                                <div class="text-sm text-slate-600">
                                    РџРѕРјРѕС‰РЅРёРє: {{ $report->helperProfile->user->name ?? 'РќРµРёР·РІРµСЃС‚РЅРѕ' }}
                                </div>
                            @endif
                        </div>
                        @if($report->summary)
                            <div class="text-sm text-slate-700 mt-2">{{ $report->summary }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Related Orders -->
    @if($order->subOrders->isNotEmpty() || $order->parentOrder)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РЎРІСЏР·Р°РЅРЅС‹Рµ Р·Р°РєР°Р·С‹</h3>
            <div class="space-y-2">
                @if($order->parentOrder)
                    <div>
                        <span class="text-sm text-slate-600">Р РѕРґРёС‚РµР»СЊСЃРєРёР№ Р·Р°РєР°Р·:</span>
                        <a href="{{ route('account.orders.show', $order->parentOrder) }}" 
                           class="ml-2 text-sm text-primary-600 hover:text-primary-700">
                            #{{ $order->parentOrder->order_number }}
                        </a>
                    </div>
                @endif
                @if($order->subOrders->isNotEmpty())
                    <div>
                        <span class="text-sm text-slate-600">Р”РѕС‡РµСЂРЅРёРµ Р·Р°РєР°Р·С‹:</span>
                        <div class="mt-2 space-y-1">
                            @foreach($order->subOrders as $subOrder)
                                <div>
                                    <a href="{{ route('account.orders.show', $subOrder) }}" 
                                       class="btn btn-tertiary">
                                        #{{ $subOrder->order_number }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Claims -->
    @php
        $existingClaims = $order->claims()->where('user_id', auth()->id())->get();
    @endphp
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900">РџСЂРµС‚РµРЅР·РёРё</h3>
            @if($order->status !== 'cancelled')
                <a href="{{ route('account.orders.claim.create', $order) }}" class="btn btn-secondary btn-sm">
                    РћСЃС‚Р°РІРёС‚СЊ РїСЂРµС‚РµРЅР·РёСЋ
                </a>
            @endif
        </div>
        @if($existingClaims->isEmpty())
            <p class="text-sm text-slate-600">РџСЂРµС‚РµРЅР·РёР№ РїРѕ СЌС‚РѕРјСѓ Р·Р°РєР°Р·Сѓ РЅРµС‚.</p>
        @else
            <div class="space-y-3">
                @foreach($existingClaims as $claim)
                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-slate-900">{{ $claim->title }}</h4>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $claim->opened_at->format('d.m.Y H:i') }}
                                    @if($claim->severity)
                                        вЂў {{ match($claim->severity) { 'low' => 'РќРёР·РєР°СЏ', 'medium' => 'РЎСЂРµРґРЅСЏСЏ', 'high' => 'Р’С‹СЃРѕРєР°СЏ', default => $claim->severity } }} РєСЂРёС‚РёС‡РЅРѕСЃС‚СЊ
                                    @endif
                                </p>
                            </div>
                            @php
                                $statusLabels = [
                                    'open' => 'РћС‚РєСЂС‹С‚Р°',
                                    'in_review' => 'РќР° СЂР°СЃСЃРјРѕС‚СЂРµРЅРёРё',
                                    'resolved' => 'Р РµС€РµРЅР°',
                                    'rejected' => 'РћС‚РєР»РѕРЅРµРЅР°',
                                    'closed' => 'Р—Р°РєСЂС‹С‚Р°',
                                ];
                                $statusColors = [
                                    'open' => 'bg-yellow-100 text-yellow-800',
                                    'in_review' => 'bg-blue-100 text-blue-800',
                                    'resolved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'closed' => 'bg-gray-100 text-gray-800',
                                ];
                            @endphp
                            <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusColors[$claim->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$claim->status] ?? $claim->status }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-700 mt-2">{{ $claim->description }}</p>
                        @if($claim->resolution_notes)
                            <div class="mt-3 pt-3 border-t border-slate-200">
                                <p class="text-xs font-medium text-slate-600 mb-1">Р РµС€РµРЅРёРµ:</p>
                                <p class="text-sm text-slate-700">{{ $claim->resolution_notes }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Notes -->
    @if($order->notes || $order->receipt_url)
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">РџСЂРёРјРµС‡Р°РЅРёСЏ Рё С‡РµРєРё</h3>
            @if($order->notes)
                <div class="text-slate-700 whitespace-pre-wrap mb-4">{{ $order->notes }}</div>
            @endif
            @if($order->receipt_url)
                <a href="{{ $order->receipt_url }}" class="text-primary-600 hover:text-primary-700" target="_blank" rel="noopener noreferrer">
                    РџРѕСЃРјРѕС‚СЂРµС‚СЊ С‡РµРє
                </a>
            @endif
        </div>
    @endif
</div>
@endsection

