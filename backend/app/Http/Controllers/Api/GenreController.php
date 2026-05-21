<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GenreCategory;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class GenreController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = GenreCategory::orderBy('sort_order')
            ->with(['genres' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        $proximity = Setting::find('genre.proximity_matrix')?->value ?? [];

        return response()->json([
            'categories' => $categories,
            'proximity'  => $proximity,
        ]);
    }
}
