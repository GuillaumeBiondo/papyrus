<?php

namespace App\Jobs;

use App\Models\CardKeyword;
use App\Models\KeywordOccurrence;
use App\Models\Project;
use App\Models\Scene;
use App\Services\KeywordScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RebuildKeywordIndex implements ShouldQueue
{
    use Queueable;

    public function __construct(public Project $project) {}

    public function handle(): void
    {
        // 1. Supprimer les occurrences existantes du projet
        KeywordOccurrence::whereHas('cardKeyword.card', fn ($q) =>
            $q->where('project_id', $this->project->id)
        )->delete();

        // 2. Charger tous les mots-clés du projet
        $keywords = CardKeyword::whereHas('card', fn ($q) =>
            $q->where('project_id', $this->project->id)
        )->with('card')->get();

        if ($keywords->isEmpty()) {
            return;
        }

        // 3. Charger toutes les scènes du projet
        $scenes = Scene::whereHas('chapter', fn ($q) =>
            $q->where('project_id', $this->project->id)
        )->get();

        if ($scenes->isEmpty()) {
            return;
        }

        // 4. Scanner
        $scanner     = app(KeywordScanner::class);
        $occurrences = [];

        foreach ($scenes as $scene) {
            foreach ($keywords as $kw) {
                $occurrences = array_merge($occurrences, $scanner->scan($scene, $kw));
            }
        }

        // 5. Insérer en bulk
        if (! empty($occurrences)) {
            KeywordOccurrence::insert($occurrences);
        }
    }
}
