<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'visitor_relationship' => ['required', Rule::in(['mahrom', 'wali', 'keluarga', 'pihak_pondok', 'lainnya', 'Pihak pondok', ' Lainnya'])],
            'purpose' => ['required', Rule::in(['menjenguk', 'bawa_bantuan', 'pertemuan_wali', 'antar_jemput', 'lainnya', 'Lainnya'])],
            'expected_arrival_datetime' => 'required|date',
            'expected_meet_duration_minutes' => 'nullable|integer|min:5|max:480',
            'visitor_count' => 'nullable|integer|min:1|max:100',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Samakan nilai form modern dengan enum lama di database:
     * 'Pihak pondok' dan ' Lainnya' masih tersimpan dengan format lama.
     */
    protected function prepareForValidation(): void
    {
        $relationship = $this->input('visitor_relationship');
        if ($relationship === 'pihak_pondok') {
            $this->merge(['visitor_relationship' => 'Pihak pondok']);
        } elseif ($relationship === 'lainnya') {
            $this->merge(['visitor_relationship' => ' Lainnya']);
        }

        if ($this->input('purpose') === 'Lainnya') {
            $this->merge(['purpose' => 'lainnya']);
        }
    }

    public function messages(): array
    {
        return [
            'visitor_name.required' => 'Nama pengunjung wajib diisi.',
            'visitor_relationship.required' => 'Hubungan pengunjung dengan santri wajib dipilih.',
            'purpose.required' => 'Tujuan kunjungan wajib dipilih.',
            'expected_arrival_datetime.required' => 'Waktu kedatangan wajib diisi.',
            'expected_meet_duration_minutes.min' => 'Durasi minimal 5 menit.',
            'expected_meet_duration_minutes.max' => 'Durasi maksimal 8 jam.',
            'visitor_count.min' => 'Jumlah pengunjung minimal 1 orang.',
        ];
    }
}
