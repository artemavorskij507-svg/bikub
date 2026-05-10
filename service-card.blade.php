{{-- resources/views/components/service-card.blade.php --}}
@props(['service'])

@php
  $title = $service->name ?? 'Послуга';
  $desc  = $service->description ?? 'Короткий опис послуги';
  // Обрезаем описание до 100 символов для карточки
  if (mb_strlen($desc) > 100) {
    $desc = mb_substr($desc, 0, 100) . '...';
  }
  $price = null;
  if (isset($service->default_pricing)) {
    $pricing = is_array($service->default_pricing) ? $service->default_pricing : json_decode($service->default_pricing, true);
    if (is_array($pricing)) {
      $price = $pricing['base_price'] ?? $pricing['price_from'] ?? null;
    }
  }
  $slug  = $service->slug ?? 'service';
  $image = $service->image_url ?? ('https://picsum.photos/seed/'.($slug).'/640/360');
  $href  = route('public.service', $slug);
@endphp

<a href="{{ $href }}"
   class="group block rounded-3xl overflow-hidden ring-1 ring-slate-200/80 dark:ring-white/10 bg-white/80 dark:bg-white/5 backdrop-blur hover:shadow-xl hover:ring-sky-200 transition-transform duration-300 hover:-translate-y-1">
  <div class="relative aspect-[16/10] overflow-hidden">
    <img src="{{ $image }}" alt="{{ $title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-60"></div>
    <span class="absolute left-3 top-3 inline-flex items-center rounded-full bg-white/90 text-slate-700 px-3 py-1 text-xs font-semibold ring-1 ring-slate-200">
      {{ $price ? ('від '.number_format($price,0,'',' ').' kr') : 'Перейти' }}
    </span>
  </div>
  <div class="p-5">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 line-clamp-2">{{ $desc }}</p>
    <div class="mt-4 flex items-center gap-2 text-sky-700 dark:text-sky-400">
      <span class="underline-offset-4 group-hover:underline">Детальніше</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.172 12 8.222 7.05l1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/></svg>
    </div>
  </div>
</a>

