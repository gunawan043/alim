<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'from_room_id' => 'required|exists:dormitory_rooms,id',
            'to_room_id' => 'required|exists:dormitory_rooms,id|different:from_room_id',
            'move_date' => 'required|date',
            'reason' => 'nullable|string',
            'move_type' => 'nullable|in:reguler,disciplinary,medical,upgrade,other',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'to_room_id.different' => 'Kamar tujuan harus berbeda dari kamar asal.',
            'move_date.required' => 'Tanggal mutasi wajib diisi.',
        ];
    }
}
