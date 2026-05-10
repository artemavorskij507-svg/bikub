{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', 'GLF Bikube Services — знайдіть та замовте послугу в Нарвіку')

@section('content')
<!-- Fallback Tailwind (якщо Vite не прогружений) -->
<script>
  (function(){
    var id='tw-cdn-fallback';
    if(!document.getElementById(id)){
      var s=document.createElement('script');
      s.id=id; s.src="https://cdn.tailwindcss.com";
      document.head.appendChild(s);
    }
  })();
</script>

<div class="min-h-screen bg-gradient-to-b from-sky-50 to-white dark:from-slate-900 dark:to-slate-950">
  {{-- Hero --}}
  <section class="relative overflow-hidden">
    <div class="absolute -top-32 -right-16 h-80 w-80 rounded-full blur-3xl opacity-40 bg-blue-300 dark:bg-blue-600"></div>
    <div class="absolute -bottom-24 -left-10 h-72 w-72 rounded-full blur-3xl opacity-30 bg-cyan-200 dark:bg-cyan-700"></div>

    <div class="mx-auto max-w-7xl px-4 pt-16 pb-8 sm:px-6 lg:px-8">
      <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full bg-white/70 dark:bg-white/10 px-3 py-1 ring-1 ring-slate-200 dark:ring-white/10">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-sm text-slate-600 dark:text-slate-300">Бета 1.0 — Нарвік</span>
          </div>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
            GLF Bikube — міські послуги, <span class="text-sky-600 dark:text-sky-400">які приїжджають</span> до вас
          </h1>
          <p class="mt-4 text-lg text-slate-600 dark:text-slate-300">
            Доставка покупок, переїзд «під ключ», майстер, еко-утилізація, кур'єр — усе в одній платформі.
            Розумне ціноутворення, ETA, реальний час.
          </p>

          {{-- Пошукова панель --}}
          <form action="{{ route('public.catalog.index') }}" method="GET" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3 bg-white/70 dark:bg-white/5 p-3 rounded-2xl ring-1 ring-slate-200 dark:ring-white/10 backdrop-blur">
            <select name="category" class="col-span-1 sm:col-span-1 w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 px-3 py-2 text-sm">
              <option value="">Категорія: все</option>
              @foreach(($categories ?? []) as $c)
                <option value="{{ $c->code }}">{{ $c->name }}</option>
              @endforeach
            </select>

            <input name="q" type="text" placeholder="Що потрібно доставити або зробити?" class="col-span-1 sm:col-span-1 w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 px-3 py-2 text-sm" />

            <button class="col-span-1 sm:col-span-1 inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-700 transition text-sm">
              Шукати
            </button>
          </form>

          <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('public.catalog.index', ['category' => 'care']) }}" class="text-sm rounded-full px-3 py-2 ring-1 ring-slate-300 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10">Доставка</a>
            <a href="{{ route('public.catalog.index', ['category' => 'tow']) }}" class="text-sm rounded-full px-3 py-2 ring-1 ring-slate-300 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10">Переїзд</a>
            <a href="{{ route('public.catalog.index', ['category' => 'master']) }}" class="text-sm rounded-full px-3 py-2 ring-1 ring-slate-300 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10">Майстер</a>
            <a href="{{ route('public.catalog.index', ['category' => 'eco']) }}" class="text-sm rounded-full px-3 py-2 ring-1 ring-slate-300 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10">Еко-утилізація</a>
          </div>

          <div class="mt-6 flex gap-3">
            <a href="{{ route('public.catalog.index') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-3 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">Відкрити каталог</a>
            <a href="/admin" class="inline-flex items-center rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200 text-slate-700 hover:bg-slate-50 dark:bg-white/10 dark:text-white dark:ring-white/10 dark:hover:bg-white/5">Адмін-панель</a>
          </div>
        </div>

        {{-- Ілюстративний блок --}}
        <div class="relative h-72 sm:h-96 lg:h-[28rem]">
          <div class="absolute inset-0 rounded-3xl bg-gradient-to-tr from-sky-400/70 to-indigo-500/70 blur-2xl opacity-60"></div>
          <div class="relative h-full w-full rounded-3xl bg-white/80 dark:bg-white/10 ring-1 ring-slate-200 dark:ring-white/10 backdrop-blur flex items-center justify-center">
            <div class="text-center p-6">
              <div class="mx-auto mb-4 h-16 w-16 rounded-2xl bg-sky-500/90 dark:bg-sky-500 flex items-center justify-center">
                <i class="fa-solid fa-truck text-white text-3xl"></i>
              </div>
              <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Єдиний центр послуг</h3>
              <p class="mt-2 text-slate-600 dark:text-slate-300">Доставка, переїзд, майстер, еко — бронювання в пару кліків.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Featured services --}}
  {{-- CATEGORIES GRID --}}
  <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-12">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Популярні категорії</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @forelse(($categories ?? []) as $cat)
        <x-category-tile :category="$cat"/>
      @empty
        @foreach([['Доставка','delivery','📦'],['Переїзд','moving','🚚'],['Майстер','handyman','🛠️'],['Еко','eco','♻️']] as $stub)
          <x-category-tile :category="(object)['name'=>$stub[0],'code'=>$stub[1],'icon'=>$stub[2]]"/>
        @endforeach
      @endforelse
    </div>
  </section>

  {{-- FEATURED --}}
  <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div class="mt-10 flex items-end justify-between">
      <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Популярні зараз</h2>
      <a href="{{ route('public.catalog.index') }}" class="text-sky-700 hover:underline dark:text-sky-400">Дивитися всі</a>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @forelse(($featured ?? []) as $service)
        <x-service-card :service="$service" />
      @empty
        {{-- Плейсхолдери, якщо немає даних --}}
        @for ($i=0; $i<3; $i++)
          <div class="rounded-2xl p-6 bg-white/70 dark:bg-white/5 ring-1 ring-slate-200 dark:ring-white/10">
            <div class="h-10 w-10 rounded-xl bg-sky-500/80"></div>
            <div class="mt-4 h-4 w-1/2 bg-slate-200 dark:bg-white/10 rounded"></div>
            <div class="mt-2 h-4 w-2/3 bg-slate-200 dark:bg-white/10 rounded"></div>
            <div class="mt-4">
              <a href="{{ route('public.catalog.index') }}" class="inline-flex items-center text-sky-700 hover:underline dark:text-sky-400">Перейти до послуг →</a>
            </div>
          </div>
        @endfor
      @endforelse
    </div>
  </section>

  {{-- Footer --}}
  <footer class="border-t border-slate-200/70 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-sm text-slate-500 dark:text-slate-400">© {{ date('Y') }} GLF Bikube</p>
      <div class="flex gap-4 text-sm text-slate-600 dark:text-slate-300">
        <a href="{{ route('public.catalog.index') }}" class="hover:underline">Каталог</a>
        <a href="/admin" class="hover:underline">Адмін</a>
        <a href="#" class="hover:underline">Політика конфіденційності</a>
      </div>
    </div>
  </footer>
</div>
@endsection

