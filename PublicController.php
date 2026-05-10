<?php

namespace App\Http\Controllers;

use App\Models\GeoZone;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Главная страница (landing)
     */
    public function home()
    {
        $zones = GeoZone::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $categories = ServiceCategory::where('is_active', true)
            ->select('id', 'name', 'code')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $featured = ServiceType::where('is_active', true)
            ->with('serviceCategory')
            ->orderBy('sort_order')
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();

        return view('home', compact('zones', 'categories', 'featured'));
    }

    /**
     * Главная страница каталога с фильтрацией
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

        // Запрос с фильтрацией
        $query = ServiceType::with('serviceCategory')
            ->where('is_active', true);

        // Фильтр по категории
        if ($request->filled('category')) {
            $query->whereHas('serviceCategory', function ($q) use ($request) {
                $q->where('code', $request->input('category'));
            });
        }

        // Поиск по названию и описанию
        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        // Сортировка
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
     * Страница категории услуг
     */
    public function categoryServices($categoryCode)
    {
        $category = ServiceCategory::where('code', $categoryCode)->firstOrFail();
        $services = $category->serviceTypes()->where('is_active', true)->get();

        return view('public.category', compact('category', 'services'));
    }

    /**
     * Форма заказа услуги
     */
    public function orderForm($serviceCode)
    {
        $service = ServiceType::where('slug', $serviceCode)->firstOrFail();

        return view('public.order-form', compact('service'));
    }

    /**
     * Категорія за slug (в нашій моделі використовується code)
     */
    public function category(string $slug)
    {
        $category = ServiceCategory::where('code', $slug)->firstOrFail();
        $services = $category->serviceTypes()->where('is_active', true)->orderBy('sort_order')->get();

        return view('public.category', compact('category', 'services'));
    }

    /**
     * Деталі сервісу (поки що редірект на форму замовлення)
     */
    public function service(string $slug)
    {
        $service = ServiceType::where('slug', $slug)->firstOrFail();

        return redirect()->route('order.form', ['serviceCode' => $service->slug]);
    }

    /**
     * Страница Care услуг
     */
    public function care()
    {
        $category = ServiceCategory::where('code', 'care')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Care Services', 'code' => 'care'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Eco услуг
     */
    public function eco()
    {
        $category = ServiceCategory::where('code', 'eco')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Eco Services', 'code' => 'eco'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Market услуг
     */
    public function market()
    {
        $category = ServiceCategory::where('code', 'market')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Market Services', 'code' => 'market'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Tow услуг
     */
    public function tow()
    {
        $category = ServiceCategory::where('code', 'tow')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Tow Services', 'code' => 'tow'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Rent услуг
     */
    public function rent()
    {
        $category = ServiceCategory::where('code', 'rent')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Rent Services', 'code' => 'rent'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Shuttle услуг
     */
    public function shuttle()
    {
        $category = ServiceCategory::where('code', 'shuttle')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Shuttle Services', 'code' => 'shuttle'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Master услуг
     */
    public function master()
    {
        $category = ServiceCategory::where('code', 'master')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Master Services', 'code' => 'master'],
            'services' => $services,
        ]);
    }

    /**
     * Страница Food услуг
     */
    public function food()
    {
        $category = ServiceCategory::where('code', 'food')->first();
        $services = $category ? $category->serviceTypes()->where('is_active', true)->get() : collect();

        return view('public.category', [
            'category' => $category ?: (object) ['name' => 'Food Services', 'code' => 'food'],
            'services' => $services,
        ]);
    }
}
