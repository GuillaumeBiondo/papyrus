<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(AvailableFontsSeeder::class);
        $this->call(GenreSeeder::class);

        \App\Models\Setting::firstOrCreate(
            ['key' => 'snapshot_interval_words'],
            ['value' => '100', 'label' => 'Auto-snapshot (mots entre chaque sauvegarde)', 'group' => 'editor']
        );
    }
}
