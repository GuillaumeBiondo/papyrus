<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->upsert([
            [
                'key'        => 'premium.project_limit',
                'value'      => json_encode(1),
                'label'      => 'Nombre de projets (compte gratuit)',
                'group'      => 'premium',
                'updated_at' => now(),
            ],
        ], ['key'], ['value', 'label', 'group', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['premium.project_limit'])->delete();
    }
};
