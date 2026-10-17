<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|uuid|exists:assets,id',
            'damage_type' => 'required|in:ringan,berat,total',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string|min:10|max:5000',
            'damage_photo' => 'nullable|image|max:5120',
            'damage_location' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.exists' => 'Aset tidak ditemukan.',
            'description.min' => 'Deskripsi minimal 10 karakter.',
        ];
    }
}
