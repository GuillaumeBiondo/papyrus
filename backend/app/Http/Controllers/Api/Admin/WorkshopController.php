<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['workshops' => Workshop::with('contentType')->orderBy('sort_order')->get()]);
    }

    public function update(Request $request, Workshop $workshop): JsonResponse
    {
        $data = $request->validate([
            'label'           => ['sometimes', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'is_active'       => ['sometimes', 'boolean'],
            'is_premium'      => ['sometimes', 'boolean'],
            'sort_order'      => ['sometimes', 'integer', 'min:0'],
            'content_type_id' => ['nullable', 'uuid', 'exists:content_types,id'],
        ]);

        $workshop->update($data);

        return response()->json(['workshop' => $workshop->fresh()->load('contentType')]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:workshops,id'],
        ]);

        foreach ($request->order as $position => $id) {
            Workshop::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
