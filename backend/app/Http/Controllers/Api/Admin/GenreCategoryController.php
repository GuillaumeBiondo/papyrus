<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\GenreCategory;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GenreCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = GenreCategory::orderBy('sort_order')
            ->with(['genres' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'                  => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/', 'unique:genre_categories,id'],
            'name'                => ['required', 'string', 'max:255'],
            'color'               => ['required', 'string', 'max:20'],
            'light_color'         => ['required', 'string', 'max:20'],
            'text_color'          => ['required', 'string', 'max:20'],
            'adjacent_categories' => ['nullable', 'array'],
            'adjacent_categories.*' => ['string', 'exists:genre_categories,id'],
            'sort_order'          => ['integer'],
        ]);

        $category = GenreCategory::create($data);
        $category->load('genres');

        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, GenreCategory $genreCategory): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'color'               => ['sometimes', 'required', 'string', 'max:20'],
            'light_color'         => ['sometimes', 'required', 'string', 'max:20'],
            'text_color'          => ['sometimes', 'required', 'string', 'max:20'],
            'adjacent_categories' => ['nullable', 'array'],
            'adjacent_categories.*' => ['string'],
            'sort_order'          => ['integer'],
        ]);

        $genreCategory->update($data);
        $genreCategory->load('genres');

        return response()->json(['category' => $genreCategory]);
    }

    public function destroy(GenreCategory $genreCategory): JsonResponse
    {
        $genreCategory->delete();

        return response()->json(null, 204);
    }

    public function reorderCategories(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'             => ['required', 'array'],
            'items.*.id'        => ['required', 'string', 'exists:genre_categories,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            GenreCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['ok' => true]);
    }

    public function storeGenre(Request $request, GenreCategory $genreCategory): JsonResponse
    {
        $data = $request->validate([
            'id'         => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/', 'unique:genres,id'],
            'name'       => ['required', 'string', 'max:255'],
            'bridges'    => ['nullable', 'array'],
            'bridges.*'  => ['string', 'exists:genre_categories,id'],
            'sort_order' => ['integer'],
        ]);

        $genre = $genreCategory->genres()->create($data);

        return response()->json(['genre' => $genre], 201);
    }

    public function updateGenre(Request $request, GenreCategory $genreCategory, Genre $genre): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'bridges'    => ['nullable', 'array'],
            'bridges.*'  => ['string'],
            'sort_order' => ['integer'],
        ]);

        $genre->update($data);

        return response()->json(['genre' => $genre]);
    }

    public function destroyGenre(GenreCategory $genreCategory, Genre $genre): JsonResponse
    {
        $genre->delete();

        return response()->json(null, 204);
    }

    public function reorderGenres(Request $request, GenreCategory $genreCategory): JsonResponse
    {
        $items = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'string', 'exists:genres,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            Genre::where('id', $item['id'])
                ->where('genre_category_id', $genreCategory->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['ok' => true]);
    }

    public function getProximity(): JsonResponse
    {
        $setting = Setting::find('genre.proximity_matrix');

        return response()->json(['proximity' => $setting?->value ?? []]);
    }

    public function updateProximity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'proximity'   => ['required', 'array'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'genre.proximity_matrix'],
            [
                'value' => $data['proximity'],
                'label' => 'Matrice de proximité des univers',
                'group' => 'genre',
            ],
        );

        return response()->json(['ok' => true]);
    }
}
