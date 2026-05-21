<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:200'],
            'genres'            => ['nullable', 'array'],
            'genres.*'          => ['string', 'max:100'],
            'color'             => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_words'      => ['nullable', 'integer', 'min:1'],
            'word_goal_arc'     => ['nullable', 'integer', 'min:1'],
            'word_goal_chapter' => ['nullable', 'integer', 'min:1'],
            'word_goal_scene'   => ['nullable', 'integer', 'min:1'],
            'content_type_id'   => ['nullable', 'uuid', 'exists:content_types,id'],
        ];
    }
}
