<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arcs', function (Blueprint $table) {
            $table->timestamp('summary_generated_at')->nullable()->after('summary');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->timestamp('summary_generated_at')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('arcs', function (Blueprint $table) {
            $table->dropColumn('summary_generated_at');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('summary_generated_at');
        });
    }
};
