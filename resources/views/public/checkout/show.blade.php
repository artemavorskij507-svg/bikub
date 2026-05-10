@extends('layouts.app')

@section('title', ($scenario['public_title'] ?? $scenario['title'] ?? $scenario['code']).' - Checkout')

@section('content')
<x-bikube.os-shell container-class="space-y-6">
    <x-bikube.page-header
        eyebrow="Checkout"
        :title="$scenario['public_title'] ?? $scenario['title'] ?? $scenario['code']"
        :subtitle="$scenario['short_description'] ?? 'Guided request flow with transparent pricing and SLA.'"
        :chips="['Service details', 'Contact & address', 'Confirm request']"
        badge="BiKuBe OS Checkout v1"
        :open-url="route('public.category', ['slug' => $scenario['category_slug'] ?? 'delivery'])"
        open-label="Back to category"
    />

    <x-bikube.stepper :steps="['Service details', 'Contact / address', 'Confirm request']" :current="1" />

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <x-bikube.action-card title="Request form" subtitle="Required fields are marked with *">
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ implode('; ', $errors->all()) }}
                </div>
            @endif

            @auth
                <form method="POST" action="{{ route('checkout.store', ['scenario' => $scenario['code']]) }}" class="grid gap-3">
                    @csrf

                    @if(request()->filled('ad_id'))
                        <input type="hidden" name="ad_id" value="{{ request('ad_id') }}">
                    @endif

                    @php
                        $fields = array_values(array_unique(array_merge($scenario['required_fields'] ?? [], $scenario['optional_fields'] ?? [])));
                    @endphp

                    @foreach($fields as $field)
                        @php
                            $required = in_array($field, $scenario['required_fields'] ?? [], true);
                            $isTextarea = $field === 'items' || str_contains($field, 'note');
                            $placeholder = $field === 'items'
                                ? '[{"name":"Item","quantity":1,"price":10}]'
                                : null;
                        @endphp

                        @if($field === 'items')
                            <x-bikube.checkout-field
                                name="items"
                                :label="ucfirst(str_replace('_', ' ', $field))"
                                :required="$required"
                                type="textarea"
                                rows="4"
                                :placeholder="$placeholder"
                            />
                        @else
                            <x-bikube.checkout-field
                                :name="$field"
                                :label="ucfirst(str_replace('_', ' ', $field))"
                                :required="$required"
                                :type="$isTextarea ? 'textarea' : 'text'"
                                :rows="$isTextarea ? 3 : 4"
                            />
                        @endif
                    @endforeach

                    <x-bikube.checkout-field
                        name="notes"
                        label="Notes"
                        type="textarea"
                        rows="4"
                        placeholder="Optional notes for dispatch team"
                    />

                    <div class="flex flex-wrap gap-2 pt-1">
                        <button type="submit" class="bikube-os-btn bikube-os-btn-primary">Create request</button>
                        <a href="{{ route('public.category', ['slug' => $scenario['category_slug'] ?? 'delivery']) }}" class="bikube-os-btn bikube-os-btn-soft">Back</a>
                    </div>
                </form>
            @else
                <x-bikube.empty-state
                    title="Login required"
                    message="Please login to continue checkout and create your request."
                    :action-href="route('login')"
                    action-label="Login"
                />
            @endauth
        </x-bikube.action-card>

        <div class="space-y-4">
            <x-bikube.action-card title="Scenario summary">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info">
                        <p class="bikube-os-info-label">Scenario</p>
                        <p class="bikube-os-info-value">{{ $scenario['code'] ?? '—' }}</p>
                    </div>
                    <div class="bikube-os-info">
                        <p class="bikube-os-info-label">Estimated price</p>
                        <p class="bikube-os-info-value">{{ number_format($estimate['total_amount'] ?? 0, 2) }} {{ $estimate['currency'] ?? 'NOK' }}</p>
                    </div>
                    <div class="bikube-os-info">
                        <p class="bikube-os-info-label">SLA</p>
                        <p class="bikube-os-info-value">{{ $scenario['sla_minutes'] ?? 90 }} min</p>
                    </div>
                    <div class="bikube-os-info">
                        <p class="bikube-os-info-label">Category</p>
                        <p class="bikube-os-info-value">{{ $scenario['category_slug'] ?? 'delivery' }}</p>
                    </div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Trust & safety">
                <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                    <li>Request is tracked in real time.</li>
                    <li>Operations center reviews edge cases and SLA risk.</li>
                    <li>No hidden fields are exposed to clients.</li>
                </ul>
            </x-bikube.action-card>
        </div>
    </div>
</x-bikube.os-shell>
@endsection
