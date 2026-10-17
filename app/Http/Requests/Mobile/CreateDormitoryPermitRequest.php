<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class CreateDormitoryPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'permit_type' => 'required|in:pulang,keluar_kota,berobat,sakit,keperluan_keluarga,lainnya',
            'destination' => 'required|string|max:255',
            'purpose' => 'required|string|max:500',
            'departure_datetime' => 'required|date|after:now',
            'expected_return_datetime' => 'required|date|after:departure_datetime',
            'companion_name' => 'nullable|string|max:100',
            'companion_relation' => 'nullable|string|max:50',
            'companion_phone' => 'nullable|string|min:10|max:20',
            'companion_is_mahrom' => 'nullable|boolean',
            'mahrom_id' => 'nullable|uuid|exists:student_mahroms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Santri wajib dipilih.',
            'student_id.uuid' => 'Santri tidak valid.',
            'student_id.exists' => 'Santri tidak ditemukan.',
            'permit_type.required' => 'Jenis izin wajib dipilih.',
            'permit_type.in' => 'Jenis izin tidak valid.',
            'destination.required' => 'Tujuan wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
            'departure_datetime.required' => 'Tanggal berangkat wajib diisi.',
            'departure_datetime.after' => 'Tanggal berangkat harus di waktu yang akan datang.',
            'expected_return_datetime.required' => 'Tanggal kembali wajib diisi.',
            'expected_return_datetime.after' => 'Tanggal kembali harus setelah tanggal berangkat.',
            'companion_phone.min' => 'Nomor telepon pendamping minimal 10 digit.',
            'mahrom_id.uuid' => 'Mahrom tidak valid.',
            'mahrom_id.exists' => 'Mahrom tidak ditemukan.',
        ];
    }
}
