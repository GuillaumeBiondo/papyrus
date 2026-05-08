<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginEvent;
use App\Models\Project;
use App\Models\Scene;
use App\Models\SceneSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    private const GRID_DAYS = 365;

    public function global(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'daily'  => $this->dailyGrid($userId),
            'hourly' => $this->hourlyGrid($userId),
        ]);
    }

    public function forProject(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $sceneIds = Scene::whereHas(
            'chapter.arc',
            fn($q) => $q->where('project_id', $project->id)
        )->pluck('id');

        $userId = $request->user()->id;

        return response()->json([
            'daily'  => $this->dailyGrid($userId, $sceneIds->all()),
            'hourly' => $this->hourlyGrid($userId, $sceneIds->all()),
        ]);
    }

    // ── Grille 365 jours ──────────────────────────────────────

    private function dailyGrid(int $userId, array $sceneIds = []): array
    {
        $from = now()->subDays(self::GRID_DAYS)->startOfDay();

        $loginsByDay = LoginEvent::where('user_id', $userId)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as logins')
            ->groupBy('date')
            ->pluck('logins', 'date');

        $wordsQuery = SceneSnapshot::where('user_id', $userId)
            ->where('word_delta', '>', 0)
            ->where('created_at', '>=', $from);

        if ($sceneIds) $wordsQuery->whereIn('scene_id', $sceneIds);

        $wordsByDay = $wordsQuery
            ->selectRaw('DATE(created_at) as date, SUM(word_delta) as words')
            ->groupBy('date')
            ->pluck('words', 'date');

        $days = [];
        for ($i = self::GRID_DAYS; $i >= 0; $i--) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $days[] = [
                'date'   => $date,
                'logins' => (int) ($loginsByDay[$date] ?? 0),
                'words'  => (int) ($wordsByDay[$date] ?? 0),
            ];
        }

        return $days;
    }

    // ── Heatmap 7j × 24h ─────────────────────────────────────

    private function hourlyGrid(int $userId, array $sceneIds = []): array
    {
        $loginsMap = LoginEvent::where('user_id', $userId)
            ->selectRaw('DAYOFWEEK(created_at) - 1 as day, HOUR(created_at) as hour, COUNT(*) as logins')
            ->groupBy('day', 'hour')
            ->get()
            ->mapWithKeys(fn($r) => ["{$r->day}:{$r->hour}" => (int) $r->logins]);

        $wordsQuery = SceneSnapshot::where('user_id', $userId)->where('word_delta', '>', 0);
        if ($sceneIds) $wordsQuery->whereIn('scene_id', $sceneIds);

        $wordsMap = $wordsQuery
            ->selectRaw('DAYOFWEEK(created_at) - 1 as day, HOUR(created_at) as hour, SUM(word_delta) as words')
            ->groupBy('day', 'hour')
            ->get()
            ->mapWithKeys(fn($r) => ["{$r->day}:{$r->hour}" => (int) $r->words]);

        $grid = [];
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $key    = "{$day}:{$hour}";
                $grid[] = [
                    'day'    => $day,
                    'hour'   => $hour,
                    'logins' => $loginsMap[$key] ?? 0,
                    'words'  => $wordsMap[$key]  ?? 0,
                ];
            }
        }

        return $grid;
    }
}
