<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exception / SLA Center</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f6f7fb; color: #111827; }
        .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; }
        .label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
        .value { font-size: 24px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: top; }
        th { background: #f8fafc; font-weight: 700; }
        .status { font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: .04em; }
    </style>
</head>
<body>
<h1>Exception / SLA Center (Fallback)</h1>
<p>Filament route fallback. Core metrics and latest exceptions are available below.</p>

<div class="grid">
    <div class="card"><div class="label">Jobs Total</div><div class="value">{{ $summary['jobs_total'] }}</div></div>
    <div class="card"><div class="label">Open Exceptions</div><div class="value">{{ $summary['exceptions_open'] }}</div></div>
    <div class="card"><div class="label">All Exceptions</div><div class="value">{{ $summary['exceptions_total'] }}</div></div>
    <div class="card"><div class="label">SLA Warning</div><div class="value">{{ $summary['sla_warning'] }}</div></div>
    <div class="card"><div class="label">SLA Breached</div><div class="value">{{ $summary['sla_breached'] }}</div></div>
</div>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Severity</th>
        <th>Status</th>
        <th>Job</th>
        <th>Detected</th>
    </tr>
    </thead>
    <tbody>
    @forelse($exceptions as $exception)
        <tr>
            <td>{{ $exception->id }}</td>
            <td>{{ $exception->type ?? $exception->exception_type ?? '-' }}</td>
            <td>{{ $exception->severity ?? '-' }}</td>
            <td class="status">{{ $exception->status ?? '-' }}</td>
            <td>{{ $exception->service_job_id ?? '-' }}</td>
            <td>{{ optional($exception->detected_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No exceptions found.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
