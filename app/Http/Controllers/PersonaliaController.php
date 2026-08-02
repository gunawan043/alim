<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GtkAddress;
use App\Models\GtkFamilyMember;
use App\Models\GtkProfile;
use App\Models\GtkTransferRequest;
use App\Models\GtkWorkUnitHistory;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonaliaController extends Controller
{
    /**
     * LIST GTK
     */
    public function index()
    {
        $gtks = GtkProfile::with(['user', 'addresses', 'familyMembers'])->paginate(15);

        return response()->json($gtks);
    }

    /**
     * CREATE GTK (USER + PROFILE + ADDRESS)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',

            'nama_lengkap' => 'required|string',
            'nik' => 'required|string',
            'no_kk' => 'required|string',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'nama_ibu_kandung' => 'required',
            'status_perkawinan' => 'required',
            'no_hp' => 'required',

            'alamat_ktp' => 'required|array',
        ]);

        DB::transaction(function () use ($validated) {

            /** USER */
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            /** ROLE — observer GtkEmployment::created akan sinkronkan role
             *  begitu GtkEmployment dibuat (lihat GtkEmploymentObserver).
             *  Untuk sementara assign role Guru sebagai baseline. */
            $user->assignRole('Guru');

            /** PROFILE */
            $profile = GtkProfile::create([
                'user_id' => $user->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'nik' => $validated['nik'],
                'no_kk' => $validated['no_kk'],
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'nama_ibu_kandung' => $validated['nama_ibu_kandung'],
                'status_perkawinan' => $validated['status_perkawinan'],
                'no_hp' => $validated['no_hp'],
            ]);

            /** ALAMAT KTP */
            GtkAddress::create([
                'user_id' => $user->id,
                'type' => 'ktp',
                ...$validated['alamat_ktp'],
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREATE_GTK',
                'record_id' => $profile->id,
                'table_name' => 'gtk_profiles',
            ]);
        });

        return response()->json(['message' => 'GTK berhasil dibuat'], 201);
    }

    /**
     * DETAIL GTK
     */
    public function show(GtkProfile $gtk)
    {
        $this->authorize('view', $gtk);

        return response()->json(
            $gtk->load(['user', 'addresses', 'familyMembers'])
        );
    }

    /**
     * UPDATE GTK
     */
    public function update(Request $request, GtkProfile $gtk)
    {
        $this->authorize('update', $gtk);

        $validated = $request->validate([
            'nama_lengkap' => 'string',
            'no_hp' => 'string',
            'status_perkawinan' => 'string',
        ]);

        $gtk->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_GTK',
            'record_id' => $gtk->id,
            'table_name' => 'gtk_profiles',
        ]);

        return response()->json(['message' => 'Data GTK diperbarui']);
    }

    /**
     * DELETE GTK (SOFT DELETE USER)
     */
    public function destroy(GtkProfile $gtk)
    {
        $this->authorize('delete', $gtk);

        DB::transaction(function () use ($gtk) {
            $gtk->user->update(['is_active' => false]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'DEACTIVATE_GTK',
                'record_id' => $gtk->id,
                'table_name' => 'users',
            ]);
        });

        return response()->json(['message' => 'GTK dinonaktifkan']);
    }

    /**
     * TAMBAH ANGGOTA KELUARGA
     */
    public function addFamily(Request $request, GtkProfile $gtk)
    {
        $this->authorize('update', $gtk);

        $validated = $request->validate([
            'nama' => 'required',
            'hubungan' => 'required|in:suami,istri,anak',
            'tanggal_lahir' => 'nullable|date',
            'sekolah' => 'nullable|string',
        ]);

        GtkFamilyMember::create([
            'user_id' => $gtk->user_id,
            ...$validated,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ADD_FAMILY_MEMBER',
            'record_id' => $gtk->id,
        ]);

        return response()->json(['message' => 'Anggota keluarga ditambahkan']);
    }

    /**
     * GANTI ROLE USER
     */
    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$validated['role']]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'CHANGE_ROLE',
            'record_id' => $user->id,
        ]);

        return response()->json(['message' => 'Role diperbarui']);
    }

    public function createWorkUnit(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'jenis' => 'required|in:sekolah,asrama,lembaga,unit',
            'jenjang' => 'nullable|string',
        ]);

        $unit = WorkUnit::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'CREATE_WORK_UNIT',
            'record_id' => $unit->id,
            'table_name' => 'work_units',
        ]);

        return response()->json($unit, 201);
    }

    public function assignGtkToUnit(Request $request, User $user)
    {
        $validated = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'jabatan' => 'nullable|string',
            'is_primary' => 'boolean',
        ]);

        $user->workUnits()->syncWithoutDetaching([
            $validated['work_unit_id'] => [
                'jabatan' => $validated['jabatan'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
            ],
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ASSIGN_GTK_TO_UNIT',
            'record_id' => $user->id,
        ]);

        return response()->json(['message' => 'GTK berhasil dimasukkan ke satuan kerja']);
    }

    public function transferGtk(Request $request, User $user)
    {
        $validated = $request->validate([
            'to_work_unit_id' => 'required|exists:work_units,id',
            'jabatan' => 'nullable|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldUnit = $user->workUnits()->wherePivot('is_primary', true)->first();

        // Nonaktifkan primary lama
        if ($oldUnit) {
            $user->workUnits()->updateExistingPivot(
                $oldUnit->id,
                ['is_primary' => false]
            );
        }

        // Set unit baru
        $user->workUnits()->syncWithoutDetaching([
            $validated['to_work_unit_id'] => [
                'jabatan' => $validated['jabatan'],
                'is_primary' => true,
            ],
        ]);

        // HISTORY
        GtkWorkUnitHistory::create([
            'user_id' => $user->id,
            'from_work_unit_id' => $oldUnit?->id,
            'to_work_unit_id' => $validated['to_work_unit_id'],
            'jabatan' => $validated['jabatan'],
            'action' => $oldUnit ? 'TRANSFER' : 'ASSIGN',
            'reason' => $validated['reason'],
            'performed_by' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'message' => 'Perpindahan GTK berhasil dicatat',
        ]);
    }

    public function history(User $user)
    {
        return GtkWorkUnitHistory::with([
            'fromWorkUnit',
            'toWorkUnit',
            'performedBy',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function requestTransfer(Request $request, User $user)
    {
        $this->authorize('create', GtkTransferRequest::class);

        $validated = $request->validate([
            'to_work_unit_id' => 'required|exists:work_units,id',
            'jabatan' => 'nullable|string',
            'reason' => 'required|string|max:500',
        ]);

        $currentUnit = $user->workUnits()->wherePivot('is_primary', true)->first();

        GtkTransferRequest::create([
            'user_id' => $user->id,
            'from_work_unit_id' => $currentUnit?->id,
            'to_work_unit_id' => $validated['to_work_unit_id'],
            'jabatan' => $validated['jabatan'],
            'reason' => $validated['reason'],
            'requested_by' => Auth::id(),
            'request_ip' => request()->ip(),
            'request_user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'message' => 'Permohonan perpindahan berhasil diajukan',
        ]);
    }

    public function approveTransfer(Request $request, GtkTransferRequest $transfer)
    {
        $this->authorize('approve', GtkTransferRequest::class);

        abort_if($transfer->status !== 'PENDING', 400, 'Request tidak valid');

        DB::transaction(function () use ($transfer, $request) {

            $user = $transfer->gtk;

            // Nonaktifkan unit lama
            if ($transfer->from_work_unit_id) {
                $user->workUnits()->updateExistingPivot(
                    $transfer->from_work_unit_id,
                    ['is_primary' => false]
                );
            }

            // Aktifkan unit baru
            $user->workUnits()->syncWithoutDetaching([
                $transfer->to_work_unit_id => [
                    'jabatan' => $transfer->jabatan,
                    'is_primary' => true,
                ],
            ]);

            // HISTORY (IMMUTABLE)
            GtkWorkUnitHistory::create([
                'user_id' => $user->id,
                'from_work_unit_id' => $transfer->from_work_unit_id,
                'to_work_unit_id' => $transfer->to_work_unit_id,
                'jabatan' => $transfer->jabatan,
                'action' => 'TRANSFER',
                'reason' => $transfer->reason,
                'performed_by' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // UPDATE STATUS
            $transfer->update([
                'status' => 'APPROVED',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_note' => $request->approval_note,
            ]);
        });

        return response()->json(['message' => 'Perpindahan disetujui & dieksekusi']);
    }

    public function rejectTransfer(Request $request, GtkTransferRequest $transfer)
    {
        $this->authorize('approve', GtkTransferRequest::class);

        $transfer->update([
            'status' => 'REJECTED',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $request->approval_note,
        ]);

        return response()->json(['message' => 'Permohonan ditolak']);
    }
}
