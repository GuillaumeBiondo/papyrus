<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SceneSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scene_id', 'user_id', 'content', 'word_count', 'word_delta', 'trigger', 'label',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
            'word_delta' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Rétention : garder les 12 derniers autos, 1 sur 4 pour les plus anciens.
    public static function pruneAutos(string $sceneId): void
    {
        $ids = self::where('scene_id', $sceneId)
            ->where('trigger', 'auto')
            ->orderBy('created_at', 'desc')
            ->pluck('id');

        if ($ids->count() <= 12) return;

        // Les plus anciens (au-delà des 12 récents), oldest-first
        $older = $ids->slice(12)->reverse()->values();

        // Garder 1 sur 4 (index 3, 7, 11…), supprimer le reste
        $toDelete = $older->filter(fn($id, $idx) => ($idx + 1) % 4 !== 0);

        self::whereIn('id', $toDelete)->delete();
    }
}
