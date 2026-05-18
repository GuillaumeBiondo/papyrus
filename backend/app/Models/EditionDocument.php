<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditionDocument extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'title',
        'content',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'  => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Catalogue complet des types de documents, dans l'ordre d'affichage
    public const TYPES = [
        ['key' => 'dedication',       'label' => 'Dédicace',              'category' => 'liminary', 'sort_order' => 10],
        ['key' => 'epigraph',         'label' => 'Épigraphe',             'category' => 'liminary', 'sort_order' => 20],
        ['key' => 'foreword',         'label' => 'Avant-propos',          'category' => 'liminary', 'sort_order' => 30],
        ['key' => 'author_note',      'label' => 'Note de l\'auteur',     'category' => 'liminary', 'sort_order' => 40],
        ['key' => 'acknowledgements', 'label' => 'Remerciements',         'category' => 'annex',   'sort_order' => 10],
        ['key' => 'about_author',     'label' => 'À propos de l\'auteur', 'category' => 'annex',   'sort_order' => 20],
        ['key' => 'glossary',         'label' => 'Glossaire',             'category' => 'annex',   'sort_order' => 30],
        ['key' => 'character_index',  'label' => 'Index des personnages', 'category' => 'annex',   'sort_order' => 40],
        ['key' => 'timeline',         'label' => 'Chronologie',           'category' => 'annex',   'sort_order' => 50],
        ['key' => 'genealogy',        'label' => 'Arbre généalogique',    'category' => 'annex',   'sort_order' => 60],
        ['key' => 'map',              'label' => 'Carte du monde',        'category' => 'annex',   'sort_order' => 70],
        ['key' => 'bestiary',         'label' => 'Bestiaire / Panthéon',  'category' => 'annex',   'sort_order' => 80],
        ['key' => 'notes',            'label' => 'Notes & références',    'category' => 'annex',   'sort_order' => 90],
        ['key' => 'bibliography',     'label' => 'Bibliographie',         'category' => 'annex',   'sort_order' => 100],
        ['key' => 'reading_club',     'label' => 'Questions de lecture',  'category' => 'annex',   'sort_order' => 110],
        ['key' => 'playlist',         'label' => 'Playlist',              'category' => 'annex',   'sort_order' => 120],
        ['key' => 'preview',          'label' => 'Extrait de la suite',   'category' => 'annex',   'sort_order' => 130],
    ];
}
