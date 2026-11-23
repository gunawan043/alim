<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UploadsSecure;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCardSetting;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentCardController extends Controller
{
    use UploadsSecure;

    public function index(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return redirect()
                ->route('user.students.index', ['userId' => $userId])
                ->with('error', 'Satuan pendidikan tidak terdeteksi.');
        }

        $school = School::findOrFail($schoolId);
        $setting = StudentCardSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['layout' => StudentCardSetting::defaultLayout()],
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
        ));
    }

    public function updateSettings(Request $request, string $userId)
    {
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
            ['layout' => StudentCardSetting::defaultLayout()],
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
        $setting->validity_text = $validated['validity_text'] ?? null;
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

        return redirect()
            ->route('user.students.cards.index', ['userId' => $userId, 'student_id' => $request->input('selected_student_id')])
            ->with('success', 'Pengaturan kartu pelajar berhasil disimpan.');
    }

    public function printPdf(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return back()->with('error', 'Satuan pendidikan tidak terdeteksi.');
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
            ['layout' => StudentCardSetting::defaultLayout()],
        );

        $school = School::findOrFail($schoolId);
        $layout = $setting->resolvedLayout();
        $frontBg = $this->dataUri($setting->front_background_path);
        $backBg = $this->dataUri($setting->back_background_path);
        $signatureData = $this->dataUri($setting->signature_path ?: $school->ttd_ksp_path);

        $cards = $students->map(function (Student $student) use ($setting, $school, $signatureData) {
            return [
                'student' => $student,
                'class' => $this->classLabel($student),
                'photo' => $this->dataUri($student->photo_path),
                'school' => $school->name,
                'signature' => $signatureData,
                'signature_name' => $setting->signature_name ?: $school->principal_full_name,
                'signature_title' => $setting->signature_title ?: 'Kepala Satuan Pendidikan',
                'validity_text' => $setting->validity_text ?: 'Berlaku selama menjadi santri',
            ];
        })->all();

        return app(DocumentPdfService::class)->fromView(
            'students.cards.pdf',
            compact('cards', 'layout', 'setting', 'frontBg', 'backBg'),
            'Kartu-Pelajar-'.now()->format('Ymd-His'),
            'portrait',
            'A4',
            'attachment',
        );
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
            foreach ($default[$side] as $field => $values) {
                $input = $layout[$side][$field] ?? [];
                $row = [];

                foreach (['x', 'y', 'w', 'h'] as $key) {
                    $row[$key] = isset($input[$key]) && is_numeric($input[$key])
                        ? round(max(0, min(120, (float) $input[$key])), 2)
                        : $values[$key];
                }

                if (isset($values['size'])) {
                    $row['size'] = isset($input['size']) && is_numeric($input['size'])
                        ? round(max(4, min(40, (float) $input['size'])), 2)
                        : $values['size'];
                    $row['align'] = in_array($input['align'] ?? null, ['left', 'center', 'right'], true)
                        ? $input['align']
                        : $values['align'];
                    $row['bold'] = ! empty($input['bold']);
                }

                $clean[$side][$field] = $row;
            }
        }

        return $clean;
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
