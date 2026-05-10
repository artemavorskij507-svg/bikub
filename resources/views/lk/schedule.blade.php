@extends('lk.layout')

@section('title', 'График и смены')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Расписание
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">График и смены</h1>
            <p class="text-slate-500 font-medium mt-2">Управляйте доступностью и отслеживайте свои смены</p>
        </div>
    </div>

    {{-- Availability --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden relative" x-data="{
        availabilityToday: {{ $availability['today'] ? 'true' : 'false' }},
        availabilityTomorrow: {{ $availability['tomorrow'] ? 'true' : 'false' }},
        loadingToday: false,
        loadingTomorrow: false,
        savedToday: false,
        savedTomorrow: false,
        async updateAvailability(type, value) {
            if (type === 'today') { this.loadingToday = true; this.savedToday = false; }
            else { this.loadingTomorrow = true; this.savedTomorrow = false; }

            try {
                const response = await fetch('{{ route('lk.schedule.update-availability') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ [type]: value }),
                });
                const data = await response.json();
                if (data.success) {
                    if (type === 'today') {
                        this.availabilityToday = data.availability.today;
                        this.savedToday = true;
                        setTimeout(() => { this.savedToday = false; }, 2000);
                    } else {
                        this.availabilityTomorrow = data.availability.tomorrow;
                        this.savedTomorrow = true;
                        setTimeout(() => { this.savedTomorrow = false; }, 2000);
                    }
                }
            } catch (e) {
                console.error(e);
                alert('Ошибка при обновлении доступности');
            } finally {
                if (type === 'today') this.loadingToday = false;
                else this.loadingTomorrow = false;
            }
        }
    }">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-green-400 via-teal-500 to-blue-500"></div>
        <div class="p-8 md:p-10">
            <h2 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                Моя доступность
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Today --}}
                <label class="group relative cursor-pointer block">
                    <div class="flex items-center gap-5 p-6 rounded-3xl border-2 transition-all duration-300" 
                         :class="availabilityToday ? 'border-green-400 bg-green-50 shadow-lg shadow-green-500/10' : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'">
                        
                        <div class="relative flex-shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                             :class="availabilityToday ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-400'">
                            <input type="checkbox" :checked="availabilityToday" @change="updateAvailability('today', $event.target.checked)" :disabled="loadingToday" class="hidden">
                            <svg x-show="!loadingToday" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                            <svg x-show="loadingToday" class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        
                        <div class="flex-1">
                            <div class="text-lg font-black text-slate-900 mb-1">Сегодня</div>
                            <div class="text-sm font-medium transition-colors" :class="availabilityToday ? 'text-green-700' : 'text-slate-500'">
                                <span x-text="availabilityToday ? 'Я готов к работе' : 'Не доступен'"></span>
                            </div>
                        </div>

                        <div class="w-4 h-4 rounded-full transition-colors" :class="availabilityToday ? 'bg-green-500' : 'bg-slate-200'"></div>
                    </div>
                </label>

                {{-- Tomorrow --}}
                <label class="group relative cursor-pointer block">
                    <div class="flex items-center gap-5 p-6 rounded-3xl border-2 transition-all duration-300" 
                         :class="availabilityTomorrow ? 'border-blue-400 bg-blue-50 shadow-lg shadow-blue-500/10' : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'">
                        
                        <div class="relative flex-shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                             :class="availabilityTomorrow ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-400'">
                            <input type="checkbox" :checked="availabilityTomorrow" @change="updateAvailability('tomorrow', $event.target.checked)" :disabled="loadingTomorrow" class="hidden">
                            <svg x-show="!loadingTomorrow" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <svg x-show="loadingTomorrow" class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        
                        <div class="flex-1">
                            <div class="text-lg font-black text-slate-900 mb-1">Завтра</div>
                            <div class="text-sm font-medium transition-colors" :class="availabilityTomorrow ? 'text-blue-700' : 'text-slate-500'">
                                <span x-text="availabilityTomorrow ? 'Запланировано' : 'Не доступен'"></span>
                            </div>
                        </div>

                        <div class="w-4 h-4 rounded-full transition-colors" :class="availabilityTomorrow ? 'bg-blue-500' : 'bg-slate-200'"></div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- Today Shifts --}}
    <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
        <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            Смены сегодня
        </h2>

        @if($todayShifts->isEmpty())
            <div class="text-center py-16 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                <div class="w-20 h-20 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-slate-500 font-bold">На сегодня смен нет</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($todayShifts as $shift)
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 rounded-full -mr-10 -mt-10 blur-xl"></div>
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex flex-col items-center justify-center text-amber-600 border border-amber-100">
                                    <span class="text-xs font-bold uppercase">Начало</span>
                                    <span class="text-xl font-black">{{ $shift->start_at->format('H:i') }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <h3 class="text-lg font-black text-slate-900">Активная смена</h3>
                                        @php
                                            $statusConfig = ['open' => ['bg'=>'bg-green-100', 'text'=>'text-green-700', 'label'=>'Открыта'], 'hold' => ['bg'=>'bg-amber-100', 'text'=>'text-amber-700', 'label'=>'Удержана'], 'locked' => ['bg'=>'bg-blue-100', 'text'=>'text-blue-700', 'label'=>'Блок'], 'closed' => ['bg'=>'bg-red-100', 'text'=>'text-red-700', 'label'=>'Закрыта']];
                                            $st = $statusConfig[$shift->status] ?? ['bg'=>'bg-slate-100', 'text'=>'text-slate-600', 'label'=>$shift->status];
                                        @endphp
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">{{ $st['label'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm font-medium text-slate-600">
                                        <span>🏁 До {{ $shift->end_at->format('H:i') }}</span>
                                        <span>•</span>
                                        <span>📍 {{ $shift->zone->name ?? 'Без зоны' }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($shift->serviceType)
                                <div class="px-5 py-2 bg-white/60 rounded-xl text-sm font-bold text-slate-700 border border-white shadow-sm">
                                    {{ $shift->serviceType->name }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Upcoming --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 h-full">
            <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                Предстоящие
            </h2>

            @if($upcomingShifts->isEmpty())
                <div class="text-center py-12 text-slate-400 font-medium">Нет предстоящих смен</div>
            @else
                <div class="space-y-4">
                    @foreach($upcomingShifts as $shift)
                        <div class="flex items-center p-4 bg-slate-50 rounded-2xl hover:bg-white hover:shadow-md transition-all border border-slate-100 group">
                            <div class="flex-shrink-0 w-14 text-center mr-4">
                                <div class="text-xs font-bold text-slate-400 uppercase">{{ $shift->start_at->translatedFormat('M') }}</div>
                                <div class="text-2xl font-black text-slate-900">{{ $shift->start_at->format('d') }}</div>
                            </div>
                            <div class="flex-1 border-l-2 border-slate-200 pl-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $shift->start_at->format('H:i') }} - {{ $shift->end_at->format('H:i') }}</div>
                                        <div class="text-xs text-slate-500 font-medium mt-0.5">{{ $shift->zone->name ?? 'Зона не указана' }}</div>
                                    </div>
                                    <div class="px-2 py-1 bg-white rounded-lg border border-slate-200 text-[10px] font-bold text-slate-600 shadow-sm">
                                        {{ $shift->serviceType->name ?? 'Смена' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Past --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 h-full">
            <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                История
            </h2>

            @if($pastShifts->isEmpty())
                <div class="text-center py-12 text-slate-400 font-medium">История пуста</div>
            @else
                <div class="space-y-4 opacity-70 hover:opacity-100 transition-opacity">
                    @foreach($pastShifts as $shift)
                        <div class="flex items-center p-4 bg-white border border-slate-100 rounded-2xl">
                            <div class="flex-shrink-0 w-14 text-center mr-4">
                                <div class="text-xs font-bold text-slate-400">{{ $shift->start_at->format('d.m') }}</div>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-700">{{ $shift->start_at->format('H:i') }} - {{ $shift->end_at->format('H:i') }}</div>
                                <div class="text-xs text-slate-400">{{ $shift->zone->name ?? '-' }}</div>
                            </div>
                            <div class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">Завершена</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection