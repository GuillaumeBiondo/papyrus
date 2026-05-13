<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiEnrichType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiEnrichController extends Controller
{
    public function index(): JsonResponse
    {
        $types = AiEnrichType::orderBy('sort_order')->orderBy('id')->get();
        return response()->json(['types' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type_key'      => ['required', 'string', 'max:50', 'unique:ai_enrich_types,type_key', 'regex:/^[a-z0-9_]+$/'],
            'label'         => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'is_active'     => ['boolean'],
            'is_premium'    => ['boolean'],
            'system_prompt' => ['required', 'string'],
            'sort_order'    => ['integer'],
        ]);

        if (! isset($data['sort_order'])) {
            $data['sort_order'] = (AiEnrichType::max('sort_order') ?? -1) + 1;
        }

        $type = AiEnrichType::create($data);
        return response()->json(['type' => $type], 201);
    }

    public function update(Request $request, AiEnrichType $aiEnrichType): JsonResponse
    {
        $data = $request->validate([
            'label'         => ['sometimes', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'is_active'     => ['sometimes', 'boolean'],
            'is_premium'    => ['sometimes', 'boolean'],
            'system_prompt' => ['sometimes', 'string'],
            'sort_order'    => ['sometimes', 'integer'],
        ]);

        $aiEnrichType->update($data);
        return response()->json(['type' => $aiEnrichType->fresh()]);
    }

    public function destroy(AiEnrichType $aiEnrichType): JsonResponse
    {
        $aiEnrichType->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $position => $id) {
            AiEnrichType::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
