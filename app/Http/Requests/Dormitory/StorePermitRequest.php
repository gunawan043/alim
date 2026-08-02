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
            'permit_type' => 'required|in:pulang,keluar_kota,berobat,keperluan_keluarga,lainnya,sakit,darurat',
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
            // Emergency-specific fields
            'is_emergency' => 'boolean',
            'emergency_contact_name' => 'nullable|string|max:191',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'is_special_permission' => 'boolean',
            'special_reason' => 'nullable|string',
        ];

        if ($this->input('permit_type') === 'sakit') {
            $rules['health_permit_id'] = 'required|exists:student_health_permits,id';
        }

        // Emergency permits must have contact info
        if ($this->boolean('is_emergency')) {
            $rules['emergency_contact_name'] = 'required|string|max:191';
            $rules['emergency_contact_phone'] = 'required|string|max:20';
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
            'is_emergency.boolean' => 'Field is_emergency harus berupa true/false.',
            'emergency_contact_name.required' => 'Nama kontak darurat wajib diisi untuk izin darurat.',
            'emergency_contact_phone.required' => 'No. HP kontak darurat wajib diisi untuk izin darurat.',
        ];
    }

    /**
     * Prepare data so booleans survive validation properly.
     */
    protected function passedValidation(): void
    {
        $data = $this->all();
        foreach (['is_emergency', 'is_special_permission', 'companion_is_mahrom'] as $key) {
            if (! isset($data[$key])) {
                $this->merge([$key => false]);
            }
        }
    }
}
