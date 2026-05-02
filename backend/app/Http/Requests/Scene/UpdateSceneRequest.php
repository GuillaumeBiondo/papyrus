<?php

namespace App\Http\Requests\Scene;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    protected function failedValidation(Validator $validator): never
    {
        \Log::error('UpdateSceneRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input'  => collect($this->all())->map(fn($v) => is_string($v) ? mb_substr($v, 0, 80) : $v)->toArray(),
        ]);
        throw new ValidationException($validator);
    }
}
