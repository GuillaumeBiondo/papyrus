<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\ChangelogRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $readIds = $user->changelogReads()->pluck('changelog_id');

        $unread = Changelog::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotIn('id', $readIds)
            ->orderByDesc('published_at')
            ->get(['id', 'version', 'title', 'body', 'published_at']);

        return response()->json([
            'changelogs' => $unread,
            'count'      => $unread->count(),
        ]);
    }

    public function markRead(Request $request, Changelog $changelog): JsonResponse
    {
        $user = $request->user();

        ChangelogRead::firstOrCreate(
            ['user_id' => $user->id, 'changelog_id' => $changelog->id],
            ['read_at' => now()]
        );

        return response()->json(null, 204);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $readIds = $user->changelogReads()->pluck('changelog_id');

        $toMark = Changelog::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotIn('id', $readIds)
            ->pluck('id');

        $now = now();
        $rows = $toMark->map(fn($id) => [
            'id'           => \Illuminate\Support\Str::uuid()->toString(),
            'user_id'      => $user->id,
            'changelog_id' => $id,
            'read_at'      => $now,
        ])->all();

        if ($rows) {
            ChangelogRead::insert($rows);
        }

        return response()->json(null, 204);
    }
}
