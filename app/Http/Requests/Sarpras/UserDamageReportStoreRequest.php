<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class UserDamageReportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'damage_level' => 'required|in:ringan,sedang,berat',
            'description' => 'required|string',
            'notes' => 'nullable|string',
        ];
    }
}
