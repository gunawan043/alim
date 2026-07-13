<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;

class MaintenanceScheduleStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'target_type' => 'required|in:asset,building,room',
            'asset_id' => 'required_if:target_type,asset|nullable|exists:assets,id',
            'building_id' => 'required_if:target_type,building|nullable|exists:asset_buildings,id',
            'room_id' => 'required_if:target_type,room|nullable|exists:asset_rooms,id',
            'maintenance_type' => 'required|string|max:100',
            'frequency' => 'required|in:'.implode(',', AssetMaintenanceSchedule::FREQUENCY_OPTIONS),
            'next_maintenance_date' => 'required|date',
            'responsible_user_id' => 'nullable|exists:users,id',
            'vendor_name' => 'nullable|string|max:191',
            'estimated_cost' => 'nullable|numeric|min:0',
            'reminder_days_before' => 'nullable|integer|min:1|max:90',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'target_type.required' => 'Tipe target pemeliharaan wajib dipilih.',
            'frequency.required' => 'Frekuensi pemeliharaan wajib dipilih.',
            'maintenance_type.required' => 'Jenis pemeliharaan wajib diisi.',
            'next_maintenance_date.required' => 'Tanggal pemeliharaan berikutnya wajib diisi.',
        ];
    }
}
