<?php

namespace Database\Seeders;

use App\Models\AvailableFont;
use Illuminate\Database\Seeder;

class AvailableFontsSeeder extends Seeder
{
    public function run(): void
    {
        $fonts = [
            // Serif — corps de texte long
            ['name' => 'Crimson Text',       'google_font_slug' => 'Crimson+Text',       'css_family' => '"Crimson Text", serif',       'category' => 'serif',     'sort_order' => 0],
            ['name' => 'Lora',               'google_font_slug' => 'Lora',               'css_family' => '"Lora", serif',               'category' => 'serif',     'sort_order' => 1],
            ['name' => 'Libre Baskerville',  'google_font_slug' => 'Libre+Baskerville',  'css_family' => '"Libre Baskerville", serif',  'category' => 'serif',     'sort_order' => 2],
            ['name' => 'EB Garamond',        'google_font_slug' => 'EB+Garamond',        'css_family' => '"EB Garamond", serif',         'category' => 'serif',     'sort_order' => 3],
            ['name' => 'Merriweather',       'google_font_slug' => 'Merriweather',       'css_family' => '"Merriweather", serif',        'category' => 'serif',     'sort_order' => 4],
            ['name' => 'Spectral',           'google_font_slug' => 'Spectral',           'css_family' => '"Spectral", serif',            'category' => 'serif',     'sort_order' => 5],
            ['name' => 'Cormorant Garamond', 'google_font_slug' => 'Cormorant+Garamond', 'css_family' => '"Cormorant Garamond", serif',  'category' => 'serif',     'sort_order' => 6],
            ['name' => 'Playfair Display',   'google_font_slug' => 'Playfair+Display',   'css_family' => '"Playfair Display", serif',    'category' => 'serif',     'sort_order' => 7],
            ['name' => 'Gentium Plus',       'google_font_slug' => 'Gentium+Plus',       'css_family' => '"Gentium Plus", serif',        'category' => 'serif',     'sort_order' => 8],

            // Sans-serif
            ['name' => 'Inter',              'google_font_slug' => 'Inter',              'css_family' => '"Inter", sans-serif',          'category' => 'sans-serif','sort_order' => 9],
            ['name' => 'Source Sans 3',      'google_font_slug' => 'Source+Sans+3',      'css_family' => '"Source Sans 3", sans-serif',  'category' => 'sans-serif','sort_order' => 10],
            ['name' => 'Nunito',             'google_font_slug' => 'Nunito',             'css_family' => '"Nunito", sans-serif',          'category' => 'sans-serif','sort_order' => 11],

            // Monospace
            ['name' => 'Courier Prime',      'google_font_slug' => 'Courier+Prime',      'css_family' => '"Courier Prime", monospace',   'category' => 'monospace', 'sort_order' => 12],
            ['name' => 'JetBrains Mono',     'google_font_slug' => 'JetBrains+Mono',     'css_family' => '"JetBrains Mono", monospace',  'category' => 'monospace', 'sort_order' => 13],
        ];

        foreach ($fonts as $font) {
            AvailableFont::firstOrCreate(
                ['google_font_slug' => $font['google_font_slug']],
                $font,
            );
        }
    }
}
