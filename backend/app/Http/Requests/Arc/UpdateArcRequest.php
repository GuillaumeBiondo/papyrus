<?php

namespace App\Http\Requests\Arc;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArcRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
