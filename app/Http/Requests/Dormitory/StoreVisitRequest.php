<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'room_id' => 'nullable|exists:dormitory_rooms,id',
            'visitor_name' => 'required|string|max:191',
            'visitor_id_number' => 'nullable|string|max:30',
            'visitor_phone' => 'nullable|string|max:20',
            'visitor_relationship' => 'required|in:mahrom,wali,keluarga,Pihak pondok,Lainnya',
            'purpose' => 'required|in:menjenguk,bawa_bantuan,pertemuan_wali,antar_jemput,Lainnya',
            'expected_arrival_datetime' => 'required|date',
            'expected_meet_duration_minutes' => 'nullable|integer|min:15',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'visitor_name.required' => 'Nama pengunjung wajib diisi.',
            'visitor_relationship.required' => 'Hubungan pengunjung dengan santri wajib dipilih.',
            'purpose.required' => 'Tujuan kunjungan wajib dipilih.',
            'expected_arrival_datetime.required' => 'Waktu kedatangan wajib diisi.',
            'expected_meet_duration_minutes.min' => 'Durasi mínimo 15 menit.',
        ];
    }
}
