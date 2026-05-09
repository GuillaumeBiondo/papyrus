<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        $name = Str::uuid() . '.' . $file->extension();
        $path = Storage::disk('public')->putFileAs('changelog-images', $file, $name);

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
