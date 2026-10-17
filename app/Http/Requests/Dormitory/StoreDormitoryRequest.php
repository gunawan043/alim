<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreDormitoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_unit_id' => 'required|exists:work_units,id',
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string|max:20|unique:dormitories,code',
            'name' => 'nullable|string|max:191',
            'gender' => 'required|in:putra,putri,campuran',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'required|integer|min:1',
            'total_rooms' => 'nullable|integer|min:0',
            'total_wings' => 'nullable|integer|min:0',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode asrama wajib diisi.',
            'code.unique' => 'Kode asrama sudah digunakan.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'capacity.min' => 'Kapasitas minimal 1.',
        ];
    }
}
