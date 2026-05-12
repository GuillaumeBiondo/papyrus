<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key'        => 'word_goals.project',
                'value'      => json_encode(80000),
                'label'      => 'Objectif de mots par défaut — Projet',
                'group'      => 'objectifs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'word_goals.arc',
                'value'      => json_encode(20000),
                'label'      => 'Objectif de mots par défaut — Arc',
                'group'      => 'objectifs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'word_goals.chapter',
                'value'      => json_encode(5000),
                'label'      => 'Objectif de mots par défaut — Chapitre',
                'group'      => 'objectifs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'word_goals.scene',
                'value'      => json_encode(1000),
                'label'      => 'Objectif de mots par défaut — Scène',
                'group'      => 'objectifs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'word_goals.project',
            'word_goals.arc',
            'word_goals.chapter',
            'word_goals.scene',
        ])->delete();
    }
};
