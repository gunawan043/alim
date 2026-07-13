<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVisitLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'approval_note.max' => 'Catatan persetujuan maksimal 500 karakter.',
        ];
    }
}
