<?php

namespace App\Modules\Classifieds\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Models\ClassifiedAdBump;
use App\Modules\Classifieds\Models\ClassifiedAdFavorite;
use Illuminate\Http\Request;

class AccountClassifiedsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Show list */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Базовый запрос - только объявления пользователя
        $query = ClassifiedAd::where('user_id', $user->id)
            ->with(['category', 'shop'])
            ->orderBy('created_at', 'desc');

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фильтр по категории
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Поиск по названию и описанию
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Фильтр по дате создания
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Пагинация
        $perPage = $request->input('per_page', 15);
        $ads = $query->paginate($perPage)->withQueryString();

        // Статистика
        $stats = [
            'total' => ClassifiedAd::where('user_id', $user->id)->count(),
            'published' => ClassifiedAd::where('user_id', $user->id)->where('status', 'published')->count(),
            'moderation' => ClassifiedAd::where('user_id', $user->id)->where('status', 'moderation')->count(),
            'draft' => ClassifiedAd::where('user_id', $user->id)->where('status', 'draft')->count(),
            'sold' => ClassifiedAd::where('user_id', $user->id)->where('status', 'sold')->count(),
            'expired' => ClassifiedAd::where('user_id', $user->id)->where('status', 'expired')->count(),
            'total_views' => ClassifiedAd::where('user_id', $user->id)->sum('views_count'),
        ];

        // Категории для фильтра
        $categories = AdCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('classifieds.my-ads', compact('ads', 'stats', 'categories'));
    }

    /** Show single ad */
    public function show(ClassifiedAd $ad)
    {
        // optional ownership check
        // abort_if($ad->user_id !== auth()->id(), 403);
        return view('classifieds.account-show', compact('ad'));
    }

    /** Toggle favorite */
    public function favorite(ClassifiedAd $ad)
    {
        $user = auth()->user();
        $exists = ClassifiedAdFavorite::where('user_id', $user->id)
            ->where('classified_ad_id', $ad->id)
            ->exists();
        if (! $exists) {
            ClassifiedAdFavorite::create(['user_id' => $user->id, 'classified_ad_id' => $ad->id]);
        }

        return back();
    }

    /** Remove favorite */
    public function unfavorite(ClassifiedAd $ad)
    {
        ClassifiedAdFavorite::where('user_id', auth()->id())
            ->where('classified_ad_id', $ad->id)
            ->delete();

        return back();
    }

    /** Bump (promote) ad */
    public function bump(ClassifiedAd $ad)
    {
        // Simple implementation: create a bump record
        ClassifiedAdBump::create([
            'classified_ad_id' => $ad->id,
            'user_id' => auth()->id(),
        ]);
        // Update ad's bumped_at if needed
        $ad->touch();

        return back();
    }
}
