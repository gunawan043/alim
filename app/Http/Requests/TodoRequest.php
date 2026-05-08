<?php

namespace App\Http\Requests;

use App\Models\Todo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled at controller level
    }

    public function rules(): array
    {
        $rules = [
            'title'           => 'required|string|max:255',
            'todo_list_id'    => 'nullable|uuid|exists:todo_lists,id',
            'owner_id'        => 'nullable|uuid|exists:users,id',
            'delegated_by'    => 'nullable|uuid|exists:users,id',
            'priority'        => ['nullable', Rule::in(Todo::PRIORITIES)],
            'status'          => ['nullable', Rule::in(Todo::STATUSES)],
            'due_date'        => 'nullable|date',
            'due_time'        => 'nullable|date_format:H:i',
            'reminder_at'     => 'nullable|date',
            'tags'            => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'is_pinned'       => 'nullable',
            'is_private'      => 'nullable',
            'work_unit_id'    => 'nullable|uuid|exists:work_units,id',
            'school_id'       => 'nullable|uuid|exists:schools,id',
            'academic_year_id'=> 'nullable|uuid|exists:academic_years,id',
            'related_type'    => 'nullable|string|max:100',
            'related_id'      => 'nullable|uuid',
            'cancelled_reason'=> 'nullable|string|max:500',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['title'] = 'sometimes|required|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Judul tugas wajib diisi.',
            'title.max'        => 'Judul tidak boleh lebih dari 255 karakter.',
            'priority.in'     => 'Prioritas tidak valid.',
            'status.in'        => 'Status tidak valid.',
            'due_date.date'    => 'Format tanggal tidak valid.',
            'owner_id.exists'  => 'Pengguna yang ditugaskan tidak ditemukan.',
            'delegated_by.exists' => 'Atasan delegasi tidak ditemukan.',
            'todo_list_id.exists' => 'Daftar todo tidak ditemukan.',
            'work_unit_id.exists' => 'Unit kerja tidak ditemukan.',
            'school_id.exists'   => 'Sekolah tidak ditemukan.',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default priority and status only if not provided
        $this->merge([
            'priority' => $this->priority ?? 'sedang',
            'status'   => $this->status ?? 'belum_mulai',
        ]);
    }
}
