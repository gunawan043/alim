<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:dormitory_rooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,ended',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Wali Kamar (Pegawai) wajib dipilih.',
            'room_id.required' => 'Kamar wajib dipilih.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'status.required' => 'Status penugasan wajib dipilih.',
        ];
    }
}
