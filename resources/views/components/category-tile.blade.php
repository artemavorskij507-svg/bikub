{{-- resources/views/components/category-tile.blade.php --}}
@props(['category'])

@php
  $name = $category->name ?? 'Категорія';
  $slug = $category->code ?? $category->slug ?? 'category';
  $icon = $category->icon ?? '📦';
@endphp

<a href="{{ route('public.category', $slug) }}"
   class="group rounded-2xl p-5 ring-1 ring-slate-200 bg-white/80
          hover:ring-sky-200 hover:shadow-lg transition overflow-hidden relative block">
  <div class="text-2xl">{{ $icon }}</div>
  <div class="mt-2 font-semibold text-slate-900">{{ $name }}</div>
  <div class="mt-1 text-sm text-slate-600">Перейти до послуг</div>
  <div class="absolute -right-6 -bottom-6 h-24 w-24 rounded-full bg-sky-500/10 group-hover:bg-sky-500/20 transition"></div>
</a>
