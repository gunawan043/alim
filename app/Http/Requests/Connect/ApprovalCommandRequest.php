<?php

namespace App\Http\Requests\Connect;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Validasi bentuk payload command persetujuan dari ALIM Connect.
 *
 * Catatan keamanan: `actor_core_person_id` hanya diterima sebagai referensi
 * audit. Otorisasi aktor selalu diselesaikan Core lewat pemetaan identitas
 * WhatsApp yang terverifikasi (`connect_actor_identities`), bukan dari payload.
 */
class ApprovalCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'command_id' => ['required', 'string', 'max:100'],
            'request_id' => ['required', 'uuid'],
            'decision' => ['required', Rule::in([
                'approved',
                'rejected',
                'needs_clarification',
                'expired',
                'cancelled',
            ])],
            'approved_duration_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.(int) config('connect_approval.max_duration_days', 365),
                'required_if:decision,approved',
                'prohibited_if:decision,rejected,needs_clarification,expired,cancelled',
            ],
            'actor_external_id' => ['required', 'string', 'max:191'],
            'actor_core_person_id' => ['nullable', 'uuid'],
            'source' => ['required', Rule::in(['whatsapp'])],
            'external_message_id' => ['required', 'string', 'max:191'],
            'message_text' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approved_duration_days.required_if' => 'Durasi izin wajib disebutkan untuk keputusan approved.',
            'approved_duration_days.prohibited_if' => 'Keputusan rejected tidak boleh membawa durasi.',
            'source.in' => 'Sumber keputusan harus whatsapp.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Payload command tidak valid.',
            'data' => null,
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => [
                'code' => 'validation_failed',
                'context' => $validator->errors()->toArray(),
            ],
        ], 422));
    }
}
