<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->boolean('is_active')->default(true);
            $table->enum('target', ['selection', 'all', 'both'])->default('all');
            $table->boolean('has_extra_input')->default(false);
            $table->string('extra_input_label', 200)->nullable();
            $table->string('extra_input_placeholder', 500)->nullable();
            $table->text('pre_prompt');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('ai_verifications')->insert([
            [
                'label'                  => 'Style',
                'is_active'              => true,
                'target'                 => 'all',
                'has_extra_input'        => false,
                'extra_input_label'      => null,
                'extra_input_placeholder'=> null,
                'pre_prompt'             => "Tu es un éditeur littéraire expert. Analyse le texte suivant et identifie les problèmes de style : tournures maladroites, phrases qui n'ont pas de sens, formulations trop familières ou vulgaires, répétitions de structures. Pour chaque problème détecté, propose une reformulation plus élégante et adaptée à un roman littéraire.",
                'sort_order'             => 0,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'label'                  => 'Répétitions',
                'is_active'              => true,
                'target'                 => 'all',
                'has_extra_input'        => false,
                'extra_input_label'      => null,
                'extra_input_placeholder'=> null,
                'pre_prompt'             => "Tu es un éditeur littéraire expert. Analyse le texte suivant et identifie les répétitions de mots ou d'expressions dans un périmètre proche (même phrase ou phrases adjacentes). Pour chaque répétition détectée, propose un synonyme ou une reformulation alternative qui enrichit le style sans trahir le sens.",
                'sort_order'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'label'                  => 'Reformuler',
                'is_active'              => true,
                'target'                 => 'selection',
                'has_extra_input'        => true,
                'extra_input_label'      => 'Consigne (optionnel)',
                'extra_input_placeholder'=> 'Ex : rends cette phrase plus dynamique, plus poétique, plus sobre…',
                'pre_prompt'             => "Tu es un éditeur littéraire expert. Reformule le texte sélectionné en respectant le style et le ton de l'auteur. Propose une version améliorée de chaque passage sélectionné.",
                'sort_order'             => 2,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_verifications');
    }
};
