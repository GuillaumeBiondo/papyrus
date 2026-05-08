<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key'        => 'maintenance.enabled',
                'value'      => json_encode(false),
                'label'      => 'Maintenance activée',
                'group'      => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'maintenance.start_at',
                'value'      => json_encode(null),
                'label'      => 'Début de maintenance programmé',
                'group'      => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'maintenance.end_at',
                'value'      => json_encode(null),
                'label'      => 'Fin de maintenance programmée',
                'group'      => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'maintenance.message',
                'value'      => json_encode('Le service est temporairement en maintenance. Merci de réessayer plus tard.'),
                'label'      => 'Message affiché lors de la maintenance',
                'group'      => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'maintenance.warning_message',
                'value'      => json_encode(''),
                'label'      => 'Message d\'avertissement (maintenance à venir)',
                'group'      => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'maintenance.enabled',
            'maintenance.start_at',
            'maintenance.end_at',
            'maintenance.message',
            'maintenance.warning_message',
        ])->delete();
    }
};
