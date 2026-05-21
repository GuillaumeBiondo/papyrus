<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genre_categories', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name');
            $table->string('color', 20);
            $table->string('light_color', 20);
            $table->string('text_color', 20);
            $table->json('adjacent_categories')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('genre_category_id', 50);
            $table->string('name');
            $table->json('bridges')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('genre_category_id')
                  ->references('id')
                  ->on('genre_categories')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
        Schema::dropIfExists('genre_categories');
    }
};
