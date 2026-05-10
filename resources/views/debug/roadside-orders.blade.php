<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadside ↔ Orders Debug</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: #10b981; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .badge-info { background: #3b82f6; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Roadside ↔ Orders Debug</h1>
    <p><small>Только для окружения local/testing</small></p>

    <div class="container">
        <h2>📊 Статистика</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Экстренные вызовы</div>
                <div class="stat-value">{{ $stats['roadside_emergencies_total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">С заказом</div>
                <div class="stat-value">{{ $stats['roadside_emergencies_with_order'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Без заказа</div>
                <div class="stat-value">{{ $stats['roadside_emergencies_without_order'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Заявки на осмотр</div>
                <div class="stat-value">{{ $stats['inspection_requests_total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">С заказом</div>
                <div class="stat-value">{{ $stats['inspection_requests_with_order'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Без заказа</div>
                <div class="stat-value">{{ $stats['inspection_requests_without_order'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Roadside заказы</div>
                <div class="stat-value">{{ $stats['roadside_orders_total'] }}</div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>🚨 Последние 10 экстренных вызовов</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Order ID</th>
                    <th>Order Number</th>
                    <th>Создан</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roadside_emergencies as $emergency)
                    <tr>
                        <td>{{ $emergency->id }}</td>
                        <td>{{ $emergency->incident_type }}</td>
                        <td><span class="badge badge-info">{{ $emergency->status }}</span></td>
                        <td>
                            @if($emergency->order_id)
                                <span class="badge badge-success">{{ $emergency->order_id }}</span>
                            @else
                                <span class="badge badge-warning">—</span>
                            @endif
                        </td>
                        <td>
                            @if($emergency->order)
                                {{ $emergency->order->order_number }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $emergency->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Нет данных</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="container">
        <h2>🔍 Последние 10 заявок на осмотр</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пресет</th>
                    <th>Статус</th>
                    <th>Order ID</th>
                    <th>Order Number</th>
                    <th>Создан</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inspection_requests as $request)
                    <tr>
                        <td>{{ $request->id }}</td>
                        <td>{{ $request->preset->title ?? 'N/A' }}</td>
                        <td><span class="badge badge-info">{{ $request->status }}</span></td>
                        <td>
                            @if($request->order_id)
                                <span class="badge badge-success">{{ $request->order_id }}</span>
                            @else
                                <span class="badge badge-warning">—</span>
                            @endif
                        </td>
                        <td>
                            @if($request->order)
                                {{ $request->order->order_number }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Нет данных</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="container">
        <h2>📦 Последние 10 Roadside заказов</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Number</th>
                    <th>Статус</th>
                    <th>Тип услуги</th>
                    <th>Roadside Emergency</th>
                    <th>Inspection Request</th>
                    <th>Создан</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roadside_orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td><span class="badge badge-info">{{ $order->status }}</span></td>
                        <td>
                            @php
                                $serviceType = $order->orderItems->first()?->serviceType;
                            @endphp
                            {{ $serviceType->code ?? 'N/A' }}
                        </td>
                        <td>
                            @if($order->roadsideEmergency)
                                <span class="badge badge-success">{{ $order->roadsideEmergency->id }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($order->vehicleInspection)
                                <span class="badge badge-success">{{ $order->vehicleInspection->id }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Нет данных</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="container">
        <h2>⚙️ Типы услуг Roadside</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                @forelse($service_types as $serviceType)
                    <tr>
                        <td>{{ $serviceType->id }}</td>
                        <td><code>{{ $serviceType->code }}</code></td>
                        <td>{{ $serviceType->name }}</td>
                        <td>{{ $serviceType->category }}</td>
                        <td>
                            @if($serviceType->is_active)
                                <span class="badge badge-success">Да</span>
                            @else
                                <span class="badge badge-danger">Нет</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Типы услуг не найдены. Запустите: php artisan db:seed --class=RoadsideServiceTypesSeeder</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

