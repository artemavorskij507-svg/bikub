<?php

namespace App\Modules\Classifieds\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapboxGeocodingService
{
    protected string $endpoint = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';

    public function getCoordinates(string $address): ?array
    {
        $token = config('services.mapbox.token') ?? env('MAPBOX_TOKEN');

        if (empty($token)) {
            Log::warning('Mapbox token is missing.');

            return null;
        }

        $url = $this->endpoint.rawurlencode($address).'.json';

        try {
            $resp = Http::timeout(3)
                ->retry(2, 200)
                ->get($url, [
                    'access_token' => $token,
                    'limit' => 1,
                    'autocomplete' => 'false',
                ]);

            if (! $resp->successful()) {
                return null;
            }

            $data = $resp->json();

            if (empty($data['features'][0]['center'])) {
                return null;
            }

            // Mapbox returns [lng, lat]
            [$lng, $lat] = $data['features'][0]['center'];

            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        } catch (\Throwable $e) {
            Log::error('Mapbox API error: '.$e->getMessage());

            return null;
        }
    }
}
