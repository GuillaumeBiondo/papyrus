<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index(): JsonResponse
    {
        $changelogs = Changelog::withCount('reads')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['changelogs' => $changelogs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version'      => ['nullable', 'string', 'max:30'],
            'title'        => ['required', 'string', 'max:200'],
            'body'         => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        $changelog = Changelog::create($data);

        return response()->json(['changelog' => $changelog], 201);
    }

    public function update(Request $request, Changelog $changelog): JsonResponse
    {
        $data = $request->validate([
            'version'      => ['nullable', 'string', 'max:30'],
            'title'        => ['sometimes', 'string', 'max:200'],
            'body'         => ['sometimes', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        $changelog->update($data);

        return response()->json(['changelog' => $changelog->fresh()]);
    }

    public function destroy(Changelog $changelog): JsonResponse
    {
        $changelog->delete();

        return response()->json(null, 204);
    }
}
