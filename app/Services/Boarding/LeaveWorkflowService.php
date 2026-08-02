<?php

namespace App\Services\Boarding;

use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Exceptions\ActivePermitExistsException;
use App\Domain\Exceptions\QuotaExceededException;
use App\Domain\Services\BoardingRulesEngine;
use App\Domain\Services\BoardingTimelineService;
use App\Domain\Types\DefaultBoardingContext;
use App\Events\Boarding\LeaveApproved;
use App\Events\Boarding\LeaveReturned;
use App\Models\DormitoryLeavePolicy;
use App\Models\DormitoryPermit;
use App\Models\PermitType;
use App\Models\Student;
use App\Models\StudentMahrom;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Full leave-request lifecycle:
 *
 *   submit()              → Rules Engine → create permit
 *   approve()             → Rules Engine → change status ON_LEAVE
 *   reject()              → update status PERMIT_REJECTED, keep IN_DORM
 *   recordReturn()        → update status IN_DORM, mark quota consumed
 *
 * Every state change goes through StudentStatusService (single source of truth)
 * and emits a timeline event (full audit trail).
 */
class LeaveWorkflowService
{
    /**
     * Kategori izin khusus — diperoleh dari PermitType::where('category', 'special')
     * — selalu butuh approval kepala_asrama / admin_asrama dan tidak di-auto-approve
     * walaupun policy.auto_approve_gtk = true.
     */
    public static function getSpecialPermitTypes(): array
    {
        // Cache di kelas jika perlu; disini langsung fetch dari DB (kecil dataset).
        return PermitType::where('category', 'special')->pluck('code')->toArray();
    }

    public function __construct(
        private readonly BoardingRulesEngine $engine,
        private readonly BoardingTimelineService $timeline,
        private readonly StudentStatusService $status,
    ) {}

