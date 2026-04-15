<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('card_id');
            $table->foreign('card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->uuid('linked_card_id');
            $table->foreign('linked_card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_links');
    }
};
