<?php

namespace App\Services;

use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Facades\Cache;

/**
 * Notifikasi ke wali saat nilai satu buku mapel dipublikasikan atau berubah.
 *
 * Dipakai oleh publikasi manual (NilaiKelasController) dan auto publish
 * (NilaiGuruController). Update nilai setelah publish dikirim sebagai event
 * `nilai_updated`; autosave per sel dibatasi 60 detik per buku agar tidak
 * membanjiri wali.
 */
class NilaiPublishNotificationService
{
    public const UPDATE_COOLDOWN_SECONDS = 60;

    public function __construct(private NotificationUniversalService $notifications) {}

    /**
     * @return int Jumlah notifikasi yang dibuat (satu per akun wali).
     */
    public function notifyBookPublished(TeacherAdminBook $book, ?string $jenis = null): int
    {
        $this->markUpdateCooldown($book);

        return $this->send(
            $book,
            'nilai_published',
            'Nilai '.($book->subject?->name ?? 'mapel').' dipublikasikan',
            'Nilai '.($book->subject?->name ?? 'mapel').' untuk '.($book->studyGroup?->name ?? 'kelas').' sudah dapat dilihat.',
            $jenis,
        );
    }

    /**
     * Kirim notifikasi update, maksimal sekali per buku dalam 60 detik.
     *
     * @return int Jumlah notifikasi yang dibuat (0 bila sedang cooldown).
     */
    public function notifyBookUpdated(TeacherAdminBook $book, ?string $jenis = null): int
    {
        $cacheKey = $this->updateCacheKey($book);

        if (! Cache::add($cacheKey, now()->timestamp, self::UPDATE_COOLDOWN_SECONDS)) {
            return 0;
        }

        return $this->send(
            $book,
            'nilai_updated',
            'Nilai '.($book->subject?->name ?? 'mapel').' diperbarui',
            'Nilai '.($book->subject?->name ?? 'mapel').' untuk '.($book->studyGroup?->name ?? 'kelas').' telah diperbarui. Buka aplikasi untuk melihat nilai terbaru.',
            $jenis,
        );
    }

    private function send(
        TeacherAdminBook $book,
        string $action,
        string $title,
        string $message,
        ?string $jenis,
    ): int {
        $book->loadMissing(['subject', 'studyGroup']);

        $studentIds = StudentClassHistory::query()
            ->where('study_group_id', $book->study_group_id)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $data = [
            'screen' => 'grades',
            'book_id' => $book->id,
            'subject_id' => $book->subject_id,
            'study_group_id' => $book->study_group_id,
            'semester' => $book->semester,
            'academic_year_id' => $book->academic_year_id,
        ];

        if ($jenis !== null) {
            $data['jenis'] = $jenis;
        }

        return $this->notifications->notifyGuardiansForStudents($studentIds, [
            'module' => 'academic',
            'reference_type' => 'teacher_admin_book',
            'reference_id' => $book->id,
            'type' => 'info',
            'action' => $action,
            'title' => $title,
            'message' => $message,
            'priority' => 'medium',
            'send_push' => true,
            'data' => $data,
        ]);
    }

    private function markUpdateCooldown(TeacherAdminBook $book): void
    {
        Cache::put(
            $this->updateCacheKey($book),
            now()->timestamp,
            self::UPDATE_COOLDOWN_SECONDS,
        );
    }

    private function updateCacheKey(TeacherAdminBook $book): string
    {
        return 'nilai-update-notified:'.$book->id;
    }
}
