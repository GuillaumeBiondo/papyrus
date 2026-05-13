<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('workshops')->insert([
            [
                'key'         => 'writing',
                'label'       => 'Atelier Écriture',
                'description' => 'Rédaction des scènes, structure narrative, fiches personnages.',
                'is_active'   => true,
                'is_premium'  => false,
                'sort_order'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'editing',
                'label'       => 'Atelier Édition',
                'description' => 'Révision approfondie, mise en page, préparation à la publication.',
                'is_active'   => false,
                'is_premium'  => true,
                'sort_order'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
