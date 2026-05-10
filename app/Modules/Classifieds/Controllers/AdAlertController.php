<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdAlertController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:ad_categories,id',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'frequency' => 'required|in:daily,weekly',
        ]);

        $savedSearch = SavedSearch::create([
            'user_id' => Auth::id(),
            'query' => $request->input('query'),
            'category_id' => $request->input('category_id'),
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
            'frequency' => $request->input('frequency'),
        ]);

        return response()->json([
            'message' => 'Search alert created successfully',
            'alert' => $savedSearch,
        ], 201);
    }
}
