<?php

namespace App\Http\Requests\Sarpras;

use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QrAuditSubmitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'condition' => 'required|in:'.implode(',', Asset::CONDITION_OPTIONS),
            'last_condition_update' => 'nullable|date',
            'last_audit_by' => 'nullable|exists:users,id',
            'last_audit_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'condition.required' => 'Kondisi aset wajib dipilih.',
            'photos.*.image' => 'Foto audit harus berupa gambar.',
            'photos.*.max' => 'Ukuran foto audit maksimal 5MB.',
        ];
    }
}
