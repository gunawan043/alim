<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nik'             => 'required|string|size:16|regex:/^[0-9]{16}$/',
            'name'            => 'required|string|max:255',
            'gender'          => 'required|string|in:L,P',
            'birth_place'     => 'nullable|string|max:100',
            'birth_date'      => 'required|date|before:today',
            'no_kk'           => 'nullable|string|size:16|regex:/^[0-9]{16}$/',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nik.regex'   => 'NIK harus 16 digit angka.',
            'nik.size'    => 'NIK harus 16 digit.',
            'birth_date.before' => 'Tanggal lahir tidak valid.',
        ];
    }
}