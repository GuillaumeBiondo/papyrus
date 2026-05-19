<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->upsert([
            [
                'key'        => 'ai.openai_voice_model',
                'value'      => json_encode('whisper-1'),
                'label'      => 'Modèle de dictée vocale',
                'group'      => 'ai',
                'updated_at' => now(),
            ],
        ], ['key'], ['value', 'label', 'group', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'ai.openai_voice_model')->delete();
    }
};
