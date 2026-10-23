<?php

declare(strict_types=1);

namespace App\Http\Requests\Dormitory;

use App\Models\DormitoryReward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRewardRequest extends FormRequest
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
            'point_catalog_id' => [
                'nullable',
                'uuid',
                Rule::exists('dormitory_point_catalogs', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('kind', 'reward')
                ),
            ],
            'title' => 'nullable|required_without:point_catalog_id|string|max:255',
            'category' => [
                'nullable',
                'required_without:point_catalog_id',
                Rule::in(array_keys(DormitoryReward::categories())),
            ],
            'description' => 'nullable|string|max:1000',
            'level' => [
                'nullable',
                'required_without:point_catalog_id',
                Rule::in(array_keys(DormitoryReward::levels())),
            ],
            'points' => 'nullable|integer|min:0',
            'proof_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'awarded_date' => 'required|date',
            'send_notification' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Kategori penghargaan tidak valid.',
            'level.in' => 'Level penghargaan tidak valid.',
            'point_catalog_id.exists' => 'Katalog penghargaan tidak aktif atau tidak ditemukan.',
        ];
    }
}
