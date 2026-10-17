<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class RejectVisitLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}
