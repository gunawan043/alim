<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        $wo = $this->route('workOrder');

        return $user->id == $wo->assignee_id
            || in_array($user->role ?? '', ['admin', 'sarpras_pic']);
    }

    public function rules(): array
    {
        return [
            'progress_type' => 'required|in:note,photo,sparepart,cost',
            'notes' => 'required_if:progress_type,note|required|string|max:5000',
            'photos.*' => 'nullable|image|max:5120',
            'before_photos.*' => 'nullable|image|max:5120',
            'after_photos.*' => 'nullable|image|max:5120',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'status' => 'sometimes|required|in:assigned,accepted,working,waiting_sparepart,completed,closed,cancelled',
        ];
    }
}
