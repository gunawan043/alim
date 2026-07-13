<?php

namespace App\Http\Requests;

use App\Services\JadwalGeneratorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JadwalKbmUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->userCanEdit();
    }

    public function rules(): array
    {
        $schoolId = $this->attributes->get('schoolContextId');

        return [
            'entries' => 'required|array|min:1',
            'entries.*.id' => [
                'required',
                'uuid',
                Rule::exists('jadwal_kbms', 'id')->where('school_id', $schoolId),
            ],
            'entries.*.day_of_week' => [
                'required',
                'integer',
                'between:1,6',
            ],
            'entries.*.slot_index' => [
                'required',
                'integer',
                'between:1,'.JadwalGeneratorService::MAX_PERIODS_PER_DAY,
            ],
            'entries.*.teacher_id' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id')->where('school_id', $schoolId),
            ],
            'entries.*.subject_id' => [
                'required',
                'uuid',
                Rule::exists('subjects', 'id'),
            ],
            'entries.*.room' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'entries.required' => 'Tidak ada entri jadwal yang dikirim.',
            'entries.array' => 'Format entri jadwal tidak valid.',
            'entries.min' => 'Minimal satu entri jadwal harus dikirim.',
            'entries.*.id.required' => 'ID jadwal wajib diisi.',
            'entries.*.id.uuid' => 'ID jadwal tidak valid.',
            'entries.*.id.exists' => 'Jadwal tidak ditemukan di sekolah ini.',
            'entries.*.day_of_week.required' => 'Hari wajib dipilih.',
            'entries.*.day_of_week.between' => 'Hari harus antara 1 (Senin) sampai 6 (Sabtu).',
            'entries.*.slot_index.required' => 'Slot jam wajib diisi.',
            'entries.*.slot_index.between' => 'Slot harus antara 1 sampai '.JadwalGeneratorService::MAX_PERIODS_PER_DAY.'.',
            'entries.*.teacher_id.exists' => 'Guru tidak ditemukan di sekolah ini.',
            'entries.*.subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'entries.*.subject_id.exists' => 'Mata pelajaran tidak ditemukan.',
            'entries.*.room.max' => 'Ruang maksimal 50 karakter.',
        ];
    }

    private function userCanEdit(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return canPermission('jadwal_kbm_update')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-update-form-request');
    }
}
