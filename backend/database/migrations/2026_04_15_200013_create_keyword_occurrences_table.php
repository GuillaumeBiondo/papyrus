<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('card_keyword_id');
            $table->foreign('card_keyword_id')->references('id')->on('card_keywords')->cascadeOnDelete();
            $table->uuid('scene_id');
            $table->foreign('scene_id')->references('id')->on('scenes')->cascadeOnDelete();
            $table->integer('position_start');
            $table->integer('position_end');
            $table->string('context_excerpt', 300)->nullable();
            $table->timestamp('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_occurrences');
    }
};
