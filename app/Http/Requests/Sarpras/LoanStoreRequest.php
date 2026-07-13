<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class LoanStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'purpose' => 'required|string',
            'loan_date' => 'required|date',
            'loan_time' => 'nullable',
            'expected_return_date' => 'required|date|after_or_equal:loan_date',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Aset wajib dipilih.',
            'purpose.required' => 'Tujuan peminjaman wajib diisi.',
            'loan_date.required' => 'Tanggal pinjam wajib diisi.',
            'expected_return_date.required' => 'Tanggal rencana kembali wajib diisi.',
            'expected_return_date.after_or_equal' => 'Tanggal kembali harus sama atau setelah tanggal pinjam.',
        ];
    }
}
