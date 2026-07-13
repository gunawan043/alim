<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;

class MaintenanceScheduleUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'maintenance_type' => 'required|string|max:100',
            'frequency' => 'required|in:'.implode(',', AssetMaintenanceSchedule::FREQUENCY_OPTIONS),
            'next_maintenance_date' => 'required|date',
            'responsible_user_id' => 'nullable|exists:users,id',
            'vendor_name' => 'nullable|string|max:191',
            'estimated_cost' => 'nullable|numeric|min:0',
            'reminder_days_before' => 'nullable|integer|min:1|max:90',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'maintenance_type.required' => 'Jenis pemeliharaan wajib diisi.',
            'frequency.required' => 'Frekuensi pemeliharaan wajib dipilih.',
            'next_maintenance_date.required' => 'Tanggal pemeliharaan berikutnya wajib diisi.',
        ];
    }
}
