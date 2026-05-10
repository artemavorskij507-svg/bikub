@extends('layouts.app')

@section('title','Каталог послуг — GLF Bikube')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-sky-50 to-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between gap-4">
      <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Каталог послуг</h1>
      <form method="GET" class="flex items-center gap-2">
        <select name="sort" class="rounded-xl border-slate-200">
          <option value="">Сортувати</option>
          <option value="popular" @selected(request('sort')==='popular')>Популярне</option>
          <option value="new" @selected(request('sort')==='new')>Новинки</option>
        </select>
        <button class="rounded-xl bg-sky-600 text-white px-4 py-2 hover:bg-sky-700">Застосувати</button>
      </form>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[260px_1fr]">
      {{-- Sidebar filters --}}
      <aside class="space-y-4">
        <form method="GET" class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white/80">
          <label class="block text-sm font-semibold text-slate-700">Категорія</label>
          <select name="category" class="mt-1 w-full rounded-xl border-slate-200">
            <option value="">Всі категорії</option>
            @foreach(($categories ?? []) as $c)
              <option value="{{ $c->code }}" @selected(request('category')===$c->code)>{{ $c->name }}</option>
            @endforeach
          </select>

          {{-- Фильтр по зоне временно отключен, так как service_types не имеет geo_zone_id --}}
          {{-- <label class="block mt-4 text-sm font-semibold text-slate-700">Зона</label>
          <select name="zone" class="mt-1 w-full rounded-xl border-slate-200">
            <option value="">Всі зони</option>
            @foreach(($zones ?? []) as $z)
              <option value="{{ $z->id }}" @selected(request('zone')==$z->id)>{{ $z->name }}</option>
            @endforeach
          </select> --}}

          <label class="block mt-4 text-sm font-semibold text-slate-700">Пошук</label>
          <input type="text" name="q" value="{{ request('q') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Наприклад: доставка" />

          <button class="mt-4 w-full rounded-xl bg-slate-900 text-white py-2 hover:bg-slate-800">Фільтрувати</button>
        </form>
      </aside>

      {{-- Results --}}
      <section>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          @forelse($services as $service)
            <x-service-card :service="$service"/>
          @empty
            <div class="rounded-2xl p-6 ring-1 ring-slate-200 bg-white/80">
              <div class="text-slate-600">Нічого не знайдено. Спробуйте змінити фільтри.</div>
            </div>
          @endforelse
        </div>
        <div class="mt-8">
          {{ $services->withQueryString()->links() }}
        </div>
      </section>
    </div>
  </div>
</div>
@endsection
