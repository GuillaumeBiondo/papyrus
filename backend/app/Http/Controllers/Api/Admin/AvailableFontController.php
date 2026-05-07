<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailableFontResource;
use App\Models\AvailableFont;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailableFontController extends Controller
{
    public function index(): JsonResponse
    {
        $fonts = AvailableFont::orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['fonts' => AvailableFontResource::collection($fonts)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'google_font_slug' => ['required', 'string', 'max:200', Rule::unique('available_fonts')],
            'css_family'       => ['required', 'string', 'max:200'],
            'category'         => ['required', Rule::in(['serif', 'sans-serif', 'monospace'])],
        ]);

        $data['sort_order'] = AvailableFont::max('sort_order') + 1;

        $font = AvailableFont::create($data);

        return response()->json(['font' => new AvailableFontResource($font)], 201);
    }

    public function update(Request $request, AvailableFont $font): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:100'],
            'category'   => ['sometimes', Rule::in(['serif', 'sans-serif', 'monospace'])],
            'enabled'    => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $font->update($data);

        return response()->json(['font' => new AvailableFontResource($font->fresh())]);
    }

    public function destroy(AvailableFont $font): JsonResponse
    {
        $font->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:available_fonts,id'],
        ]);

        foreach ($request->order as $position => $id) {
            AvailableFont::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
