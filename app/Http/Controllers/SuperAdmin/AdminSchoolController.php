<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\Village;
use App\Models\WorkUnit;
use Illuminate\Http\Request;

class AdminSchoolController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = School::with(['workUnit', 'province', 'city', 'principalUser']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('npsn', 'like', "%{$request->search}%")
                    ->orWhere('school_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('level') && $request->level) {
            $query->where('school_level', $request->level);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === '1');
        }

        $schools = $query->orderBy('name')->paginate(20);
        $workUnits = WorkUnit::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();

        return view('super-admin.schools.index', compact('schools', 'workUnits', 'provinces', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = $request->route('userId');
        $workUnits = WorkUnit::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.schools.create', compact('workUnits', 'provinces', 'users', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'npsn' => 'required|string|max:20|unique:schools,npsn',
            'school_code' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string|size:2',
            'city_code' => 'nullable|string|size:4',
            'district_code' => 'nullable|string|size:7',
            'village_code' => 'nullable|string|size:10',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'school_level' => 'nullable|in:sd,smp,sma,smk',
            'school_gender' => 'nullable|in:putra,putri',
            'school_status' => 'nullable|in:negeri,swasta',
            'accreditation' => 'nullable|in:A,B,C,SP,SUDAH_PERFORMA,BELUM_PERFORMA',
            'accreditation_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:30',
            'principal_nupy' => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'kop_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'kop_nama' => 'nullable|string|max:255',
            'kop_alamat' => 'nullable|string',
            'kop_telp' => 'nullable|string|max:50',
            'kop_email' => 'nullable|email|max:100',
            'kop_website' => 'nullable|string|max:100',
            'kop_npsn' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:30',
        ]);

        $school = School::create($validated);

        if ($request->hasFile('logo_path')) {
            $school->update(['logo_path' => $request->file('logo_path')->store('schools', 'public')]);
        }
        if ($request->hasFile('kop_path')) {
            $school->update(['kop_path' => $request->file('kop_path')->store('schools', 'public')]);
        }

        return redirect()->route('user.sa.schools.index', ['userId' => $request->user()->id])
            ->with('success', 'Sekolah berhasil ditambahkan.');
    }

    public function edit(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $school = School::findOrFail($id);
        $workUnits = WorkUnit::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.schools.edit', compact('school', 'workUnits', 'provinces', 'users', 'userId'));
    }

    public function update(Request $request, string $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'npsn' => 'required|string|max:20|unique:schools,npsn,' . $id,
            'school_code' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string|size:2',
            'city_code' => 'nullable|string|size:4',
            'district_code' => 'nullable|string|size:7',
            'village_code' => 'nullable|string|size:10',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'school_level' => 'nullable|in:sd,smp,sma,smk',
            'school_gender' => 'nullable|in:putra,putri',
            'school_status' => 'nullable|in:negeri,swasta',
            'accreditation' => 'nullable|in:A,B,C,SP,SUDAH_PERFORMA,BELUM_PERFORMA',
            'accreditation_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:30',
            'principal_nupy' => 'nullable|string|max:50',
            'principal_user_id' => 'nullable|exists:users,id',
            'operational_hours' => 'nullable|in:pagi,siang,full_day',
            'established_date' => 'nullable|date',
            'established_decree' => 'nullable|string|max:100',
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'kop_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'kop_nama' => 'nullable|string|max:255',
            'kop_alamat' => 'nullable|string',
            'kop_telp' => 'nullable|string|max:50',
            'kop_email' => 'nullable|email|max:100',
            'kop_website' => 'nullable|string|max:100',
            'kop_npsn' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:30',
        ]);

        $school->update($validated);

        if ($request->hasFile('logo_path')) {
            $school->update(['logo_path' => $request->file('logo_path')->store('schools', 'public')]);
        }
        if ($request->hasFile('kop_path')) {
            $school->update(['kop_path' => $request->file('kop_path')->store('schools', 'public')]);
        }

        return redirect()->route('user.sa.schools.index', ['userId' => $request->user()->id])
            ->with('success', 'Sekolah berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $school = School::findOrFail($id);
        $school->delete();

        return redirect()->route('user.sa.schools.index', ['userId' => $request->user()->id])
            ->with('success', 'Sekolah berhasil dihapus.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $school = School::findOrFail($id);
        $school->update(['is_active' => !$school->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $school->is_active,
            'message' => $school->is_active ? 'Sekolah diaktifkan.' : 'Sekolah dinonaktifkan.',
        ]);
    }
}
