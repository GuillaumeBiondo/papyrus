<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chapter_id');
            $table->foreign('chapter_id')->references('id')->on('chapters')->cascadeOnDelete();
            $table->string('title', 200);
            $table->longText('content')->nullable();
            $table->enum('status', ['idea', 'draft', 'revised', 'final'])->default('idea');
            $table->integer('order')->default(0);
            $table->integer('word_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
