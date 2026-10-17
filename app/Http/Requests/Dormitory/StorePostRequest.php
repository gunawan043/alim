<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:pengumuman,undangan,laporan,darurat',
            'visibility' => 'required|in:wali,pengurus,umum',
            'needs_response' => 'boolean',
            'is_pinned' => 'boolean',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'content.required' => 'Konten wajib diisi.',
        ];
    }
}
