<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class StoreMahromRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:191',
            'hubungan' => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,anak,sepupu,lainnya',
            'jenis_kelamin' => 'nullable|in:L,P',
            'nik' => 'nullable|string|max:30|unique:student_mahroms,id_number',
            'nomor_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'catatan' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama mahrom wajib diisi.',
            'hubungan.required' => 'Hubungan keluarga wajib dipilih.',
            'hubungan.in' => 'Hubungan tidak valid.',
            'nik.unique' => 'NIK sudah digunakan oleh mahrom lain.',
            'foto.mimes' => 'Foto harus berformat jpg, jpeg, atau png.',
            'foto.max' => 'Ukuran foto maksimal 1 MB.',
        ];
    }
}
