<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use Illuminate\Http\JsonResponse;

class ContentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $contentTypes = ContentType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'slug', 'is_premium', 'description']);

        return response()->json(['content_types' => $contentTypes]);
    }
}
