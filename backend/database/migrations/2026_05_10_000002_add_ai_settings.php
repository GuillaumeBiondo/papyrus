<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key'        => 'ai.openai_model',
                'value'      => json_encode('gpt-4o-mini'),
                'label'      => 'Modèle OpenAI (ex: gpt-4o-mini, gpt-4o)',
                'group'      => 'ai',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'ai.response_format',
                'value'      => json_encode(
                    "Réponds en JSON uniquement, sans markdown, avec exactement ce format :\n"
                    . "{\"changes\": [{\"originalText\": \"extrait exact du texte original\", \"suggestedText\": \"version corrigée\", \"explanation\": \"explication courte\"}]}\n"
                    . "- originalText doit être une citation exacte et verbatim du texte fourni (sensible à la casse et aux espaces)\n"
                    . "- Ne retourne rien d'autre que ce JSON\n"
                    . "- Si aucune correction n'est nécessaire, retourne {\"changes\": []}"
                ),
                'label'      => 'Instructions de format de réponse pour le LLM',
                'group'      => 'ai',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['ai.openai_model', 'ai.response_format'])->delete();
    }
};
