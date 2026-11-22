<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Models\EkstrakurikulerAnggota;
use App\Models\ExamSchedule;
use App\Models\JadwalKbm;
use App\Models\LibraryLoan;
use App\Models\LibraryMember;
use App\Models\LibraryVisit;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentClassHistory;
use App\Models\StudentHealthCheckup;
use App\Models\StudentHealthMetric;
use App\Models\StudentHealthPermit;
use App\Models\StudentMedicineLog;
use App\Models\Uks\UksPatient;
use App\Models\WaliSantri;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Informasi tambahan per anak untuk wali: kesehatan, perpustakaan,
 * riwayat kelas, asrama, ekstrakurikuler, lomba, guru, dan ujian.
 */
class WaliChildInformationController extends Controller
{
    use ApiResponse;

    public function healthHistory(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $checkups = StudentHealthCheckup::where('student_id', $student->id)
            ->latest('checkup_date')->limit(30)->get()
            ->map(fn (StudentHealthCheckup $row) => [
                'id' => $row->id,
                'date' => $row->checkup_date?->toDateString(),
                'type' => $row->checkup_type,
                'height_cm' => $row->height_cm,
                'weight_kg' => $row->weight_kg,
                'bmi' => $row->bmi,
                'bmi_category' => $row->bmi_category,
                'vision_left' => $row->vision_left,
                'vision_right' => $row->vision_right,
                'hearing_status' => $row->hearing_status,
                'dental_status' => $row->dental_status,
                'notes' => $row->notes,
            ]);

        $metrics = StudentHealthMetric::where('student_id', $student->id)
            ->latest('record_date')->limit(30)->get()
            ->map(fn (StudentHealthMetric $row) => [
                'id' => $row->id,
                'date' => $row->record_date?->toDateString(),
                'height_cm' => $row->height_cm,
                'weight_kg' => $row->weight_kg,
                'bmi' => $row->bmi,
                'bmi_category' => $row->bmi_category,
                'measurement_session' => $row->measurement_session,
                'notes' => $row->notes,
            ]);

        $permits = StudentHealthPermit::where('student_id', $student->id)
            ->latest('start_date')->limit(30)->get()
            ->map(fn (StudentHealthPermit $row) => [
                'id' => $row->id,
                'type' => $row->permit_type,
                'type_label' => $row->permit_type_text,
                'description' => $row->description,
                'start_date' => $row->start_date?->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
                'rest_days' => $row->rest_days,
                'status' => $row->status,
                'status_label' => $row->status_text,
                'approval_note' => $row->approval_note,
            ]);

        $uksVisits = UksPatient::where('student_id', $student->id)
            ->latest('arrived_at')->limit(30)->get()
            ->map(fn (UksPatient $row) => [
                'id' => $row->id,
                'arrived_at' => $row->arrived_at?->toIso8601String(),
                'discharged_at' => $row->discharged_at?->toIso8601String(),
                'status' => $row->status,
                'chief_complaint' => $row->chief_complaint,
                'diagnosis' => $row->diagnosis,
                'treatment' => $row->treatment,
                'medicine_given' => $row->medicine_given,
                'referred_to_faskes' => $row->referred_to_faskes,
                'referral_reason' => $row->referral_reason,
                'notes' => $row->notes,
            ]);

        $medicines = StudentMedicineLog::with('inventory:id,medicine_name')
            ->where('student_id', $student->id)
            ->latest('log_date')->limit(30)->get()
            ->map(fn (StudentMedicineLog $row) => [
                'id' => $row->id,
                'date' => $row->log_date?->toDateString(),
                'time_given' => $row->time_given,
                'medicine' => $row->inventory?->medicine_name,
                'quantity' => $row->quantity_given,
                'dosage' => $row->dosage,
                'purpose' => $row->purpose,
                'notes' => $row->notes,
            ]);

        return $this->ok([
            'checkups' => $checkups,
            'growth' => $metrics,
            'permits' => $permits,
            'uks_visits' => $uksVisits,
            'medicines' => $medicines,
        ], 'Riwayat kesehatan berhasil diambil.');
    }

    public function libraryLoans(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);
        $memberIds = LibraryMember::where('student_id', $student->id)->pluck('id');

        if ($memberIds->isEmpty()) {
            return $this->ok(['records' => [], 'total' => 0], 'Belum terdaftar sebagai anggota perpustakaan.');
        }

