<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\GenreCategory;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'id'                  => 'noire',
                'name'                => 'Littérature noire',
                'color'               => '#7c3141',
                'light_color'         => '#fdf1f3',
                'text_color'          => '#581829',
                'adjacent_categories' => ['blanche'],
                'sort_order'          => 0,
                'genres'              => [
                    ['id' => 'polar',                   'name' => 'Polar',                   'bridges' => null, 'sort_order' => 0],
                    ['id' => 'thriller',                'name' => 'Thriller',                'bridges' => null, 'sort_order' => 1],
                    ['id' => 'roman-noir',              'name' => 'Roman noir',              'bridges' => null, 'sort_order' => 2],
                    ['id' => 'true-crime',              'name' => 'True crime',              'bridges' => null, 'sort_order' => 3],
                    ['id' => 'cosy-mystery',            'name' => 'Cosy mystery',            'bridges' => null, 'sort_order' => 4],
                    ['id' => 'polar-terroir',           'name' => 'Polar de terroir',        'bridges' => null, 'sort_order' => 5],
                    ['id' => 'thriller-horrifique',     'name' => 'Thriller horrifique',     'bridges' => null, 'sort_order' => 6],
                    ['id' => 'roman-espionnage',        'name' => "Roman d'espionnage",      'bridges' => null, 'sort_order' => 7],
                    ['id' => 'thriller-psychologique',  'name' => 'Thriller psychologique',  'bridges' => null, 'sort_order' => 8],
                    ['id' => 'thriller-politique',      'name' => 'Thriller politique',      'bridges' => null, 'sort_order' => 9],
                ],
            ],
            [
                'id'                  => 'blanche',
                'name'                => 'Littérature blanche',
                'color'               => '#94a3b8',
                'light_color'         => '#f8fafc',
                'text_color'          => '#475569',
                'adjacent_categories' => ['noire', 'sentimentale', 'imaginaire'],
                'sort_order'          => 1,
                'genres'              => [
                    ['id' => 'roman-apprentissage',      'name' => "Roman d'apprentissage",         'bridges' => null,           'sort_order' => 0],
                    ['id' => 'roman-aventures',          'name' => "Roman d'aventures",             'bridges' => null,           'sort_order' => 1],
                    ['id' => 'autofiction',              'name' => 'Autofiction',                   'bridges' => null,           'sort_order' => 2],
                    ['id' => 'realisme-magique',         'name' => 'Réalisme magique',              'bridges' => ['imaginaire'], 'sort_order' => 3],
                    ['id' => 'roman-historique',         'name' => 'Roman historique',              'bridges' => ['sentimentale'],'sort_order' => 4],
                    ['id' => 'thriller-archeologique',   'name' => 'Thriller archéologique',        'bridges' => ['noire'],      'sort_order' => 5],
                    ['id' => 'roman-secrets-famille',    'name' => 'Roman à secrets de famille',    'bridges' => null,           'sort_order' => 6],
                ],
            ],
            [
                'id'                  => 'sentimentale',
                'name'                => 'Littérature sentimentale',
                'color'               => '#f472b6',
                'light_color'         => '#fdf2f8',
                'text_color'          => '#be185d',
                'adjacent_categories' => ['imaginaire', 'blanche'],
                'sort_order'          => 2,
                'genres'              => [
                    ['id' => 'romance-erotique',    'name' => 'Romance érotique',          'bridges' => null,           'sort_order' => 0],
                    ['id' => 'dark-romance',        'name' => 'Dark romance',              'bridges' => ['noire'],      'sort_order' => 1],
                    ['id' => 'new-romance',         'name' => 'New romance / New adult',   'bridges' => null,           'sort_order' => 2],
                    ['id' => 'romance-lgbt',        'name' => 'Romance LGBT',              'bridges' => null,           'sort_order' => 3],
                    ['id' => 'comedie-romantique',  'name' => 'Comédie romantique',        'bridges' => null,           'sort_order' => 4],
                    ['id' => 'chick-lit',           'name' => 'Chick-lit',                 'bridges' => null,           'sort_order' => 5],
                    ['id' => 'romantasy',           'name' => 'Romantasy',                 'bridges' => ['imaginaire'], 'sort_order' => 6],
                    ['id' => 'feel-good',           'name' => 'Feel good',                 'bridges' => null,           'sort_order' => 7],
                    ['id' => 'romance-policiere',   'name' => 'Romance policière',         'bridges' => ['noire'],      'sort_order' => 8],
                    ['id' => 'romance-historique',  'name' => 'Romance historique',        'bridges' => ['blanche'],    'sort_order' => 9],
                ],
            ],
            [
                'id'                  => 'imaginaire',
                'name'                => "Littérature de l'imaginaire",
                'color'               => '#fb923c',
                'light_color'         => '#fff7ed',
                'text_color'          => '#c2410c',
                'adjacent_categories' => ['sentimentale', 'blanche'],
                'sort_order'          => 3,
                'genres'              => [
                    ['id' => 'fantasy',                  'name' => 'Fantasy',                   'bridges' => null,        'sort_order' => 0],
                    ['id' => 'heroic-fantasy',           'name' => 'Heroic Fantasy',            'bridges' => null,        'sort_order' => 1],
                    ['id' => 'urban-fantasy',            'name' => 'Urban Fantasy',             'bridges' => null,        'sort_order' => 2],
                    ['id' => 'dark-fantasy',             'name' => 'Dark Fantasy',              'bridges' => null,        'sort_order' => 3],
                    ['id' => 'fantasy-epique',           'name' => 'Fantasy épique',            'bridges' => null,        'sort_order' => 4],
                    ['id' => 'fantasy-historique',       'name' => 'Fantasy historique',        'bridges' => ['blanche'], 'sort_order' => 5],
                    ['id' => 'cosy-fantasy',             'name' => 'Cosy Fantasy',              'bridges' => null,        'sort_order' => 6],
                    ['id' => 'grimdark',                 'name' => 'Grimdark',                  'bridges' => null,        'sort_order' => 7],
                    ['id' => 'fantastique',              'name' => 'Fantastique',               'bridges' => null,        'sort_order' => 8],
                    ['id' => 'gothique',                 'name' => 'Gothique',                  'bridges' => null,        'sort_order' => 9],
                    ['id' => 'horreur',                  'name' => 'Horreur',                   'bridges' => null,        'sort_order' => 10],
                    ['id' => 'science-fiction',          'name' => 'Science-fiction',           'bridges' => null,        'sort_order' => 11],
                    ['id' => 'hard-sf',                  'name' => 'Hard SF',                   'bridges' => null,        'sort_order' => 12],
                    ['id' => 'space-opera',              'name' => 'Space opera',               'bridges' => null,        'sort_order' => 13],
                    ['id' => 'cyberpunk',                'name' => 'Cyberpunk',                 'bridges' => null,        'sort_order' => 14],
                    ['id' => 'steampunk',                'name' => 'Steampunk',                 'bridges' => null,        'sort_order' => 15],
                    ['id' => 'solarpunk',                'name' => 'Solarpunk / Hopepunk',      'bridges' => null,        'sort_order' => 16],
                    ['id' => 'dystopie',                 'name' => 'Dystopie',                  'bridges' => null,        'sort_order' => 17],
                    ['id' => 'utopie',                   'name' => 'Utopie',                    'bridges' => null,        'sort_order' => 18],
                    ['id' => 'uchronie',                 'name' => 'Uchronie',                  'bridges' => null,        'sort_order' => 19],
                    ['id' => 'post-apocalyptique',       'name' => 'Post-apocalyptique',        'bridges' => null,        'sort_order' => 20],
                    ['id' => 'climate-fiction',          'name' => 'Climate Fiction',           'bridges' => null,        'sort_order' => 21],
                    ['id' => 'litterature-ecologique',   'name' => 'Littérature écologique',    'bridges' => null,        'sort_order' => 22],
                    ['id' => 'nature-writing',           'name' => 'Nature writing',            'bridges' => null,        'sort_order' => 23],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $genres = $categoryData['genres'];
            unset($categoryData['genres']);

            $category = GenreCategory::updateOrCreate(
                ['id' => $categoryData['id']],
                $categoryData,
            );

            foreach ($genres as $genreData) {
                Genre::updateOrCreate(
                    ['id' => $genreData['id']],
                    array_merge($genreData, ['genre_category_id' => $category->id]),
                );
            }
        }

        // Proximity matrix
        Setting::updateOrCreate(
            ['key' => 'genre.proximity_matrix'],
            [
                'value' => [
                    'noire'        => ['noire' => 1.0, 'blanche' => 0.65, 'sentimentale' => 0.35, 'imaginaire' => 0.25],
                    'blanche'      => ['noire' => 0.65, 'blanche' => 1.0, 'sentimentale' => 0.55, 'imaginaire' => 0.50],
                    'sentimentale' => ['noire' => 0.35, 'blanche' => 0.55, 'sentimentale' => 1.0, 'imaginaire' => 0.75],
                    'imaginaire'   => ['noire' => 0.25, 'blanche' => 0.50, 'sentimentale' => 0.75, 'imaginaire' => 1.0],
                ],
                'label' => 'Matrice de proximité des univers',
                'group' => 'genre',
            ],
        );
    }
}
