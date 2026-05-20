<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\StoreCardLinkRequest;
use App\Http\Requests\Card\StoreCardRequest;
use App\Http\Requests\Card\UpdateAttributesRequest;
use App\Http\Requests\Card\UpdateCardLinkRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Services\OpenAiService;
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

        $query = $project->cards()->with(['attributes', 'images']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('q')) {
            $query->where('title', 'ilike', '%' . $request->q . '%');
        }

        $perPage = min($request->integer('per_page', 15), 200);

        return CardResource::collection($query->orderBy('title')->paginate($perPage));
    }

    public function store(StoreCardRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Card::class, $project]);

        $card = $project->cards()->create($request->safe()->only('type', 'title'));

        if ($request->has('attributes')) {
            $card->attributes()->createMany($request->input('attributes'));
        }

        return (new CardResource($card->load('attributes')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Card $card): CardResource
    {
        $this->authorize('view', $card);

        $card->load('attributes', 'links.linkedCard', 'keywords', 'notes', 'images');

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
        $card->attributes()->createMany($request->input('attributes'));

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

    public function updateLink(UpdateCardLinkRequest $request, Card $card, CardLink $link): CardLinkResource
    {
        $this->authorize('update', $card);

        $link->update($request->validated());

        return new CardLinkResource($link->load('linkedCard'));
    }

    public function destroyLink(Card $card, CardLink $link): JsonResponse
    {
        $this->authorize('update', $card);

        $link->delete();

        return response()->json(null, 204);
    }

    public function integrateLoreNote(Request $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $request->validate(['note_id' => ['required', 'uuid']]);

        $note = $card->notes()->findOrFail($request->note_id);

        $openAi = app(OpenAiService::class);

        $systemPrompt = <<<'PROMPT'
Tu es un assistant pour un auteur de roman. Tu vas enrichir le lore (biographie/description narrative) d'un personnage en y intégrant de manière fluide et naturelle les informations contenues dans une note.

Règles :
- Retourne uniquement le nouveau texte du lore, sans introduction, sans balise, sans explication.
- Le ton doit rester littéraire et cohérent.
- Si le lore actuel est vide, crée un texte narratif à partir de la note.
- Intègre toutes les informations de la note sans en perdre aucune.
PROMPT;

        $currentLore = $card->lore ?? '';
        $userPrompt  = "=== Lore actuel ===\n{$currentLore}\n\n=== Note à intégrer ===\n{$note->body}";

        $newLore = $openAi->summarize($systemPrompt, $userPrompt, 'gpt-4o-mini', 2000);

        $card->update(['lore' => $newLore]);
        $note->delete();

        return response()->json(['lore' => $newLore]);
    }

    // --- Scène ↔ Fiche ---

    public function sceneCards(Scene $scene): ResourceCollection
    {
        $this->authorize('view', $scene);

        return CardResource::collection($scene->cards()->with('attributes')->get());
    }

    public function byKeywordsInScene(Scene $scene): ResourceCollection
    {
        $this->authorize('view', $scene);

        $projectId = $scene->chapter->arc->project_id;

        $cards = Card::where('project_id', $projectId)
            ->whereHas('keywords', function ($q) use ($scene) {
                $q->whereHas('occurrences', fn ($q2) => $q2->where('scene_id', $scene->id));
            })
            ->with(['keywords', 'images'])
            ->orderBy('title')
            ->get();

        return CardResource::collection($cards);
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
