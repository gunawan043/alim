<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class LinkWaliSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'role' => 'required|in:ayah,ibu,kakek,nenek,wali,lainnya',
            'approval_token' => 'nullable|string',
        ];
    }
}
