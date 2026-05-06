<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->json('type_schema')->nullable();
            $table->timestamps();
        });

        // Seed the initial "novel" content type so existing projects can reference it.
        DB::table('content_types')->insert([
            'id'          => Str::uuid()->toString(),
            'name'        => 'Roman',
            'slug'        => 'novel',
            'is_active'   => true,
            'type_schema' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_types');
    }
};