        $records = LibraryLoan::with(['unit:id,name', 'items.item.title:id,title,author'])
            ->whereIn('library_member_id', $memberIds)
            ->latest('loan_date')->limit(50)->get()
            ->map(fn (LibraryLoan $loan) => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'unit' => $loan->unit?->name,
                'loan_date' => $loan->loan_date?->toDateString(),
                'due_date' => $loan->due_date?->toDateString(),
                'returned_at' => $loan->returned_at?->toDateString(),
                'status' => $loan->status,
                'fine_amount' => (float) $loan->fine_amount,
                'fine_paid_at' => $loan->fine_paid_at?->toIso8601String(),
                'items' => $loan->items->map(fn ($item) => [
                    'title' => $item->item?->title?->title,
                    'author' => $item->item?->title?->author,
                    'inventory_code' => $item->item?->inventory_code,
                    'returned_at' => $item->returned_at?->toDateString(),
                    'status' => $item->status,
                ])->values(),
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Riwayat peminjaman berhasil diambil.');
    }

    public function libraryVisits(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);
        $memberIds = LibraryMember::where('student_id', $student->id)->pluck('id');

        $records = LibraryVisit::with('unit:id,name')
            ->when($memberIds->isNotEmpty(), fn ($query) => $query->whereIn('library_member_id', $memberIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->latest('visit_date')->limit(50)->get()
            ->map(fn (LibraryVisit $visit) => [
                'id' => $visit->id,
                'unit' => $visit->unit?->name,
                'date' => $visit->visit_date?->toDateString(),
                'purpose' => $visit->purpose,
                'notes' => $visit->notes,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Riwayat kunjungan perpustakaan berhasil diambil.');
    }

    public function libraryMemberCard(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);
        $members = LibraryMember::with(['unit:id,name', 'cards' => fn ($query) => $query->latest('issued_at')])
            ->where('student_id', $student->id)->get();

        return $this->ok([
            'members' => $members->map(fn (LibraryMember $member) => [
                'member_number' => $member->member_number,
                'unit' => $member->unit?->name,
                'status' => $member->status,
                'joined_at' => $member->joined_at?->toDateString(),
                'cards' => $member->cards->map(fn ($card) => [
                    'card_number' => $card->card_number,
                    'status' => $card->status,
                    'issued_at' => $card->issued_at?->toDateString(),
                    'valid_until' => $card->valid_until?->toDateString(),
                ])->values(),
            ])->values(),
        ], 'Kartu anggota perpustakaan berhasil diambil.');
    }

    public function classHistory(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $records = StudentClassHistory::with(['studyGroup.gradeLevel:id,name,level', 'studyGroup.homeroomTeacher:id,name', 'academicYear:id,name,semester'])
            ->where('student_id', $student->id)
            ->orderByDesc('join_date')
            ->get()
            ->map(fn (StudentClassHistory $row) => [
                'id' => $row->id,
                'academic_year' => $row->academicYear?->name,
                'semester' => $row->academicYear?->semester,
                'class' => $row->studyGroup?->name,
                'grade_level' => $row->studyGroup?->gradeLevel?->name,
                'homeroom_teacher' => $row->studyGroup?->homeroomTeacher?->name,
                'attendance_number' => $row->attendance_number,
                'join_date' => $row->join_date?->toDateString(),
                'leave_date' => $row->leave_date?->toDateString(),
                'is_active' => (bool) $row->is_active,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Riwayat kelas berhasil diambil.');
    }

    public function dormitoryHistory(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $records = DormitoryResident::with(['dormitory:id,name', 'room.wing:id,name'])
            ->where('student_id', $student->id)
            ->orderByDesc('check_in_date')
            ->get()
            ->map(fn (DormitoryResident $row) => [
                'id' => $row->id,
                'dormitory' => $row->dormitory?->name,
                'wing' => $row->room?->wing?->name,
                'room' => $row->room?->code,
                'bed_number' => $row->bed_number,
                'check_in_date' => $row->check_in_date?->toDateString(),
                'check_out_date' => $row->check_out_date?->toDateString(),
                'check_out_reason' => $row->check_out_reason,
                'is_active' => (bool) $row->is_active,
                'notes' => $row->notes,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Riwayat asrama berhasil diambil.');
    }

    public function dormitoryRewards(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $records = DormitoryReward::with('dormitory:id,name')
            ->where('student_id', $student->id)
            ->latest('awarded_date')->limit(50)->get()
            ->map(fn (DormitoryReward $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'category' => $row->category,
                'description' => $row->description,
                'level' => $row->level,
                'points' => $row->points,
                'awarded_date' => $row->awarded_date?->toDateString(),
                'dormitory' => $row->dormitory?->name,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Penghargaan asrama berhasil diambil.');
    }

    public function extracurricular(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $records = EkstrakurikulerAnggota::with('ekstrakurikuler:id,nama,pembimbing,hari,jam_mulai,jam_selesai,lokasi,status')
            ->where('student_id', $student->id)
            ->latest('tanggal_bergabung')->get()
            ->map(fn (EkstrakurikulerAnggota $row) => [
                'id' => $row->id,
                'name' => $row->ekstrakurikuler?->nama,
                'mentor' => $row->ekstrakurikuler?->pembimbing,
                'day' => $row->ekstrakurikuler?->hari,
                'start_time' => $row->ekstrakurikuler?->jam_mulai,
                'end_time' => $row->ekstrakurikuler?->jam_selesai,
                'location' => $row->ekstrakurikuler?->lokasi,
                'status' => $row->status,
                'joined_at' => $row->tanggal_bergabung?->toDateString(),
                'left_at' => $row->tanggal_keluar?->toDateString(),
                'notes' => $row->keterangan,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Data ekstrakurikuler berhasil diambil.');
    }

    public function competitions(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);

        $records = StudentAchievement::where('student_id', $student->id)
            ->whereNotNull('event_name')
            ->latest('event_date')->limit(50)->get()
            ->map(fn (StudentAchievement $row) => [
                'id' => $row->id,
                'event_name' => $row->event_name,
                'organizer' => $row->organizer,
                'level' => $row->level,
                'level_label' => $row->level_label,
                'position' => $row->position,
                'position_label' => $row->position_label,
                'event_date' => $row->event_date?->toDateString(),
                'event_location' => $row->event_location,
                'is_verified' => (bool) $row->is_verified,
                'certificate_available' => (bool) $row->certificate_path,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Data lomba dan prestasi berhasil diambil.');
    }

    public function teachers(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);
        $current = StudentClassHistory::with(['studyGroup.homeroomTeacher:id,name', 'studyGroup.gradeLevel:id,name'])
            ->where('student_id', $student->id)->where('is_active', true)->first();

        $teachers = collect();

        if ($current?->studyGroup) {
            if ($current->studyGroup->homeroomTeacher) {
                $teachers->push([
                    'id' => $current->studyGroup->homeroomTeacher->id,
                    'name' => $current->studyGroup->homeroomTeacher->name,
                    'role' => 'Wali Kelas',
                    'subject' => null,
                ]);
            }

            $schedules = JadwalKbm::with(['teacher:id,name', 'subject:id,name'])
                ->where('study_group_id', $current->studyGroup->id)
                ->where('is_active', true)
                ->get();

            foreach ($schedules as $schedule) {
                if (! $schedule->teacher || ! $schedule->subject) {
                    continue;
                }
                $teachers->push([
                    'id' => $schedule->teacher->id,
                    'name' => $schedule->teacher->name,
                    'role' => 'Guru Mata Pelajaran',
                    'subject' => $schedule->subject->name,
                ]);
            }
        }

        return $this->ok([
            'current_class' => $current?->studyGroup?->name,
            'grade_level' => $current?->studyGroup?->gradeLevel?->name,
            'teachers' => $teachers->unique('id')->values(),
        ], 'Kontak guru berhasil diambil.');
    }

    public function examSchedules(Request $request, string $studentId): JsonResponse
    {
        $student = $this->student($request, $studentId);
        $current = StudentClassHistory::where('student_id', $student->id)->where('is_active', true)->first();

        if (! $current) {
            return $this->ok(['records' => [], 'total' => 0], 'Santri belum memiliki kelas aktif.');
        }

        $records = ExamSchedule::with(['subject:id,name', 'teacher:id,name'])
            ->where('study_group_id', $current->study_group_id)
            ->where('tanggal_ujian', '>=', now()->toDateString())
            ->orderBy('tanggal_ujian')
            ->get()
            ->map(fn (ExamSchedule $row) => [
                'id' => $row->id,
                'subject' => $row->subject?->name,
                'teacher' => $row->teacher?->name,
                'type' => $row->jenis_ujian,
                'date' => $row->tanggal_ujian?->toDateString(),
                'start_time' => $row->waktu_mulai?->format('H:i'),
                'end_time' => $row->waktu_selesai?->format('H:i'),
                'room' => $row->ruangan,
                'status' => $row->status,
                'notes' => $row->catatan,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Jadwal ujian berhasil diambil.');
    }

    private function student(Request $request, string $studentId): Student
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_if($schoolId === null, 404, 'Konteks sekolah tidak tersedia.');

        $hasAccess = WaliSantri::where('user_id', $request->user()->id)
            ->where('student_id', $studentId)
            ->where('school_id', $schoolId)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->exists();

        abort_unless($hasAccess, 404, 'Santri tidak ditemukan atau Anda tidak memiliki akses.');

        return Student::with('school:id,name,school_code')->findOrFail($studentId);
    }
}
