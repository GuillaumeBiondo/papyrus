<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scene_cards', function (Blueprint $table) {
            $table->uuid('scene_id');
            $table->foreign('scene_id')->references('id')->on('scenes')->cascadeOnDelete();
            $table->uuid('card_id');
            $table->foreign('card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->primary(['scene_id', 'card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_cards');
    }
};
