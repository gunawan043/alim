<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JadwalKbmGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->userCanGenerate();
    }

    public function rules(): array
    {
        $schoolId = $this->attributes->get('schoolContextId');

        return [
            'study_group_ids' => 'required|array|min:1',
            'study_group_ids.*' => [
                'required',
                'uuid',
                Rule::exists('study_groups', 'id')->where('school_id', $schoolId),
            ],
            'academic_year_id' => [
                'required',
                'uuid',
                Rule::exists('academic_years', 'id')->where('school_id', $schoolId),
            ],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'overwrite' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'study_group_ids.required' => 'Pilih minimal satu rombel.',
            'study_group_ids.array' => 'Daftar rombel tidak valid.',
            'study_group_ids.min' => 'Pilih minimal satu rombel.',
            'study_group_ids.*.uuid' => 'ID rombel tidak valid.',
            'study_group_ids.*.exists' => 'Rombel tidak ditemukan di sekolah ini.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan di sekolah ini.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.in' => 'Semester harus ganjil atau genap.',
            'overwrite.boolean' => 'Nilai timpa harus berupa boolean.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'overwrite' => $this->boolean('overwrite'),
        ]);
    }

    private function userCanGenerate(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('jadwal_kbm_generate')
            || $user->can('jadwal_kbm_manage')
            || $user->hasRole(['Super Admin', 'Mudir', 'Wadir 1', 'Waka', 'Wakil Kepala Sekolah', 'Admin Tata Usaha']);
    }
}
