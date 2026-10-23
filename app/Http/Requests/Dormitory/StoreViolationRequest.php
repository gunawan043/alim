<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'room_id' => 'required|exists:dormitory_rooms,id',
            'point_catalog_id' => [
                'required',
                'uuid',
                Rule::exists('dormitory_point_catalogs', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('kind', 'violation')
                ),
            ],
            'violation_date' => 'required|date',
            'violation_category' => 'nullable|in:ringan,sedang,berat',
            'violation_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'points' => 'nullable|integer|min:0',
            'action_taken' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'witness_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'point_catalog_id.required' => 'Jenis pelanggaran wajib dipilih dari katalog.',
            'point_catalog_id.exists' => 'Katalog pelanggaran tidak aktif atau tidak ditemukan.',
            'violation_category.required' => 'Kategori pelanggaran wajib dipilih.',
            'violation_type.required' => 'Jenis pelanggaran wajib diisi.',
            'points.min' => 'Poin tidak boleh negatif.',
        ];
    }
}
