<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetLoan;
use Illuminate\Foundation\Http\FormRequest;

class LoanReturnRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'condition_on_return' => 'required|in:'.implode(',', AssetLoan::CONDITION_OPTIONS),
            'damage_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'condition_on_return.required' => 'Kondisi aset saat kembali wajib dipilih.',
        ];
    }
}
