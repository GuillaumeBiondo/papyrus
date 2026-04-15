<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annotation_cards', function (Blueprint $table) {
            $table->uuid('annotation_id');
            $table->foreign('annotation_id')->references('id')->on('annotations')->cascadeOnDelete();
            $table->uuid('card_id');
            $table->foreign('card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->primary(['annotation_id', 'card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annotation_cards');
    }
};
