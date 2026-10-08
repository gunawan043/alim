<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SchoolGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Dashboard per ROLE.
 *
 * Setiap role punya 1 controller turunan + 1 config + 1 view dispatcher.
 * Widget yang ditampilkan adalah gabungan (merge, deduplicated) dari:
 *   1. Widget jabatan struktural  (gtk_employments.jabatan_id → structural_positions.code)
 *   2. Widget tugas tambahan      (gtk_additional_tasks.nama_tugas, bisa multiple)
 *
 * Konteks sekolah di-resolve berurutan: school context middleware → gtk_employments
 * → session → work unit (SchoolGroupService), supaya data tidak bocor lintas sekolah.
 */
abstract class DashboardController extends Controller
{
    /** Prefix cache key per role, mis. 'dashboard.sp' */
    protected string $cachePrefix = 'dashboard';

    /** TTL cache widget dalam detik (0 = nonaktif). Bisa dioverride controller turunan. */
    protected int $cacheTtl = 300;

    /** School id hasil resolusi konteks (dipakai semua partial). */
    protected ?string $resolvedSchoolId = null;

    /** true = dashboard lintas unit (mis. Pimpinan/Super Admin); tanpa filter sekolah. */
    protected bool $globalScope = false;

    /** Label jabatan aktif (dipakai welcome banner). */
    protected string $activeJabatanLabel = '';

    /** Fingerprint scope (jabatan + tugas) — dipakai di cache key. */
    protected string $activeScopeKey = '';

    /** Slug role, dipakai untuk config + view path. Contoh: 'satuan-pendidikan' */
    abstract protected function getRoleSlug(): string;

    /** Nama role spatie (guard_name web). Contoh: 'Satuan Pendidikan' */
    abstract protected function getRoleLabel(): string;

    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Cek role via spatie
        if (! $user instanceof User || ! $user->hasRole($this->getRoleLabel())) {
            abort(403, 'Anda tidak memiliki akses ke dashboard ini.');
        }

        // 2. Load config role
        $config = $this->loadConfig();

        // 3. Deteksi jabatan struktural + tugas tambahan
        $jabatanCode   = $this->detectJabatanCode($user);
        $tugasTambahan = $this->detectTugasTambahan($user);

        // 4. Resolve widget = jabatan + tugas tambahan (deduplicated)
        $widgets = $this->resolveWidgets($config, $jabatanCode, $tugasTambahan);

        $jabatanLabel = $config['jabatan'][$jabatanCode]['label']
            ?? $config['default']['label']
            ?? $this->getRoleLabel();

        // 5. Konteks yang dibutuhkan partial
        $this->activeJabatanLabel = $jabatanLabel;
        $this->activeScopeKey = substr(md5($jabatanCode . '|' . implode('|', $tugasTambahan)), 0, 10);
        $this->resolvedSchoolId   = $this->globalScope ? null : $this->resolveSchoolId($request, $user);

        // 6. Load data tiap widget via partial (cache + error handling per widget)
        $data = $this->loadWidgetsData($widgets, $user);

        // 7. Kelompokkan widget ke section (Ringkasan, Akademik, dll)
        $sections = $this->groupWidgets($widgets, $data, $config);

