<?php

namespace App\Observers;

use App\Models\Scene;

class SceneObserver
{
    public function saving(Scene $scene): void
    {
        if ($scene->isDirty('content')) {
            $scene->word_count = str_word_count(strip_tags($scene->content ?? ''));
        }
    }
}
