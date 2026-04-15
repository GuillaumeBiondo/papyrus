<?php

namespace App\Http\Requests\Scene;

use Illuminate\Foundation\Http\FormRequest;

class ReorderSceneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'         => ['required', 'array'],
            'items.*.id'    => ['required', 'uuid', 'exists:scenes,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
