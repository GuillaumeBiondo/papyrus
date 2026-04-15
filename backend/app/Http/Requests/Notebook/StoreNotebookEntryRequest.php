<?php

namespace App\Http\Requests\Notebook;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotebookEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'      => ['nullable', 'string', 'max:200'],
            'body'       => ['required', 'string'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
        ];
    }
}
