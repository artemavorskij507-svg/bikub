<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index()
    {
        // Кешируем sitemap на 1 час
        $sitemap = Cache::remember('classifieds_sitemap', 3600, function () {
            $categories = AdCategory::where('is_active', true)->get();
            $ads = ClassifiedAd::published()
                ->whereNotNull('slug')
                ->select('slug', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->get();

            return view('classifieds.sitemap', compact('categories', 'ads'))->render();
        });

        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }
}
