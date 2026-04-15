<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\StoreCardLinkRequest;
use App\Http\Requests\Card\StoreCardRequest;
use App\Http\Requests\Card\UpdateAttributesRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Http\Resources\CardLinkResource;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\CardAttribute;
use App\Models\CardLink;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CardController extends Controller
{
    public function index(Request $request, Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Card::class, $project]);

        $query = $project->cards()->with('attributes');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('q')) {
            $query->where('title', 'ilike', '%' . $request->q . '%');
        }

        return CardResource::collection($query->paginate(15));
    }

    public function store(StoreCardRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Card::class, $project]);

        $card = $project->cards()->create($request->safe()->only('type', 'title'));

        if ($request->has('attributes')) {
            $card->attributes()->createMany($request->attributes);
        }

        return (new CardResource($card->load('attributes')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Card $card): CardResource
    {
        $this->authorize('view', $card);

        $card->load('attributes', 'links.linkedCard', 'keywords', 'notes');

        return new CardResource($card);
    }

    public function update(UpdateCardRequest $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $card->update($request->validated());

        return new CardResource($card);
    }

    public function destroy(Card $card): JsonResponse
    {
        $this->authorize('delete', $card);

        $card->delete();

        return response()->json(null, 204);
    }

    // --- Attributs ---

    public function updateAttributes(UpdateAttributesRequest $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $card->attributes()->delete();
        $card->attributes()->createMany($request->attributes);

        return new CardResource($card->load('attributes'));
    }

    // --- Liens ---

    public function links(Card $card): ResourceCollection
    {
        $this->authorize('view', $card);

        return CardLinkResource::collection($card->links()->with('linkedCard')->get());
    }

    public function storeLink(StoreCardLinkRequest $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $link = $card->links()->create($request->validated());

        return (new CardLinkResource($link->load('linkedCard')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyLink(Card $card, CardLink $link): JsonResponse
    {
        $this->authorize('update', $card);

        $link->delete();

        return response()->json(null, 204);
    }

    // --- Scène ↔ Fiche ---

    public function sceneCards(Scene $scene): ResourceCollection
    {
        $this->authorize('view', $scene);

        return CardResource::collection($scene->cards()->with('attributes')->get());
    }

    public function attachToScene(Scene $scene, Card $card): JsonResponse
    {
        $this->authorize('update', $scene);

        $scene->cards()->syncWithoutDetaching([$card->id]);

        return response()->json(['message' => 'Fiche attachée.'], 201);
    }

    public function detachFromScene(Scene $scene, Card $card): JsonResponse
    {
        $this->authorize('update', $scene);

        $scene->cards()->detach($card->id);

        return response()->json(null, 204);
    }
}
