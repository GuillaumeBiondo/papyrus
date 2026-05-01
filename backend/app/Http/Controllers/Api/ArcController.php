<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arc\StoreArcRequest;
use App\Http\Requests\Arc\UpdateArcRequest;
use App\Http\Resources\ArcResource;
use App\Models\Arc;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ArcController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $arcs = $project->arcs()->with('chapters.scenes')->get();

        return response()->json([
            'data' => ArcResource::collection($arcs),
        ]);
    }

    public function store(StoreArcRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $arc = $project->arcs()->create([
            ...$request->validated(),
            'order' => $request->input('order', $project->arcs()->count()),
        ]);

        return (new ArcResource($arc))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateArcRequest $request, Arc $arc): ArcResource
    {
        $this->authorize('update', $arc->project);

        $arc->update($request->validated());

        return new ArcResource($arc);
    }

    public function destroy(Arc $arc): JsonResponse
    {
        $this->authorize('delete', $arc->project);

        $arc->delete();

        return response()->json(null, 204);
    }

    public function reorder(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        foreach (request()->input('items', []) as $item) {
            Arc::where('id', $item['id'])->where('project_id', $project->id)
               ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }
}
