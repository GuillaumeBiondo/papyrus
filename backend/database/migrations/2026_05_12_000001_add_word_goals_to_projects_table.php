<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('word_goal_arc')->nullable()->after('target_words');
            $table->unsignedInteger('word_goal_chapter')->nullable()->after('word_goal_arc');
            $table->unsignedInteger('word_goal_scene')->nullable()->after('word_goal_chapter');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['word_goal_arc', 'word_goal_chapter', 'word_goal_scene']);
        });
    }
};
