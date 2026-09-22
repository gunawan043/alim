<?php

declare(strict_types=1);

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dormitory_ids' => [
                'nullable',
                'array',
            ],
            'dormitory_ids.*' => [
                'string',
                'uuid',
                'exists:dormitories,id',
            ],
            'start_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('end_date')) {
                        if ($value > $this->input('end_date')) {
                            $fail('Tanggal mulai harus sebelum atau sama dengan tanggal akhir.');
                        }
                    }
                },
            ],
            'end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('start_date')) {
                        if ($value < $this->input('start_date')) {
                            $fail('Tanggal akhir harus setelah atau sama dengan tanggal mulai.');
                        }
                    }
                },
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
