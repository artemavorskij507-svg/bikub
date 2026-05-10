<?php

namespace App\Jobs;

use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Services\MapboxGeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeocodeClassifiedAdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $adId) {}

    public function handle(MapboxGeocodingService $service): void
    {
        $ad = ClassifiedAd::find($this->adId);

        if (! $ad || ! $ad->address || $ad->hasLocation()) {
            return;
        }

        $coords = $service->getCoordinates($ad->address);

        if (! $coords) {
            return;
        }

        // сохраняем простые координаты в столбцы lat / lng
        $ad->lat = $coords['lat'];
        $ad->lng = $coords['lng'];
        $ad->save();
    }
}
