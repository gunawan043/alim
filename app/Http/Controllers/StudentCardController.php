<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UploadsSecure;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCardSetting;
use App\Models\StudyGroup;
use App\Services\Ljk\LjkQrRenderer;
use App\Services\Pdf\DocumentPdfService;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentCardController extends Controller
{
    use UploadsSecure;

    public function index(Request $request, string $userId)
    {
        abort_unless($this->canManageCards($request), 403, 'Pengaturan kartu pelajar hanya untuk KSP, Waka, dan TU.');

        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return redirect()
                ->route('user.students.index', ['userId' => $userId])
                ->with('error', 'Satuan pendidikan tidak terdeteksi.');
        }

        $school = School::findOrFail($schoolId);
        $setting = StudentCardSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['card_width_mm' => 85.60, 'card_height_mm' => 53.98, 'layout' => StudentCardSetting::defaultLayout()],
        );

        $query = $this->studentQuery($schoolId);

        if ($request->filled('q')) {
            $search = '%'.trim((string) $request->q).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('nis', 'like', $search)
                    ->orWhere('nisn', 'like', $search);
            });
        }

        $students = (clone $query)->orderBy('name')->limit(50)->get();
        $selected = null;

        if ($request->filled('student_id')) {
            $selected = (clone $query)->whereKey($request->student_id)->first();
        }

        $selected ??= $students->first();

        $layout = $setting->resolvedLayout();
        $frontBackground = $this->storageUrl($setting->front_background_path);
        $backBackground = $this->storageUrl($setting->back_background_path);
        $signature = $this->storageUrl($setting->signature_path ?: $school->ttd_ksp_path);
        $signatureName = $setting->signature_name ?: $school->principal_full_name;
        $signatureTitle = $setting->signature_title ?: 'Kepala Satuan Pendidikan';
        $validityText = $setting->validity_text ?: 'Berlaku selama menjadi santri';
        $backQr = $this->qrData($selected, $layout['back']['back_qr_source'] ?? 'nis');

        return view('students.cards.index', compact(
            'userId',
            'schoolId',
            'school',
            'setting',
            'layout',
            'students',
            'selected',
            'frontBackground',
            'backBackground',
            'signature',
            'signatureName',
            'signatureTitle',
            'validityText',
            'backQr',
        ));
    }

    public function updateSettings(Request $request, string $userId)
    {
        abort_unless($this->canManageCards($request), 403, 'Pengaturan kartu pelajar hanya untuk KSP, Waka, dan TU.');

        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return back()->with('error', 'Satuan pendidikan tidak terdeteksi.');
        }

        $validated = $request->validate([
            'card_width_mm' => ['required', 'numeric', 'min:50', 'max:120'],
            'card_height_mm' => ['required', 'numeric', 'min:30', 'max:100'],
            'signature_name' => ['nullable', 'string', 'max:150'],
            'signature_title' => ['nullable', 'string', 'max:150'],
            'validity_text' => ['nullable', 'string', 'max:150'],
            'front_background' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'back_background' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'layout' => ['nullable', 'array'],
        ]);

        $setting = StudentCardSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['card_width_mm' => 85.60, 'card_height_mm' => 53.98, 'layout' => StudentCardSetting::defaultLayout()],
        );

        foreach ([
            'front_background' => 'front_background_path',
            'back_background' => 'back_background_path',
            'signature' => 'signature_path',
        ] as $field => $column) {
            if ($request->hasFile($field)) {
                $old = $setting->{$column};
                $path = $this->storeSecureFile($request->file($field), 'public', 'students/cards/'.$schoolId, null);

                if ($path) {
                    if ($old) {
                        Storage::disk('public')->delete($old);
                    }
                    $setting->{$column} = $path;
                }
            }
        }

        $setting->card_width_mm = $validated['card_width_mm'];
        $setting->card_height_mm = $validated['card_height_mm'];
        $setting->signature_name = $validated['signature_name'] ?? null;
        $setting->signature_title = $validated['signature_title'] ?? null;
        if (array_key_exists('validity_text', $validated)) {
            $setting->validity_text = $validated['validity_text'];
        }
        $setting->layout = $this->sanitizeLayout($validated['layout'] ?? []);
        $setting->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'STUDENT_CARD_SETTINGS_UPDATED',
            'table_name' => 'student_card_settings',
            'record_id' => $setting->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan kartu tersimpan otomatis.',
            ]);
        }

        return redirect()
            ->route('user.students.cards.index', ['userId' => $userId, 'student_id' => $request->input('selected_student_id')])
            ->with('success', 'Pengaturan kartu pelajar berhasil disimpan.');
    }

    public function preview(Request $request, string $userId)
    {
        $data = $this->buildPrintData($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        return view('students.cards.preview', [
            'userId' => $userId,
            'params' => $this->printParams($request),
            'cards' => $data['cards'],
            'setting' => $data['setting'],
        ]);
    }

    public function printPdf(Request $request, string $userId)
    {
        $data = $this->buildPrintData($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $paper = [0, 0, $this->mmToPt((float) ($data['setting']->card_width_mm ?: 85.60)), $this->mmToPt((float) ($data['setting']->card_height_mm ?: 53.98))];

        return app(DocumentPdfService::class)->fromView(
            'students.cards.pdf',
            $data,
            'Kartu-Pelajar-'.now()->format('Ymd-His'),
            'portrait',
            $paper,
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }

    public function png(Request $request, string $userId)
    {
        $data = $this->buildPrintData($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $workDir = storage_path('app/tmp/kartu-'.uniqid('', true));
        @mkdir($workDir, 0755, true);
        $pdfPath = $workDir.'/kartu.pdf';
        $pattern = $workDir.'/kartu-%03d.png';
        file_put_contents($pdfPath, $pdf);

        try {
            if (! $this->commandExists('gs')) {
                abort(503, 'Server belum menyediakan Ghostscript untuk ekspor PNG.');
            }

            $process = new \Symfony\Component\Process\Process([
                'gs', '-dSAFER', '-dBATCH', '-dNOPAUSE',
                '-sDEVICE=png16m', '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4',
                '-r300', '-sOutputFile='.$pattern, $pdfPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: 'Ghostscript gagal membuat PNG.');
            }

            $files = glob($workDir.'/kartu-*.png') ?: [];
            sort($files);

            if ($files === []) {
                throw new \RuntimeException('Tidak ada file PNG yang dihasilkan.');
            }

            $zipPath = $workDir.'/Kartu-Pelajar-'.now()->format('Ymd-His').'.zip';
            $zip = new \ZipArchive;

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Gagal membuat arsip PNG.');
            }

            foreach ($files as $index => $file) {
                $zip->addFile($file, sprintf('kartu-%02d.png', $index + 1));
            }

            $zip->close();
            @unlink($pdfPath);
            foreach ($files as $file) {
                @unlink($file);
            }
        } catch (\Throwable $e) {
            \Log::warning('Gagal mengekspor kartu pelajar PNG', ['error' => $e->getMessage()]);
            @unlink($pdfPath);
            abort(500, 'Gagal membuat PNG kartu pelajar. Gunakan opsi Download PDF.');
        }

        return response()
            ->download($zipPath, 'Kartu-Pelajar-'.now()->format('Ymd-His').'.zip')
            ->deleteFileAfterSend(true);
    }

    private function buildPrintData(Request $request): array|\Illuminate\Http\RedirectResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $user = $request->user();

        if (! $schoolId) {
            return back()->with('error', 'Satuan pendidikan tidak terdeteksi.');
        }

        if (! $this->canManageCards($request)) {
            $studyGroupId = $request->input('study_group_id');

            if (! $studyGroupId) {
                abort(403, 'Walkes hanya dapat mencetak kartu untuk kelas yang diwalikan.');
            }

            $homeroom = StudyGroup::query()
                ->whereKey($studyGroupId)
                ->where('school_id', $schoolId)
                ->where('homeroom_teacher_id', $user?->id)
                ->first();

            abort_unless($homeroom, 403, 'Anda bukan wali kelas rombel ini.');
            $request->merge(['study_group_id' => $homeroom->id]);
        }

        $query = $this->studentQuery($schoolId);

        if ($request->filled('student_id')) {
            $query->whereKey($request->student_id);
        } elseif (is_array($request->input('ids')) && $request->input('ids') !== []) {
            $query->whereIn('id', $request->input('ids'));
        } elseif ($request->filled('study_group_id')) {
            $query->whereHas('currentClassHistory', fn ($q) => $q->where('study_group_id', $request->study_group_id));
        }

        if ($request->filled('q')) {
            $search = '%'.trim((string) $request->q).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('nis', 'like', $search)
                    ->orWhere('nisn', 'like', $search);
            });
        }

        $students = $query->orderBy('name')->limit(100)->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada santri yang cocok untuk dicetak.');
        }

        $setting = StudentCardSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['card_width_mm' => 85.60, 'card_height_mm' => 53.98, 'layout' => StudentCardSetting::defaultLayout()],
        );

        $school = School::findOrFail($schoolId);
        $layout = $setting->resolvedLayout();
        $frontBg = $this->dataUri($setting->front_background_path);
        $backBg = $this->dataUri($setting->back_background_path);
        $signatureData = $this->dataUri($setting->signature_path ?: $school->ttd_ksp_path);

        $cards = $students->map(function (Student $student) use ($setting, $school, $signatureData, $layout) {
            return [
                'student' => $student,
                'class' => $this->classLabel($student),
                'photo' => $this->dataUri($student->photo_path),
                'school' => $school->name,
                'signature' => $signatureData,
                'signature_name' => $setting->signature_name ?: $school->principal_full_name,
                'signature_title' => $setting->signature_title ?: 'Kepala Satuan Pendidikan',
                'ttl' => $this->ttl($student),
                'address' => $student->address ?: '-',
                'back_qr' => $this->qrData($student, $layout['back']['back_qr_source'] ?? 'nis'),
            ];
        })->all();

        return compact('cards', 'layout', 'setting', 'frontBg', 'backBg');
    }

    /**
     * @return array<string, mixed>
     */
    private function printParams(Request $request): array
    {
        $params = $request->only(['student_id', 'ids', 'study_group_id', 'q']);

        return array_filter($params, fn ($value) => $value !== null && $value !== '');
    }

    private function commandExists(string $command): bool
    {
        $process = new \Symfony\Component\Process\Process(['which', $command]);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    private function mmToPt(float $mm): float
    {
        return round($mm * 72 / 25.4, 3);
    }

    private function canManageCards(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        if (UserJabatan::isSchoolLeader($user)) {
            return true;
        }

        return UserJabatan::hasAnyLoose($user, [
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ]);
    }

    private function studentQuery(string $schoolId)
    {
        return Student::withoutGlobalScope('school_context')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->with(['currentClassHistory.studyGroup.gradeLevel', 'school']);
    }

    private function resolveSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId')
            ?: $request->user()?->employment?->school_id;

        return $schoolId ? (string) $schoolId : null;
    }

    private function sanitizeLayout(array $layout): array
    {
        $default = StudentCardSetting::defaultLayout();
        $clean = [];

        foreach (['front', 'back'] as $side) {
            foreach ($default[$side] as $key => $defaultValue) {
                $input = $layout[$side][$key] ?? null;

                if (is_bool($defaultValue)) {
                    $clean[$side][$key] = ! empty($input);

                    continue;
                }

                if (is_string($defaultValue)) {
                    $clean[$side][$key] = $key === 'back_qr_source'
                        && in_array($input, ['nis', 'nisn', 'both'], true)
                        ? $input
                        : $defaultValue;

                    continue;
                }

                if (! is_numeric($defaultValue)) {
                    continue;
                }

                $min = match ($key) {
                    'line_height' => 1.0,
                    'photo_left', 'photo_top', 'info_left', 'info_top', 'back_qr_left', 'back_qr_top', 'signature_gap' => 0.0,
                    'qr_size', 'back_qr_size' => 5.0,
                    'photo_width', 'photo_height', 'signature_width', 'signature_height' => 5.0,
                    default => 4.0,
                };
                $max = match ($key) {
                    'line_height' => 2.5,
                    'photo_left', 'info_left' => 80.0,
                    'photo_top', 'info_top' => 45.0,
                    'photo_width', 'photo_height' => 80.0,
                    'qr_size' => 40.0,
                    'signature_width' => 60.0,
                    'signature_height' => 50.0,
                    'signature_gap' => 5.0,
                    'back_qr_left' => 100.0,
                    'back_qr_top' => 70.0,
                    'back_qr_size' => 50.0,
                    default => 24.0,
                };

                $clean[$side][$key] = is_numeric($input)
                    ? round(max($min, min($max, (float) $input)), 2)
                    : $defaultValue;
            }
        }

        return $clean;
    }

    private function qrData(?Student $student, string $source = 'nis'): ?string
    {
        if (! $student) {
            return null;
        }

        $nis = trim((string) $student->nis);
        $nisn = trim((string) $student->nisn);

        $payload = match ($source) {
            'nisn' => $nisn !== '' ? $nisn : $nis,
            'both' => trim(($nis !== '' ? 'NIS:'.$nis : '').($nisn !== '' ? ' NISN:'.$nisn : '')),
            default => $nis !== '' ? $nis : $nisn,
        };

        return $payload !== ''
            ? LjkQrRenderer::pngDataUri($payload, 600, 2, 'Q')
            : null;
    }

    private function ttl(Student $student): string
    {
        $place = trim((string) $student->birth_place);
        $date = $student->birth_date?->format('d/m/Y');

        return trim(($place !== '' ? $place : '').($date ? ($place !== '' ? ', ' : '').$date : '')) ?: '-';
    }

    private function classLabel(Student $student): string
    {
        $history = $student->currentClassHistory;
        $group = $history?->studyGroup;

        if (! $group) {
            return '-';
        }

        return trim(($group->gradeLevel?->name ?? '').' '.$group->name) ?: $group->name;
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    private function dataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);

        if (! is_file($absolute)) {
            return null;
        }

        $mime = mime_content_type($absolute) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }
}
