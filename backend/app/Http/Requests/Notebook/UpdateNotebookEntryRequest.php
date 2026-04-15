<?php

namespace App\Http\Requests\Notebook;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotebookEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'      => ['nullable', 'string', 'max:200'],
            'body'       => ['sometimes', 'string'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
        ];
    }
}
