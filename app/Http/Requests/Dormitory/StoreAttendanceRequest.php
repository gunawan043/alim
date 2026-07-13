<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_date' => 'required|date',
            'session' => 'required|in:subuh,pagi,siang,sore,isya,malam',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.room_id' => 'required|exists:dormitory_rooms,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa,pulang',
            'attendances.*.notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_date.required' => 'Tanggal absensi wajib diisi.',
            'session.required' => 'Sesi absensi wajib dipilih.',
            'session.in' => 'Sesi absensi tidak valid.',
            'attendances.required' => 'Data absensi wajib diisi.',
            'attendances.min' => 'Minimal satu santri harus diabsen.',
            'attendances.*.student_id.required' => 'ID santri wajib diisi.',
            'attendances.*.status.required' => 'Status kehadiran wajib diisi.',
            'attendances.*.status.in' => 'Status kehadiran tidak valid.',
        ];
    }
}
