<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRejectWaliRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'note'   => 'nullable|string|max:500',
        ];
    }
}