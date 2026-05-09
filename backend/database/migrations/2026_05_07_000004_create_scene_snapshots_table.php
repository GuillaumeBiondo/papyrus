<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scene_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('scene_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('content');
            $table->unsignedInteger('word_count')->default(0);
            $table->integer('word_delta')->default(0);
            $table->enum('trigger', ['auto', 'manual', 'restore'])->default('auto');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['scene_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_snapshots');
    }
};
