<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RequestWaliRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nik_santri'     => 'required|string|size:16|regex:/^\d{16}$/',
            'role'           => 'required|in:ayah,ibu,kakek,nenek,wali,lainnya',
            'no_kk'          => 'nullable|string|size:16|regex:/^\d{16}$/',
            'approval_token' => 'nullable|string',
        ];
    }
}