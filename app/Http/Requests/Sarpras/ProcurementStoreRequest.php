<?php

namespace App\Http\Requests\Sarpras;

use App\Models\ProcurementRequest;
use Illuminate\Foundation\Http\FormRequest;

class ProcurementStoreRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:191',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.estimated_price_per_unit' => 'nullable|numeric|min:0',
            'items.*.room_id' => 'nullable|exists:asset_rooms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'request_date.required' => 'Tanggal pengajuan wajib diisi.',
            'purpose.required' => 'Tujuan pengadaan wajib diisi.',
            'urgency.required' => 'Urgensi pengadaan wajib dipilih.',
            'items.required' => 'Daftar item pengadaan wajib diisi.',
            'items.min' => 'Minimal 1 item pengadaan.',
            'items.*.item_name.required' => 'Nama item wajib diisi.',
            'items.*.quantity.required' => 'Jumlah item wajib diisi.',
            'items.*.quantity.min' => 'Jumlah minimal 1.',
        ];
    }
}
