<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_enrich_types', function (Blueprint $table) {
            $table->json('allowed_content_types')->nullable()->after('is_premium');
        });
    }

    public function down(): void
    {
        Schema::table('ai_enrich_types', function (Blueprint $table) {
            $table->dropColumn('allowed_content_types');
        });
    }
};
