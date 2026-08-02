<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => 'required|string|size:16|regex:/^\d{16}$/',
            'name' => 'required|string|min:2|max:100',
            'gender' => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'required|date|before:today',
            'no_kk' => 'nullable|string|size:16|regex:/^\d{16}$/',
            'school_id' => 'nullable|uuid|exists:schools,id',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'nik.size' => 'NIK harus 16 digit.',
            'birth_date.before' => 'Tanggal lahir tidak valid.',
        ];
    }
}
