<?php

namespace App\Http\Controllers;

use App\Models\GtkWorkUnit;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Documents\DocumentVisualService;
use App\Services\Documents\SchoolLeaderService;
use App\Services\GtkPositionAssignmentService;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    /**
     * KSP, Waka, dan TU boleh mengubah profil sekolah (termasuk kop/TTD/stempel).
     */
    private function canEditSchool(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isSystemAdmin()
            || canPermission('school-update')
            || $user->hasRole('Kepala Sekolah')
            || $user->hasRole('Kepala Satuan Pendidikan')) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala sekolah',
            'wakil kepala sekolah',
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ]);
    }

    /**
     * Validate user has access to the given school via their work unit.
     * If user has view_global_school_data permission → always allowed.
     * Otherwise → school must belong to one of user's gtk_work_unit entries.
     */
    private function validateSchoolAccess(string $userId, string $schoolId): ?School
    {
        $authUser = auth()->user();

        // Super Admin bypasses userId matching check
        if (! ($authUser && $authUser->isSystemAdmin())) {
            abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');
        }

        $school = School::with('workUnit')->find($schoolId);
        if (! $school) {
            abort(404, 'Sekolah tidak ditemukan.');
        }

        if (canPermission('view_global_school_data')) {
            return $school;
        }

        $userWorkUnitIds = GtkWorkUnit::where('user_id', $authUser->id)
            ->pluck('work_unit_id')
            ->toArray();

        abort_if(
            ! in_array($school->work_unit_id, $userWorkUnitIds),
            403,
            'Anda tidak memiliki akses ke sekolah ini.'
        );

        return $school;
    }

    public function index(Request $request, string $userId)
    {
        $user = auth()->user();
        // Super Admin bypasses userId matching check
        if (! $user || ! $user->isSystemAdmin()) {
            abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');
        }

        // Super Admin & Administrator: school-all-access or view_global_school_data
        // Tata Usaha: school_view (can only see schools in their work unit)
        $hasFullAccess = canPermission('school-all-access') || canPermission('view_global_school_data');
        $hasViewAccess = canPermission('school_view');

        // If the user doesn't have any school-related permission, show a helpful message
        if (! $hasFullAccess && ! $hasViewAccess) {
            abort(403, 'Hanya Super Admin, Administrator, atau Tata Usaha yang dapat mengakses halaman sekolah.');
        }

        $query = School::with(['workUnit', 'principalUser'])->orderBy('name');

        if (canPermission('school-all-access')) {
            // Super Admin & Administrator: lihat semua sekolah
        } elseif (! canPermission('view_global_school_data')) {
            $userWorkUnitIds = GtkWorkUnit::where('user_id', $user->id)
                ->pluck('work_unit_id')
                ->toArray();
            $query->whereIn('work_unit_id', $userWorkUnitIds);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('npsn', 'like', "%{$request->search}%");
        }
        if ($request->filled('level')) {
            $query->where('school_level', $request->level);
        }
        if ($request->filled('status')) {
            $query->where('school_status', $request->status);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $schools = $query->paginate(12)->withQueryString();

        return view('schools.index', compact('schools', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $user = auth()->user();
        // Super Admin bypasses userId matching check
        if (! $user || ! $user->isSystemAdmin()) {
            abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');
        }

        // Super Admin & Tata Usaha: buat sekolah
        $hasCreatePerm = canPermission('school-create') ||
                         ($user && $user->hasRole('Kepala Sekolah'));

        abort_unless($hasCreatePerm, 403, 'Hanya Super Admin, Administrator, atau Tata Usaha yang dapat membuat sekolah baru.');

        $workUnits = WorkUnit::where('type', 'Unit Akademik')->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        // Semua GTK (lintas divisi) bisa dipilih sebagai kepala sekolah.
        $principals = User::whereHas('employment')
            ->with(['employment', 'gtkWorkUnits.workUnit'])
            ->orderBy('name')
            ->get();

        return view('schools.create', compact('workUnits', 'provinces', 'principals', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $user = auth()->user();
        // Super Admin bypasses userId matching check
        if (! $user || ! $user->isSystemAdmin()) {
            abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');
        }

        // Tata Usaha juga bisa buat sekolah via role detection
        $hasCreatePerm = canPermission('school-create') ||
                         ($user && $user->hasRole('Kepala Sekolah'));

        abort_unless($hasCreatePerm, 403, 'Hanya Super Admin, Administrator, atau Tata Usaha yang dapat membuat sekolah baru.');

        $data = $request->validate([
            'work_unit_id' => 'nullable|exists:work_units,id',
            'school_code' => 'nullable|string|max:20',
            'npsn' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:30',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string|max:2',
            'city_code' => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'school_level' => 'required|in:sd,smp,sma,smk',
            'school_status' => 'required|in:negeri,swasta',
            'boarding_type' => 'nullable|in:boarding,non_boarding,both',
            'accreditation' => 'nullable|string|max:10',
            'accreditation_year' => 'nullable|integer|min:2000|max:2099',
            'principal_name' => 'nullable|string|max:255',
            'principal_name_ar' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:30',
            'principal_nupy' => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'kop_nama' => 'nullable|string|max:255',
            'kop_alamat' => 'nullable|string',
            'kop_telp' => 'nullable|string|max:50',
            'kop_email' => 'nullable|email|max:100',
            'kop_website' => 'nullable|string|max:100',
            'kop_npsn' => 'nullable|string|max:20',
            'kopsis_active' => 'boolean',
            'bank_name' => 'nullable|string|max:100',
            'bank_cabang' => 'nullable|string|max:100',
            'bank_rekening' => 'nullable|string|max:50',
            'bank_an' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:30',
            'logo_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kop_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'ttd_ksp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'stamp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        // Auto-fill name from WorkUnit if work_unit_id is selected
        if (! empty($data['work_unit_id'])) {
            $wu = WorkUnit::find($data['work_unit_id']);
            if ($wu) {
                $data['name'] = $wu->name;
            }
        }

        // Auto-fill principal_name from user if principal_user_id is set
        if (! empty($data['principal_user_id'])) {
            $user = User::find($data['principal_user_id']);
            if ($user) {
                $data['principal_name'] = $user->name;

                if (empty($data['principal_name_ar']) && $user->name_ar) {
                    $data['principal_name_ar'] = $user->name_ar;
                }
            }
        }

        // Handle file uploads
        foreach (['logo_path', 'kop_path', 'ttd_ksp_path', 'stamp_path'] as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store("schools/{$field}", 'public');
                $data[$field] = $path;
            }
        }

        $school = School::create($data);

        return redirect()->route('user.schools.show', ['userId' => $userId, 'schoolId' => $school->id])
            ->with('success', 'Data sekolah berhasil disimpan.');
    }

    public function show(string $userId, string $schoolId)
    {
        $school = $this->validateSchoolAccess($userId, $schoolId);

        return view('schools.show', [
            'school' => $school,
            'userId' => $userId,
            'canEditSchool' => $this->canEditSchool(),
        ]);
    }

    public function edit(string $userId, string $schoolId)
    {
        // Super Admin bypasses userId matching check via validateSchoolAccess already
        abort_unless($this->canEditSchool(), 403, 'Hanya Super Admin, KSP, Waka, atau Tata Usaha yang dapat mengedit sekolah.');

        $school = $this->validateSchoolAccess($userId, $schoolId);
        $canEditSchool = $this->canEditSchool();
        $workUnits = WorkUnit::where('type', 'Unit Akademik')->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        // Semua GTK (lintas divisi) bisa dipilih sebagai kepala sekolah.
        $principals = User::whereHas('employment')
            ->with(['employment', 'gtkWorkUnits.workUnit'])
            ->orderBy('name')
            ->get();

        return view('schools.edit', compact('school', 'workUnits', 'provinces', 'principals', 'userId', 'canEditSchool'));
    }

    public function updateMedia(Request $request, string $userId, string $schoolId)
    {
        abort_unless($this->canEditSchool(), 403, 'Hanya Super Admin, KSP, Waka, atau Tata Usaha yang dapat mengunggah dokumen sekolah.');

        $school = $this->validateSchoolAccess($userId, $schoolId);

        $request->validate([
            'logo_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kop_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'ttd_ksp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'stamp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'remove_logo_path' => 'nullable|boolean',
            'remove_kop_path' => 'nullable|boolean',
            'remove_ttd_ksp_path' => 'nullable|boolean',
            'remove_stamp_path' => 'nullable|boolean',
            'document_visual' => 'nullable|array',
            'document_visual.mutasi.show_ttd_ksp' => 'nullable|boolean',
            'document_visual.mutasi.show_stamp' => 'nullable|boolean',
            'document_visual.mutasi.validation_mode' => 'nullable|in:visual,qr,pades',
            'document_visual.sk.show_ttd_ksp' => 'nullable|boolean',
            'document_visual.sk.show_stamp' => 'nullable|boolean',
            'document_visual.sk.validation_mode' => 'nullable|in:visual,qr,pades',
            'document_visual.skl.show_ttd_ksp' => 'nullable|boolean',
            'document_visual.skl.show_stamp' => 'nullable|boolean',
            'document_visual.skl.validation_mode' => 'nullable|in:visual,qr,pades',
            'document_visual.*.ttd_width' => 'nullable|numeric|min:5|max:100',
            'document_visual.*.ttd_height' => 'nullable|numeric|min:5|max:60',
            'document_visual.*.ttd_margin' => 'nullable|numeric|min:0|max:30',
            'document_visual.*.stamp_width' => 'nullable|numeric|min:5|max:100',
            'document_visual.*.stamp_height' => 'nullable|numeric|min:5|max:100',
            'document_visual.*.stamp_margin' => 'nullable|numeric|min:0|max:30',
            'document_leader_name' => 'nullable|string|max:255',
            'document_leader_nupy' => 'nullable|string|max:50',
        ]);

        $updated = [];

        foreach (['logo_path', 'kop_path', 'ttd_ksp_path', 'stamp_path'] as $field) {
            if ($request->boolean("remove_{$field}")) {
                if ($school->{$field}) {
                    Storage::disk('public')->delete($school->{$field});
                }
                $school->{$field} = null;
                $updated[] = $field;

                continue;
            }

            if ($request->hasFile($field)) {
                if ($school->{$field}) {
                    Storage::disk('public')->delete($school->{$field});
                }
                $school->{$field} = $request->file($field)->store("schools/{$field}", 'public');
                $updated[] = $field;
            }
        }

        $visualChanged = false;
        if ($request->has('document_visual')) {
            app(DocumentVisualService::class)
                ->update($school, (array) $request->input('document_visual', []));
            $visualChanged = true;
        }

        if ($request->has('document_leader_name') || $request->has('document_leader_nupy')) {
            $visual = is_array($school->document_visual_settings) ? $school->document_visual_settings : [];
            $leaderName = trim((string) $request->input('document_leader_name', ''));
            $leaderNupy = trim((string) $request->input('document_leader_nupy', ''));
            $auto = app(SchoolLeaderService::class)->autoForSchool($school);
            $visual['leader'] = ($leaderName === $auto['display_name'] && $leaderNupy === $auto['nupy'])
                ? ['name' => '', 'nupy' => '']
                : ['name' => $leaderName, 'nupy' => $leaderNupy];
            $school->document_visual_settings = $visual;
            $visualChanged = true;
        }

        $school->save();

        $changed = array_merge($updated, $visualChanged ? ['pengaturan tampilan TTD/stempel'] : []);

        return back()->with(
            'success',
            $changed === []
                ? 'Tidak ada file atau pengaturan yang diubah.'
                : 'Dokumen sekolah berhasil diperbarui: '.implode(', ', $changed).'.'
        );
    }

    public function update(Request $request, string $userId, string $schoolId, GtkPositionAssignmentService $assignments)
    {
        abort_unless($this->canEditSchool(), 403, 'Hanya Super Admin, KSP, Waka, atau Tata Usaha yang dapat mengedit sekolah.');

        $school = $this->validateSchoolAccess($userId, $schoolId);

        $data = $request->validate([
            'work_unit_id' => 'nullable|exists:work_units,id',
            'school_code' => 'nullable|string|max:20',
            'npsn' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:30',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string|max:2',
            'city_code' => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'school_level' => 'required|in:sd,smp,sma,smk',
            'school_status' => 'required|in:negeri,swasta',
            'accreditation' => 'nullable|string|max:10',
            'accreditation_year' => 'nullable|integer|min:2000|max:2099',
            'principal_name' => 'nullable|string|max:255',
            'principal_name_ar' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:30',
            'principal_nupy' => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'kop_nama' => 'nullable|string|max:255',
            'kop_alamat' => 'nullable|string',
            'kop_telp' => 'nullable|string|max:50',
            'kop_email' => 'nullable|email|max:100',
            'kop_website' => 'nullable|string|max:100',
            'kop_npsn' => 'nullable|string|max:20',
            'kopsis_active' => 'boolean',
            'bank_name' => 'nullable|string|max:100',
            'bank_cabang' => 'nullable|string|max:100',
            'bank_rekening' => 'nullable|string|max:50',
            'bank_an' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:30',
            'logo_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kop_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'ttd_ksp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'stamp_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        foreach (['logo_path', 'kop_path', 'ttd_ksp_path', 'stamp_path'] as $field) {
            if ($request->hasFile($field)) {
                if ($school->{$field}) {
                    Storage::disk('public')->delete($school->{$field});
                }
                $path = $request->file($field)->store("schools/{$field}", 'public');
                $data[$field] = $path;
            }
            // Remove old file if user checked "remove"
            if ($request->boolean("remove_{$field}")) {
                if ($school->{$field}) {
                    Storage::disk('public')->delete($school->{$field});
                }
                $data[$field] = null;
            }
        }

        $school->update($data);

        // Kepala sekolah yang dipilih otomatis menjadi GTK berjabatan Kepala Satuan Pendidikan
        // pada satuan kerja sekolah ini.
        if ($school->principal_user_id) {
            $principal = User::find($school->principal_user_id);

            if ($principal) {
                $assignments->assign($principal, $school->workUnit, 'Kepala Satuan Pendidikan', 'kepala_sekolah');
            }
        }

        return redirect()->route('user.schools.show', ['userId' => $userId, 'schoolId' => $school->id])
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $schoolId)
    {
        // Tata Usaha juga bisa menghapus sekolah
        $hasDeletePerm = canPermission('school-delete') ||
                         (auth()->user() && auth()->user()->hasRole('Kepala Sekolah'));

        abort_unless($hasDeletePerm, 403, 'Hanya Super Admin, Administrator, atau Tata Usaha yang dapat menghapus sekolah.');

        $school = $this->validateSchoolAccess($userId, $schoolId);

        // Delete associated files
        foreach (['logo_path', 'kop_path', 'ttd_ksp_path', 'stamp_path'] as $field) {
            if ($school->{$field}) {
                Storage::disk('public')->delete($school->{$field});
            }
        }

        $school->delete();

        return redirect()->route('user.schools.index', ['userId' => $userId])
            ->with('success', 'Sekolah berhasil dihapus.');
    }
}
