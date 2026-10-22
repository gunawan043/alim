<?php

namespace App\Http\Controllers;

use App\Exports\GtkArabicNameTemplateExport;
use App\Models\GtkEmployment;
use App\Models\User;
use App\Services\ArabicName\ArabicNameImportService;
use App\Services\ArabicName\ArabicNameSpreadsheetReader;
use App\Services\SchoolGroupService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import nama Arab GTK (menu terpisah dari import GTK umum).
 * Pencocokan: NUPY pada data kepegawaian (gtk_employments).
 */
class GtkArabicNameController extends Controller
{
    public function form(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $stats = app(ArabicNameImportService::class)->gtkStats($schoolId);

        $missingEmployees = GtkEmployment::query()
            ->with('user:id,name,name_ar')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('user', fn ($q) => $q->where(fn ($qq) => $qq->whereNull('name_ar')->orWhere('name_ar', '')))
            ->orderBy('nupy')
            ->limit(30)
            ->get();

        return view('gtk.import-nama-arab', compact('userId', 'stats', 'missingEmployees'));
    }

    public function review(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');
        $allowedIds = $schoolId ? SchoolGroupService::userIdsForSchool($schoolId) : null;

        $status = in_array($request->input('status'), ['pending', 'approved', 'missing', 'all'], true)
            ? $request->input('status')
            : 'pending';

        $query = User::query()
            ->whereHas('employment')
            ->with('employment:id,user_id,nupy,school_id')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('id', $allowedIds))
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
                ->orWhereHas('employment', fn ($e) => $e->where('nupy', 'like', "%{$search}%")));
        }

        $employees = $query->paginate(25)->withQueryString();
        $stats = app(ArabicNameImportService::class)->gtkStats($schoolId);

        $base = User::query()
            ->whereHas('employment')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('id', $allowedIds));

        $stats['pending'] = (clone $base)->where('name_ar_status', 'pending')->count();
        $stats['approved'] = (clone $base)->where('name_ar_status', 'approved')->count();
        $stats['duplicate_nupy'] = GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('nupy')->where('nupy', '!=', '')
            ->select('nupy')->groupBy('nupy')->havingRaw('COUNT(*) > 1')->pluck('nupy')->count();

        return view('gtk.review-nama-arab', compact('userId', 'employees', 'stats', 'status'));
    }

    public function approve(Request $request, string $userId, string $gtkUserId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');
        $allowedIds = $schoolId ? SchoolGroupService::userIdsForSchool($schoolId) : null;

        $user = User::query()
            ->whereHas('employment')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('id', $allowedIds))
            ->findOrFail($gtkUserId);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
        ]);

        $user->update([
            'name_ar' => trim($validated['name_ar']),
            'name_ar_status' => 'approved',
            'name_ar_reviewed_at' => now(),
            'name_ar_reviewed_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Nama Arab {$user->name} disetujui.");
    }

    public function approveAll(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');
        $allowedIds = $schoolId ? SchoolGroupService::userIdsForSchool($schoolId) : null;

        $count = User::query()
            ->whereHas('employment')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('id', $allowedIds))
            ->where('name_ar_status', 'pending')
            ->whereNotNull('name_ar')
            ->where('name_ar', '!=', '')
            ->update([
                'name_ar_status' => 'approved',
                'name_ar_reviewed_at' => now(),
                'name_ar_reviewed_by' => $request->user()?->id,
            ]);

        return back()->with('success', "{$count} nama Arab GTK disetujui.");
    }

    public function import(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $rows = app(ArabicNameSpreadsheetReader::class)->read($request->file('file'));
        $result = app(ArabicNameImportService::class)->importGtk($rows, $schoolId);

        return redirect()
            ->route('user.gtk.import-nama-arab-form', ['userId' => $userId])
            ->with('import_result', $result);
    }

    public function template(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        return Excel::download(
            new GtkArabicNameTemplateExport($schoolId),
            'template-nama-arab-gtk-'.date('Ymd').'.xlsx'
        );
    }

    public function generate(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $overwrite = $request->boolean('overwrite');
        $limit = config('arabic_names.driver') === 'ai'
            ? max(1, (int) config('arabic_names.ai.batch_limit', 20))
            : null;

        $count = app(ArabicNameImportService::class)->generateGtk($schoolId, $overwrite, $limit);

        $batchNote = $limit ? " (batch AI maks {$limit} nama — jalankan lagi untuk sisanya)" : '';

        return redirect()
            ->route('user.gtk.import-nama-arab-form', ['userId' => $userId])
            ->with('success', "Nama Arab dibuat otomatis untuk {$count} GTK{$batchNote}. Silakan tinjau hasilnya di Data GTK.");
    }

    private function authorizeGtkImport(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
                || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                || $user->hasRole('Super Admin')
                || $user->hasRole('Humas Personalia')
            ),
            403,
            'Import nama Arab GTK hanya dapat dilakukan oleh Humas Personalia atau Super Admin.'
        );
    }
}
