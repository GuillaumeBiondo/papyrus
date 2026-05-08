<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SceneSnapshotResource;
use App\Models\Scene;
use App\Models\SceneSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SceneSnapshotController extends Controller
{
    public function index(Request $request, Scene $scene): JsonResponse
    {
        $this->authorize('view', $scene);

        $snapshots = $scene->snapshots()
            ->select(['id', 'trigger', 'label', 'word_count', 'word_delta', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['snapshots' => SceneSnapshotResource::collection($snapshots)]);
    }

    public function store(Request $request, Scene $scene): JsonResponse
    {
        $this->authorize('update', $scene);

        $data = $request->validate([
            'content'    => ['required', 'string'],
            'word_count' => ['required', 'integer', 'min:0'],
            'word_delta' => ['required', 'integer'],
            'trigger'    => ['required', Rule::in(['auto', 'manual'])],
            'label'      => ['nullable', 'string', 'max:200'],
        ]);

        $snapshot = $scene->snapshots()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        // Mettre à jour le word_count sur la scène
        $scene->update(['word_count' => $data['word_count']]);

        if ($data['trigger'] === 'auto') {
            SceneSnapshot::pruneAutos($scene->id);
        }

        return response()->json(['snapshot' => new SceneSnapshotResource($snapshot)], 201);
    }

    public function show(Request $request, Scene $scene, SceneSnapshot $snapshot): JsonResponse
    {
        $this->authorize('view', $scene);
        abort_if($snapshot->scene_id !== $scene->id, 404);

        return response()->json(['snapshot' => new SceneSnapshotResource($snapshot)]);
    }

    public function restore(Request $request, Scene $scene, SceneSnapshot $snapshot): JsonResponse
    {
        $this->authorize('update', $scene);
        abort_if($snapshot->scene_id !== $scene->id, 404);

        $currentWordCount = self::wordCount($scene->content);
        $sourceLabel      = $snapshot->label
            ? "« {$snapshot->label} »"
            : $snapshot->created_at->locale('fr')->isoFormat('D MMM YYYY [à] HH[h]mm');

        $restore = $scene->snapshots()->create([
            'user_id'    => $request->user()->id,
            'content'    => $snapshot->content,
            'word_count' => $snapshot->word_count,
            'word_delta' => $snapshot->word_count - $currentWordCount,
            'trigger'    => 'restore',
            'label'      => "Restauration de {$sourceLabel}",
        ]);

        $scene->update(['content' => $snapshot->content, 'word_count' => $snapshot->word_count]);

        return response()->json([
            'snapshot' => new SceneSnapshotResource($restore),
            'content'  => $snapshot->content,
        ]);
    }

    private static function wordCount(?string $json): int
    {
        if (!$json) return 0;
        $decoded = json_decode($json, true);
        $text    = $decoded ? self::extractText($decoded) : $json;
        $words   = preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    private static function extractText(array $node): string
    {
        if (isset($node['text'])) return $node['text'];
        if (isset($node['content'])) {
            return implode(' ', array_map([self::class, 'extractText'], $node['content']));
        }
        return '';
    }
}
