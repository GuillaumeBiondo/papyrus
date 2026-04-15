<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annotation\StoreAnnotationRequest;
use App\Http\Requests\Annotation\UpdateAnnotationRequest;
use App\Http\Resources\AnnotationResource;
use App\Models\Annotation;
use App\Models\Card;
use App\Models\Scene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AnnotationController extends Controller
{
    public function index(Scene $scene): ResourceCollection
    {
        $this->authorize('viewAny', [Annotation::class, $scene]);

        $annotations = $scene->annotations()
            ->with('user', 'cards')
            ->paginate(15);

        return AnnotationResource::collection($annotations);
    }

    public function store(StoreAnnotationRequest $request, Scene $scene): JsonResponse
    {
        $this->authorize('create', [Annotation::class, $scene]);

        $annotation = $scene->annotations()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return (new AnnotationResource($annotation->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAnnotationRequest $request, Annotation $annotation): AnnotationResource
    {
        $this->authorize('update', $annotation);

        $annotation->update($request->validated());

        return new AnnotationResource($annotation->load('user'));
    }

    public function destroy(Annotation $annotation): JsonResponse
    {
        $this->authorize('delete', $annotation);

        $annotation->delete();

        return response()->json(null, 204);
    }

    public function linkCard(Annotation $annotation, Card $card): JsonResponse
    {
        $this->authorize('linkCard', $annotation);

        $annotation->cards()->syncWithoutDetaching([$card->id]);

        return response()->json(['message' => 'Fiche liée.'], 201);
    }

    public function unlinkCard(Annotation $annotation, Card $card): JsonResponse
    {
        $this->authorize('linkCard', $annotation);

        $annotation->cards()->detach($card->id);

        return response()->json(null, 204);
    }
}
