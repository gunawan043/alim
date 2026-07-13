<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class ReviewDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()->role ?? '', ['admin', 'sarpras_pic']);
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:verified,rejected',
            'review_notes' => 'nullable|string|max:5000',
        ];
    }
}
