<?php

namespace App\Http\Requests\Arc;

use Illuminate\Foundation\Http\FormRequest;

class StoreArcRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
