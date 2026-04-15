<?php

namespace App\Services;

use App\Models\CardKeyword;
use App\Models\Scene;
use Illuminate\Support\Str;

class KeywordScanner
{
    public function scan(Scene $scene, CardKeyword $keyword): array
    {
        $content  = strip_tags($scene->content ?? '');
        $search   = $keyword->case_sensitive
            ? $keyword->keyword
            : mb_strtolower($keyword->keyword);
        $haystack = $keyword->case_sensitive
            ? $content
            : mb_strtolower($content);

        $offset  = 0;
        $results = [];

        while (($pos = mb_strpos($haystack, $search, $offset)) !== false) {
            $start   = max(0, $pos - 80);
            $excerpt = mb_substr($content, $start, 160);

            $results[] = [
                'id'               => Str::uuid()->toString(),
                'card_keyword_id'  => $keyword->id,
                'scene_id'         => $scene->id,
                'position_start'   => $pos,
                'position_end'     => $pos + mb_strlen($search),
                'context_excerpt'  => $excerpt,
                'computed_at'      => now(),
            ];

            $offset = $pos + 1;
        }

        return $results;
    }
}
