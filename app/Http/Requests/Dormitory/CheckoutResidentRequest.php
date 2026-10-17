<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_out_date' => 'required|date',
            'check_out_reason' => 'required|in:lulus,pindah_kamar,keluar,sakit,lainnya',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'check_out_date.required' => 'Tanggal check-out wajib diisi.',
            'check_out_reason.required' => 'Alasan check-out wajib dipilih.',
            'check_out_reason.in' => 'Alasan check-out tidak valid.',
        ];
    }
}
