<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scene_id');
            $table->foreign('scene_id')->references('id')->on('scenes')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->integer('anchor_start')->nullable();
            $table->integer('anchor_end')->nullable();
            $table->text('body');
            $table->enum('type', ['inline', 'global'])->default('global');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annotations');
    }
};
