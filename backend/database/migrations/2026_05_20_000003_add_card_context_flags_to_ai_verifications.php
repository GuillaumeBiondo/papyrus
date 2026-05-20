<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_verifications', function (Blueprint $table) {
            $table->boolean('include_card_lore')->default(false)->after('allow_multiple_cards');
            $table->boolean('include_card_links')->default(false)->after('include_card_lore');
        });
    }

    public function down(): void
    {
        Schema::table('ai_verifications', function (Blueprint $table) {
            $table->dropColumn(['include_card_lore', 'include_card_links']);
        });
    }
};
