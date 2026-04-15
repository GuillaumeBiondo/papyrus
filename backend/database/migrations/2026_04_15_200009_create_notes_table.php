<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('noteable_type');
            $table->uuid('noteable_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['noteable_type', 'noteable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
