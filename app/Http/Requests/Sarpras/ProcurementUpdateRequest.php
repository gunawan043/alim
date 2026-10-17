<?php

namespace App\Http\Requests\Sarpras;

use App\Models\ProcurementRequest;
use Illuminate\Foundation\Http\FormRequest;

class ProcurementUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'request_date' => 'required|date',
            'purpose' => 'required|string',
            'urgency' => 'required|in:'.implode(',', ProcurementRequest::URGENCY_OPTIONS),
            'budget_source' => 'nullable|string|max:100',
            'total_estimated_budget' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'request_date.required' => 'Tanggal pengajuan wajib diisi.',
            'purpose.required' => 'Tujuan pengadaan wajib diisi.',
            'urgency.required' => 'Urgensi pengadaan wajib dipilih.',
        ];
    }
}
