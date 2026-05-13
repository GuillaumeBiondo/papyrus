<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiEnrichController extends Controller
{
    private const TYPES = ['definition', 'synonymes', 'metaphores', 'champ_lexical', 'registre'];

    public function enrich(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'text' => ['required', 'string', 'max:300'],
        ]);

        ['system' => $system, 'user' => $user] = $this->buildPrompt($data['type'], $data['text']);

        $model   = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $service = new OpenAiService();
        $items   = $service->enrich($system, $user, $model);

        return response()->json(['items' => $items]);
    }

    /**
     * @return array{system: string, user: string}
     */
    private function buildPrompt(string $type, string $text): array
    {
        $json = 'Réponds UNIQUEMENT en JSON avec la clé "items" contenant un tableau d\'objets {"text": string, "detail": string}. Aucun autre contenu en dehors du JSON.';
        $mot  = "« {$text} »";

        return match ($type) {
            'definition' => [
                'system' => "Tu es un lexicographe français. {$json} \"text\" est la définition, \"detail\" est la catégorie grammaticale ou le registre (ex : \"nom féminin\", \"sens figuré\"). Donne 2 à 4 définitions pertinentes, du sens propre au sens figuré.",
                'user'   => "Définitions de {$mot}",
            ],
            'synonymes' => [
                'system' => "Tu es un linguiste français spécialisé en stylistique. {$json} \"text\" est le synonyme, \"detail\" est une courte indication de nuance ou registre (peut être vide). Donne entre 5 et 15 synonymes variés, des plus proches aux plus éloignés sémantiquement, avec des registres différents.",
                'user'   => "Synonymes de {$mot}",
            ],
            'metaphores' => [
                'system' => "Tu es un écrivain poète français expert en figures de style. {$json} \"text\" est la métaphore ou comparaison complète, directement utilisable dans un texte littéraire. \"detail\" est l'image ou le registre évoqué (poétique, surréaliste, classique, contemporain…). Propose entre 3 et 6 métaphores originales et évocatrices.",
                'user'   => "Métaphores littéraires pour {$mot}",
            ],
            'champ_lexical' => [
                'system' => "Tu es un linguiste français. {$json} \"text\" est le mot du champ lexical, \"detail\" est une brève explication de son lien sémantique avec le terme initial (peut être vide). Donne entre 8 et 15 mots appartenant au même univers sémantique ou champ lexical.",
                'user'   => "Champ lexical de {$mot}",
            ],
            'registre' => [
                'system' => "Tu es un linguiste français expert en registres de langue. {$json} \"text\" est une façon d'exprimer le même sens, \"detail\" est le registre précis (populaire, familier, courant, soutenu, littéraire, argotique, technique, verlan…). Couvre au moins 5 registres différents.",
                'user'   => "Comment exprimer {$mot} dans différents registres de langue ?",
            ],
            default => throw new \InvalidArgumentException("Type inconnu : {$type}"),
        };
    }
}
