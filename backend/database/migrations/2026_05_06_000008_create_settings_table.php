<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->string('label', 200)->nullable();
            $table->string('group', 100)->default('general');
            $table->timestamps();
        });

        DB::table('settings')->insert([
            [
                'key'        => 'registration.open',
                'value'      => json_encode(false),
                'label'      => 'Inscription ouverte',
                'group'      => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'projects.max_per_user',
                'value'      => json_encode(null),
                'label'      => 'Maximum de projets par utilisateur (null = illimité)',
                'group'      => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
