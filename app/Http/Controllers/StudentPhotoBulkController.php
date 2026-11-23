<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UploadsSecure;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class StudentPhotoBulkController extends Controller
{
    use UploadsSecure;

    private const MAX_FILE_BYTES = 2 * 1024 * 1024;
    private const MAX_ZIP_ENTRIES = 1000;

    public function form(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);
        $schools = collect();

        if (! $schoolId && $request->attributes->get('isGlobalView')) {
            $schools = School::orderBy('name')->get();
            $schoolId = $request->input('school_id');

            if ($schoolId && ! $schools->contains('id', $schoolId)) {
                $schoolId = null;
            }
        }

        if (! $schoolId && $schools->isEmpty()) {
            return redirect()
                ->route('user.students.index', ['userId' => $userId])
                ->with('error', 'Satuan pendidikan tidak terdeteksi. Pilih sekolah terlebih dahulu.');
        }

        $school = $schoolId ? School::find($schoolId) : null;
        $studyGroup = null;
        $studyGroupId = $request->input('study_group_id');

        if ($schoolId && $studyGroupId) {
            $studyGroup = StudyGroup::query()
                ->whereKey($studyGroupId)
                ->where('school_id', $schoolId)
                ->first();

            if (! $studyGroup) {
                $studyGroupId = null;
            }
        }

        $studentQuery = $schoolId
            ? Student::withoutGlobalScope('school_context')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
            : null;

        if ($studentQuery && $studyGroup) {
            $studentQuery->whereIn('id', StudentClassHistory::query()
                ->where('study_group_id', $studyGroup->id)
                ->where('is_active', true)
                ->select('student_id'));
        }

        $studentCount = $studentQuery ? $studentQuery->count() : 0;

        return view('students.photos.bulk', compact(
            'userId', 'schoolId', 'school', 'studentCount', 'schools', 'studyGroup', 'studyGroupId'
        ));
    }

    public function process(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return back()->with('error', 'Satuan pendidikan tidak terdeteksi. Pilih sekolah terlebih dahulu.');
        }

        $request->validate([
            'photos' => ['nullable', 'array', 'max:1000'],
            'photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'archive' => ['nullable', 'file', 'mimes:zip', 'max:102400'],
        ], [
            'photos.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'archive.mimes' => 'Arsip harus berformat ZIP.',
            'archive.max' => 'Ukuran ZIP maksimal 100 MB.',
        ]);

        $files = $this->collectFiles($request);

        if ($files === []) {
            return back()->with('error', 'Tidak ada file foto yang bisa diproses. Pilih foto atau ZIP terlebih dahulu.');
        }

        $studyGroupId = $request->input('study_group_id');
        $studyGroup = null;

        if ($studyGroupId) {
            $studyGroup = StudyGroup::query()
                ->whereKey($studyGroupId)
                ->where('school_id', $schoolId)
                ->first();

            if (! $studyGroup) {
                return back()->with('error', 'Rombel yang dipilih tidak valid untuk satuan pendidikan ini.');
            }
        }

        $studentQuery = Student::withoutGlobalScope('school_context')
            ->where('school_id', $schoolId)
            ->where('status', 'active');

        if ($studyGroup) {
            $studentQuery->whereIn('id', StudentClassHistory::query()
                ->where('study_group_id', $studyGroup->id)
                ->where('is_active', true)
                ->select('student_id'));
        }

        $students = $studentQuery->get(['id', 'name', 'nis', 'nisn', 'photo_path']);

        $lookup = [];
        foreach ($students as $student) {
            foreach ([$student->nisn, $student->nis] as $identifier) {
                $identifier = strtolower(trim((string) $identifier));
                if ($identifier !== '' && ! isset($lookup[$identifier])) {
                    $lookup[$identifier] = $student;
                }
            }
        }

        $result = [
            'total' => count($files),
            'success' => 0,
            'failed' => 0,
            'unmatched' => 0,
            'failures' => [],
        ];
        $processedStudentIds = [];

        foreach ($files as $entry) {
            /** @var UploadedFile $file */
            $file = $entry['file'];
            $tmp = $entry['tmp'] ?? null;
            $name = $file->getClientOriginalName();

            try {
                $key = strtolower(trim(pathinfo($name, PATHINFO_FILENAME)));
                $student = $lookup[$key] ?? null;

                if (! $student) {
                    $result['unmatched']++;
                    $result['failed']++;
                    $this->addFailure($result, $name, 'NIS/NISN tidak ditemukan atau santri tidak aktif.');
                    continue;
                }

                if (isset($processedStudentIds[$student->id])) {
                    $result['failed']++;
                    $this->addFailure($result, $name, 'Duplikat: santri ini sudah mendapat foto lain dalam proses yang sama.');
                    continue;
                }

                $mime = $file->getMimeType();

                if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $result['failed']++;
                    $this->addFailure($result, $name, 'Format gambar tidak didukung.');
                    continue;
                }

                if ((int) $file->getSize() > self::MAX_FILE_BYTES) {
                    $result['failed']++;
                    $this->addFailure($result, $name, 'Ukuran foto melebihi 2 MB.');
                    continue;
                }

                $path = $this->storeSecureFile($file, 'public', 'students/photos', null);

                if (! $path) {
                    $result['failed']++;
                    $this->addFailure($result, $name, 'Foto gagal disimpan.');
                    continue;
                }

                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }

                $student->update(['photo_path' => $path]);
                $processedStudentIds[$student->id] = true;
                $result['success']++;
            } finally {
                if (is_string($tmp) && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'STUDENT_PHOTOS_BULK_UPLOAD',
            'table_name' => 'students',
            'record_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'school_id' => $schoolId,
                'study_group_id' => $studyGroup?->id,
                'total' => $result['total'],
                'success' => $result['success'],
                'failed' => $result['failed'],
                'unmatched' => $result['unmatched'],
            ],
        ]);

        return redirect()
            ->route('user.students.photos.bulk-form', ['userId' => $userId])
            ->with('photo_bulk_result', $result)
            ->with(
                $result['success'] > 0 ? 'success' : 'error',
                $result['success'] > 0
                    ? "Upload foto masal selesai: {$result['success']} foto berhasil disimpan."
                    : 'Tidak ada foto yang berhasil disimpan.'
            );
    }

    /**
     * @return array<int, array{file: UploadedFile, tmp: ?string}>
     */
    private function collectFiles(Request $request): array
    {
        $entries = [];

        foreach ($request->file('photos', []) as $file) {
            if ($file instanceof UploadedFile) {
                $entries[] = ['file' => $file, 'tmp' => null];
            }
        }

        if ($request->hasFile('archive')) {
            $archive = $request->file('archive');

            if (! class_exists(ZipArchive::class)) {
                throw ValidationException::withMessages([
                    'archive' => 'Ekstensi ZIP PHP belum aktif di server.',
                ]);
            }

            $zip = new ZipArchive;

            if ($archive instanceof UploadedFile && $zip->open($archive->getRealPath()) !== true) {
                throw ValidationException::withMessages([
                    'archive' => 'File ZIP tidak dapat dibuka.',
                ]);
            }

            try {
                if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                    throw ValidationException::withMessages([
                        'archive' => 'Isi ZIP maksimal '.self::MAX_ZIP_ENTRIES.' file per upload.',
                    ]);
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $name = $stat['name'] ?? '';

                    if ($name === '' || str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/')) {
                        continue;
                    }

                    $base = basename($name);

                    if ($base === '' || str_starts_with($base, '.')) {
                        continue;
                    }

                    $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));

                    if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        continue;
                    }

                    if ((int) ($stat['size'] ?? 0) > self::MAX_FILE_BYTES) {
                        throw ValidationException::withMessages([
                            'archive' => 'File '.$base.' di dalam ZIP melebihi 2 MB.',
                        ]);
                    }

                    $contents = $zip->getFromIndex($i);

                    if ($contents === false) {
                        continue;
                    }

                    $tmp = tempnam(sys_get_temp_dir(), 'student_photo_');

                    if ($tmp === false) {
                        continue;
                    }

                    file_put_contents($tmp, $contents);
                    $entries[] = [
                        'file' => new UploadedFile($tmp, $base, null, null, true),
                        'tmp' => $tmp,
                    ];
                }
            } finally {
                $zip->close();
            }
        }

        return $entries;
    }

    private function resolveSchoolId(Request $request): ?string
    {
        if ($request->attributes->get('isGlobalView') && $request->filled('school_id')) {
            $schoolId = School::whereKey($request->input('school_id'))->value('id');

            if ($schoolId) {
                return (string) $schoolId;
            }
        }

        $schoolId = $request->attributes->get('schoolContextId')
            ?: $request->user()?->school_id;

        return $schoolId ? (string) $schoolId : null;
    }

    private function addFailure(array &$result, string $filename, string $reason): void
    {
        if (count($result['failures']) < 100) {
            $result['failures'][] = ['file' => $filename, 'reason' => $reason];
        }
    }
}
