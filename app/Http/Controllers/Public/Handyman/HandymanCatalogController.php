<?php

namespace App\Http\Controllers\Public\Handyman;

use App\Http\Controllers\Controller;
use App\Models\HandymanService;
use Illuminate\Http\Request;

class HandymanCatalogController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        $query = HandymanService::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name');

        if ($category) {
            $query->where('category', $category);
        }

        $services = $query->get();

        // Популярные услуги можно пока сделать TODO (по числу заказов)
        return view('public.handyman.index', [
            'services' => $services,
            'currentCategory' => $category,
        ]);
    }

    public function show(string $slug)
    {
        $service = HandymanService::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('public.handyman.show', [
            'service' => $service,
        ]);
    }

    public function customRequest()
    {
        return view('public.handyman.custom-request');
    }
}
