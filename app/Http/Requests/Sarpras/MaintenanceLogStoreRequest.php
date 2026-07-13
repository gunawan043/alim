<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetMaintenanceLog;
use Illuminate\Foundation\Http\FormRequest;

class MaintenanceLogStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'schedule_id' => 'nullable|exists:asset_maintenance_schedules,id',
            'target_type' => 'required|in:asset,building,room',
            'asset_id' => 'required_if:target_type,asset|nullable|exists:assets,id',
            'building_id' => 'required_if:target_type,building|nullable|exists:asset_buildings,id',
            'room_id' => 'required_if:target_type,room|nullable|exists:asset_rooms,id',
            'maintenance_type' => 'required|string|max:100',
            'maintenance_date' => 'required|date',
            'performed_by' => 'nullable|exists:users,id',
            'vendor_name' => 'nullable|string|max:191',
            'actual_cost' => 'nullable|numeric|min:0',
            'condition_before' => 'nullable|in:'.implode(',', AssetMaintenanceLog::CONDITION_OPTIONS),
            'condition_after' => 'nullable|in:'.implode(',', AssetMaintenanceLog::CONDITION_OPTIONS),
            'work_description' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'next_action_needed' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'target_type.required' => 'Tipe target wajib dipilih.',
            'maintenance_type.required' => 'Jenis perawatan wajib diisi.',
            'maintenance_date.required' => 'Tanggal perawatan wajib diisi.',
        ];
    }
}
