<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class CreateDormitoryVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'visitor_name' => 'required|string|max:100',
            'visitor_id_number' => 'nullable|string|max:50',
            'visitor_phone' => 'required|string|min:10|max:20',
            'visitor_relationship' => 'required|in:mahrom,wali,keluarga,pihak_pondok,lainnya',
            'purpose' => 'required|in:menjenguk,bawa_bantuan,pertemuan_wali,antar_jemput,lainnya',
            'expected_arrival_datetime' => 'required|date|after:now',
            'expected_meet_duration_minutes' => 'nullable|integer|min:15|max:480',
            'notes' => 'nullable|string|max:500',
            'mahrom_id' => 'nullable|uuid|exists:student_mahroms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Santri wajib dipilih.',
            'student_id.exists' => 'Santri tidak ditemukan.',
            'visitor_name.required' => 'Nama pengunjung wajib diisi.',
            'visitor_phone.required' => 'Nomor telepon pengunjung wajib diisi.',
            'visitor_phone.min' => 'Nomor telepon minimal 10 digit.',
            'visitor_relationship.required' => 'Hubungan dengan pengunjung wajib dipilih.',
            'purpose.required' => 'Tujuan kunjungan wajib dipilih.',
            'expected_arrival_datetime.required' => 'Waktu kedatangan wajib diisi.',
            'expected_arrival_datetime.after' => 'Waktu kedatangan harus di waktu yang akan datang.',
            'mahrom_id.exists' => 'Mahrom tidak ditemukan.',
        ];
    }
}
