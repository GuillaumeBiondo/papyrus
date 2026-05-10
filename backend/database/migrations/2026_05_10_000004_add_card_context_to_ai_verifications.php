<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_verifications', function (Blueprint $table) {
            $table->json('allowed_card_types')->nullable()->after('pre_prompt');
            $table->boolean('allow_multiple_cards')->default(false)->after('allowed_card_types');
        });
    }

    public function down(): void
    {
        Schema::table('ai_verifications', function (Blueprint $table) {
            $table->dropColumn(['allowed_card_types', 'allow_multiple_cards']);
        });
    }
};
