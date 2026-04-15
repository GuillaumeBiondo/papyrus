<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'               => ['required', 'string', 'max:50'],
            'title'              => ['required', 'string', 'max:200'],
            'attributes'         => ['sometimes', 'array'],
            'attributes.*.key'   => ['required', 'string', 'max:100'],
            'attributes.*.value' => ['nullable'],
        ];
    }
}
