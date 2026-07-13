<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class BulkAuditSubmitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'audits' => 'required|array',
            'audits.*.asset_id' => 'required|exists:assets,id',
            'audits.*.condition' => 'required|in:'.implode(',', \App\Models\Asset::CONDITION_OPTIONS),
            'audits.*.notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'audits.required' => 'Data audit wajib diisi.',
            'audits.*.asset_id.required' => 'ID aset dalam setiap baris audit wajib diisi.',
            'audits.*.asset_id.exists' => 'Salah satu aset tidak ditemukan.',
            'audits.*.condition.required' => 'Kondisi dalam setiap baris audit wajib dipilih.',
        ];
    }
}
