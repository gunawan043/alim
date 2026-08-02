<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryLeavePolicy;
use App\Models\PermitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DormitoryLeavePolicyController extends Controller
{
    /**
     * Daftar semua jenis izin (master + per-asrama policy).
     *
     * Sumber:
     * - `PermitType` untuk daftar jenis izin master.
     * - `DormitoryLeavePolicy` untuk konfigurasi per-asrama.
     *
     * View digabung (LEFT JOIN di memori) sehingga setiap baris master
     * tetap muncul walaupun belum dibuat policy-nya.
     */
    public function index(Request $request, string $userId, string $asramaUuid): View
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        // Bawa semua policy per-asrama ke memori (key by permit_type).
        $policies = DormitoryLeavePolicy::where('dormitory_id', $asramaUuid)
            ->orderByRaw("CASE WHEN permit_type = '__default__' THEN 0 ELSE 1 END")
            ->orderBy('permit_type')
            ->get()
            ->keyBy('permit_type');

        return view('dormitory.leave-policies.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'policies' => $policies,
        ]);
    }

    /**
     * Halaman edit konfigurasi per-jenis izin.
     *
     * `permitType` sekarang divalidasi terhadap master (PermitType.code
     * yang aktif) atau '__default__' (template).
     */
    public function show(Request $request, string $userId, string $asramaUuid, string $permitType): View
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        // Validasi permit_type: harus salah satu dari PermitType.code (aktif)
        // atau '__default__' (template default).
        $validCodes = PermitType::where('is_active', true)->pluck('code')->toArray();
        $validCodes[] = '__default__';

        if (! in_array($permitType, $validCodes, true)) {
            abort(404, 'Jenis izin tidak dikenal.');
        }

        /** @var DormitoryLeavePolicy $policy */
        $policy = DormitoryLeavePolicy::firstOrCreate(
            ['dormitory_id' => $asramaUuid, 'permit_type' => $permitType],
            [
                'is_enabled' => true,
                'requires_approval' => true,
                'quota_per_week' => null,
                'quota_per_month' => null,
                'quota_per_semester' => null,
                'quota_per_year' => null,
                'auto_approve_gtk' => false,
                'auto_approve_kepala_asrama' => false,
                'emergency_bypass_quota' => true,
                'emergency_notify_wa_kepala' => true,
                'emergency_approver_roles' => ['kepala_asrama', 'admin_asrama'],
                'updated_by' => auth()->id(),
            ]
        );

        // Dapatkan info master PermitType (untuk label & metadata dinamis).
        $permitTypeMaster = PermitType::where('code', $permitType)->first();

        return view('dormitory.leave-policies.edit', compact(
            'dormitory', 'userId', 'policy', 'permitType', 'permitTypeMaster'
        ));
    }

    /**
     * Store / update konfigurasi per-jenis izin.
     *
     * Sekarang `permit_type` divalidasi terhadap PermitType.code (DB)
     * + '__default__' sebagai kode template.
     */
    public function storeOrUpdate(Request $request, string $userId, string $asramaUuid): RedirectResponse
    {
        $validPermitCodes = PermitType::pluck('code')->toArray();
        $validPermitCodes[] = '__default__';

        $data = $request->validate([
            'permit_type' => ['required', 'string', Rule::in($validPermitCodes)],
            'is_enabled' => 'boolean',
            'requires_approval' => 'boolean',
            'quota_per_week' => 'nullable|integer|min:0|max:9999',
            'quota_per_month' => 'nullable|integer|min:0|max:9999',
            'quota_per_semester' => 'nullable|integer|min:0|max:9999',
            'quota_per_year' => 'nullable|integer|min:0|max:9999',
            'pulang_quota' => 'nullable|integer|min:0|max:9999',
            'pulang_quota_period' => 'nullable|required_with:pulang_quota|in:monthly,quarterly,semester,yearly',
            'special_quota_mode' => 'nullable|in:none,shared_with_pulang,own_quota',
            'auto_approve_gtk' => 'boolean',
            'auto_approve_kepala_asrama' => 'boolean',
            'emergency_bypass_quota' => 'boolean',
            'emergency_notify_wa_kepala' => 'boolean',
            'emergency_approver_roles' => 'nullable|array',
            'emergency_approver_roles.*' => 'string|min:1',
        ]);

        $data['special_quota_mode'] = $data['special_quota_mode'] ?? 'none';
        $data['dormitory_id'] = $asramaUuid;
        $data['updated_by'] = auth()->id();
        $data['is_enabled'] = $request->filled('is_enabled');
        $data['requires_approval'] = $request->filled('requires_approval');
        $data['auto_approve_gtk'] = $request->filled('auto_approve_gtk');
        $data['auto_approve_kepala_asrama'] = $request->filled('auto_approve_kepala_asrama');
        $data['emergency_bypass_quota'] = $request->filled('emergency_bypass_quota');
        $data['emergency_notify_wa_kepala'] = $request->filled('emergency_notify_wa_kepala');

        DormitoryLeavePolicy::updateOrCreate(
            ['dormitory_id' => $asramaUuid, 'permit_type' => $data['permit_type']],
            $data
        );

        return redirect()->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Konfigurasi izin berhasil disimpan.');
    }

    /**
     * Apply template `__default__` ke SEMUA jenis izin (master aktif).
     *
     * Tidak pakai hardcoded list lagi — ambil dari PermitType::active.
     */
    public function applyDefaults(Request $request, string $userId, string $asramaUuid): RedirectResponse
    {
        $defaults = DormitoryLeavePolicy::where('dormitory_id', $asramaUuid)
            ->where('permit_type', '__default__')
            ->first();

        if (! $defaults) {
            return back()->withErrors(['error' => 'Pengaturan default belum dibuat. Silakan simpan sebagai default terlebih dahulu.']);
        }

        // Iterasi ke SEMUA jenis izin aktif di master, bukan hardcoded list.
        $permitCodes = PermitType::where('is_active', true)->pluck('code');

        foreach ($permitCodes as $code) {
            DormitoryLeavePolicy::updateOrCreate(
                ['dormitory_id' => $asramaUuid, 'permit_type' => $code],
                array_merge(
                    $defaults->toArray(),
                    ['permit_type' => $code, 'updated_by' => auth()->id()]
                )
            );
        }

        return redirect()->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Default berhasil diterapkan ke semua jenis izin.');
    }
}
