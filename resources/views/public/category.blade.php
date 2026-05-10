@extends('layouts.app')

@section('title', ($category->name ?? ucfirst(str_replace('-', ' ', $category->slug ?? $category->code ?? 'service'))).' - BiKuBe')

@section('content')
@php
    $categorySlug = $category->slug ?? $category->code ?? null;
    $categoryName = $category->name ?? ucfirst(str_replace('-', ' ', $categorySlug ?? 'service'));
    $isDelivery = $categorySlug === 'delivery';
    $isFood = $categorySlug === 'food';
@endphp

<x-bikube.os-shell container-class="space-y-6">
    @if($isDelivery)
        <section class="relative overflow-hidden rounded-3xl border border-lime-300/30 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 p-6 text-slate-100 shadow-2xl">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_78%_20%,rgba(132,204,22,0.22),transparent_38%),radial-gradient(circle_at_12%_80%,rgba(59,130,246,0.2),transparent_34%)]"></div>
            <div class="grid gap-6 lg:grid-cols-[1.3fr,1fr] lg:items-end">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-lime-300/40 bg-lime-300/10 px-3 py-1 text-xs font-bold tracking-wide text-lime-300">BIKUBE DELIVERY</span>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-5xl">Everything You Need, Delivered Fast</h1>
                    <p class="max-w-2xl text-sm text-slate-300 md:text-base">Premium delivery command page with rapid scenario access, predictable SLA and transparent checkout handoff.</p>
                </div>
                <div class="rounded-2xl border border-lime-300/20 bg-slate-950/40 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Fast access</p>
                    <a href="{{ route('checkout.show', ['scenario' => 'delivery.groceries']) }}" class="mt-2 inline-flex w-full justify-center rounded-xl border border-lime-400 bg-lime-500 px-4 py-2 text-sm font-bold text-slate-950 transition hover:bg-lime-400">Start groceries checkout</a>
                </div>
            </div>
        </section>
    @elseif($isFood)
        <section class="relative overflow-hidden rounded-3xl border border-amber-300/25 bg-gradient-to-br from-slate-950 via-stone-950 to-red-950 p-6 text-slate-100 shadow-2xl">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_30%,rgba(59,130,246,0.26),transparent_36%),radial-gradient(circle_at_83%_35%,rgba(239,68,68,0.24),transparent_38%),linear-gradient(to_right,rgba(15,23,42,0.58),rgba(249,115,22,0.08),rgba(127,29,29,0.58))]"></div>
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-amber-300/35 bg-amber-300/10 px-3 py-1 text-xs font-bold tracking-wide text-amber-200">BIKUBE FOOD</span>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-5xl">Kitchen Battle of Flavors</h1>
                    <p class="max-w-2xl text-sm text-amber-100/85 md:text-base">Cinematic food-first showcase with intense flavor mood and fast path to meal checkout.</p>
                </div>
                <div class="rounded-2xl border border-amber-300/20 bg-slate-950/35 p-4">
                    <p class="text-xs uppercase tracking-wide text-amber-200">Battle-ready ordering</p>
                    <a href="{{ route('public.category', ['slug' => 'food']) }}" class="mt-2 inline-flex w-full justify-center rounded-xl border border-amber-400 bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 transition hover:bg-amber-400">Open food scenarios</a>
                </div>
            </div>
        </section>
    @else
        <x-bikube.page-header
            eyebrow="Service Category"
            :title="$categoryName"
            subtitle="Choose a service and continue to guided checkout."
            :chips="['Trusted operators', 'Transparent pricing', 'Live status tracking']"
            badge="BiKuBe OS Public v1"
            :refresh-url="url()->current()"
        />
    @endif

    @if(isset($services) && $services->count() > 0)
        <section class="bikube-os-grid-3">
            @foreach($services as $service)
                @php
                    $pricing = is_string($service->default_pricing ?? null) ? json_decode($service->default_pricing, true) : ($service->default_pricing ?? []);
                    $basePrice = is_array($pricing) ? ($pricing['base_price'] ?? null) : null;
                    $serviceSlug = $service->slug ?? $service->code ?? null;
                @endphp
                <x-bikube.service-card
                    :title="$service->name"
                    :description="$service->description ?: 'Professional service request with operational tracking.'"
                    :price="$basePrice !== null ? number_format((float) $basePrice, 2).' NOK' : null"
                    cta-label="Open checkout"
                    :cta-href="$serviceSlug ? route('checkout.show', ['scenario' => $serviceSlug]) : route('public.categories')"
                    class="{{ $isDelivery ? '!bg-gradient-to-br !from-slate-900 !to-emerald-950 !border-lime-300/35' : ($isFood ? '!bg-gradient-to-br !from-slate-900 !to-red-950 !border-amber-300/35' : '') }}"
                />
            @endforeach
        </section>
    @else
        <x-bikube.empty-state
            title="No active services"
            message="We are preparing this category. Please choose another category or come back later."
            :action-href="route('public.categories')"
            action-label="Browse categories"
        />
    @endif

    <section class="bikube-os-grid-3">
        <x-bikube.kpi-card label="Category" :value="$category->code ?? 'service'" hint="Mapped by public routing" tone="{{ $isFood ? 'red' : 'blue' }}" />
        <x-bikube.kpi-card label="Services visible" :value="isset($services) ? $services->count() : 0" hint="Active only" tone="{{ $isDelivery ? 'emerald' : 'amber' }}" />
        <x-bikube.kpi-card label="Support" value="24/7" hint="Operations center available" tone="amber" />
    </section>
</x-bikube.os-shell>
@endsection
