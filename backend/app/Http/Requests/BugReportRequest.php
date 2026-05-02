<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'message'          => ['required', 'string', 'max:5000'],
            'url'              => ['required', 'string', 'max:2000'],
            'api_calls'        => ['sometimes', 'array'],
            'api_calls.*.method'   => ['sometimes', 'string'],
            'api_calls.*.url'      => ['sometimes', 'string'],
            'api_calls.*.status'   => ['sometimes', 'nullable', 'integer'],
            'api_calls.*.request'  => ['sometimes', 'nullable', 'string'],
            'api_calls.*.response' => ['sometimes', 'nullable', 'string'],
            'api_calls.*.at'       => ['sometimes', 'string'],
            'console_errors'   => ['sometimes', 'array'],
            'console_errors.*' => ['sometimes', 'string'],
        ];
    }
}
