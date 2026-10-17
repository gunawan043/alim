<?php

declare(strict_types=1);

namespace App\Http\Requests\Dormitory;

use App\Models\DormitoryReward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
            'title' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(DormitoryReward::categories()))],
            'description' => 'nullable|string|max:1000',
            'level' => ['required', Rule::in(array_keys(DormitoryReward::levels()))],
            'proof_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'awarded_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Kategori penghargaan tidak valid.',
            'level.in' => 'Level penghargaan tidak valid.',
        ];
    }
}
