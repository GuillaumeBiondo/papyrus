<?php

namespace App\Http\Requests\Annotation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnotationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'         => ['sometimes', 'string'],
            'type'         => ['sometimes', Rule::in(['inline', 'global'])],
            'anchor_start' => ['nullable', 'integer', 'min:0'],
            'anchor_end'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
