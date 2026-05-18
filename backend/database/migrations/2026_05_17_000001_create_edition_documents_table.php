<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title', 255)->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_documents');
    }
};
