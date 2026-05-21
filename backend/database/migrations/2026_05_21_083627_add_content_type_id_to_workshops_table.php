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
        Schema::table('workshops', function (Blueprint $table) {
            $table->uuid('content_type_id')->nullable()->after('key');
            $table->foreign('content_type_id')->references('id')->on('content_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropForeign(['content_type_id']);
            $table->dropColumn('content_type_id');
        });
    }
};
