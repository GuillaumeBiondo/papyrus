<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_enrich_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_key', 50)->unique();
            $table->string('label', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('system_prompt');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $json = 'Réponds UNIQUEMENT en JSON avec la clé "items" contenant un tableau d\'objets {"text": string, "detail": string}. Aucun autre contenu en dehors du JSON. L\'utilisateur te fournira un mot ou une expression entre guillemets.';

        DB::table('ai_enrich_types')->insert([
            [
                'type_key'    => 'definition',
                'label'       => 'Définition',
                'description' => '2 à 4 définitions du sens propre au sens figuré, avec catégorie grammaticale.',
                'is_active'   => true,
                'system_prompt' => "Tu es un lexicographe français. {$json} \"text\" est la définition, \"detail\" est la catégorie grammaticale ou le registre (ex : \"nom féminin\", \"sens figuré\"). Donne 2 à 4 définitions pertinentes, du sens propre au sens figuré.",
                'sort_order'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'type_key'    => 'synonymes',
                'label'       => 'Synonymes',
                'description' => '5 à 15 synonymes variés avec nuances de registre.',
                'is_active'   => true,
                'system_prompt' => "Tu es un linguiste français spécialisé en stylistique. {$json} \"text\" est le synonyme, \"detail\" est une courte indication de nuance ou registre (peut être vide). Donne entre 5 et 15 synonymes variés, des plus proches aux plus éloignés sémantiquement, avec des registres différents.",
                'sort_order'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'type_key'    => 'metaphores',
                'label'       => 'Métaphores',
                'description' => '3 à 6 métaphores littéraires directement utilisables en texte.',
                'is_active'   => true,
                'system_prompt' => "Tu es un écrivain poète français expert en figures de style. {$json} \"text\" est la métaphore ou comparaison complète, directement utilisable dans un texte littéraire. \"detail\" est l'image ou le registre évoqué (poétique, surréaliste, classique, contemporain…). Propose entre 3 et 6 métaphores originales et évocatrices.",
                'sort_order'  => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'type_key'    => 'champ_lexical',
                'label'       => 'Champ lexical',
                'description' => '8 à 15 mots du même univers sémantique.',
                'is_active'   => true,
                'system_prompt' => "Tu es un linguiste français. {$json} \"text\" est le mot du champ lexical, \"detail\" est une brève explication de son lien sémantique avec le terme initial (peut être vide). Donne entre 8 et 15 mots appartenant au même univers sémantique ou champ lexical.",
                'sort_order'  => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'type_key'    => 'registre',
                'label'       => 'Registre de langue',
                'description' => 'Le même sens décliné en populaire / courant / soutenu / littéraire.',
                'is_active'   => true,
                'system_prompt' => "Tu es un linguiste français expert en registres de langue. {$json} \"text\" est une façon d'exprimer le même sens, \"detail\" est le registre précis (populaire, familier, courant, soutenu, littéraire, argotique, technique, verlan…). Couvre au moins 5 registres différents.",
                'sort_order'  => 4,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_enrich_types');
    }
};
