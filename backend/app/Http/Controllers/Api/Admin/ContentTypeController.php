<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = ContentType::withCount('projects')->orderBy('name')->get();

        return response()->json(['content_types' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'short_name'  => ['nullable', 'string', 'max:50'],
            'slug'        => ['required', 'string', 'max:100', 'unique:content_types,slug', 'regex:/^[a-z0-9\-]+$/'],
            'is_active'   => ['boolean'],
            'is_premium'  => ['boolean'],
            'description' => ['nullable', 'string'],
            'type_schema' => ['nullable', 'array'],
        ]);

        $type = ContentType::create([
            'name'        => $data['name'],
            'short_name'  => $data['short_name'] ?? null,
            'slug'        => $data['slug'],
            'is_active'   => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
            'type_schema' => $data['type_schema'] ?? null,
        ]);

        return response()->json(['content_type' => $type], 201);
    }

    public function update(Request $request, ContentType $contentType): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'short_name'  => ['nullable', 'string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
            'is_premium'  => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'type_schema' => ['nullable', 'array'],
        ]);

        $contentType->update($data);

        return response()->json(['content_type' => $contentType->fresh()]);
    }
}
