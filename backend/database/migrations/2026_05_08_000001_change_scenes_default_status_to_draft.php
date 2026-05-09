<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE scenes ALTER COLUMN status SET DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE scenes ALTER COLUMN status SET DEFAULT 'idea'");
    }
};
