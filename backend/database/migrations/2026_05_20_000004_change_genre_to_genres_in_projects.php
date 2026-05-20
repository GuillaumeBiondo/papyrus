<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('genres_new')->nullable()->after('title');
        });

        DB::table('projects')->whereNotNull('genre')->orderBy('id')->each(function ($project) {
            DB::table('projects')->where('id', $project->id)->update([
                'genres_new' => json_encode([$project->genre]),
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('genre');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('genres_new', 'genres');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('genre_old', 200)->nullable()->after('title');
        });

        DB::table('projects')->whereNotNull('genres')->orderBy('id')->each(function ($project) {
            $arr = json_decode($project->genres, true);
            DB::table('projects')->where('id', $project->id)->update([
                'genre_old' => is_array($arr) && count($arr) > 0 ? implode(', ', $arr) : null,
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('genres');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('genre_old', 'genre');
        });
    }
};