        return view("dashboard.{$this->getRoleSlug()}.index", [
            'user'          => $user,
            'roleSlug'      => $this->getRoleSlug(),
            'jabatanCode'   => $jabatanCode,
            'jabatanLabel'  => $jabatanLabel,
            'tugasTambahan' => $tugasTambahan,
            'widgets'       => $widgets,
            'sections'      => $sections,
            'data'          => $data,
            'config'        => $config,
            'viewPath'      => "dashboard.{$this->getRoleSlug()}",
        ]);
    }

    // =====================================================================
    // CONFIG & DETEKSI
    // =====================================================================

    protected function loadConfig(): array
    {
        $path = config_path("dashboard/{$this->getRoleSlug()}.php");

        if (! file_exists($path)) {
            Log::error("Dashboard config not found: {$path}");

            return ['jabatan' => [], 'tugas_tambahan' => [], 'default' => []];
        }

        return require $path;
    }

    /**
     * Kode jabatan struktural dari structural_positions.code.
     * Contoh: 'tenaga_kependidikan_satuan_pendidikan_kepala_fc314'.
     */
    protected function detectJabatanCode(User $user): string
    {
        try {
            $code = DB::table('gtk_employments')
                ->join('structural_positions', 'structural_positions.id', '=', 'gtk_employments.jabatan_id')
                ->where('gtk_employments.user_id', $user->id)
                ->value('structural_positions.code');
        } catch (\Throwable $e) {
            Log::warning('detectJabatanCode failed: ' . $e->getMessage());
            $code = null;
        }

        return $code ?: 'default';
    }

    /**
     * Daftar tugas tambahan user (gtk_additional_tasks.nama_tugas).
     * Contoh hasil: ['Wali Kelas', 'Tim Kurikulum'].
     *
     * @return array<int, string>
     */
    protected function detectTugasTambahan(User $user): array
    {
        try {
            return DB::table('gtk_additional_tasks')
                ->where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('tst')->orWhere('tst', '>=', now()->toDateString());
                })
                ->orderBy('tmt')
                ->pluck('nama_tugas')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('detectTugasTambahan failed: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Gabungkan widget dari jabatan struktural + seluruh tugas tambahan.
     *
     * @param  array<int, string>  $tugasTambahan
     * @return array<int, string>
     */
    protected function resolveWidgets(array $config, string $jabatanCode, array $tugasTambahan): array
    {
        $widgets = $config['jabatan'][$jabatanCode]['widgets']
            ?? $config['default']['widgets']
            ?? [];

        foreach ($tugasTambahan as $tugas) {
            $widgets = array_merge(
                $widgets,
                $config['tugas_tambahan'][$tugas]['widgets'] ?? []
            );
        }

        return array_values(array_unique($widgets));
    }

    /**
     * Kelompokkan widget yang sudah di-resolve ke section sesuai 'section_map' config.
     * Widget yang tidak terpetakan masuk ke section 'lainnya' (atau section pertama).
     *
     * @param  array<int, string>  $widgets
     * @return array<int, array{key:string, title:string, icon:string, widgets:array<int,string>}>
     */
    protected function groupWidgets(array $widgets, array $data, array $config): array
    {
        $definitions = $config['sections'] ?? [];
        $map         = $config['section_map'] ?? [];

        if (empty($definitions)) {
            return [[
                'key'     => 'semua',
                'title'   => '',
                'icon'    => '',
                'widgets' => $widgets,
            ]];
        }

        $groups = [];
        foreach ($definitions as $key => $meta) {
            $groups[$key] = [
                'key'     => $key,
                'title'   => $meta['title'] ?? ucfirst($key),
                'icon'    => $meta['icon'] ?? 'ri-apps-2-line',
                'widgets' => [],
            ];
        }

        $fallback = array_key_exists('lainnya', $groups) ? 'lainnya' : array_key_first($groups);

        foreach ($widgets as $widgetKey) {
            // Widget yang tidak diimplementasikan (partial belum ada) tidak ditampilkan.
            if (($data[$widgetKey]['message'] ?? null) === 'Partial not found') {
                continue;
            }

            $sectionKey = $map[$widgetKey] ?? $fallback;

            if (! isset($groups[$sectionKey])) {
                $sectionKey = $fallback;
            }

            $groups[$sectionKey]['widgets'][] = $widgetKey;
        }

        return array_values(array_filter($groups, fn ($group) => ! empty($group['widgets'])));
    }

    // =====================================================================
    // KONTEKS SEKOLAH
    // =====================================================================

    /**
     * Resolve school id user:
     * 1. School context middleware (request attribute)
     * 2. gtk_employments.school_id
     * 3. session('school_id')
     * 4. Work unit → school (SchoolGroupService)
     */
    protected function resolveSchoolId(Request $request, User $user): ?string
    {
        try {
            if ($ctx = $request->attributes->get('schoolContextId')) {
                return (string) $ctx;
            }

            $schoolId = DB::table('gtk_employments')
                ->where('user_id', $user->id)
                ->value('school_id');

            if ($schoolId) {
                return (string) $schoolId;
            }
        } catch (\Throwable $e) {
            Log::warning('resolveSchoolId failed: ' . $e->getMessage());
        }

        if ($sessionSchool = session('school_id')) {
            return (string) $sessionSchool;
        }

        try {
            return optional(SchoolGroupService::getUserSchool($user))->id;
        } catch (\Throwable $e) {
            Log::warning('resolveSchoolId (work unit) failed: ' . $e->getMessage());

            return null;
        }
    }

    // =====================================================================
    // LOAD DATA WIDGET
    // =====================================================================

    /**
     * Jalankan partial tiap widget. Partial mengembalikan array data.
     * Widget yang partial-nya belum ada ditandai 'Partial not found' (di-skip oleh view).
     * Error pada satu widget tidak menghentikan widget lain.
     */
    protected function loadWidgetsData(array $widgets, User $user): array
    {
        $data = [];

        foreach ($widgets as $widgetKey) {
            $partialFile = $this->resolvePartialPath($widgetKey);

            if ($partialFile === null) {
                Log::debug("Widget partial not found: {$widgetKey} (role {$this->getRoleSlug()})");
                $data[$widgetKey] = ['error' => true, 'message' => 'Partial not found'];
                continue;
            }

            try {
                $schoolPart = $this->getUserSchoolId($user) ?? 'global';
                $jabatanPart = $this->detectJabatanCode($user);
                $cacheKey = "{$this->cachePrefix}.{$widgetKey}.user.{$user->id}.jabatan.{$jabatanPart}.scope.{$this->activeScopeKey}.school.{$schoolPart}";

                $data[$widgetKey] = $this->cacheTtl > 0
                    ? Cache::remember($cacheKey, $this->cacheTtl, fn () => $this->runPartial($partialFile, $user))
                    : $this->runPartial($partialFile, $user);
            } catch (\Throwable $e) {
                Log::error("Widget [{$widgetKey}] failed: " . $e->getMessage());
                $data[$widgetKey] = ['error' => true, 'message' => $e->getMessage()];
            }
        }

        return $data;
    }

    /**
     * Cari partial widget: folder role dulu, lalu library bersama (_shared).
     */
    protected function resolvePartialPath(string $widgetKey): ?string
    {
        $roleFile = resource_path("views/dashboard/{$this->getRoleSlug()}/partials/{$widgetKey}.php");

        if (file_exists($roleFile)) {
            return $roleFile;
        }

        $sharedFile = resource_path("views/dashboard/_shared/partials/{$widgetKey}.php");

        return file_exists($sharedFile) ? $sharedFile : null;
    }

    /**
     * Partial dijalankan dalam scope $this (controller) sehingga bisa akses helper,
     * dan menerima variabel $user.
     */
    protected function runPartial(string $partialFile, User $user): array
    {
        return (function () use ($partialFile, $user) {
            $result = require $partialFile;

            return is_array($result) ? $result : [];
        })->call($this);
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Rombel (study group) tempat user menjadi wali kelas.
     * Urutan sumber:
     *  1. homeroom_assignments (canonical, status active + rentang tanggal)
     *  2. homeroom_teachers (is_active)
     *  3. gtk_employments.study_group_id
     *  4. study_groups.homeroom_teacher_id = users.id
     */
    protected function getHomeroomStudyGroup(User $user): ?object
    {
        try {
            $academicYearId = $this->getActiveAcademicYearId();
            $today = now()->toDateString();

            $rombel = DB::table('homeroom_assignments as ha')
                ->join('study_groups as sg', 'sg.id', '=', 'ha.study_group_id')
                ->where('ha.teacher_id', $user->id)
                ->where('ha.status', 'active')
                ->where('ha.start_date', '<=', $today)
                ->where(fn ($q) => $q->whereNull('ha.end_date')->orWhere('ha.end_date', '>=', $today))
                ->whereNull('ha.deleted_at')
                ->when($academicYearId, fn ($q) => $q->where('ha.academic_year_id', $academicYearId))
                ->select('sg.*')
                ->first();

            if ($rombel) {
                return $rombel;
            }

            $rombel = DB::table('homeroom_teachers as ht')
                ->join('study_groups as sg', 'sg.id', '=', 'ht.study_group_id')
                ->where('ht.teacher_id', $user->id)
                ->where('ht.is_active', 1)
                ->when($academicYearId, fn ($q) => $q->where('ht.academic_year_id', $academicYearId))
                ->select('sg.*')
                ->first();

            if ($rombel) {
                return $rombel;
            }

            $gtk = DB::table('gtk_employments')->where('user_id', $user->id)->first();

            if ($gtk && ! empty($gtk->study_group_id)) {
                $rombel = DB::table('study_groups')->where('id', $gtk->study_group_id)->first();

                if ($rombel) {
                    return $rombel;
                }
            }

            return DB::table('study_groups')
                ->where('homeroom_teacher_id', $user->id)
                ->orderByDesc('is_active')
                ->first();
        } catch (\Throwable $e) {
            Log::warning('getHomeroomStudyGroup failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * School id efektif user (hasil resolusi konteks; fallback ke gtk_employments).
     */
    protected function getUserSchoolId(User $user): ?string
    {
        if ($this->resolvedSchoolId !== null) {
            return $this->resolvedSchoolId;
        }

        return DB::table('gtk_employments')
            ->where('user_id', $user->id)
            ->value('school_id');
    }

    protected function getActiveAcademicYearId(): ?string
    {
        return Cache::remember('active_academic_year_id', 3600, function () {
            return DB::table('academic_years')->where('is_active', 1)->value('id');
        });
    }
}
