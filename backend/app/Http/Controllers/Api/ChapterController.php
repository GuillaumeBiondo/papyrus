<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\ReorderChapterRequest;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Arc;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;

class ChapterController extends Controller
{
    public function index(Arc $arc): JsonResponse
    {
        $this->authorize('view', $arc->project);

        $chapters = $arc->chapters()->with('scenes')->get();

        return response()->json(['data' => ChapterResource::collection($chapters)]);
    }

    public function store(StoreChapterRequest $request, Arc $arc): JsonResponse
    {
        $this->authorize('update', $arc->project);

        $chapter = $arc->chapters()->create([
            ...$request->validated(),
            'order' => $request->input('order', $arc->chapters()->count()),
        ]);

        return (new ChapterResource($chapter))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): ChapterResource
    {
        $this->authorize('update', $chapter->arc->project);

        $chapter->update($request->validated());

        return new ChapterResource($chapter);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorize('delete', $chapter->arc->project);

        $chapter->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderChapterRequest $request): JsonResponse
    {
        foreach ($request->items as $item) {
            $data = ['order' => $item['order']];
            if (isset($item['arc_id'])) $data['arc_id'] = $item['arc_id'];
            Chapter::where('id', $item['id'])->update($data);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }
}
