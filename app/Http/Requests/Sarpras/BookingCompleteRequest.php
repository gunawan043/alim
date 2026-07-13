<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class BookingCompleteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'condition_after' => 'nullable|string',
            'actual_start_time' => 'nullable',
            'actual_end_time' => 'nullable',
        ];
    }
}
