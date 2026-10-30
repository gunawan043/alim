<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class RecordReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_return_datetime' => 'required|date',
            'card_returned' => 'required|accepted',
            'inspection_done' => 'required|accepted',
            'late_return_reason' => 'nullable|string|max:1000',
            'return_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'actual_return_datetime.required' => 'Waktu kembali aktual wajib diisi.',
            'card_returned.accepted' => 'Kartu izin wajib dikembalikan.',
            'inspection_done.accepted' => 'Pemeriksaan barang bawaan wajib dilakukan sebelum santri kembali.',
        ];
    }
}
