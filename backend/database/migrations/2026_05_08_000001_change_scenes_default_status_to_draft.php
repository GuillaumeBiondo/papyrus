<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->enum('status', ['idea', 'draft', 'revised', 'final'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->enum('status', ['idea', 'draft', 'revised', 'final'])->default('idea')->change();
        });
    }
};
