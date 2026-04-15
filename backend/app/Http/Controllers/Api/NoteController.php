<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Card;
use App\Models\Note;
use App\Models\Scene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteController extends Controller
{
    public function indexForScene(Scene $scene): ResourceCollection
    {
        $this->authorize('view', $scene);

        return NoteResource::collection($scene->notes()->paginate(15));
    }

    public function storeForScene(StoreNoteRequest $request, Scene $scene): JsonResponse
    {
        $this->authorize('create', [$scene->chapter->project]);

        $note = $scene->notes()->create($request->validated());

        return (new NoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    public function indexForCard(Card $card): ResourceCollection
    {
        $this->authorize('view', $card);

        return NoteResource::collection($card->notes()->paginate(15));
    }

    public function storeForCard(StoreNoteRequest $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $note = $card->notes()->create($request->validated());

        return (new NoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateNoteRequest $request, Note $note): NoteResource
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return new NoteResource($note);
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(null, 204);
    }
}
