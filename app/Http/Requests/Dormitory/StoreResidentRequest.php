<?php

namespace App\Http\Requests\Dormitory;

use App\Services\StudentLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreResidentRequest extends FormRequest
{
    private ?StudentLookupService $lookup = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'room_id' => 'required|exists:dormitory_rooms,id',
            'bed_number' => 'nullable|integer|min:1',
            'check_in_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Pilih santri yang akan ditempatkan.',
            'student_id.exists' => 'Santri tidak ditemukan.',
            'room_id.required' => 'Kamar wajib dipilih.',
            'room_id.exists' => 'Kamar tidak ditemukan.',
            'bed_number.min' => 'Nomor tempat tidur minimal 1.',
            'check_in_date.required' => 'Tanggal penempatan wajib diisi.',
            'check_in_date.date' => 'Format tanggal penempatan tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // nothing to add, but hook into afterValidation
    }

    protected function passedValidation(): void
    {
        $data = $this->validated();
        $service = $this->getLookupService();

        // Validate student is active and eligible for placement
        $validation = $service->validateAssignment(
            $data['student_id'],
            $this->route('asramaUuid'),
            $this->getAcademicYearId()
        );

        if (! $validation->valid) {
            throw ValidationException::withMessages([
                'student_id' => $validation->error,
            ]);
        }

        // Validate room belongs to this dormitory
        $roomBelongsToDormitory = $service->roomBelongsToDormitory(
            $data['room_id'],
            $this->route('asramaUuid')
        );

        if (! $roomBelongsToDormitory) {
            throw ValidationException::withMessages([
                'room_id' => 'Kamar tidak berada di asrama yang dipilih.',
            ]);
        }
    }

    /**
     * Get or create the active academic year ID.
     */
    private function getAcademicYearId(): ?string
    {
        return \App\Models\AcademicYear::where('is_active', true)->value('id');
    }

    /**
     * Lazy-load service to avoid DI issue in FormRequest.
     */
    private function getLookupService(): StudentLookupService
    {
        return $this->lookup ??= app(StudentLookupService::class);
    }
}
