<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StorePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'room_id' => 'required|exists:dormitory_rooms,id',
            'permit_type' => 'required|in:pulang,keluar_kota,berobat,keperluan_keluarga,lainnya,sakit',
            'destination' => 'nullable|string|max:191',
            'purpose' => 'nullable|string',
            'departure_datetime' => 'required|date',
            'expected_return_datetime' => 'required|date|after:departure_datetime',
            'mahrom_id' => 'nullable|exists:student_mahroms,id',
            'companion_name' => 'nullable|string|max:191',
            'companion_relation' => 'nullable|string|max:100',
            'companion_phone' => 'nullable|string|max:20',
            'companion_is_mahrom' => 'boolean',
            'notes' => 'nullable|string',
        ];

        if ($this->input('permit_type') === 'sakit') {
            $rules['health_permit_id'] = 'required|exists:student_health_permits,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Santri wajib dipilih.',
            'permit_type.required' => 'Jenis izin wajib dipilih.',
            'departure_datetime.required' => 'Waktu berangkat wajib diisi.',
            'departure_datetime.date' => 'Format waktu berangkat tidak valid.',
            'expected_return_datetime.required' => 'Waktu kembali wajib diisi.',
            'expected_return_datetime.after' => 'Waktu kembali harus setelah waktu berangkat.',
        ];
    }
}
