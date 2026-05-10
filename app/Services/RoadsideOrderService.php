<?php

namespace App\Services;

use App\Models\GeoZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use App\Models\VehicleInspectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoadsideOrderService
{
    /**
     * Create Order from RoadsideEmergency.
     */
    public function createOrderFromEmergency(RoadsideEmergency $emergency): Order
    {
        return DB::transaction(function () use ($emergency) {
            // Determine service type based on incident type
            $serviceTypeCode = match ($emergency->incident_type) {
                'tow_needed' => 'vehicle_transport',
                default => 'roadside_assistance',
            };

            $serviceType = ServiceType::where('code', $serviceTypeCode)->first();

            if (! $serviceType) {
                throw new \Exception("Service type {$serviceTypeCode} not found. Please run RoadsideServiceTypesSeeder.");
            }

            // Determine geo zone
            $geoZone = null;
            if ($emergency->lat && $emergency->lng) {
                $geoZone = $this->findGeoZoneByCoordinates($emergency->lat, $emergency->lng);
            }

            // Create Order
            $order = Order::create([
                'user_id' => $emergency->customer_id,
                'status' => 'pending',
                'geo_zone_id' => $geoZone?->id,
                'location' => $emergency->lat && $emergency->lng ? [
                    'lat' => $emergency->lat,
                    'lng' => $emergency->lng,
                ] : null,
                'metadata' => [
                    'created_from' => 'roadside_emergency',
                    'roadside_emergency_id' => $emergency->id,
                    'incident_type' => $emergency->incident_type,
                ],
            ]);

            // Create OrderItem
            OrderItem::create([
                'order_id' => $order->id,
                'service_type_id' => $serviceType->id,
                'name' => $serviceType->name,
                'description' => $emergency->incident_description,
                'quantity' => 1,
                'unit_price' => 0, // Price will be calculated later
                'total_price' => 0,
            ]);

            // Link RoadsideEmergency to Order
            $emergency->order_id = $order->id;

            // Generate tracking token if not exists
            if (empty($emergency->tracking_token)) {
                $emergency->generateTrackingToken();
            } else {
                $emergency->save();
            }

            Log::info('Order created from RoadsideEmergency', [
                'order_id' => $order->id,
                'emergency_id' => $emergency->id,
                'tracking_token' => $emergency->tracking_token,
            ]);

            return $order;
        });
    }

    /**
     * Create Order from VehicleInspectionRequest.
     */
    public function createOrderFromInspectionRequest(VehicleInspectionRequest $request): Order
    {
        return DB::transaction(function () use ($request) {
            $serviceType = ServiceType::where('code', 'vehicle_inspection')->first();

            if (! $serviceType) {
                throw new \Exception('Service type vehicle_inspection not found. Please run RoadsideServiceTypesSeeder.');
            }

            // Determine geo zone
            $geoZone = GeoZone::where('is_active', true)->first();

            // Create Order
            $order = Order::create([
                'user_id' => $request->customer_id,
                'status' => 'pending',
                'geo_zone_id' => $geoZone?->id,
                'location' => $request->address ? [
                    'address' => $request->address,
                ] : null,
                'metadata' => [
                    'created_from' => 'vehicle_inspection_request',
                    'vehicle_inspection_request_id' => $request->id,
                    'preset_id' => $request->preset_id,
                    'vehicle_make' => $request->vehicle_make,
                    'vehicle_model' => $request->vehicle_model,
                    'vehicle_year' => $request->vehicle_year,
                ],
            ]);

            // Create OrderItem
            OrderItem::create([
                'order_id' => $order->id,
                'service_type_id' => $serviceType->id,
                'name' => $request->preset->title ?? $serviceType->name,
                'description' => $request->preset->description ?? null,
                'quantity' => 1,
                'unit_price' => $request->preset->price ?? 0,
                'total_price' => $request->preset->price ?? 0,
            ]);

            // Link VehicleInspectionRequest to Order
            $request->order_id = $order->id;
            $request->save();

            Log::info('Order created from VehicleInspectionRequest', [
                'order_id' => $order->id,
                'request_id' => $request->id,
            ]);

            return $order;
        });
    }

    /**
     * Find geo zone by coordinates.
     */
    protected function findGeoZoneByCoordinates(float $lat, float $lng): ?GeoZone
    {
        // Try to find zone using containsPoint method
        $zones = GeoZone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            if ($zone->containsPoint($lat, $lng)) {
                return $zone;
            }
        }

        // If no zone found, return first active zone as fallback
        return GeoZone::where('is_active', true)->first();
    }
}
