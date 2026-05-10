@extends('layouts.app')

@section('title', 'Мои объявления')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">Мои объявления</h1>
    @livewire('user-ads-table')
    <div class="mt-8">
        <a href="{{ route('account.classifieds.create') }}"
           class="bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-600">
            Добавить объявление
        </a>
    </div>
</div>
@endsection
