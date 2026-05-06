<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelog_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('changelog_id');
            $table->timestamp('read_at');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('changelog_id')->references('id')->on('changelogs')->cascadeOnDelete();
            $table->unique(['user_id', 'changelog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_reads');
    }
};
