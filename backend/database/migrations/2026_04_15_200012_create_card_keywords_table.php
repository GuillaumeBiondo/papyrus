<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('card_id');
            $table->foreign('card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->string('keyword', 100);
            $table->boolean('case_sensitive')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_keywords');
    }
};
