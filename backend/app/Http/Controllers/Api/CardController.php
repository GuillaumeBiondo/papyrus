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

        $card->loadMissing(['attributes', 'links.linkedCard']);
        $note = $card->notes()->findOrFail($request->note_id);

        $openAi = app(OpenAiService::class);

        $systemPrompt = <<<'PROMPT'
Tu es un assistant d'écriture minimaliste. Ta seule tâche est d'insérer le contenu d'une note dans un lore existant.

RÈGLES ABSOLUES — toute violation est une erreur :
1. Retourne uniquement le texte du lore résultant, sans introduction, sans balise, sans commentaire.
2. N'invente RIEN. Chaque phrase du résultat doit provenir mot pour mot de la note ou du lore existant. Aucun détail supplémentaire, aucune métaphore, aucun remplissage.
3. Si le lore est vide : reformule la note en français correct et fluide — chaque information de la note doit apparaître, et rien d'autre.
4. Si le lore n'est pas vide : insère les informations de la note à l'endroit le plus cohérent du lore, en modifiant seulement la liaison syntaxique (une virgule, un "et", une nouvelle phrase). Ne réécris pas les parties existantes du lore.
5. Ne supprime aucune information existante du lore.
6. Le contexte de la fiche (nom, type, attributs) est fourni pour aider le placement — pas pour inventer du contenu.
PROMPT;

        // Build card context
        $cardContext = "=== Fiche : {$card->title} ({$card->type}) ===\n";
        foreach ($card->attributes as $attr) {
            $val = is_string($attr->value) ? $attr->value : json_encode($attr->value);
            $val = strip_tags($val);
            if ($val !== '' && $val !== 'null') {
                $cardContext .= "- {$attr->key} : {$val}\n";
            }
        }
        foreach ($card->links as $link) {
            $linkedTitle = $link->linkedCard?->title ?? '?';
            $cardContext .= "- Liaison : {$linkedTitle}";
            if (!empty($link->label)) {
                $cardContext .= " ({$link->label})";
            }
            $cardContext .= "\n";
        }

        $currentLore = $card->lore ?? '';
        $userPrompt  = "{$cardContext}\n=== Lore actuel ===\n{$currentLore}\n\n=== Note à intégrer ===\n{$note->body}";

        $newLore = $openAi->summarize($systemPrompt, $userPrompt, 'gpt-4o-mini', 2000, 0.1);

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
