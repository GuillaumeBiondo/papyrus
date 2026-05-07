<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailableFontResource;
use App\Models\AvailableFont;
use Illuminate\Http\JsonResponse;

class FontController extends Controller
{
    public function index(): JsonResponse
    {
        $fonts = AvailableFont::where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['fonts' => AvailableFontResource::collection($fonts)]);
    }
}
