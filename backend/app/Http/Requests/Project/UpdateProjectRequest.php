<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'string', 'max:200'],
            'genres'       => ['sometimes', 'nullable', 'array'],
            'genres.*'     => ['string', 'max:100'],
            'status'       => ['sometimes', 'in:draft,in_progress,revision,complete'],
            'color'        => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_words'    => ['sometimes', 'nullable', 'integer', 'min:1'],
            'word_goal_arc'   => ['sometimes', 'nullable', 'integer', 'min:1'],
            'word_goal_chapter' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'word_goal_scene' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
