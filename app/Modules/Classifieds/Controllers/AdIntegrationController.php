<?php

namespace App\Modules\Classifieds\Controllers;

use App\Events\AdSoldEvent;
use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdIntegrationController extends Controller
{
    /**
     * Bridge to Delivery Module – перенаправление в форму создания доставки
     * с предзаполненными параметрами по объявлению.
     */
    public function createDeliveryRequest(ClassifiedAd $ad): RedirectResponse
    {
        if (! $ad->hasLocation()) {
            return back()->with('error', 'Для этого объявления не указаны координаты адреса.');
        }

        $deliveryParams = [
            'pickup_lat' => $ad->lat,
            'pickup_lng' => $ad->lng,
            'pickup_address' => $ad->address,
            'description' => "Delivery for: {$ad->title}",
            'reference_id' => "AD-{$ad->id}",
        ];

        // Проверяем существование роута доставки
        if (\Route::has('account.deliveries.create')) {
            return redirect()->route('account.deliveries.create', $deliveryParams);
        }

        // Если роут не существует, возвращаем обратно с сообщением
        return back()->with('info', 'Модуль доставки будет доступен в ближайшее время. Свяжитесь с продавцом напрямую через чат.');
    }

    /**
     * Пометить объявление как проданное и инициировать лояльность / внешние интеграции.
     */
    public function markAsSold(ClassifiedAd $ad): RedirectResponse
    {
        if (! Auth::check() || $ad->user_id !== Auth::id()) {
            abort(403);
        }

        $ad->update(['status' => 'sold']);

        AdSoldEvent::dispatch($ad);

        return redirect()->route('account.classifieds.my-ads')
            ->with('success', 'Объявление помечено как проданное. Начисление бонусов будет выполнено автоматически.');
    }
}
