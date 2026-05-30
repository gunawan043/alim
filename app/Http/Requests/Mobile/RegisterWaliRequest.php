<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RegisterWaliRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|min:2|max:100',
            'email'    => 'required|email:rfc,strict|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'no_kk'    => 'nullable|string|size:16|regex:/^\d{16}$/',
            'nik_wali' => 'nullable|string|size:16|regex:/^\d{16}$/',
            'no_hp'    => 'nullable|string|min:10|max:20|regex:/^[\d+\-\s]+$/',
            'hubungan' => 'nullable|in:ayah,ibu,kakek,nenek,wali,lainnya',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah terdaftar. Gunakan email lain atau login.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'no_kk.size' => 'No KK harus 16 digit.',
            'no_kk.regex' => 'No KK harus berupa 16 digit angka.',
            'nik_wali.size' => 'NIK Wali harus 16 digit.',
            'nik_wali.regex' => 'NIK Wali harus berupa 16 digit angka.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
        ];
    }
}