<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkOrderFromRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()->role ?? '', ['admin', 'sarpras_pic']);
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|uuid|exists:assets,id',
            'assignee_id' => 'required|uuid|exists:users,id',
            'type' => 'required|in:repair,maintenance,inspection,installation',
            'scope_of_work' => 'required|string|min:5|max:5000',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'estimated_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}