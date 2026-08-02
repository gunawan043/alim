<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomSupervisorRequest extends FormRequest
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
            'user_id.exists' => 'Pegawai tidak ditemukan.',
            'room_id.required' => 'Kamar wajib dipilih.',
            'room_id.exists' => 'Kamar tidak ditemukan.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'status.required' => 'Status penugasan wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
