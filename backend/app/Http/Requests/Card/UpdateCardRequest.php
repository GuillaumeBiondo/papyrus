<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'  => ['sometimes', 'string', 'max:50'],
            'title' => ['sometimes', 'string', 'max:200'],
        ];
    }
}
