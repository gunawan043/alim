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

        $visitPolicy = app(\App\Services\Boarding\VisitWorkflowService::class)
            ->resolvePolicyForDormitory($asramaUuid);

        $consentOverride = $visitPolicy?->guardian_consent_required;
        $effectiveConsentRequired = \App\Support\AsramaConfig::consentRequiredForDormitory($asramaUuid);

        return view('dormitory.leave-policies.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'policies' => $policies,
            'visitPolicy' => $visitPolicy,
            'consentOverride' => $consentOverride,
            'effectiveConsentRequired' => $effectiveConsentRequired,
            'canManageQuota' => app(\App\Services\Boarding\DormitoryPolicyAccess::class)
                ->canManageQuota(auth()->user(), $asramaUuid),
        ]);
    }

    /**
     * Simpan kebijakan kunjungan PER ASRAMA (Kepala/Wakil Kepala Asrama).
     */
    public function updateVisitPolicy(Request $request, string $userId, string $asramaUuid): RedirectResponse
    {
        $this->authorizeQuotaManager($asramaUuid);

        $data = $request->validate([
            'visit_strategy' => 'required|in:unrestricted,quota,banned',
            'visit_quota' => 'nullable|required_if:visit_strategy,quota|integer|min:1|max:9999',
            'visit_quota_period' => 'nullable|required_if:visit_strategy,quota|in:weekly,monthly,semester,yearly',
            'max_visitors_per_visit' => 'nullable|integer|min:1|max:100',
        ]);

        app(\App\Services\Boarding\VisitWorkflowService::class)
            ->saveDormitoryPolicy($asramaUuid, $data);

        return redirect()
            ->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Kebijakan kunjungan asrama berhasil disimpan.');
    }

    /**
     * Simpan override consent PDP wali untuk satu asrama.
     * NULL = ikuti pengaturan global.
     */
    public function updateConsentPolicy(Request $request, string $userId, string $asramaUuid): RedirectResponse
    {
        $this->authorizeQuotaManager($asramaUuid);

        $request->validate([
            'guardian_consent_required' => 'nullable|in:0,1',
        ]);

        $value = $request->input('guardian_consent_required');
        $required = ($value === null || $value === '') ? null : (bool) (int) $value;

        app(\App\Services\Boarding\VisitWorkflowService::class)
            ->saveDormitoryConsentRequirement($asramaUuid, $required);

        return redirect()
            ->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Pengaturan consent data wali berhasil disimpan.');
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

        $canManageQuota = app(\App\Services\Boarding\DormitoryPolicyAccess::class)
            ->canManageQuota(auth()->user(), $asramaUuid);

        return view('dormitory.leave-policies.edit', compact(
            'dormitory', 'userId', 'policy', 'permitType', 'permitTypeMaster', 'canManageQuota'
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
        $this->authorizeQuotaManager($asramaUuid);

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
            'emergency_approver_roles_string' => 'nullable|string|max:500',
        ]);

        if ($request->filled('emergency_approver_roles_string')) {
            $data['emergency_approver_roles'] = collect(explode(',', (string) $request->input('emergency_approver_roles_string')))
                ->map(fn ($role) => trim($role))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
        unset($data['emergency_approver_roles_string']);

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
        $this->authorizeQuotaManager($asramaUuid);

        $defaults = DormitoryLeavePolicy::where('dormitory_id', $asramaUuid)
            ->where('permit_type', '__default__')
            ->first();

        if (! $defaults) {
            return back()->withErrors(['error' => 'Pengaturan default belum dibuat. Silakan simpan sebagai default terlebih dahulu.']);
        }

        // Iterasi ke SEMUA jenis izin aktif di master, bukan hardcoded list.
        $permitCodes = PermitType::where('is_active', true)->pluck('code');

        // Jangan copy id/timestamps dari baris default; kalau ikut ter-copy,
        // updateOrCreate akan mencoba memakai UUID yang sama untuk semua jenis.
        $defaultValues = collect($defaults->toArray())
            ->only((new DormitoryLeavePolicy)->getFillable())
            ->except(['id', 'dormitory_id', 'permit_type', 'updated_by'])
            ->all();

        foreach ($permitCodes as $code) {
            DormitoryLeavePolicy::updateOrCreate(
                ['dormitory_id' => $asramaUuid, 'permit_type' => $code],
                array_merge($defaultValues, [
                    'permit_type' => $code,
                    'updated_by' => auth()->id(),
                ])
            );
        }

        return redirect()->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Default berhasil diterapkan ke semua jenis izin.');
    }

    /**
     * Hanya Kepala Asrama / Wakil Kepala Asrama yang boleh mengubah kuota.
     */
    private function authorizeQuotaManager(string $dormitoryId): void
    {
        $user = auth()->user();

        if (! $user || ! app(\App\Services\Boarding\DormitoryPolicyAccess::class)->canManageQuota($user, $dormitoryId)) {
            abort(403, 'Hanya Kepala Asrama atau Wakil Kepala Asrama yang dapat mengubah pengaturan kuota.');
        }
    }
}
