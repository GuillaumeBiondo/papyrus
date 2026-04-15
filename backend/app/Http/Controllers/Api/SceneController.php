<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scene\ReorderSceneRequest;
use App\Http\Requests\Scene\StoreSceneRequest;
use App\Http\Requests\Scene\UpdateSceneRequest;
use App\Http\Resources\SceneResource;
use App\Models\Chapter;
use App\Models\Scene;
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

        $scene->update($request->validated());

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
            Scene::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }
}
