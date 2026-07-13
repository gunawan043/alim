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
        ];
    }

    public function messages(): array
    {
        return [
            'actual_return_datetime.required' => 'Waktu kembali aktual wajib diisi.',
        ];
    }
}
