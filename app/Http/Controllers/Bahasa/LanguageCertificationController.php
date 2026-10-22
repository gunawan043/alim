<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\LanguageCertification;
use App\Models\Student;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;

class LanguageCertificationController extends Controller
{
    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $schoolId = $this->filters->schoolId($request);

        $certifications = LanguageCertification::with('student:id,name')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($this->filters->gradeLevelId($request) || $this->filters->studyGroupId($request), fn ($q) => $this->filters->applyStudentScope($q, $request))
            ->when($request->filled('language'), fn ($q) => $q->where('language', $request->language))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$request->search.'%')))
            ->orderByDesc('issued_date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => LanguageCertification::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'issued' => LanguageCertification::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'issued')->count(),
            'pending' => LanguageCertification::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'pending')->count(),
            'arab' => LanguageCertification::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('language', 'arab')->count(),
        ];

        return view('bahasa.certifications.index', [
            'userId' => $userId,
            'certifications' => $certifications,
            'stats' => $stats,
            'students' => Student::where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManage' => $this->access->canManage($request->user()),
            ...$this->filters->options($request),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);
        $validated = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'certification_name' => ['required', 'string', 'max:180'],
            'language' => ['required', 'in:arab,inggris'],
            'level' => ['nullable', 'string', 'max:60'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:issued,pending,revoked'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        LanguageCertification::create([
            ...$validated,
            'school_id' => $this->filters->schoolId($request) ?: $student->school_id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.bahasa.certifications.index', ['userId' => $userId])
            ->with('success', 'Sertifikasi bahasa disimpan.');
    }

    public function destroy(Request $request, string $userId, string $certification)
    {
        $this->authorizeManage($request);
        LanguageCertification::findOrFail($certification)->delete();

        return redirect()
            ->route('user.bahasa.certifications.index', ['userId' => $userId])
            ->with('success', 'Sertifikasi bahasa dihapus.');
    }

    public function print(Request $request, string $userId, string $certification)
    {
        $this->authorizeView($request);
        $record = LanguageCertification::with('student:id,name')->findOrFail($certification);

        return view('bahasa.certifications.print', compact('record'));
    }

    private function schoolId(Request $request): ?string
    {
        return $this->filters->schoolId($request);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->access->canManage($request->user()), 403, 'Anda tidak berwenang mengelola sertifikasi bahasa.');
    }
}
