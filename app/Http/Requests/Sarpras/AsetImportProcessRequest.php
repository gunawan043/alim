<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class AsetImportProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filename' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'filename.required' => 'File nama wajib dipilih.',
        ];
    }
}
