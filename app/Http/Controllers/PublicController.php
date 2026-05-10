<?php

namespace App\Http\Controllers;

use App\Models\Delivery\DeliveryOrder;
use App\Models\ErrandTask;
use App\Models\GeoZone;
use App\Models\HandymanService;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Models\ScheduleSlot;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Services\Orders\OrderScenarioRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicController extends Controller
{
    /**
     * Р“Р»Р°РІРЅР°СЏ СЃС‚СЂР°РЅРёС†Р° (landing)
     */
    public function home()
    {
        $zones = GeoZone::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // РђРєС‚РёРІРЅС‹Рµ РєР°С‚РµРіРѕСЂРёРё СѓСЃР»СѓРі РґР»СЏ РіР»Р°РІРЅРѕР№ СЃС‚СЂР°РЅРёС†С‹ (СЃ РєРµС€РёСЂРѕРІР°РЅРёРµРј)
        $categories = \Illuminate\Support\Facades\Cache::remember('home.service_categories', 3600, function () {
            return ServiceCategory::where('is_active', true)
                ->where('show_on_homepage', true)
                ->select('id', 'name', 'code', 'slug', 'icon', 'description', 'homepage_order', 'sort_order')
                ->orderByRaw('COALESCE(homepage_order, sort_order, 999)')
                ->orderBy('name')
                ->get();
        });

        $serviceTypes = ServiceType::where('is_active', true)
            ->with('serviceCategory')
            ->orderBy('sort_order')
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();

        // РџРѕРїСѓР»СЏСЂРЅС‹Рµ РјР°РіР°Р·РёРЅС‹ РґР»СЏ grocery delivery (СЃ РєРµС€РёСЂРѕРІР°РЅРёРµРј)
        $popularStores = \Illuminate\Support\Facades\Cache::remember('home.popular_stores', 3600, function () {
            return RetailStore::where('is_active', true)
                ->where('supports_grocery_delivery', true)
                ->select('id', 'name', 'slug', 'brand', 'address', 'city', 'latitude', 'longitude')
                ->orderBy('name')
                ->take(8)
                ->get();
        });

        // РџРѕРїСѓР»СЏСЂРЅС‹Рµ СЂРµСЃС‚РѕСЂР°РЅС‹ РґР»СЏ food delivery (СЃ РєРµС€РёСЂРѕРІР°РЅРёРµРј)
        $popularRestaurants = \Illuminate\Support\Facades\Cache::remember('home.popular_restaurants', 3600, function () {
            return Restaurant::where('is_active', true)
                ->where('supports_food_delivery', true)
                ->select('id', 'name', 'slug', 'brand', 'cuisine_type', 'address', 'city', 'latitude', 'longitude')
                ->orderBy('name')
                ->take(8)
                ->get();
        });

        // Р‘Р°Р·РѕРІР°СЏ СЃС‚Р°С‚РёСЃС‚РёРєР° РїРѕ РґРѕСЃС‚Р°РІРєРµ (Р±РµР· С‚СЏР¶С‘Р»С‹С… Р·Р°РїСЂРѕСЃРѕРІ)
        $activeDeliveriesCount = DeliveryOrder::query()
            ->whereIn('tracking_status', ['pending', 'assigned', 'picked_up', 'in_transit'])
            ->count();

        $todayOrdersCount = DeliveryOrder::query()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        // РџРѕСЃР»РµРґРЅРёРµ РґРѕСЃС‚Р°РІРєРё РґР»СЏ РїСЂРµРІСЊСЋ
        $recentDeliveries = DeliveryOrder::with(['order'])
            ->select('id', 'order_id', 'tracking_status', 'eta', 'created_at')
            ->latest()
            ->limit(5)
            ->get();

        // РџСЂРёРјРµСЂС‹ РёРЅРґРёРІРёРґСѓР°Р»СЊРЅС‹С… РїРѕСЂСѓС‡РµРЅРёР№
        $errandExamples = ErrandTask::query()
            ->select('id', 'description', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get();

        // РџРѕРїСѓР»СЏСЂРЅС‹Рµ СѓСЃР»СѓРіРё РјР°СЃС‚РµСЂР°
        $handymanPopular = HandymanService::where('is_active', true)
            ->select('id', 'name', 'slug', 'description', 'base_rate_minor', 'pricing_mode')
            ->orderBy('name')
            ->limit(6)
            ->get();

        // Eco РєР°С‚РµРіРѕСЂРёРё (РёСЃРїРѕР»СЊР·СѓРµРј ServiceType СЃ РєР°С‚РµРіРѕСЂРёРµР№ eco)
        $ecoCategories = ServiceType::whereHas('serviceCategory', function ($q) {
            $q->where('code', 'eco');
        })
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'slug', 'description')
            ->limit(8)
            ->get();

        return view('welcome', compact(
            'zones',
            'categories',
            'serviceTypes',
            'popularStores',
            'popularRestaurants',
            'activeDeliveriesCount',
            'todayOrdersCount',
            'recentDeliveries',
            'errandExamples',
            'handymanPopular',
            'ecoCategories'
        ));
    }

    /**
     * Р“Р»Р°РІРЅР°СЏ СЃС‚СЂР°РЅРёС†Р° РєР°С‚Р°Р»РѕРіР° СЃ С„РёР»СЊС‚СЂР°С†РёРµР№
     */
    public function catalog(Request $request)
    {
        $categories = ServiceCategory::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $zones = GeoZone::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Р—Р°РїСЂРѕСЃ СЃ С„РёР»СЊС‚СЂР°С†РёРµР№
        $query = ServiceType::with('serviceCategory')
            ->where('is_active', true);

        // Р¤РёР»СЊС‚СЂ РїРѕ РєР°С‚РµРіРѕСЂРёРё
        if ($request->filled('category')) {
            $query->whereHas('serviceCategory', function ($q) use ($request) {
                $q->where('code', $request->input('category'));
            });
        }

        // РџРѕРёСЃРє РїРѕ РЅР°Р·РІР°РЅРёСЋ Рё РѕРїРёСЃР°РЅРёСЋ
        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%')
                    ->orWhere('short_description', 'like', '%'.$searchTerm.'%');
            });
        }

        // РЎРѕСЂС‚РёСЂРѕРІРєР°
        $sort = $request->input('sort', 'default');
        switch ($sort) {
            case 'popular':
                $query->orderByDesc('orders_count');
                break;
            case 'new':
                $query->latest('created_at');
                break;
            default:
                $query->orderBy('sort_order')->orderByDesc('updated_at');
        }

        $services = $query->paginate(12)->withQueryString();

        return view('catalog', compact('categories', 'zones', 'services'));
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° РєР°С‚РµРіРѕСЂРёРё СѓСЃР»СѓРі
     */
    public function categoryServices($categoryCode)
    {
        $category = ServiceCategory::where('code', $categoryCode)->firstOrFail();
        $services = $category->serviceTypes()->where('is_active', true)->get();

        return view('public.category', [
            'category' => $category,
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * Р¤РѕСЂРјР° Р·Р°РєР°Р·Р° СѓСЃР»СѓРіРё
     */
    public function orderForm($serviceCode)
    {
        $service = ServiceType::where('slug', $serviceCode)->firstOrFail();

        return view('public.order-form', compact('service'));
    }

    /**
     * РљР°С‚РµРіРѕСЂС–СЏ Р·Р° slug (РІ РЅР°С€С–Р№ РјРѕРґРµР»С– РІРёРєРѕСЂРёСЃС‚РѕРІСѓС”С‚СЊСЃСЏ code)
     */
    public function category(string $slug, OrderScenarioRegistry $scenarioRegistry)
    {
        $scenarioCategorySlugs = ['delivery', 'moving', 'eco', 'handyman', 'tow', 'personal-task'];
        if (in_array($slug, $scenarioCategorySlugs, true)) {
            $scenarios = $scenarioRegistry->enabled($slug);
            if ($scenarios !== []) {
                $defaultScenario = $scenarios[0];
                $cmsPage = null;
                if (Schema::hasTable('cms_pages')) {
                    $cmsPage = DB::table('cms_pages')
                        ->where('slug', $slug)
                        ->where('status', 'published')
                        ->first();
                }

                return view('public.category-scenarios', [
                    'slug' => $slug,
                    'scenarios' => array_map(fn (array $s) => $scenarioRegistry->publicMetadata($s), $scenarios),
                    'defaultScenario' => $scenarioRegistry->publicMetadata($defaultScenario),
                    'cmsPage' => $cmsPage,
                ]);
            }
        }

        $category = ServiceCategory::where('code', $slug)
            ->orWhere('slug', $slug)
            ->where('is_active', true)
            ->first();

        $baseData = [
            'category' => $category ?: (object) [
                'name' => ucwords(str_replace('-', ' ', $slug)),
                'code' => $slug,
                'slug' => $slug,
            ],
        ];

        // РЎРїРµС†РёС„РёС‡РЅС‹Рµ РґР°РЅРЅС‹Рµ РґР»СЏ РєР°Р¶РґРѕР№ РєР°С‚РµРіРѕСЂРёРё
        switch ($slug) {
            case 'delivery':
                $deliveryServices = ServiceType::with('serviceCategory')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                $findServiceByCategory = function (array $needles) use ($deliveryServices): ?ServiceType {
                    $lowerNeedles = array_map(static fn (string $item): string => strtolower($item), $needles);

                    return $deliveryServices->first(function (ServiceType $service) use ($lowerNeedles): bool {
                        $categoryCode = strtolower((string) ($service->serviceCategory?->code ?? ''));
                        $categorySlug = strtolower((string) ($service->serviceCategory?->slug ?? ''));
                        $serviceCode = strtolower((string) ($service->code ?? ''));
                        $serviceSlug = strtolower((string) ($service->slug ?? ''));
                        $haystack = [$categoryCode, $categorySlug, $serviceCode, $serviceSlug];

                        foreach ($lowerNeedles as $needle) {
                            foreach ($haystack as $value) {
                                if ($value !== '' && str_contains($value, $needle)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    });
                };

                $groceryService = $findServiceByCategory(['delivery', 'grocery', 'retail', 'market']);
                $foodService = $findServiceByCategory(['food', 'restaurant', 'catering']);
                $freightService = $findServiceByCategory(['freight', 'moving', 'tow', 'roadside']);
                $defaultService = $groceryService ?? $foodService ?? $freightService ?? $deliveryServices->first();
                $deliveryServiceId = $defaultService?->id;

                $storeBaseQuery = RetailStore::query()
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->where('supports_grocery_delivery', true)
                            ->orWhere('has_home_delivery', true)
                            ->orWhere('supports_bulky_delivery', true);
                    });

                $narvikStores = (clone $storeBaseQuery)
                    ->where('city', 'like', '%Narvik%')
                    ->orderBy('name')
                    ->limit(12)
                    ->get();

                $stores = $narvikStores->isNotEmpty()
                    ? $narvikStores
                    : (clone $storeBaseQuery)->orderBy('name')->limit(12)->get();

                $restaurantBaseQuery = Restaurant::query()
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->where('supports_food_delivery', true)
                            ->orWhere('has_home_delivery', true);
                    });

                $narvikRestaurants = (clone $restaurantBaseQuery)
                    ->where('city', 'like', '%Narvik%')
                    ->orderBy('name')
                    ->limit(12)
                    ->get();

                $restaurants = $narvikRestaurants->isNotEmpty()
                    ? $narvikRestaurants
                    : (clone $restaurantBaseQuery)->orderBy('name')->limit(12)->get();

                $zoneBaseQuery = GeoZone::query()
                    ->where('is_active', true)
                    ->orderByDesc('priority')
                    ->orderBy('name');

                $narvikZones = (clone $zoneBaseQuery)
                    ->where(function ($query) {
                        $query->where('name', 'like', '%Narvik%')
                            ->orWhere('description', 'like', '%Narvik%');
                    })
                    ->limit(8)
                    ->get();

                $zones = $narvikZones->isNotEmpty()
                    ? $narvikZones
                    : (clone $zoneBaseQuery)->limit(8)->get();

                $slots = collect();
                if (\Illuminate\Support\Facades\Schema::hasTable('schedule_slots')) {
                    $slotQuery = ScheduleSlot::query();

                    if (\Illuminate\Support\Facades\Schema::hasColumn('schedule_slots', 'is_active')) {
                        $slotQuery->where('is_active', true);
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('schedule_slots', 'status')) {
                        $slotQuery->whereNotIn('status', ['closed']);
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('schedule_slots', 'end_at')) {
                        $slotQuery->where('end_at', '>=', now());
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('schedule_slots', 'start_at')) {
                        $slotQuery->orderBy('start_at');
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('schedule_slots', 'from')) {
                        $slotQuery->orderBy('from');
                    } else {
                        $slotQuery->orderBy('id');
                    }

                    $slots = $slotQuery->limit(8)->get();
                }

                $imageFromEntity = static function (array $fallbacks, array $paths): string {
                    foreach ($paths as $path) {
                        $value = data_get($fallbacks, $path);
                        if (is_string($value) && $value !== '') {
                            return $value;
                        }
                    }

                    return '';
                };

                $groceryFallbackImages = [
                    'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1573246123716-6b1782bfc499?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1506617564039-2f3b650b7010?auto=format&fit=crop&w=900&q=80',
                ];

                $freightFallbackImages = [
                    'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1616401784845-180882ba9ba8?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=900&q=80',
                ];

                $foodFallbackImages = [
                    'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=900&q=80',
                ];

                $storeCards = $stores->values()->map(function (RetailStore $store, int $index) use ($deliveryServiceId, $groceryService, $freightService, $imageFromEntity, $groceryFallbackImages, $freightFallbackImages) {
                    $isFreight = (bool) ($store->supports_bulky_delivery ?? false);
                    $serviceId = $isFreight
                        ? ($freightService?->id ?? $deliveryServiceId)
                        : ($groceryService?->id ?? $deliveryServiceId);

                    $minimumOrder = (float) ($store->minimum_order_amount ?? 0);
                    $deliveryFee = (float) ($store->delivery_fee ?? 0);
                    $price = $deliveryFee > 0 ? $deliveryFee : max($minimumOrder > 0 ? $minimumOrder / 10 : 0, 59.0);

                    $image = $imageFromEntity(
                        [
                            'metadata' => $store->metadata ?? [],
                            'delivery_metadata' => $store->delivery_metadata ?? [],
                        ],
                        ['metadata.image_url', 'metadata.logo_url', 'delivery_metadata.image_url', 'delivery_metadata.logo_url']
                    );

                    if ($image === '') {
                        $pool = $isFreight ? $freightFallbackImages : $groceryFallbackImages;
                        $image = $pool[$index % count($pool)];
                    }

                    $avgEta = (int) ($store->average_delivery_time_minutes ?? 45);
                    $lineOne = $minimumOrder > 0 ? "Min order NOK ".number_format($minimumOrder, 0) : 'No minimum order';
                    $lineTwo = "ETA {$avgEta} min";

                    return [
                        'id' => 'store-'.$store->id,
                        'source_type' => 'store',
                        'source_id' => $store->id,
                        'service_id' => $serviceId,
                        'image_url' => $image,
                        'title' => $store->name,
                        'store' => $store->chain_name ?: ($store->brand ?: 'BiKuBe Retail Partner'),
                        'description' => $store->description
                            ? \Illuminate\Support\Str::limit(strip_tags((string) $store->description), 120)
                            : trim(($store->address ?? '').' '.($store->city ?? 'Narvik')),
                        'items' => [
                            ['name' => $lineOne, 'price' => $minimumOrder],
                            ['name' => $lineTwo, 'price' => $deliveryFee],
                        ],
                        'price' => round($price, 2),
                        'section' => $isFreight ? 'freight' : 'grocery',
                        'source_url' => $store->slug ? '/stores/'.$store->slug : null,
                    ];
                });

                $restaurantCards = $restaurants->values()->map(function (Restaurant $restaurant, int $index) use ($deliveryServiceId, $foodService, $imageFromEntity, $foodFallbackImages) {
                    $serviceId = $foodService?->id ?? $deliveryServiceId;
                    $minimumOrder = (float) ($restaurant->minimum_order_amount ?? 0);
                    $deliveryFee = (float) ($restaurant->delivery_fee ?? 0);
                    $price = $deliveryFee > 0 ? $deliveryFee : max($minimumOrder > 0 ? $minimumOrder / 12 : 0, 49.0);

                    $image = $imageFromEntity(
                        [
                            'metadata' => $restaurant->metadata ?? [],
                            'delivery_metadata' => $restaurant->delivery_metadata ?? [],
                        ],
                        ['metadata.image_url', 'metadata.logo_url', 'delivery_metadata.image_url', 'delivery_metadata.logo_url']
                    );

                    if ($image === '') {
                        $image = $foodFallbackImages[$index % count($foodFallbackImages)];
                    }

                    $avgEta = (int) ($restaurant->average_delivery_time_minutes ?? 35);
                    $cuisine = $restaurant->cuisine_type ? ucfirst((string) $restaurant->cuisine_type) : 'Kitchen';

                    return [
                        'id' => 'restaurant-'.$restaurant->id,
                        'source_type' => 'restaurant',
                        'source_id' => $restaurant->id,
                        'service_id' => $serviceId,
                        'image_url' => $image,
                        'title' => $restaurant->name,
                        'store' => $restaurant->brand ?: 'BiKuBe Food Partner',
                        'description' => $restaurant->description
                            ? \Illuminate\Support\Str::limit(strip_tags((string) $restaurant->description), 120)
                            : trim($cuisine.' in '.($restaurant->city ?: 'Narvik')),
                        'items' => [
                            ['name' => $cuisine, 'price' => $minimumOrder],
                            ['name' => "ETA {$avgEta} min", 'price' => $deliveryFee],
                        ],
                        'price' => round($price, 2),
                        'section' => 'food',
                        'source_url' => $restaurant->slug ? '/restaurants/'.$restaurant->slug : null,
                    ];
                });

                $freightCards = $deliveryServices
                    ->filter(function (ServiceType $service): bool {
                        $categoryCode = strtolower((string) ($service->serviceCategory?->code ?? ''));
                        $categorySlug = strtolower((string) ($service->serviceCategory?->slug ?? ''));
                        $serviceCode = strtolower((string) ($service->code ?? ''));
                        $serviceSlug = strtolower((string) ($service->slug ?? ''));
                        $haystack = $categoryCode.' '.$categorySlug.' '.$serviceCode.' '.$serviceSlug;

                        return str_contains($haystack, 'moving')
                            || str_contains($haystack, 'tow')
                            || str_contains($haystack, 'roadside')
                            || str_contains($haystack, 'freight');
                    })
                    ->take(6)
                    ->values()
                    ->map(function (ServiceType $service, int $index) use ($freightFallbackImages) {
                        $basePrice = round((float) (($service->base_rate_minor ?? 12900) / 100), 2);

                        return [
                            'id' => 'service-'.$service->id,
                            'source_type' => 'service',
                            'source_id' => $service->id,
                            'service_id' => $service->id,
                            'image_url' => $freightFallbackImages[$index % count($freightFallbackImages)],
                            'title' => $service->name,
                            'store' => $service->serviceCategory?->name ?: 'BiKuBe Freight',
                            'description' => $service->description
                                ? \Illuminate\Support\Str::limit(strip_tags((string) $service->description), 120)
                                : 'Scheduled and on-demand freight pickup in Narvik.',
                            'items' => [
                                ['name' => 'Heavy load handling', 'price' => $basePrice],
                                ['name' => 'Priority dispatch', 'price' => 149.0],
                            ],
                            'price' => max($basePrice, 99.0),
                            'section' => 'freight',
                            'source_url' => $service->slug ? '/services/'.$service->slug : null,
                        ];
                    });

                $deliveryCatalog = $storeCards
                    ->concat($restaurantCards)
                    ->concat($freightCards)
                    ->values();

                if ($deliveryCatalog->isEmpty()) {
                    $deliveryCatalog = $deliveryServices->take(8)->map(function (ServiceType $service) {
                        $basePrice = round((float) (($service->base_rate_minor ?? 9900) / 100), 2);
                        $categoryCode = strtolower((string) ($service->serviceCategory?->code ?? 'delivery'));
                        $section = str_contains($categoryCode, 'food')
                            ? 'food'
                            : (str_contains($categoryCode, 'move') || str_contains($categoryCode, 'tow') ? 'freight' : 'grocery');

                        return [
                            'id' => 'service-'.$service->id,
                            'source_type' => 'service',
                            'source_id' => $service->id,
                            'service_id' => $service->id,
                            'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80',
                            'title' => $service->name,
                            'store' => $service->serviceCategory?->name ?: 'BiKuBe Delivery',
                            'description' => $service->description
                                ? \Illuminate\Support\Str::limit(strip_tags((string) $service->description), 120)
                                : 'On-demand delivery service',
                            'items' => [
                                ['name' => 'Base service', 'price' => $basePrice],
                            ],
                            'price' => max($basePrice, 49.0),
                            'section' => $section,
                            'source_url' => $service->slug ? '/services/'.$service->slug : null,
                        ];
                    })->values();
                }

                $deliveryHighlights = collect([
                    ['label' => 'Retail partners', 'value' => $stores->count(), 'tone' => 'emerald'],
                    ['label' => 'Food partners', 'value' => $restaurants->count(), 'tone' => 'amber'],
                    ['label' => 'Coverage zones', 'value' => $zones->count(), 'tone' => 'indigo'],
                    ['label' => 'Upcoming slots', 'value' => $slots->count(), 'tone' => 'slate'],
                ])->all();

                $deliveryPartners = [
                    'stores' => $stores->take(6)->map(function (RetailStore $store) {
                        return [
                            'id' => $store->id,
                            'name' => $store->name,
                            'city' => $store->city,
                            'fee' => $store->delivery_fee,
                            'min_order' => $store->minimum_order_amount,
                            'eta' => (int) ($store->average_delivery_time_minutes ?? 0),
                        ];
                    })->values(),
                    'restaurants' => $restaurants->take(6)->map(function (Restaurant $restaurant) {
                        return [
                            'id' => $restaurant->id,
                            'name' => $restaurant->name,
                            'city' => $restaurant->city,
                            'cuisine' => $restaurant->cuisine_type,
                            'fee' => $restaurant->delivery_fee,
                            'eta' => (int) ($restaurant->average_delivery_time_minutes ?? 0),
                        ];
                    })->values(),
                    'zones' => $zones->map(function (GeoZone $zone) {
                        return [
                            'id' => $zone->id,
                            'name' => $zone->name,
                            'type' => $zone->type,
                            'priority' => $zone->priority,
                            'radius_meters' => $zone->radius_meters,
                        ];
                    })->values(),
                    'slots' => $slots->map(function (ScheduleSlot $slot) {
                        $from = $slot->from ?? ($slot->start_at ? \Illuminate\Support\Carbon::parse($slot->start_at)->format('H:i') : null);
                        $to = $slot->to ?? ($slot->end_at ? \Illuminate\Support\Carbon::parse($slot->end_at)->format('H:i') : null);

                        return [
                            'id' => $slot->id,
                            'name' => $slot->name ?? $slot->label ?? 'Slot #'.$slot->id,
                            'from' => $from,
                            'to' => $to,
                        ];
                    })->values(),
                ];

                $deliveryMeta = [
                    'city' => 'Narvik',
                    'country' => 'Norway',
                    'timezone' => config('app.timezone', 'Europe/Oslo'),
                    'lat' => 68.4385,
                    'lng' => 17.4273,
                    'default_zone_id' => $zones->first()?->id,
                    'service_id_default' => $deliveryServiceId,
                    'catalog_count' => $deliveryCatalog->count(),
                    'generated_at' => now()->toIso8601String(),
                    'generated_images' => [
                        'hero' => '/images/delivery-generated/hero-delivery.svg',
                        'promo_green' => '/images/delivery-generated/promo-green.svg',
                        'promo_pink' => '/images/delivery-generated/promo-pink.svg',
                        'promo_yellow' => '/images/delivery-generated/promo-yellow.svg',
                        'basket_blue' => '/images/delivery-generated/basket-blue.svg',
                    ],
                ];

                return view('public.delivery-landing', $baseData + [
                    'deliveryCatalog' => $deliveryCatalog,
                    'deliveryServiceId' => $deliveryServiceId,
                    'deliveryHighlights' => $deliveryHighlights,
                    'deliveryPartners' => $deliveryPartners,
                    'deliveryMeta' => $deliveryMeta,
                ]);

            case 'moving':
                return view('public.moving-landing', $baseData + [
                    'services' => ServiceType::whereHas('serviceCategory', fn ($q) => $q->where('slug', 'moving'))
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(),
                ]);

            case 'handyman':
            case 'master':
                return view('public.handyman-landing', $baseData + [
                    'services' => HandymanService::where('is_active', true)
                        ->orderBy('name')
                        ->get(),
                ]);

            case 'eco':
                return view('public.eco-landing', $baseData + [
                    'services' => ServiceType::whereHas('serviceCategory', fn ($q) => $q->where('slug', 'eco'))
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(),
                ]);

            case 'social-help':
            case 'social-care':
            case 'social':
            case 'care':
                return view('public.social-landing', $baseData + [
                    'services' => ServiceType::whereHas('serviceCategory', fn ($q) => $q->whereIn('slug', ['social-help', 'social-care', 'social', 'care']))
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(),
                ]);

            case 'personal-task':
            case 'errands':
            case 'errand':
                return view('public.errand-landing', $baseData + [
                    'services' => ServiceType::whereHas('serviceCategory', fn ($q) => $q->whereIn('slug', ['personal-task', 'errands', 'errand']))
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(),
                ]);

            case 'tow':
            case 'roadside':
                return view('public.tow-landing', $baseData + [
                    'services' => ServiceType::whereHas('serviceCategory', fn ($q) => $q->whereIn('slug', ['tow', 'roadside']))
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(),
                ]);

            case 'food':
                return view('public.category', $baseData + [
                    'services' => $category
                        ? $category->serviceTypes()->where('is_active', true)->orderBy('sort_order')->get()
                        : collect(),
                    'deliveryPartners' => [],
                    'products' => collect(),
                    'restaurants' => Restaurant::where('is_active', true)->take(8)->get(),
                ]);

            default:
                if (! $category) {
                    abort(404);
                }

                // Fallback РґР»СЏ РґСЂСѓРіРёС… РєР°С‚РµРіРѕСЂРёР№
                return view('category.generic', $baseData + [
                    'services' => $category->serviceTypes()->where('is_active', true)->orderBy('sort_order')->get(),
                ]);
        }
    }

    /**
     * Р”РµС‚Р°Р»С– СЃРµСЂРІС–СЃСѓ (РїРѕРєРё С‰Рѕ СЂРµРґС–СЂРµРєС‚ РЅР° С„РѕСЂРјСѓ Р·Р°РјРѕРІР»РµРЅРЅСЏ)
     */
    public function service(string $slug)
    {
        $service = ServiceType::where('slug', $slug)->firstOrFail();

        return redirect()->route('order.form', ['serviceCode' => $service->slug]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Care СѓСЃР»СѓРі
     */
    public function care()
    {
        $category = ServiceCategory::where('code', 'care')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Care Services', 'code' => 'care'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Eco СѓСЃР»СѓРі
     */
    public function eco()
    {
        $category = ServiceCategory::where('code', 'eco')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Eco Services', 'code' => 'eco'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Market СѓСЃР»СѓРі
     */
    public function market()
    {
        $category = ServiceCategory::where('code', 'market')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Market Services', 'code' => 'market'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Tow СѓСЃР»СѓРі
     */
    public function tow()
    {
        $category = ServiceCategory::where('code', 'tow')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Tow Services', 'code' => 'tow'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Rent СѓСЃР»СѓРі
     */
    public function rent()
    {
        $category = ServiceCategory::where('code', 'rent')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Rent Services', 'code' => 'rent'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Shuttle СѓСЃР»СѓРі
     */
    public function shuttle()
    {
        $category = ServiceCategory::where('code', 'shuttle')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Shuttle Services', 'code' => 'shuttle'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° Master СѓСЃР»СѓРі
     */
    public function master()
    {
        $category = ServiceCategory::where('code', 'master')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Master Services', 'code' => 'master'],
            'services' => $services,
            'deliveryPartners' => [],
            'products' => collect(),
        ]);
    }

    /**
     * Страница Food услуг (GLF MaT)
     */
    public function food()
    {
        return $this->category('food', app(OrderScenarioRegistry::class));
    }

    /**
     * РЎРїРёСЃРѕРє РјР°РіР°Р·РёРЅРѕРІ РґР»СЏ РґРѕСЃС‚Р°РІРєРё РїСЂРѕРґСѓРєС‚РѕРІ
     */
    public function storesIndex(Request $request)
    {
        $stores = RetailStore::where('is_active', true)
            ->where('supports_grocery_delivery', true)
            ->orderBy('name')
            ->paginate(12);

        return view('public.stores.index', [
            'stores' => $stores,
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° РєРѕРЅРєСЂРµС‚РЅРѕРіРѕ РјР°РіР°Р·РёРЅР°
     */
    public function storeShow(string $slug)
    {
        $store = RetailStore::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('public.stores.show', [
            'store' => $store,
        ]);
    }

    /**
     * РЎРїРёСЃРѕРє СЂРµСЃС‚РѕСЂР°РЅРѕРІ РґР»СЏ РґРѕСЃС‚Р°РІРєРё РµРґС‹
     */
    public function restaurantsIndex(Request $request)
    {
        $restaurants = Restaurant::where('is_active', true)
            ->where('supports_food_delivery', true)
            ->orderBy('name')
            ->paginate(12);

        return view('public.restaurants.index', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * РЎС‚СЂР°РЅРёС†Р° РєРѕРЅРєСЂРµС‚РЅРѕРіРѕ СЂРµСЃС‚РѕСЂР°РЅР°
     */
    public function restaurantShow(string $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('public.restaurants.show', [
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * РЎРїРёСЃРѕРє РІСЃРµС… РєР°С‚РµРіРѕСЂРёР№ СѓСЃР»СѓРі
     */
    public function servicesIndex()
    {
        $categories = ServiceCategory::where('is_active', true)
            ->orderBy('homepage_order')
            ->orderBy('name')
            ->get();

        return view('public.services.index', [
            'categories' => $categories,
        ]);
    }
}
