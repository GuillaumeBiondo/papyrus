<?php

namespace App\Http\Requests\Chapter;

use Illuminate\Foundation\Http\FormRequest;

class ReorderChapterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'          => ['required', 'array'],
            'items.*.id'     => ['required', 'uuid', 'exists:chapters,id'],
            'items.*.order'  => ['required', 'integer', 'min:0'],
            'items.*.arc_id' => ['sometimes', 'uuid', 'exists:arcs,id'],
        ];
    }
}
