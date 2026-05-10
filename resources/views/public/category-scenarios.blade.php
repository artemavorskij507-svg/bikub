@extends('layouts.app')

@section('title', ucfirst(str_replace('-', ' ', $slug)).' - BiKuBe')

@section('content')
@php
    $isDelivery = $slug === 'delivery';
    $isFood = $slug === 'food';
@endphp

@if($isDelivery)
    <section class="relative overflow-hidden bg-slate-950 px-3 py-6 text-slate-100 sm:px-4 lg:px-6">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_84%_14%,rgba(132,204,22,0.24),transparent_38%),radial-gradient(circle_at_8%_86%,rgba(59,130,246,0.12),transparent_35%)]"></div>
        <div class="relative mx-auto w-full" style="max-width: 1800px;">
            <x-bikube.public-delivery-landing />
        </div>
    </section>
@elseif($isFood)
    <x-bikube.os-shell container-class="space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-amber-300/25 bg-gradient-to-br from-slate-950 via-stone-950 to-red-950 p-6 text-slate-100 shadow-2xl">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_30%,rgba(59,130,246,0.26),transparent_36%),radial-gradient(circle_at_83%_35%,rgba(239,68,68,0.24),transparent_38%),linear-gradient(to_right,rgba(15,23,42,0.58),rgba(249,115,22,0.08),rgba(127,29,29,0.58))]"></div>
            <div class="relative grid gap-6 lg:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-sky-300/20 bg-sky-950/20 p-4">
                    <span class="inline-flex rounded-full border border-amber-300/35 bg-amber-300/10 px-3 py-1 text-xs font-bold tracking-wide text-amber-200">BIKUBE FOOD ARENA</span>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-5xl">Kitchen Battle of Flavors</h1>
                    <p class="max-w-2xl text-sm text-amber-100/85 md:text-base">Cinematic food-first experience: chef-level meals, premium presentation and fast path to checkout.</p>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full border border-red-300/35 bg-red-400/15 px-3 py-1 text-red-100">Hot route priority</span>
                        <span class="rounded-full border border-amber-300/35 bg-amber-400/15 px-3 py-1 text-amber-100">Restaurant grade handling</span>
                        <span class="rounded-full border border-orange-300/35 bg-orange-400/15 px-3 py-1 text-orange-100">Battle-ready timings</span>
                    </div>
                </div>
                <div class="grid content-between gap-3 rounded-2xl border border-red-300/20 bg-red-950/25 p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-sky-300/25 bg-sky-500/10 p-3">
                            <p class="text-xs uppercase tracking-wide text-sky-200">Chef Side A</p>
                            <p class="mt-1 text-sm font-semibold text-white">Classic cuisine</p>
                        </div>
                        <div class="rounded-xl border border-red-300/25 bg-red-500/10 p-3">
                            <p class="text-xs uppercase tracking-wide text-red-200">Chef Side B</p>
                            <p class="mt-1 text-sm font-semibold text-white">Spice-forward cuisine</p>
                        </div>
                    </div>
                    <a href="{{ route('checkout.show', ['scenario' => $defaultScenario['key'] ?? 'delivery.meals']) }}" class="bikube-os-btn bikube-os-btn-primary !justify-center !border-amber-400 !bg-amber-500 !text-slate-950 hover:!bg-amber-400">Order food now</a>
                </div>
            </div>
        </section>
    </x-bikube.os-shell>
@else
    <x-bikube.os-shell container-class="space-y-6">
        <x-bikube.page-header
            eyebrow="Public Services"
            :title="$cmsPage->title ?? ucfirst(str_replace('-', ' ', $slug))"
            :subtitle="$cmsPage->meta ?? 'Choose a scenario and continue to secure checkout.'"
            :chips="['Fast request', 'Transparent SLA', 'Secure checkout']"
            badge="BiKuBe OS Public v1"
        />

        <section x-data="{ active: '{{ $defaultScenario['key'] }}' }" class="space-y-5">
            <div class="flex flex-wrap gap-2">
                @foreach($scenarios as $scenario)
                    <button
                        type="button"
                        @click="active='{{ $scenario['key'] }}'"
                        :class="active==='{{ $scenario['key'] }}' ? 'bikube-os-btn bikube-os-btn-primary' : 'bikube-os-btn bikube-os-btn-soft'"
                    >
                        {{ $scenario['public_title'] ?? $scenario['title'] }}
                    </button>
                @endforeach
            </div>
            <div class="space-y-4">
                @foreach($scenarios as $scenario)
                    <article x-show="active==='{{ $scenario['key'] }}'" x-cloak>
                        <x-bikube.order-card
                            :title="$scenario['public_title'] ?? $scenario['title']"
                            :meta="'Scenario: '.$scenario['key']"
                            status="active"
                            payment="pending"
                            priority="normal"
                        >
                            <div class="bikube-os-info-grid">
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Description</p>
                                    <p class="bikube-os-info-value">{{ $scenario['short_description'] ?? '—' }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Base price</p>
                                    <p class="bikube-os-info-value">{{ $scenario['base_price'] ?? '—' }} {{ $scenario['currency'] ?? '' }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">SLA</p>
                                    <p class="bikube-os-info-value">{{ $scenario['sla_minutes'] ?? '—' }} min</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('checkout.show', ['scenario' => $scenario['key']]) }}" class="bikube-os-btn bikube-os-btn-primary">
                                    {{ $scenario['public_cta'] ?? 'Start checkout' }}
                                </a>
                            </div>
                        </x-bikube.order-card>
                    </article>
                @endforeach
            </div>
        </section>
    </x-bikube.os-shell>
@endif
@endsection
