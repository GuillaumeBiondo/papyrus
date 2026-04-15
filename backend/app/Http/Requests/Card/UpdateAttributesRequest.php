<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttributesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'attributes'         => ['required', 'array'],
            'attributes.*.key'   => ['required', 'string', 'max:100'],
            'attributes.*.value' => ['nullable'],
        ];
    }
}
