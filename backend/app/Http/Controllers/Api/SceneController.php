<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scene\ReorderSceneRequest;
use App\Http\Requests\Scene\StoreSceneRequest;
use App\Http\Requests\Scene\UpdateSceneRequest;
use App\Http\Resources\SceneResource;
use App\Models\CardKeyword;
use App\Models\Chapter;
use App\Models\KeywordOccurrence;
use App\Models\Scene;
use App\Services\KeywordScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SceneController extends Controller
{
    public function index(Chapter $chapter): ResourceCollection
    {
        $this->authorize('view', $chapter->project);

        $scenes = $chapter->scenes()->paginate(15);

        return SceneResource::collection($scenes);
    }

    public function store(StoreSceneRequest $request, Chapter $chapter): JsonResponse
    {
        $this->authorize('create', [$chapter->project]);

        $scene = $chapter->scenes()->create($request->validated());

        return (new SceneResource($scene))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Scene $scene): SceneResource
    {
        $this->authorize('view', $scene);

        $scene->load('cards', 'annotations.user', 'notes');

        return new SceneResource($scene);
    }

    public function update(UpdateSceneRequest $request, Scene $scene): SceneResource
    {
        $this->authorize('update', $scene);

        // Exclude null values so nullable fields (e.g. status) don't overwrite DB values
        $data = array_filter($request->validated(), fn($v) => !is_null($v));
        $scene->update($data);

        if ($request->has('content')) {
            $this->scanKeywordsForScene($scene);
        }

        return new SceneResource($scene);
    }

    public function destroy(Scene $scene): JsonResponse
    {
        $this->authorize('delete', $scene);

        $scene->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderSceneRequest $request): JsonResponse
    {
        foreach ($request->items as $item) {
            $data = ['order' => $item['order']];
            if (isset($item['chapter_id'])) $data['chapter_id'] = $item['chapter_id'];
            Scene::where('id', $item['id'])->update($data);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }

    private function scanKeywordsForScene(Scene $scene): void
    {
        $projectId = $scene->chapter->arc->project_id;

        $keywords = CardKeyword::whereHas('card', fn ($q) =>
            $q->where('project_id', $projectId)
        )->get();

        if ($keywords->isEmpty()) {
            return;
        }

        $scene->keywordOccurrences()->delete();

        $scanner     = app(KeywordScanner::class);
        $occurrences = [];

        foreach ($keywords as $kw) {
            $occurrences = array_merge($occurrences, $scanner->scan($scene, $kw));
        }

        if (! empty($occurrences)) {
            KeywordOccurrence::insert($occurrences);
        }
    }
}
