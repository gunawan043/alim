<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'record_id' => 'required|exists:dormitory_attendances,id',
        ];
    }

    public function messages(): array
    {
        return [
            'record_id.required' => 'ID record absensi wajib diisi.',
            'record_id.exists' => 'Record absensi tidak ditemukan.',
        ];
    }
}
