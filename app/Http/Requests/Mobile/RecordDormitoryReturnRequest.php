<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RecordDormitoryReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permit_id' => 'required|uuid|exists:dormitory_permits,id',
            'actual_return_datetime' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'permit_id.required' => 'Permit izin wajib dipilih.',
            'permit_id.uuid' => 'Permit tidak valid.',
            'permit_id.exists' => 'Permit izin tidak ditemukan.',
            'actual_return_datetime.required' => 'Waktu kepulangan aktual wajib diisi.',
            'actual_return_datetime.date' => 'Format waktu tidak valid.',
        ];
    }
}
