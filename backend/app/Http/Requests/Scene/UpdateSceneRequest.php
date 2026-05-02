<?php

namespace App\Http\Requests\Scene;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSceneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'   => ['sometimes', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'status'  => ['sometimes', Rule::in(['idea', 'draft', 'revised', 'final'])],
            'order'   => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
