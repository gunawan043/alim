<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\SuratKeluar;
use App\Models\SupervisiSk;
use App\Models\SupervisiTimAnggota;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupervisiSkController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $selectedYearId = $request->input('academic_year_id', AcademicYear::where('is_active', true)->value('id'));

        $sks = SupervisiSk::with(['academicYear:id,name'])
            ->withCount('anggota')
            ->where('school_id', $schoolId)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderByDesc('tanggal')
            ->get();

        return view('waka.supervisi.sk-index', compact('sks', 'academicYears', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'nomor' => 'required|string|max:100',
            'tanggal' => 'required|date',
            'menimbang' => 'nullable|string|max:5000',
            'mengingat' => 'nullable|string|max:5000',
        ]);

        $exists = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'semester' => 'SK untuk tahun ajaran dan semester tersebut sudah ada.',
            ]);
        }

        $sk = SupervisiSk::create($validated + ['school_id' => $schoolId]);
        $this->syncDecreeAndLetter($sk);

        return redirect()
            ->route('waka.supervisi-sk.edit', $sk->id)
            ->with('success', 'SK dibuat. Tambahkan anggota tim supervisi.');
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::with(['academicYear:id,name', 'anggota.gtk.user:id,name'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $gtks = $this->gtkOptions($schoolId);

        return view('waka.supervisi.sk-edit', compact('sk', 'academicYears', 'gtks'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'nomor' => 'required|string|max:100',
            'tanggal' => 'required|date',
            'menimbang' => 'nullable|string|max:5000',
            'mengingat' => 'nullable|string|max:5000',
        ]);

        $duplicate = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('id', '!=', $sk->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'semester' => 'SK untuk tahun ajaran dan semester tersebut sudah ada.',
            ]);
        }

        $sk->update($validated);
        $this->syncDecreeAndLetter($sk->fresh(['anggota.gtk.user', 'academicYear', 'school']));

        return back()->with('success', 'Identitas SK diperbarui dan disinkronkan ke database SK/surat.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);
        $sk->delete();

        return redirect()->route('waka.supervisi-sk.index')->with('success', 'SK dihapus.');
    }

    public function storeMember(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'gtk_profile_id' => 'required|uuid|exists:gtk_profiles,id',
            'peran' => 'required|in:ksp,wksp,guru',
            'urutan' => 'nullable|integer|min:0|max:99',
        ]);

        $this->ensureGtkBelongsToSchool($schoolId, $validated['gtk_profile_id']);

        $exists = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)
            ->where('gtk_profile_id', $validated['gtk_profile_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'gtk_profile_id' => 'GTK ini sudah menjadi anggota tim pada SK tersebut.',
            ]);
        }

        SupervisiTimAnggota::create([
            'supervisi_sk_id' => $sk->id,
            'gtk_profile_id' => $validated['gtk_profile_id'],
            'peran' => $validated['peran'],
            'urutan' => $validated['urutan'] ?? 0,
        ]);

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function storeMemberBulk(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'gtk_profile_ids' => 'required|array|min:1|max:200',
            'gtk_profile_ids.*' => 'uuid|exists:gtk_profiles,id',
            'peran' => 'required|in:ksp,wksp,guru',
        ]);

        $ids = array_values(array_unique($validated['gtk_profile_ids']));

        if (in_array($validated['peran'], ['ksp', 'wksp'], true)) {
            if (count($ids) > 1) {
                throw ValidationException::withMessages([
                    'gtk_profile_ids' => 'Peran KSP/WKSP hanya boleh satu orang. Pilih satu anggota.',
                ]);
            }

            $alreadyHasRole = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)
                ->where('peran', $validated['peran'])
                ->exists();

            if ($alreadyHasRole) {
                throw ValidationException::withMessages([
                    'peran' => 'Peran '.strtoupper($validated['peran']).' sudah diisi pada SK ini.',
                ]);
            }
        }

        $existingIds = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)
            ->whereIn('gtk_profile_id', $ids)
            ->pluck('gtk_profile_id')
            ->all();

        $nextOrder = (int) SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)->max('urutan') + 1;
        $added = 0;

        foreach ($ids as $gtkId) {
            if (in_array($gtkId, $existingIds, true)) {
                continue;
            }

            $this->ensureGtkBelongsToSchool($schoolId, $gtkId);
            SupervisiTimAnggota::create([
                'supervisi_sk_id' => $sk->id,
                'gtk_profile_id' => $gtkId,
                'peran' => $validated['peran'],
                'urutan' => $nextOrder++,
            ]);
            $added++;
        }

        return back()->with('success', $added.' anggota tim ditambahkan. '.count($existingIds).' anggota sudah ada.');
    }

    public function destroyMember(Request $request, string $id, string $memberId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);
        $member = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)->findOrFail($memberId);
        $member->delete();

        return back()->with('success', 'Anggota tim dihapus.');
    }

    public function cetak(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::with(['academicYear', 'anggota.gtk.user', 'school'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        return view('waka.supervisi.sk-cetak', compact('sk'));
    }

    private function syncDecreeAndLetter(SupervisiSk $sk): void
    {
        $sk->loadMissing(['anggota.gtk.user', 'academicYear', 'school']);

        $ksp = $sk->anggota->firstWhere('peran', 'ksp')?->gtk;
        $semester = $sk->semester === 1 ? 'Ganjil' : 'Genap';
        $title = 'Pembentukan Tim Supervisi Pembelajaran Semester '.$semester.' Tahun Ajaran '.($sk->academicYear?->name ?? '-');

        $decree = $sk->decree ?: InstitutionDecree::firstOrNew(['decree_number' => $sk->nomor]);
        $decree->fill([
            'decree_number' => $sk->nomor,
            'decree_type' => 'SK Tim Supervisi',
            'title' => $title,
            'description' => $sk->menimbang,
            'academic_year_id' => $sk->academic_year_id,
            'school_id' => $sk->school_id,
            'issued_date' => $sk->tanggal,
            'effective_date' => $sk->tanggal,
            'signed_by' => $ksp?->user_id,
            'signed_position' => 'Kepala Satuan Pendidikan',
            'status' => InstitutionDecree::STATUS_ACTIVE,
        ]);
        $decree->save();

        $surat = $sk->suratKeluar ?: SuratKeluar::firstOrNew([
            'school_id' => $sk->school_id,
            'nomor_surat' => $sk->nomor,
        ]);
        $surat->fill([
            'school_id' => $sk->school_id,
            'nomor_surat' => $sk->nomor,
            'tanggal_surat' => $sk->tanggal,
            'tujuan' => 'Wadir Akademik dan Pengasuhan; Dewan Guru',
            'perihal' => $title,
            'isi_surat' => $sk->menimbang,
            'sifat' => SuratKeluar::SIFAT_BIASA,
            'penandatangan' => $ksp?->user?->name,
            'jabatan_penandatangan' => 'Kepala Satuan Pendidikan',
            'status' => SuratKeluar::STATUS_DRAFT,
        ]);
        $surat->save();

        $sk->update([
            'institution_decree_id' => $decree->id,
            'surat_keluar_id' => $surat->id,
        ]);
    }

    private function gtkOptions(string $schoolId)
    {
        $userIds = GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id')
            ->merge(
                GtkWorkUnit::query()
                    ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                    ->pluck('user_id')
            )
            ->unique();

        return GtkProfile::query()
            ->with(['user:id,name', 'latestEmployment:id,user_id,nupy,jabatan'])
            ->whereIn('user_id', $userIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();
    }

    private function ensureGtkBelongsToSchool(string $schoolId, string $gtkProfileId): void
    {
        $userIds = GtkProfile::whereKey($gtkProfileId)->pluck('user_id');
        $exists = GtkEmployment::where('school_id', $schoolId)->whereIn('user_id', $userIds)->exists()
            || GtkWorkUnit::whereIn('user_id', $userIds)
                ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                ->exists();

        abort_unless($exists, 422, 'GTK tidak terdaftar pada satuan pendidikan ini.');
    }
}
