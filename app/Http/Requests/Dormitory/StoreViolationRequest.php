<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

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
            'violation_date' => 'required|date',
            'violation_category' => 'required|in:ringan,sedang,berat',
            'violation_type' => 'required|string|max:100',
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
            'violation_category.required' => 'Kategori pelanggaran wajib dipilih.',
            'violation_type.required' => 'Jenis pelanggaran wajib diisi.',
            'points.min' => 'Poin tidak boleh negatif.',
        ];
    }
}
