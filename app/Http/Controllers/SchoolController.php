<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\WorkUnit;
use App\Models\Province;
use App\Models\User;
use App\Models\GtkWorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    /**
     * Validate user has access to the given school via their work unit.
     * If user has view_global_school_data permission → always allowed.
     * Otherwise → school must belong to one of user's gtk_work_unit entries.
     */
    private function validateSchoolAccess(string $userId, string $schoolId): ?School
    {
        $authUser = auth()->user();

        abort_unless($authUser && $authUser->id === $userId, 403, 'Akses ditolak.');

        $school = School::with('workUnit')->find($schoolId);
        if (!$school) {
            abort(404, 'Sekolah tidak ditemukan.');
        }

        if ($authUser->can('view_global_school_data')) {
            return $school;
        }

        $userWorkUnitIds = GtkWorkUnit::where('user_id', $authUser->id)
            ->pluck('work_unit_id')
            ->toArray();

        abort_if(
            !in_array($school->work_unit_id, $userWorkUnitIds),
            403,
            'Anda tidak memiliki akses ke sekolah ini.'
        );

        return $school;
    }

    public function index(Request $request, string $userId)
    {
        $user = auth()->user();
        abort_unless($user && $user->id === $userId, 403, 'Akses ditolak.');

        abort_unless(
            $user->hasRole(['Super Admin', 'Administrator']) || $user->can('view_global_school_data'),
            403,
            'Hanya Super Admin dan Administrator yang dapat mengakses halaman sekolah.'
        );

        $query = School::with(['workUnit', 'principalUser'])->orderBy('name');

        if ($user->hasRole(['Super Admin', 'Administrator'])) {
            // Super Admin & Administrator: lihat semua sekolah
        } elseif (!$user->can('view_global_school_data')) {
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
        abort_unless($user && $user->id === $userId, 403, 'Akses ditolak.');
        abort_unless(
            $user->hasRole(['Super Admin', 'Administrator']),
            403,
            'Hanya Super Admin dan Administrator yang dapat membuat sekolah baru.'
        );

        $workUnits = WorkUnit::where('type', 'Unit Akademik')->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $principals = User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn($q) => $q->where('type', 'Unsur Pimpinan'))
            ->with(['gtkWorkUnits.workUnit' => fn($q) => $q->where('type', 'Unsur Pimpinan')])
            ->orderBy('name')
            ->get();

        return view('schools.create', compact('workUnits', 'provinces', 'principals', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $user = auth()->user();
        abort_unless($user && $user->id === $userId, 403, 'Akses ditolak.');
        abort_unless(
            $user->hasRole(['Super Admin', 'Administrator']),
            403,
            'Hanya Super Admin dan Administrator yang dapat membuat sekolah baru.'
        );

        $data = $request->validate([
            'work_unit_id'    => 'nullable|exists:work_units,id',
            'school_code'      => 'nullable|string|max:20',
            'npsn'            => 'nullable|string|max:20',
            'nss'             => 'nullable|string|max:30',
            'name'            => 'nullable|string|max:255',
            'address'         => 'nullable|string',
            'province_code'   => 'nullable|string|max:2',
            'city_code'       => 'nullable|string|max:4',
            'district_code'  => 'nullable|string|max:7',
            'village_code'   => 'nullable|string|max:10',
            'postal_code'     => 'nullable|string|max:10',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'website'         => 'nullable|string|max:100',
            'school_level'   => 'required|in:sd,smp,sma,smk',
            'school_status'   => 'required|in:negeri,swasta',
            'accreditation'   => 'nullable|string|max:10',
            'accreditation_year' => 'nullable|integer|min:2000|max:2099',
            'principal_name'   => 'nullable|string|max:255',
            'principal_nip'    => 'nullable|string|max:30',
            'principal_nupy'   => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area'       => 'nullable|numeric|min:0',
            'building_area'   => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
            'kop_nama'        => 'nullable|string|max:255',
            'kop_alamat'      => 'nullable|string',
            'kop_telp'        => 'nullable|string|max:50',
            'kop_email'       => 'nullable|email|max:100',
            'kop_website'     => 'nullable|string|max:100',
            'kop_npsn'        => 'nullable|string|max:20',
            'kopsis_active'   => 'boolean',
            'bank_name'       => 'nullable|string|max:100',
            'bank_cabang'     => 'nullable|string|max:100',
            'bank_rekening'  => 'nullable|string|max:50',
            'bank_an'         => 'nullable|string|max:100',
            'npwp'            => 'nullable|string|max:30',
        ]);

        // Auto-fill name from WorkUnit if work_unit_id is selected
        if (!empty($data['work_unit_id'])) {
            $wu = WorkUnit::find($data['work_unit_id']);
            if ($wu) {
                $data['name'] = $wu->name;
            }
        }

        // Auto-fill principal_name from user if principal_user_id is set
        if (!empty($data['principal_user_id'])) {
            $user = User::find($data['principal_user_id']);
            if ($user) {
                $data['principal_name'] = $user->name;
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
        return view('schools.show', compact('school', 'userId'));
    }

    public function edit(string $userId, string $schoolId)
    {
        abort_unless(
            auth()->user()->hasRole(['Super Admin', 'Administrator', 'Admin Tata Usaha']),
            403,
            'Hanya Super Admin dan Administrator yang dapat mengedit sekolah.'
        );

        $school = $this->validateSchoolAccess($userId, $schoolId);
        $workUnits = WorkUnit::where('type', 'Unit Akademik')->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $principals = User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn($q) => $q->where('type', 'Unsur Pimpinan'))
            ->with(['gtkWorkUnits.workUnit' => fn($q) => $q->where('type', 'Unsur Pimpinan')])
            ->orderBy('name')
            ->get();
        return view('schools.edit', compact('school', 'workUnits', 'provinces', 'principals', 'userId'));
    }

    public function update(Request $request, string $userId, string $schoolId)
    {
        abort_unless(
            auth()->user()->hasRole(['Super Admin', 'Administrator', 'Admin Tata Usaha']),
            403,
            'Hanya Super Admin dan Administrator yang dapat mengedit sekolah.'
        );

        $school = $this->validateSchoolAccess($userId, $schoolId);

        $data = $request->validate([
            'work_unit_id'    => 'nullable|exists:work_units,id',
            'school_code'      => 'nullable|string|max:20',
            'npsn'            => 'nullable|string|max:20',
            'nss'             => 'nullable|string|max:30',
            'name'            => 'required|string|max:255',
            'address'         => 'nullable|string',
            'province_code'   => 'nullable|string|max:2',
            'city_code'       => 'nullable|string|max:4',
            'district_code'  => 'nullable|string|max:7',
            'village_code'   => 'nullable|string|max:10',
            'postal_code'     => 'nullable|string|max:10',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'website'         => 'nullable|string|max:100',
            'school_level'    => 'required|in:sd,smp,sma,smk',
            'school_status'   => 'required|in:negeri,swasta',
            'accreditation'   => 'nullable|string|max:10',
            'accreditation_year' => 'nullable|integer|min:2000|max:2099',
            'principal_name'   => 'nullable|string|max:255',
            'principal_nip'    => 'nullable|string|max:30',
            'principal_nupy'   => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area'       => 'nullable|numeric|min:0',
            'building_area'   => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
            'kop_nama'        => 'nullable|string|max:255',
            'kop_alamat'      => 'nullable|string',
            'kop_telp'        => 'nullable|string|max:50',
            'kop_email'       => 'nullable|email|max:100',
            'kop_website'     => 'nullable|string|max:100',
            'kop_npsn'        => 'nullable|string|max:20',
            'kopsis_active'   => 'boolean',
            'bank_name'       => 'nullable|string|max:100',
            'bank_cabang'     => 'nullable|string|max:100',
            'bank_rekening'  => 'nullable|string|max:50',
            'bank_an'         => 'nullable|string|max:100',
            'npwp'            => 'nullable|string|max:30',
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

        return redirect()->route('user.schools.show', ['userId' => $userId, 'schoolId' => $school->id])
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $schoolId)
    {
        abort_unless(
            auth()->user()->hasRole(['Super Admin', 'Administrator']),
            403,
            'Hanya Super Admin dan Administrator yang dapat menghapus sekolah.'
        );

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