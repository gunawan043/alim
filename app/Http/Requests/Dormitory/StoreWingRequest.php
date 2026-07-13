<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreWingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'floor' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:putra,putri',
            'capacity' => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode gedung wajib diisi.',
        ];
    }
}
