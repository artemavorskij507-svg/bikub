<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Eco Certificate</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
@php
    $s = $summary ?? [];
    $items = $s['items'] ?? [];
    $totals = $s['totals'] ?? [];
@endphp
<body>
    <h1>Сертификат экологичной утилизации</h1>
    <div class="muted">GLF Bikube</div>
    <div class="box">
        <div><strong>UID:</strong> {{ $certificate->certificate_uid }}</div>
        <div><strong>Заказ:</strong> #{{ $s['order_number'] ?? $certificate->order_id }}</div>
        <div><strong>Клиент:</strong> {{ $certificate->customer_name }}</div>
        <div><strong>Дата выдачи:</strong> {{ optional($certificate->issued_at)->toDateTimeString() }}</div>
    </div>
    <div class="box">
        <div><strong>Адрес:</strong> {{ $s['address'] ?? '—' }}, {{ $s['city'] ?? '' }}</div>
        <div><strong>Дата выполнения:</strong> {{ $s['performed_at'] ?? '—' }}</div>
    </div>
    <div class="box">
        <strong>Предметы</strong>
        <table>
            <thead>
            <tr>
                <th>Название</th>
                <th>Категория</th>
                <th>Путь</th>
                <th class="right">Кол-во</th>
                <th class="right">Объем (м³)</th>
                <th class="right">Вес (кг)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $it)
                <tr>
                    <td>{{ $it['name'] ?? '—' }}</td>
                    <td>{{ $it['category'] ?? '—' }}</td>
                    <td>{{ $it['disposal_path'] ?? '—' }}</td>
                    <td class="right">{{ $it['quantity'] ?? 0 }}</td>
                    <td class="right">{{ number_format((float)($it['volume_m3'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)($it['weight_kg'] ?? 0), 3, '.', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="box">
        <strong>Итоги</strong>
        <div>Общий объем: {{ number_format((float)($totals['total_volume_m3'] ?? 0), 3, '.', ' ') }} м³</div>
        <div>Общий вес: {{ number_format((float)($totals['total_weight_kg'] ?? 0), 3, '.', ' ') }} кг</div>
        <div>CO₂ сэкономлено: {{ number_format((float)($certificate->co2_saved_kg ?? 0), 3, '.', ' ') }} кг</div>
        <div>Повторно использовано: {{ (int)($certificate->items_reused_count ?? 0) }} предмет(ов)</div>
    </div>
    @if(($s['partner'] ?? null))
    <div class="box">
        <strong>Партнер по утилизации</strong>
        <div>{{ $s['partner']['name'] ?? '—' }} ({{ $s['partner']['type'] ?? '' }})</div>
    </div>
    @endif
    @if(($s['team'] ?? null))
    <div class="box">
        <strong>Эко-бригада</strong>
        <div>{{ $s['team']['name'] ?? '—' }}</div>
    </div>
    @endif
</body>
</html>


