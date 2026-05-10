@php $colors = ['created'=>'bg-slate-200 text-slate-800','in_transit'=>'bg-blue-100 text-blue-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-rose-100 text-rose-700']; @endphp
<span class="inline-flex px-2 py-1 rounded text-xs font-medium {{ $colors[$status] ?? 'bg-slate-200 text-slate-800' }}">{{ $status }}</span>
