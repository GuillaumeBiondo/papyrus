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
            $table->uuid('content_type_id')->nullable()->after('owner_id');
            $table->foreign('content_type_id')
                ->references('id')
                ->on('content_types')
                ->restrictOnDelete();

            $table->json('type_data')->nullable()->after('content_type_id');
        });

        // Point all existing projects to the "novel" content type.
        $novelId = DB::table('content_types')->where('slug', 'novel')->value('id');

        if ($novelId) {
            DB::table('projects')->whereNull('content_type_id')->update([
                'content_type_id' => $novelId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['content_type_id']);
            $table->dropColumn(['content_type_id', 'type_data']);
        });
    }
};
