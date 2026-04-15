<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardLinkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'linked_card_id' => ['required', 'uuid', 'exists:cards,id'],
            'label'          => ['nullable', 'string', 'max:100'],
        ];
    }
}
