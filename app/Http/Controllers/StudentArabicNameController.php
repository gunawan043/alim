<?php

namespace App\Http\Controllers;

use App\Exports\StudentArabicNameTemplateExport;
use App\Models\Student;
use App\Services\ArabicName\ArabicNameImportService;
use App\Services\ArabicName\ArabicNameSpreadsheetReader;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import nama Arab santri (menu terpisah dari import santri umum).
 * Pencocokan: NIS (fallback NISN) dalam satuan pendidikan.
 */
class StudentArabicNameController extends Controller
{
    public function form(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Import nama Arab memerlukan konteks satuan pendidikan.');

        $stats = app(ArabicNameImportService::class)->studentStats($schoolId);

        $missingStudents = Student::query()
            ->where('school_id', $schoolId)
            ->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'nis', 'nisn', 'name', 'name_ar']);

        return view('students.import-nama-arab', compact('userId', 'stats', 'missingStudents'));
    }

    public function review(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Review nama Arab memerlukan konteks satuan pendidikan.');

        $status = in_array($request->input('status'), ['pending', 'approved', 'missing', 'all'], true)
            ? $request->input('status')
            : 'pending';

        $query = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('name');

        match ($status) {
            'pending' => $query->where('name_ar_status', 'pending'),
            'approved' => $query->where('name_ar_status', 'approved'),
            'missing' => $query->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', '')),
            default => null,
        };

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%"));
        }

        $students = $query->paginate(25)->withQueryString();
        $stats = app(ArabicNameImportService::class)->studentStats($schoolId);

        $stats['pending'] = Student::where('school_id', $schoolId)->where('name_ar_status', 'pending')->count();
        $stats['approved'] = Student::where('school_id', $schoolId)->where('name_ar_status', 'approved')->count();
        $stats['duplicate_nis'] = Student::where('school_id', $schoolId)
            ->whereNotNull('nis')->where('nis', '!=', '')
            ->select('nis')->groupBy('nis')->havingRaw('COUNT(*) > 1')->pluck('nis')->count();

        return view('students.review-nama-arab', compact('userId', 'students', 'stats', 'status'));
    }

    public function approve(Request $request, string $userId, string $studentId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Review nama Arab memerlukan konteks satuan pendidikan.');

        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
        ]);

        $student->update([
            'name_ar' => trim($validated['name_ar']),
            'name_ar_status' => 'approved',
            'name_ar_reviewed_at' => now(),
            'name_ar_reviewed_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Nama Arab {$student->name} disetujui.");
    }

    public function approveAll(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Review nama Arab memerlukan konteks satuan pendidikan.');

        $count = Student::where('school_id', $schoolId)
            ->where('name_ar_status', 'pending')
            ->whereNotNull('name_ar')
            ->where('name_ar', '!=', '')
            ->update([
                'name_ar_status' => 'approved',
                'name_ar_reviewed_at' => now(),
                'name_ar_reviewed_by' => $request->user()?->id,
            ]);

        return back()->with('success', "{$count} nama Arab santri disetujui.");
    }

    public function import(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Import nama Arab memerlukan konteks satuan pendidikan.');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $rows = app(ArabicNameSpreadsheetReader::class)->read($request->file('file'));
        $result = app(ArabicNameImportService::class)->importStudents($rows, $schoolId);

        return redirect()
            ->route('user.students.import-nama-arab-form', ['userId' => $userId])
            ->with('import_result', $result);
    }

    public function template(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Template memerlukan konteks satuan pendidikan.');

        return Excel::download(
            new StudentArabicNameTemplateExport($schoolId),
            'template-nama-arab-santri-'.date('Ymd').'.xlsx'
        );
    }

    public function generate(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_unless($schoolId, 403, 'Generator memerlukan konteks satuan pendidikan.');

        $overwrite = $request->boolean('overwrite');
        $limit = config('arabic_names.driver') === 'ai'
            ? max(1, (int) config('arabic_names.ai.batch_limit', 20))
            : null;

        $count = app(ArabicNameImportService::class)->generateStudents($schoolId, $overwrite, $limit);

        $batchNote = $limit ? " (batch AI maks {$limit} nama — jalankan lagi untuk sisanya)" : '';

        return redirect()
            ->route('user.students.import-nama-arab-form', ['userId' => $userId])
            ->with('success', "Nama Arab dibuat otomatis untuk {$count} santri{$batchNote}. Silakan tinjau hasilnya di Data Santri.");
    }
}
