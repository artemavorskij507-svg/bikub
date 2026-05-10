<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassifiedAdController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassifiedAd::query()->where('status', 'published');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%");
            });
        }

        if ($request->filled('price_min')) {
            $query->where('price_value', '>=', (int) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price_value', '<=', (int) $request->input('price_max'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $ads = $query->orderBy('published_at', 'desc')->paginate($perPage);

        return response()->json($ads);
    }

    public function show(ClassifiedAd $ad)
    {
        return response()->json($ad->load(['category', 'user']));
    }

    public function store(Request $request)
    {
        // $this->authorize('create', ClassifiedAd::class);

        $data = $request->validate([
            'category_id' => ['required', 'exists:ad_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price_details' => ['nullable', 'array'],
            'price_value' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'moderation', 'published'])],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB max
        ]);

        $ad = new ClassifiedAd($data);
        $ad->user_id = Auth::id();
        $ad->status = $data['status'] ?? 'moderation'; // По умолчанию на модерацию
        $ad->slug = Str::slug($data['title']).'-'.Str::random(6);

        // Если указана цена, конвертируем в копейки (цена хранится в копейках)
        if (isset($data['price_value']) && $data['price_value'] > 0) {
            $ad->price_value = $data['price_value'] * 100;
        }

        $ad->save();

        // Обработка загруженных изображений
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('ads', 'public');

                \App\Modules\Classifieds\Models\AdImage::create([
                    'classified_ad_id' => $ad->id,
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }
        }

        $hasAdImagesTable = Schema::hasTable('ad_images');

        // Для веб-запросов возвращаем редирект, для API - JSON
        if ($request->expectsJson()) {
            return response()->json($hasAdImagesTable ? $ad->load('images') : $ad, 201);
        }

        return redirect()->route('account.classifieds.my-ads')
            ->with('success', 'Объявление успешно создано и отправлено на модерацию!');
    }

    public function update(Request $request, ClassifiedAd $ad)
    {
        // Проверка прав доступа
        if ($ad->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:ad_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price_details' => ['nullable', 'array'],
            'price_value' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'moderation', 'published', 'sold', 'expired'])],
        ]);

        // Конвертация цены в копейки
        if (isset($data['price_value']) && $data['price_value'] > 0) {
            $data['price_value'] = $data['price_value'] * 100;
        }

        // Если статус меняется на moderation, сбрасываем причину модерации
        if (isset($data['status']) && $data['status'] === 'moderation') {
            $data['moderation_reason'] = null;
        }

        // Если статус меняется на moderation, сбрасываем published_at
        if (isset($data['status']) && $data['status'] === 'moderation') {
            $data['published_at'] = null;
        }

        $ad->update($data);

        // Для веб-запросов возвращаем редирект, для API - JSON
        if ($request->expectsJson()) {
            return response()->json($ad);
        }

        $message = $ad->status === 'moderation'
            ? 'Объявление успешно обновлено и отправлено на модерацию!'
            : 'Объявление успешно обновлено!';

        return redirect()->route('account.classifieds.my-ads')
            ->with('success', $message);
    }

    public function destroy(ClassifiedAd $ad)
    {
        // $this->authorize('delete', $ad);

        $ad->delete();

        return response()->json(['deleted' => true]);
    }
}
