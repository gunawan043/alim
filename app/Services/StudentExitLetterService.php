<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentMutationOut;
use App\Services\Documents\SchoolLeaderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Surat mutasi keluar untuk santri yang keluar lewat jalur lain.
 *
 * Naik kelas tidak memerlukan surat. Keluar karena mutasi/pindah WAJIB punya
 * surat, termasuk ketika keputusan keluar diambil dari proses Kenaikan Kelas
 * (Promosi) atau Promosi Massal. Service ini membuatkan draft suratnya agar
 * admin tinggal melengkapi nomor surat dan sekolah tujuan.
 */
class StudentExitLetterService
{
    /**
     * Buat surat mutasi keluar dari aksi "mutasi keluar" pada promosi.
     *
     * @return StudentMutationOut|null null bila surat untuk tanggal keluar tsb sudah ada
     */
    public function createForPromotion(Student $student, ?string $leaveDate, ?string $actorId = null, string $source = 'Promosi'): ?StudentMutationOut
    {
        $leaveDate = $leaveDate ?: now()->toDateString();

        try {
            // Satu surat per tanggal keluar: hindari duplikat bila aksi diulang.
            $existing = StudentMutationOut::where('student_id', $student->id)
                ->whereDate('established_date', $leaveDate)
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $leader = app(SchoolLeaderService::class)->forSchool($student->school);

            return StudentMutationOut::create([
                'student_id' => $student->id,
                'school_id' => $student->school_id,
                'status' => 'approved',
                'submitted_at' => now(),
                'out_type' => 'mutation',
                'established_date' => $leaveDate,
                'established_city' => $this->cityName($student),
                'head_name' => $leader['display_name'],
                'head_title' => 'Kepala Sekolah',
                'head_nupy' => $leader['nupy'],
                'student_nisn' => $student->nisn,
                'student_nis' => $student->nis,
                'student_name' => $student->name,
                'student_gender' => in_array($student->gender, ['L', 'P'], true) ? $student->gender : null,
                'student_birth_date' => $student->birth_date?->toDateString(),
                'student_birth_place' => $student->birth_place,
                'student_address' => $student->address,
                'student_current_class' => $this->currentClassName($student),
                'parent_name' => $student->father_name ?: ($student->mother_name ?: $student->guardian_name),
                'parent_occupation' => $student->father_occupation ?: ($student->mother_occupation ?: $student->guardian_occupation),
                'parent_address' => $student->address,
                'parent_phone' => $student->phone ?: $student->mobile_phone,
                'reason' => 'Mutasi keluar melalui proses Kenaikan Kelas',
                'notes' => 'Dibuat otomatis dari '.$source.'. Lengkapi nomor surat dan sekolah tujuan sebelum dicetak.',
                'requested_by' => $actorId,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Kegagalan membuat surat tidak boleh membatalkan proses promosi,
            // tetapi harus tercatat agar bisa ditindaklanjuti admin.
            Log::error('Gagal membuat surat mutasi keluar dari promosi.', [
                'student_id' => $student->id,
                'leave_date' => $leaveDate,
                'exception' => $e,
            ]);

            return null;
        }
    }

    private function cityName(Student $student): ?string
    {
        $school = $student->school;

        return $school?->city?->display_name ?: $school?->name;
    }

    private function currentClassName(Student $student): ?string
    {
        $name = DB::table('student_class_histories as h')
            ->join('study_groups as g', 'g.id', '=', 'h.study_group_id')
            ->where('h.student_id', $student->id)
            ->where('h.is_active', true)
            ->orderByDesc('h.created_at')
            ->value('g.name');

        return $name ?: null;
    }
}
