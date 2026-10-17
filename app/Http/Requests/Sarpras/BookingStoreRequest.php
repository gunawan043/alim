<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:asset_rooms,id',
            'event_name' => 'nullable|string|max:191',
            'purpose' => 'required|string',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'setup_time' => 'nullable',
            'participants_count' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Ruang wajib dipilih.',
            'purpose.required' => 'Tujuan booking wajib diisi.',
            'booking_date.required' => 'Tanggal booking wajib diisi.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}
