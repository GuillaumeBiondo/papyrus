<?php

use App\Models\Scene;
use App\Models\SceneSnapshot;
use Illuminate\Database\Migrations\Migration;

// Preserve existing work: every scene with content gets its current state
// saved as its first snapshot ("État initial").
return new class extends Migration
{
    public function up(): void
    {
        Scene::whereNotNull('content')
            ->where('content', '!=', '')
            ->cursor()
            ->each(function (Scene $scene) {
                if ($scene->snapshots()->exists()) return; // safety guard

                $wordCount = $this->countWords($scene->content);

                SceneSnapshot::create([
                    'scene_id'   => $scene->id,
                    'user_id'    => $scene->chapter->arc->project->user_id,
                    'content'    => $scene->content,
                    'word_count' => $wordCount,
                    'word_delta' => $wordCount,
                    'trigger'    => 'manual',
                    'label'      => 'État initial',
                    'created_at' => $scene->updated_at ?? $scene->created_at,
                ]);
            });
    }

    public function down(): void
    {
        SceneSnapshot::where('label', 'État initial')->where('trigger', 'manual')->delete();
    }

    private function countWords(?string $json): int
    {
        if (!$json) return 0;
        $decoded = json_decode($json, true);
        $text    = $decoded ? $this->extractText($decoded) : $json;
        $words   = preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY);
        return count($words ?: []);
    }

    private function extractText(array $node): string
    {
        if (isset($node['text'])) return $node['text'];
        if (isset($node['content'])) {
            return implode(' ', array_map([$this, 'extractText'], $node['content']));
        }
        return '';
    }
};
