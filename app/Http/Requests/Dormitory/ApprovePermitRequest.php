<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_note' => 'nullable|string',
            'permit_type' => 'nullable|string|in:pulang,sakit,berobat,keperluan_keluarga,keluar_kota,darurat,lainnya',
        ];
    }
}