    /**
     * Check whether a user has GTK role.
     */
    private function isGtk(?string $userId): bool
    {
        if (! $userId) {
            return false;
        }
        $user = \App\Models\User::find($userId);
        $roleNames = $user ? $user->getRoleNames()->map(fn ($n) => strtolower($n)) : collect();
        // GTK typically has a role containing 'gtk' or the user has a GTK profile
        $hasGtkProfile = \App\Models\GtkProfile::where('user_id', $userId)->exists();
        if ($hasGtkProfile) {
            return true;
        }
        // Fallback: check Spatie roles for any GTK-ish role
        foreach ($roleNames as $r) {
            if (strpos($r, 'gtk') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user is kepala_asrama or admin_asrama.
     */
    private function isApprover(?string $userId): bool
    {
        if (! $userId) {
            return false;
        }
        try {
            $user = \App\Models\User::find($userId);
            if (! $user) {
                return false;
            }
            $roleNames = $user->getRoleNames();
            foreach ($roleNames as $r) {
                if (in_array(strtolower(trim((string) $r)), ['kepala_asrama', 'admin_asrama'])) {
                    return true;
                }
            }
        } catch (\Throwable $e) { /* Spatie may not be configured */
        }

        return false;
    }

    // ── Submit ──────────────────────────────────────────────────

    /**
     * Validate & submit a new leave request.
     *
     * Handles:
     *   - Emergency auto-detect: permit_type === 'darurat' or is_emergency flag
     *   - GTK auto-approve when policy permits
     *   - Emergency WA notification to kepala_asrama
     *
     * @param  array<string, mixed>  $data  Validated from StorePermitRequest
     */
    public function submit(array $data, string $dormitoryId, string $activeYearId): DormitoryPermit
    {
        $student = Student::find($data['student_id']);

        // 1. Pre-submit policy evaluation (run through Rules Engine for context,
        //    but never block submission here — the controller layer may choose to).
        $policy = $this->resolvePolicy($student, $dormitoryId);
        $departure = CarbonImmutable::parse($data['departure_datetime']);
        $dorm = \App\Models\Dormitory::find($dormitoryId);
        $context = new DefaultBoardingContext(
            $student,
            $dorm,
            $policy,
            'leave_request',
            $departure,
            ['permit_type' => $data['permit_type']],
            [],
            false
        );

        // Run evaluation for context (logging later) and get decision for blocking check
        $decision = $this->engine->evaluate($context);

        // ── Hard guard: tolak kalau ada izin aktif (pending / approved / picked_up / overdue)
        $this->assertNoActivePermit($data['student_id'], $activeYearId);

        // ── Quota check (separate dari Rules Engine agar mode "shared_with_pulang" & "own_quota" bisa dievaluasi konsisten)
        $isSpecialFlag = ! empty($data['is_special_permission']);
        $isEmergencyEarly = (bool) ($data['is_emergency'] ?? false) || ($data['permit_type'] ?? '') === 'darurat';
        try {
            $this->assertQuotaAvailable(
                $student, $dormitoryId, (string) $data['permit_type'],
                $departure, $isEmergencyEarly, $isSpecialFlag
            );
        } catch (QuotaExceededException $e) {
            // ⚠️ FIX: Kuota harus selalu ditolak kecuali special permission aktif atau aturan lain yang sah
            // JANGAN biarkan exception diabaikan hanya karena canBeBypassed() = true
            // Hanya jika special permission active yang dikirim di data, maka lewati
            if (! $isSpecialFlag) {
                // Re-throw exception — kuota habis dan tidak ada special permission → tolak pengajuan
                throw $e;
            }
            // Jika special permission active, lewati quota check (izinkan proceed)
        }

        // Block if quota exhausted and cannot be bypassed
        if ($decision->isDenied() && ! $decision->canBeBypassed()) {
            $denyReason = $decision->firstDenyReason();
            throw new QuotaExceededException(
                $denyReason ?: 'Pengajuan tidak dapat diproses karena melebihi kuota.',
                [
                    'policy_code' => $decision->toArray()['rule_results'][0]['policy_code'] ?? null,
                    'strategy' => $decision->toArray()['rule_results'][0]['metadata']['strategy'] ?? null,
                ]
            );
        }

        // ── Permit creation is NOT blocked by rules — we record the outcome even on denial. ──

        // Mahrom resolution (UI already fills some fields, but we validate here)
        if (! empty($data['mahrom_id'])) {
            $mahrom = StudentMahrom::where('id', $data['mahrom_id'])
                ->where('student_id', $data['student_id'])
                ->where('is_active', true)
                ->first();
            if ($mahrom) {
                $data['companion_name'] = $mahrom->name;
                $data['companion_relation'] = $mahrom->relationship_text;
                $data['companion_phone'] = $mahrom->phone;
            }
        }
        $data['companion_is_mahrom'] = isset($data['companion_is_mahrom']);

        $data['dormitory_id'] = $dormitoryId;
        $data['academic_year_id'] = $activeYearId;
        $data['id'] = $data['id'] ?? (string) Str::uuid();

        // Resolve creator: prefer explicit, then current auth user, else fail fast.
        $creator = $data['created_by'] ?? auth()->user()?->id;
        if (! $creator) {
            throw new \RuntimeException(
                'LeaveWorkflowService::submit requires an authenticated user to be logged in. '.
                'Ensure the request goes through an authenticated session or pass "created_by" explicitly.'
            );
        }
        $data['created_by'] = $creator;

        // ── Emergency detection ──────────────────────────────────────
        $isEmergency = (bool) ($data['is_emergency'] ?? false)
                      || ($data['permit_type'] ?? '') === 'darurat';

        $data['is_emergency'] = $isEmergency;

        // Emergency permits set status to pending_for_approval (not auto-pending)
        // Only kepala_asrama/admin_asrama can approve these
        // WA notification will fire after commit

        // ── GTK Auto-approve ─────────────────────────────────────────
        // Lock: izin khusus TIDAK boleh auto-approve walaupun policy.auto_approve_gtk = true.
        // Hanya kepala_asrama / admin_asrama yang boleh approve izin khusus.
        $permitType = (string) ($data['permit_type'] ?? '');
        $forceRequiresApproval = in_array($permitType, self::getSpecialPermitTypes(), true);

        // Fetch the actual DormitoryLeavePolicy (which has auto_approve_gtk field),
        // not the BoardingPolicy (which doesn't have this field).
        $leavePolicy = DormitoryLeavePolicy::where('dormitory_id', $dormitoryId)
            ->where('permit_type', $permitType)
            ->first();

        $shouldAutoApprove = false;
        if (! $forceRequiresApproval) {
            if ($this->isGtk($creator) && $leavePolicy && $leavePolicy->auto_approve_gtk) {
                $shouldAutoApprove = true;
            } elseif (! empty($data['is_special_permission'])) {
                // Special permission bypass untuk izin pulang
                $shouldAutoApprove = true;
            }
        }

        if ($shouldAutoApprove) {
            // Bypass manual approval → go straight to approved
            $data['status'] = 'approved';
            $data['approved_by'] = $creator;
            $data['approved_at'] = now();
            $data['approval_note'] = 'Auto-approved: GTK/special permission bypass.';
        } elseif ($isEmergency) {
            // Emergency: require only kepala_asrama / admin_asrama approval
            $data['status'] = 'pending';
        } else {
            $data['status'] = 'pending';
        }

        $permit = DB::transaction(function () use ($data) {
            return DormitoryPermit::create($data);
        });

        DB::afterCommit(function () use ($permit, $isEmergency) {
            Event::dispatch(new BoardingPermitSubmitted($permit));

            // ── Emergency WA notification to kepala_asrama ──────────────
            if ($isEmergency) {
                $this->notifyKepalaAsramaOfEmergency($permit);
            }
        });

        return $permit;
    }

    // ── Guards (validasi pengajuan izin) ─────────────────────────

    /**
     * Tolak pengajuan baru kalau siswa masih punya izin yang belum selesai.
     * Status yang dianggap aktif: pending, approved, picked_up, overdue.
     */
    public function assertNoActivePermit(string $studentId, string $activeYearId): void
    {
        $active = DormitoryPermit::where('student_id', $studentId)
            ->where('academic_year_id', $activeYearId)
            ->whereIn('status', ['pending', 'approved', 'picked_up', 'overdue'])
            ->orderByDesc('departure_datetime')
            ->first();

        if ($active) {
            $label = $active->permit_type_text ?? $active->permit_type;
            throw new ActivePermitExistsException(
                "Santri masih memiliki izin {$label} yang belum selesai. ".
                'Selesaikan izin sebelumnya sebelum mengajukan izin baru.',
                [
                    'existing_permit_id' => $active->id,
                    'status' => $active->status,
                    'permit_type' => $active->permit_type,
                    'departure_datetime' => optional($active->departure_datetime)->toIso8601String(),
                ]
            );
        }
    }

    /**
     * Cek kuota izin berdasarkan policy. Skip kalau emergency/special permission
     * (sesuai flag emergency_bypass_quota di policy).
     *
     * @throws QuotaExceededException
     */
    public function assertQuotaAvailable(
        ?Student $student,
        string $dormitoryId,
        string $permitType,
        CarbonImmutable $departure,
        bool $isEmergency,
        bool $isSpecial
    ): void {
        $policy = DormitoryLeavePolicy::where('dormitory_id', $dormitoryId)
            ->where('permit_type', $permitType)
            ->first();

        if (! $policy) {
            return;
        }

        // ── Emergency & Special permission bypass (sesuai policy)
        if ($isEmergency && $policy->emergency_bypass_quota) {
            return;
        }
        if ($isSpecial) {
            return;
        }

        // ── Izin khusus: pakai special_quota_mode
        if (in_array($permitType, self::getSpecialPermitTypes(), true)) {
            $mode = $policy->special_quota_mode ?? 'none';
            if ($mode === 'none') {
                return;
            }
            if ($mode === 'shared_with_pulang') {
                $pulangPolicy = DormitoryLeavePolicy::where('dormitory_id', $dormitoryId)
                    ->where('permit_type', 'pulang')->first();
                $this->checkPulangQuota(
                    $student?->id, $dormitoryId, $pulangPolicy, $departure, true
                );

                return;
            }
            // own_quota → pakai field existing
            $this->checkPeriodQuota($student?->id, $dormitoryId, $permitType, $policy, $departure);

            return;
        }

        // ── Izin pulang: pakai pulang_quota + pulang_quota_period (fallback ke quota_per_month)
        if ($permitType === 'pulang') {
            $this->checkPulangQuota(
                $student?->id, $dormitoryId, $policy, $departure, false
            );

            return;
        }

        // ── Darurat: default bypass
        if ($permitType === 'darurat') {
            return;
        }
    }

    /**
     * Cek kuota berdasarkan kebijakan pulang (pulang quota).
     *
     * Alur validasi sesuai spesifikasi:
     *   1. Dapatkan konfigurasi kuota dari policy (via resolvePulangQuota)
     *   2. Hitung jumlah izin sah yang dimiliki santri di periode yang sama
     *      - Hanya hitung izin dengan status: approved, picked_up, returned, overdue
     *      - Tidak menghitung yang ditolak atau batal (pending/rejected)
     *   3. Bandingkan jumlah dengan kuota, lempok exception jika melebihi
     *
     * @param  bool  $includeSpecialTypes  true jika dipanggil dari mode shared_with_pulang
     */
    private function checkPulangQuota(
        ?string $studentId,
        string $dormitoryId,
        ?DormitoryLeavePolicy $policy,
        CarbonImmutable $departure,
        bool $includeSpecialTypes
    ): void {
        // Pastikan data dasar tersedia
        if (! $studentId || ! $policy) {
            \Log::debug('[CHECK_PULANG_QUOTA_EARLY_EXIT]', [
                'studentId' => $studentId,
                'hasPolicy' => (bool) $policy,
                'reason' => 'studentId kosong atau policy null',
            ]);

            return;
        }

        // Langkah 1: Ambil konfigurasi kuota dari policy
        $resolved = $policy->resolvePulangQuota();
        if (! $resolved) {
            \Log::debug('[CHECK_PULANG_QUOTA_NO_RESOLVED]', [
                'studentId' => $studentId,
                'dormitoryId' => $dormitoryId,
                'reason' => 'resolvePulangQuota() return null',
            ]);

            return;
        }

        $quota = (int) $resolved['quota'];
        $period = $resolved['period'];

        // Tentukan jenis izin yang dihitung
        $countTypes = $includeSpecialTypes
            ? array_merge(['pulang'], self::getSpecialPermitTypes())
            : ['pulang'];

        // Langkah 2: Hitung rentang periode sesuai pengaturan
        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($departure, $period);

        // Hitung jumlah izin sah (yang sudah masuk siklus dan belum dibatalkan)
        $used = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $dormitoryId)
            ->whereIn('permit_type', $countTypes)
            ->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue'])
            ->where('departure_datetime', '>=', $rangeStart)
            ->where('departure_datetime', '<', $rangeEnd)
            ->count();

        // DEBUG: log full state before threshold check
        \Log::debug('[CHECK_PULANG_QUOTA_DEBUG]', [
            'studentId' => $studentId,
            'dormitoryId' => $dormitoryId,
            'period' => $period,
            'rangeStart' => $rangeStart->toDateTimeString(),
            'rangeEnd' => $rangeEnd->toDateTimeString(),
            'used' => $used,
            'quota' => $quota,
            'countTypes' => $countTypes,
            'includeSpecialTypes' => $includeSpecialTypes,
            'willThrow' => ($used >= $quota),
        ]);

        // Langkah 3: Bandingkan dengan kuota
        if ($used >= $quota) {
            // Debug log untuk diagnosa lebih lanjut
            \Log::debug('[QUOTA_EXCEEDED_DEBUG]', [
                'student_id' => $studentId,
                'dormitory_id' => $dormitoryId,
                'permit_type' => $includeSpecialTypes ? 'pulang+special' : 'pulang',
                'used' => $used,
                'quota' => $quota,
                'period' => $period,
                'range_start' => $rangeStart->toDateTimeString(),
                'range_end' => $rangeEnd->toDateTimeString(),
            ]);

            throw new QuotaExceededException(
                "Santri telah mencapai kuota izin pulang ({$used}/{$quota}) untuk periode {$period}. ".
                'Ajukan special permission jika bersifat mendesak.',
                [
                    'period' => $period,
                    'used' => $used,
                    'quota' => $quota,
                    'mode' => $includeSpecialTypes ? 'shared_with_pulang' : 'pulang',
                ]
            );
        }
    }

    /**
     * Cek kuota izin khusus yang punya kuota sendiri (mode own_quota).
     *
     * Alur validasi sesuai spesifikasi:
     *   1. Cari konfigurasi kuota pada policy (pake field quota_* yang paling spesifik yang diisi)
     *   2. Hitung jumlah izin sah milik santri di periode yang sama
     *   3. Bandingkan dengan kuota, lempok exception jika melebihi
     */
    private function checkPeriodQuota(
        ?string $studentId,
        string $dormitoryId,
        string $permitType,
        DormitoryLeavePolicy $policy,
        CarbonImmutable $departure
    ): void {
        if (! $studentId) {
            return;
        }

        // Langkah 1: Cari konfigurasi kuota (field yang paling spesifik yang diisi)
        $quota = null;
        $period = null;
        $candidates = [
            ['period' => 'week', 'column' => 'quota_per_week'],
            ['period' => 'month', 'column' => 'quota_per_month'],
            ['period' => 'semester', 'column' => 'quota_per_semester'],
            ['period' => 'year', 'column' => 'quota_per_year'],
        ];
        foreach ($candidates as $cand) {
            $val = $policy->{$cand['column']};
            if ($val !== null && $val > 0) {
                $quota = (int) $val;
                $period = $cand['period'];
                break;
            }
        }

        // Jika tidak ada kuota atau kuota = 0 → tidak ada batasan
        if ($quota === null || $quota <= 0) {
            return;
        }

        // Langkah 2: Hitung rentang periode
        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($departure, $period);

        // Hitung jumlah izin sah (status sudah melewati tahap aktif dan belum dibatalkan)
        $used = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $dormitoryId)
            ->where('permit_type', $permitType)
            ->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue'])
            ->where('departure_datetime', '>=', $rangeStart)
            ->where('departure_datetime', '<', $rangeEnd)
            ->count();

        // Langkah 3: Bandingkan dengan kuota
        if ($used >= $quota) {
            \Log::debug('[QUOTA_EXCEEDED_DEBUG]', [
                'student_id' => $studentId,
                'dormitory_id' => $dormitoryId,
                'permit_type' => $permitType,
                'used' => $used,
                'quota' => $quota,
                'period' => $period,
                'range_start' => $rangeStart->toDateTimeString(),
                'range_end' => $rangeEnd->toDateTimeString(),
            ]);

            throw new QuotaExceededException(
                "Santri telah mencapai kuota izin {$permitType} ({$used}/{$quota}) untuk periode {$period}.",
                [
                    'permit_type' => $permitType,
                    'period' => $period,
                    'used' => $used,
                    'quota' => $quota,
                    'mode' => 'own_quota',
                ]
            );
        }
    }

    /**
     * Inspect kuota untuk permit tertentu tanpa throw exception. Return array berisi:
     *   - used   : jumlah izin yang sudah terpakai
     *   - quota  : batas kuota (null = tanpa batas)
     *   - over   : true jika used >= quota
     *   - period : label periode (monthly/semester/etc) atau null
     *
     * @return array{used: int, quota: int|null, over: bool, period: string|null}
     */
    public function inspectQuotaForPermit(
        string $studentId,
        string $dormitoryId,
        string $permitType,
        CarbonImmutable $departure,
        bool $isSpecial = false
    ): array {
        if (! $studentId) {
            return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
        }

        $policy = DormitoryLeavePolicy::where('dormitory_id', $dormitoryId)
            ->where('permit_type', $permitType)
            ->first();

        // Izin khusus: mode none → tanpa batas
        if ($isSpecial) {
            $mode = $policy?->special_quota_mode ?? 'none';
            if ($mode === 'none') {
                return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
            }
            if ($mode === 'own_quota' && $policy) {
                return $this->inspectOwnQuota($studentId, $permitType, $policy, $departure);
            }
            // shared_with_pulang → pakai policy pulang
            $pulangPolicy = DormitoryLeavePolicy::where('dormitory_id', $dormitoryId)
                ->where('permit_type', 'pulang')
                ->first();
            if (! $pulangPolicy) {
                return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
            }

            return $this->inspectPulangWithSpecial($studentId, $pulangPolicy, $departure);
        }

        // Izin pulang biasa
        if ($permitType === 'pulang' && $policy) {
            return $this->inspectPulangQuota($studentId, $policy, $departure);
        }

        return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
    }

    private function inspectOwnQuota(string $studentId, string $permitType, DormitoryLeavePolicy $policy, CarbonImmutable $departure): array
    {
        // Langkah 1: Cari konfigurasi kuota (field yang paling spesifik yang diisi)
        $quota = null;
        $period = null;
        $candidates = [
            ['period' => 'week', 'column' => 'quota_per_week'],
            ['period' => 'month', 'column' => 'quota_per_month'],
            ['period' => 'semester', 'column' => 'quota_per_semester'],
            ['period' => 'year', 'column' => 'quota_per_year'],
        ];
        foreach ($candidates as $cand) {
            $val = $policy->{$cand['column']};
            if ($val !== null && $val > 0) {
                $quota = (int) $val;
                $period = $cand['period'];
                break;
            }
        }

        if ($quota === null || $quota <= 0) {
            return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
        }

        // Langkah 2: Hitung rentang periode
        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($departure, $period);

        // Hitung jumlah izin sah
        $used = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $policy->dormitory_id)
            ->where('permit_type', $permitType)
            ->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue'])
            ->where('departure_datetime', '>=', $rangeStart)
            ->where('departure_datetime', '<', $rangeEnd)
            ->count();

        return [
            'used' => (int) $used,
            'quota' => $quota,
            'over' => $used >= $quota,
            'period' => $period,
        ];
    }

