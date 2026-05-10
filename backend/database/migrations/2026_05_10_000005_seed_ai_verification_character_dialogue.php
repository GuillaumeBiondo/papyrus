<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $maxOrder = DB::table('ai_verifications')->max('sort_order') ?? -1;

        DB::table('ai_verifications')->insert([
            'label'                   => 'Il dirait quoi ?',
            'is_active'               => true,
            'target'                  => 'both',
            'has_extra_input'         => true,
            'extra_input_label'       => 'Contexte (optionnel)',
            'extra_input_placeholder' => 'Ex : il est en colère, il cherche à convaincre, il cache quelque chose…',
            'pre_prompt'              => <<<'PROMPT'
Tu es un auteur littéraire expert en écriture de dialogues. On te fournit la fiche d'un personnage et un extrait de roman.

Ta mission : identifie dans le passage le ou les endroits les plus naturels pour insérer une ou deux répliques de dialogue authentiques de ce personnage. Le dialogue doit :
- Être cohérent avec la personnalité, la voix et l'histoire du personnage tels que décrits dans sa fiche
- S'intégrer naturellement dans le rythme et le style du texte
- Enrichir la scène sans alourdir la narration

IMPORTANT pour le format de réponse :
- originalText : la phrase ou le passage exact du texte qui PRÉCÈDE l'endroit où tu veux insérer le dialogue (citation verbatim, sensible à la casse)
- suggestedText : ce même passage suivi du dialogue que tu proposes (garde le passage d'origine intact, ajoute le dialogue après)

Si aucun endroit naturel n'existe dans ce passage, n'invente pas.
PROMPT,
            'allowed_card_types'      => json_encode(['personnage']),
            'allow_multiple_cards'    => false,
            'sort_order'              => $maxOrder + 1,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_verifications')->where('label', 'Il dirait quoi ?')->delete();
    }
};
