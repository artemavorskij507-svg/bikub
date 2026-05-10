<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нове замовлення GLF BiKube</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">🚴 GLF BiKube</h1>
        <p style="color: white; margin: 10px 0 0 0;">Нове замовлення створено!</p>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #667eea; margin-top: 0;">Замовлення #{{ $order->order_number }}</h2>

        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p><strong>Клієнт:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Статус:</strong> 
                <span style="background: {{ $order->status === 'completed' ? '#10b981' : ($order->status === 'in_progress' ? '#f59e0b' : '#667eea') }}; 
                            color: white; 
                            padding: 4px 12px; 
                            border-radius: 12px; 
                            font-size: 12px;">
                    {{ ucfirst($order->status) }}
                </span>
            </p>
            @if($order->scheduled_at)
                <p><strong>Заплановано:</strong> {{ $order->scheduled_at->format('d.m.Y H:i') }}</p>
            @endif
        </div>

        @if($order->items && $order->items->count() > 0)
            <h3 style="color: #667eea;">Список послуг:</h3>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                @foreach($order->items as $item)
                    <div style="padding: 15px; border-bottom: 1px solid #eee;">
                        <strong>{{ $item->service_type->name ?? 'Послуга' }}</strong>
                        <p style="margin: 5px 0; color: #666; font-size: 14px;">
                            {{ $item->service_type->description ?? '' }}
                        </p>
                        <span style="color: #667eea; font-weight: bold;">{{ number_format($item->price, 0) }} NOK</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #667eea;">Загальна сума:</h3>
            <p style="font-size: 24px; font-weight: bold; color: #667eea; margin: 10px 0;">
                {{ number_format($order->total_amount, 0) }} NOK
            </p>
        </div>

        @if($order->notes)
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #f59e0b; border-radius: 4px;">
                <strong>Примітки:</strong>
                <p style="margin: 5px 0;">{{ $order->notes }}</p>
            </div>
        @endif

        <div style="margin-top: 30px; text-align: center;">
            <a href="{{ url('/admin/orders/' . $order->id) }}" 
               style="background: #667eea; 
                      color: white; 
                      padding: 12px 30px; 
                      text-decoration: none; 
                      border-radius: 6px; 
                      display: inline-block;">
                Переглянути замовлення
            </a>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center; color: #999; font-size: 12px;">
        <p>GLF BiKube AS • Нарвік, Норвегія</p>
        <p>© {{ date('Y') }} Всі права захищені</p>
    </div>
</body>
</html>

