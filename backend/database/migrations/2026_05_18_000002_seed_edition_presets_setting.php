<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->upsert([
            [
                'key'        => 'premium.edition_presets',
                'value'      => '0',
                'label'      => 'Préréglages d\'édition (premium)',
                'group'      => 'premium',
                'updated_at' => now(),
            ],
        ], ['key'], ['value', 'label', 'group', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'premium.edition_presets')->delete();
    }
};
