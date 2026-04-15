<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\ReorderChapterRequest;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ChapterController extends Controller
{
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('view', $project);

        $chapters = $project->chapters()->with('scenes')->paginate(15);

        return ChapterResource::collection($chapters);
    }

    public function store(StoreChapterRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $chapter = $project->chapters()->create($request->validated());

        return (new ChapterResource($chapter))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): ChapterResource
    {
        $this->authorize('update', $chapter->project);

        $chapter->update($request->validated());

        return new ChapterResource($chapter);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorize('delete', $chapter->project);

        $chapter->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderChapterRequest $request): JsonResponse
    {
        foreach ($request->items as $item) {
            Chapter::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }
}
