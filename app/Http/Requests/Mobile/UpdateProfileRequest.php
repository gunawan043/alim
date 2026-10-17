<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'no_kk' => 'sometimes|nullable|string|size:16|regex:/^[0-9]{16}$/',
            'nik_wali' => 'sometimes|nullable|string|size:16|regex:/^[0-9]{16}$/',
            'no_hp' => 'sometimes|nullable|string|max:20',
        ];
    }
}
