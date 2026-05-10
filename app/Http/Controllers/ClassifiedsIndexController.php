<?php

namespace App\Http\Controllers;

use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ClassifiedsIndexController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassifiedAd::query()
            ->published()
            ->with(['category', 'user', 'shop']);

        // Поиск
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Фильтр по категории
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Фильтр по цене
        if ($request->filled('price_min')) {
            $query->where('price_value', '>=', (int) $request->input('price_min') * 100);
        }
        if ($request->filled('price_max')) {
            $query->where('price_value', '<=', (int) $request->input('price_max') * 100);
        }

        // Фильтр по наличию цены
        if ($request->filled('has_price')) {
            if ($request->input('has_price') === 'yes') {
                $query->whereNotNull('price_value')->where('price_value', '>', 0);
            } elseif ($request->input('has_price') === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('price_value')->orWhere('price_value', 0);
                });
            }
        }

        // Фильтр по типу продавца (частное лицо / магазин)
        if ($request->filled('seller_type')) {
            if ($request->input('seller_type') === 'shop') {
                $query->whereNotNull('shop_id');
            } elseif ($request->input('seller_type') === 'private') {
                $query->whereNull('shop_id');
            }
        }

        // Фильтр по дате публикации
        if ($request->filled('date')) {
            $date = $request->input('date');
            switch ($date) {
                case 'today':
                    $query->whereDate('published_at', today());
                    break;
                case 'week':
                    $query->where('published_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('published_at', '>=', now()->subMonth());
                    break;
            }
        }

        // Сортировка
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->whereNotNull('price_value')
                    ->where('price_value', '>', 0)
                    ->orderBy('price_value', 'asc')
                    ->orderByDesc('published_at');
                break;
            case 'price_desc':
                $query->whereNotNull('price_value')
                    ->where('price_value', '>', 0)
                    ->orderBy('price_value', 'desc')
                    ->orderByDesc('published_at');
                break;
            case 'views':
                $query->orderByDesc('views_count')
                    ->orderByDesc('published_at');
                break;
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderByPromotion();
                break;
        }

        $hasAdImagesTable = Schema::hasTable('ad_images');

        $perPage = min((int) $request->input('per_page', 24), 48);
        if ($hasAdImagesTable) {
            $ads = $query->with(['images', 'category', 'shop'])->paginate($perPage)->withQueryString();
        } else {
            $ads = $query->with(['category', 'shop'])->paginate($perPage)->withQueryString();
        }

        if ($hasAdImagesTable) {
            $ads->getCollection()->each(function ($ad) {
                $ad->load('images');
            });
        }

        // Категории для фильтра
        $categories = AdCategory::where('is_active', true)
            ->withCount(['ads' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->get();

        // Статистика
        $stats = [
            'total' => ClassifiedAd::published()->count(),
            'with_price' => ClassifiedAd::published()
                ->whereNotNull('price_value')
                ->where('price_value', '>', 0)
                ->count(),
            'shops' => ClassifiedAd::published()
                ->whereNotNull('shop_id')
                ->distinct('shop_id')
                ->count('shop_id'),
            'categories' => AdCategory::where('is_active', true)->count(),
        ];

        // Популярные категории
        $popularCategories = AdCategory::where('is_active', true)
            ->withCount(['ads' => function ($q) {
                $q->published();
            }])
            ->get()
            ->filter(function ($category) {
                return ($category->ads_count ?? 0) > 0;
            })
            ->sortByDesc('ads_count')
            ->take(5)
            ->values();

        $withImages = $hasAdImagesTable ? ['images', 'category', 'shop', 'user'] : ['category', 'shop', 'user'];

        // Топ объявления (VIP/Premium/Top)
        $featuredAds = ClassifiedAd::published()
            ->with($withImages)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('vip_expires_at')
                        ->where('vip_expires_at', '>', now());
                })
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('top_expires_at')
                            ->where('top_expires_at', '>', now());
                    })
                    ->orWhere('is_premium', true);
            })
            ->orderByPromotion()
            ->take(6)
            ->get();

        // Рекомендуемые объявления
        $recommendedAds = ClassifiedAd::published()
            ->with($withImages)
            ->whereNotIn('id', $featuredAds->pluck('id')->toArray())
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        // Популярные магазины
        $popularShops = \App\Modules\Classifieds\Models\Shop::where('is_active', true)
            ->withCount(['ads' => function ($q) {
                $q->published();
            }])
            ->get()
            ->filter(function ($shop) {
                return ($shop->ads_count ?? 0) > 0;
            })
            ->sortByDesc('ads_count')
            ->take(6)
            ->values();

        return view('classifieds.index', compact('ads', 'categories', 'stats', 'popularCategories', 'featuredAds', 'recommendedAds', 'popularShops'));
    }
}
