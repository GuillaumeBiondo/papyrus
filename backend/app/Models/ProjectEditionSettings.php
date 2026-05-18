<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEditionSettings extends Model
{
    protected $fillable = ['project_id', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function defaults(): array
    {
        return [
            'template' => 'pocket',
            'page'     => [
                'width'         => 11,
                'height'        => 18,
                'margin_top'    => 20,
                'margin_bottom' => 20,
                'margin_inner'  => 25,
                'margin_outer'  => 20,
                'gutter'        => 5,
            ],
            'text' => [
                'alignment'  => 'justified',
                'line_height' => 1.4,
                'body_font'  => 'Georgia',
                'body_font_size' => 11,
            ],
            'titles' => [
                'font'            => null,
                'size'            => 16,
                'alignment'       => 'center',
                'numbering'       => 'none',
                'drop_cap'        => false,
                'drop_cap_lines'  => 3,
                'vertical_position' => 'top',
                'space_before'    => 3.0,
                'space_after'     => 2.0,
            ],
            'structure' => [
                'chapter_start'           => 'odd',
                'part_page'               => true,
                'scene_separator'         => 'stars',
                'scene_separator_custom'  => null,
                'separator_space_before'  => 1.5,
                'separator_space_after'   => 1.0,
            ],
            'headers' => [
                'left_field'        => 'book_title',
                'right_field'       => 'chapter_title',
                'header_rule'       => true,
                'footer_rule'       => true,
                'rule_space_before' => 2,
                'rule_space_after'  => 4,
            ],
            'footer' => [
                'position'           => 'outer',
                'show_on_liminaries' => true,
                'show_on_toc'        => false,
                'show_on_parts'      => false,
            ],
        ];
    }
}
