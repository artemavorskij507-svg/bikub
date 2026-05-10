<div class="grid grid-cols-3 gap-4">
    <div class="p-3 rounded-lg bg-blue-50 border border-blue-100">
        <div class="text-2xl font-bold text-blue-600">{{ $total ?? 0 }}</div>
        <div class="text-xs text-blue-600 font-medium mt-1">Всего услуг</div>
    </div>
    <div class="p-3 rounded-lg bg-green-50 border border-green-100">
        <div class="text-2xl font-bold text-green-600">{{ $active ?? 0 }}</div>
        <div class="text-xs text-green-600 font-medium mt-1">Активных</div>
    </div>
    <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
        <div class="text-2xl font-bold text-slate-600">{{ $inactive ?? 0 }}</div>
        <div class="text-xs text-slate-600 font-medium mt-1">Неактивных</div>
    </div>
</div>

