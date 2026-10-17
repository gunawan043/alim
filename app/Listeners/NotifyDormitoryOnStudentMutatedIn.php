<?php

namespace App\Listeners;

use App\Events\StudentMutatedIn;
use App\Models\Dormitory;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Santri MUTASI MASUK → masuk daftar "belum berkamar" di asrama yang sesuai
 * (satuan pendidikan + gender) dan petugas asrama mendapat notifikasi untuk
 * menempatkan kamar.
 */
class NotifyDormitoryOnStudentMutatedIn
{
    public function handle(StudentMutatedIn $event): void
    {
        try {
            $student = $event->student;
            $school = \App\Models\School::find($student->school_id);

            // Sekolah bertipe boarding → langsung tandai boarding agar
            // otomatis muncul di daftar "Tarik Data Santri Belum Berkamar".
            if ($school && $school->boarding_type === 'boarding' && ! $student->boarding_status) {
                $student->forceFill(['boarding_status' => 'boarding'])->saveQuietly();
            }

            $genderCode = $student->gender === 'L' ? 'putra' : ($student->gender === 'P' ? 'putri' : null);

            $dormitories = Dormitory::query()
                ->where('school_id', $student->school_id)
                ->where('is_active', true)
                ->when($genderCode, fn ($q) => $q->whereIn('gender', [$genderCode, 'campuran']))
                ->get();

            foreach ($dormitories as $dormitory) {
                app(NotificationUniversalService::class)->notifyDormitoryStaff($dormitory->id, [
                    'module' => 'dormitory',
                    'reference_type' => 'student',
                    'reference_id' => $student->id,
                    'type' => 'info',
                    'action' => 'student_mutated_in',
                    'title' => 'Santri mutasi masuk: '.$student->name,
                    'message' => 'Belum memiliki kamar — silakan tempatkan melalui halaman Penempatan Kamar.',
                    'priority' => 'medium',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Notif mutasi masuk asrama gagal: '.$e->getMessage());
        }
    }
}
