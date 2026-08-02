<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class QrScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qr_token' => 'required|string',
            'scan_type' => 'required|in:lookup,loan,return,audit,opname',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'condition' => 'nullable|in:baik,rusak_ringan,rusak_berat,total',
            'session_id' => 'nullable|uuid|exists:stock_opname_sessions,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
