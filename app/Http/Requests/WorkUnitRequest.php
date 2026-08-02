<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:191',
            'kode' => 'required|string|max:191|unique:work_units,kode',
            'jenis' => 'required|string|max:191',
            'induk' => 'nullable|string|max:191',
            'is_active' => 'boolean',
        ];

        // Jika update, ignore unique untuk kode yang sedang diupdate
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['kode'] = 'required|string|max:191|unique:work_units,kode,'.$this->route('work_unit');
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama satuan kerja wajib diisi',
            'nama.max' => 'Nama maksimal 191 karakter',
            'kode.required' => 'Kode wajib diisi',
            'kode.unique' => 'Kode sudah digunakan',
            'kode.max' => 'Kode maksimal 191 karakter',
            'jenis.required' => 'Jenis satuan kerja wajib diisi',
            'jenis.max' => 'Jenis maksimal 191 karakter',
            'induk.max' => 'Induk maksimal 191 karakter',
        ];
    }
}
