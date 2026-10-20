<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\Supervisi;
use App\Models\SupervisiPengaturan;
use App\Models\SupervisiSk;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupervisiPengaturanController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $selectedYearId = $request->input('academic_year_id', $activeYearId);
        $selectedSemester = (int) $request->input('semester', 1);

        $pengaturan = SupervisiPengaturan::with([
            'academicYear:id,name',
            'supervisor.user:id,name',
            'supervisee.user:id,name',
        ])
            ->where('school_id', $schoolId)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->where('semester', $selectedSemester)
            ->orderByDesc('is_active')
            ->orderBy('supervisor_gtk_id')
            ->get();

        $gtks = $this->gtkOptions($schoolId);

        $latestByPair = Supervisi::query()
            ->where('school_id', $schoolId)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderByDesc('tanggal_supervisi')
            ->orderByDesc('created_at')
            ->get()
            ->unique(fn (Supervisi $row) => $row->observer_id.'|'.$row->gtk_id)
            ->keyBy(fn (Supervisi $row) => $row->observer_id.'|'.$row->gtk_id);

        return view('waka.supervisi.pengaturan', compact(
            'pengaturan',
            'academicYears',
            'activeYearId',
            'selectedYearId',
            'selectedSemester',
            'gtks',
            'latestByPair'
        ));
    }

    public function store(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'supervisor_gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'supervisee_gtk_id' => 'required|uuid|exists:gtk_profiles,id|different:supervisor_gtk_id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->ensureGtkBelongsToSchool($schoolId, $validated['supervisor_gtk_id']);
        $this->ensureGtkBelongsToSchool($schoolId, $validated['supervisee_gtk_id']);

        $sk = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->first();

        $isSkMember = $sk && $sk->anggota()
            ->where('gtk_profile_id', $validated['supervisor_gtk_id'])
            ->exists();

        if (! $isSkMember) {
            throw ValidationException::withMessages([
                'supervisor_gtk_id' => 'Supervisor harus termasuk anggota SK tim supervisi pada semester tersebut.',
            ]);
        }

        $exists = SupervisiPengaturan::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('supervisor_gtk_id', $validated['supervisor_gtk_id'])
            ->where('supervisee_gtk_id', $validated['supervisee_gtk_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'supervisee_gtk_id' => 'Pasangan supervisor dan guru ini sudah terdaftar pada tahun ajaran tersebut.',
            ]);
        }

        SupervisiPengaturan::create([
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'supervisor_gtk_id' => $validated['supervisor_gtk_id'],
            'supervisee_gtk_id' => $validated['supervisee_gtk_id'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Pengaturan supervisor berhasil ditambahkan.');
    }

    public function toggle(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $pengaturan = SupervisiPengaturan::where('school_id', $schoolId)->findOrFail($id);
        $pengaturan->update(['is_active' => ! $pengaturan->is_active]);

        return back()->with('success', 'Status pengaturan supervisor diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $pengaturan = SupervisiPengaturan::where('school_id', $schoolId)->findOrFail($id);
        $pengaturan->delete();

        return back()->with('success', 'Pengaturan supervisor dihapus.');
    }

    private function ensureGtkBelongsToSchool(string $schoolId, string $gtkProfileId): void
    {
        $userIds = GtkProfile::query()->whereKey($gtkProfileId)->pluck('user_id');
        $belongsToSchool = GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $userIds)
            ->exists()
            || \App\Models\GtkWorkUnit::query()
                ->whereIn('user_id', $userIds)
                ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                ->exists();

        if (! $belongsToSchool) {
            throw ValidationException::withMessages([
                'supervisor_gtk_id' => 'GTK yang dipilih tidak terdaftar pada satuan pendidikan ini.',
            ]);
        }
    }

    private function gtkOptions(string $schoolId)
    {
        $userIds = GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id')
            ->merge(
                \App\Models\GtkWorkUnit::query()
                    ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                    ->pluck('user_id')
            )
            ->unique();

        return GtkProfile::query()
            ->with(['user:id,name', 'latestEmployment:id,user_id,nupy,jabatan'])
            ->whereIn('user_id', $userIds)
            ->get()
            ->sortBy(fn (GtkProfile $gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();
    }
}
