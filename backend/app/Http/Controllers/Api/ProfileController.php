<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE_KB   = 2048;

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio'  => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $request->user()->update($data);

        return response()->json(['user' => new UserResource($request->user()->fresh())]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp',
                'mimetypes:' . implode(',', self::ALLOWED_MIMES),
                'max:' . self::MAX_SIZE_KB,
            ],
        ]);

        $user = $request->user();
        $this->deleteAvatarFile($user->avatar_stored_name);

        $file       = $request->file('avatar');
        $storedName = Str::uuid() . '.' . $file->extension();
        Storage::disk('local')->putFileAs('user-avatars', $file, $storedName);

        $user->update(['avatar_stored_name' => $storedName]);

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->deleteAvatarFile($user->avatar_stored_name);
        $user->update(['avatar_stored_name' => null]);

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    public function serveAvatar(Request $request): Response
    {
        $user = $request->user();
        $name = $user->avatar_stored_name;
        $path = 'user-avatars/' . $name;

        abort_if(! $name || ! Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path);

        return response(
            Storage::disk('local')->get($path),
            200,
            [
                'Content-Type'  => in_array($mime, self::ALLOWED_MIMES, true) ? $mime : 'image/jpeg',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate(['preferences' => 'required|array']);

        $user    = $request->user();
        $current = $user->preferences ?? [];
        $user->update(['preferences' => array_merge($current, $request->preferences)]);

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    private function deleteAvatarFile(?string $storedName): void
    {
        if ($storedName) {
            Storage::disk('local')->delete('user-avatars/' . $storedName);
        }
    }
}