    private function inspectPulangQuota(string $studentId, DormitoryLeavePolicy $policy, CarbonImmutable $departure): array
    {
        $resolved = $policy->resolvePulangQuota();
        if (! $resolved) {
            return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
        }

        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($departure, $resolved['period']);

        $used = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $policy->dormitory_id)
            ->where('permit_type', 'pulang')
            ->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue'])
            ->where('departure_datetime', '>=', $rangeStart)
            ->where('departure_datetime', '<', $rangeEnd)
            ->count();

        return [
            'used' => (int) $used,
            'quota' => (int) $resolved['quota'],
            'over' => $used >= (int) $resolved['quota'],
            'period' => $resolved['period'],
        ];
    }

    private function inspectPulangWithSpecial(string $studentId, DormitoryLeavePolicy $pulangPolicy, CarbonImmutable $departure): array
    {
        $resolved = $pulangPolicy->resolvePulangQuota();
        if (! $resolved) {
            return ['used' => 0, 'quota' => null, 'over' => false, 'period' => null];
        }

        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($departure, $resolved['period']);

        $used = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $pulangPolicy->dormitory_id)
            ->whereIn('permit_type', array_merge(['pulang'], self::getSpecialPermitTypes()))
            ->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue'])
            ->where('departure_datetime', '>=', $rangeStart)
            ->where('departure_datetime', '<', $rangeEnd)
            ->count();

        return [
            'used' => (int) $used,
            'quota' => (int) $resolved['quota'],
            'over' => $used >= (int) $resolved['quota'],
            'period' => $resolved['period'],
        ];
    }

    /**
     * Resolve window [start, end) untuk periode kalender tertentu.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriodRange(CarbonImmutable $departure, string $period): array
    {
        return match ($period) {
            'weekly', 'week' => [$departure->startOfWeek(), $departure->startOfWeek()->addWeek()],
            'monthly', 'month' => [$departure->firstOfMonth(), $departure->firstOfMonth()->addMonth()],
            'quarterly' => [
                $departure->copy()->startOfQuarter(),
                $departure->copy()->startOfQuarter()->addQuarter(),
            ],
            'semester' => $this->resolveSemesterRange($departure),
            'yearly', 'year' => [$departure->copy()->startOfYear(), $departure->copy()->startOfYear()->addYear()],
            default => [$departure->firstOfMonth(), $departure->firstOfMonth()->addMonth()],
        };
    }

    /**
     * Semester 1: Jul–Dec, Semester 2: Jan–Jun (umum Indonesia).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveSemesterRange(CarbonImmutable $departure): array
    {
        if ($departure->month >= 7) {
            return [
                $departure->copy()->setMonth(7)->startOfMonth(),
                $departure->copy()->addYear()->setMonth(1)->startOfMonth(),
            ];
        }

        return [
            $departure->copy()->setMonth(1)->startOfMonth(),
            $departure->copy()->setMonth(7)->startOfMonth(),
        ];
    }

    // ── Approve ─────────────────────────────────────────────────

    /**
     * Approve a permit → student moves to ON_LEAVE status.
     *
     * This calls the Rules Engine a second time to double-check approval is
     * consistent with current policy (quota may have changed since submission).
     */
    public function approve(string $permitId, string $dormitoryId, ?string $note = null): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);

        return DB::transaction(function () use ($permit, $dormitoryId, $note) {
            // 1. Post-approval policy re-evaluation
            $student = $permit->student;
            $policy = $this->resolvePolicy($student, $dormitoryId);
            $context = new DefaultBoardingContext(
                $student,
                $student->dormitory ?? \App\Models\Dormitory::find($dormitoryId),
                $policy,
                'leave_approval',
                CarbonImmutable::now(),
                ['permit_type' => $permit->permit_type],
                [],
                false
            );
            $decision = $this->engine->evaluate($context);

            // 2. Update permit
            $permit->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            // 3. Transition student status to ON_LEAVE
            $expectedReturn = $permit->expected_return_datetime
                ? CarbonImmutable::instance($permit->expected_return_datetime)
                : null;

            $this->status->markOnLeave(
                studentId: $permit->student_id,
                permitId: $permit->id,
                expectedReturnAt: $expectedReturn,
            );

            // 4. Dispatch domain event (deferred to after commit so it never
            //    fires inside a rollback path).
            DB::afterCommit(function () use ($permit, $note) {
                Event::dispatch(new BoardingPermitDecided(
                    permit: $permit,
                    decision: BoardingPermitDecided::APPROVED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            // 5. Dispatch integration event after commit so listeners that
            //    write attendance rows / push notifications never run inside
            //    a transaction that could roll back.
            if ($student = $permit->student) {
                DB::afterCommit(function () use ($permit, $student, $note) {
                    Event::dispatch(new LeaveApproved(
                        permit: $permit,
                        student: $student,
                        approvalNote: $note,
                    ));
                });
            }

            return $permit;
        });
    }

    // ── Reject ──────────────────────────────────────────────────

    /**
     * Reject a permit → student stays IN_DORM (or whatever current status is).
     */
    public function reject(string $permitId, string $dormitoryId, ?string $note = null): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);

        return DB::transaction(function () use ($permit, $note) {
            // Update permit
            $permit->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            // Dispatch after commit to avoid noise if something else in
            // the same transaction later rolls back.
            DB::afterCommit(function () use ($permit, $note) {
                Event::dispatch(new BoardingPermitDecided(
                    permit: $permit,
                    decision: BoardingPermitDecided::REJECTED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            return $permit;
        });
    }

    // ── Return ──────────────────────────────────────────────────

    /**
     * Record student return from leave → status back to IN_DORM.
     */
    public function recordReturn(string $permitId, string $dormitoryId, string $actualReturnDatetime): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);
        $student = $permit->student;

        return DB::transaction(function () use ($permit, $student, $actualReturnDatetime) {
            $permit->update([
                'actual_return_datetime' => $actualReturnDatetime,
                'status' => 'returned',
            ]);

            // Transition status back to IN_DORM
            $this->status->markReturned(
                studentId: $permit->student_id,
                permitId: $permit->id,
            );

            // Dispatch integration event after commit so listeners that
            // touch attendance / notifications never run inside a rollback path.
            if ($student) {
                DB::afterCommit(function () use ($permit, $student) {
                    Event::dispatch(new LeaveReturned(
                        permit: $permit,
                        student: $student,
                        note: 'Student returned from leave.',
                    ));
                });
            }

            return $permit;
        });
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function resolvePolicy(?Student $student, string $dormitoryId): ?\App\Models\BoardingPolicy
    {
        return \App\Models\BoardingPolicy::query()
            ->select('boarding_policies.*')
            ->join('dormitory_policy_assignments', 'dormitory_policy_assignments.boarding_policy_id', '=', 'boarding_policies.id')
            ->where('dormitory_policy_assignments.target_id', $dormitoryId)
            ->where('dormitory_policy_assignments.policy_assignment_type', 'dormitory')
            ->where('boarding_policies.is_active', true)
            ->orderByDesc('dormitory_policy_assignments.priority')
            ->orderByDesc('dormitory_policy_assignments.effective_from')
            ->first();
    }

    /**
     * Send WhatsApp notification to kepala_asrama / admin_asrama about emergency permit.
     */
    private function notifyKepalaAsramaOfEmergency(DormitoryPermit $permit): void
    {
        try {
            // Find all users with kepala_asrama or admin_asrama role for this dormitory
            $targets = \DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', ['kepala_asrama', 'admin_asrama'])
                ->select('users.name', 'users.phone', 'users.no_hp')
                ->get();

            if ($targets->isEmpty()) {
                return;
            }

            $studentName = $permit->student?->name ?? '—';
            $dormitoryName = $permit->dormitory?->name ?? '—';
            $departureTime = $permit->departure_datetime ? $permit->departure_datetime->format('d/m/Y H:i') : '—';
            $dest = $permit->destination ?? '—';
            $emergName = $permit->emergency_contact_name ?? '—';
            $emergPhone = $permit->emergency_contact_phone ?? '—';

            $msg = "🚨 *PERINGATAN DARURAT — Izin Santri*\n\n"
                 .'Santri: '.$studentName."\n"
                 .'Asrama: '.$dormitoryName."\n"
                 .'Waktu Berangkat: '.$departureTime."\n"
                 .'Tujuan: '.$dest."\n"
                 .'Kontak Darurat: '.$emergName.' ('.$emergPhone.")\n\n"
                 .'Mohon segera periksa dan setujui izin ini.';

            foreach ($targets as $target) {
                $phone = preg_replace('/^0/', '62', (string) ($target->no_hp ?: $target->phone));
                if (! $phone || strlen($phone) < 10) {
                    continue;
                }
                $phone = preg_replace('/[^0-9]/', '', $phone);
                \Log::channel('daily')->info('[EMERGENCY WA] Would send to '.$phone.': '.$msg);
                // TODO: Integrate with WhatsApp API provider (e.g. Fonnte, Twilio, Wablas)
                // $this->sendWhatsAppMessage($phone, $msg);
            }
        } catch (\Throwable $e) {
            \Log::channel('daily')->error('[EMERGENCY WA] Notification failed: '.$e->getMessage());
        }
    }
}
