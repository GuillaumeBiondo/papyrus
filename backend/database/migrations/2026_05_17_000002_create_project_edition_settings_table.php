<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_edition_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->json('settings');
            $table->timestamps();

            $table->unique('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_edition_settings');
    }
};
