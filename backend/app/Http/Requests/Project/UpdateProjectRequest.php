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
            'genre'        => ['nullable', 'string', 'max:100'],
            'color'        => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_words' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
