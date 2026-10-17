<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryViolation;
use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konsol Sistem — Dashboard Super Admin.
 *
 * Menampilkan: ringkasan pengguna & akses, aktivitas sistem, kesehatan
 * database, serta ringkasan operasional lintas unit.
 */
class SystemDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        // ── Statistik Pengguna & Akses ────────────────────────────
        $stats = [
            'users_total'       => DB::table('users')->whereNull('deleted_at')->count(),
            'users_active'      => DB::table('users')->where('is_active', 1)->whereNull('deleted_at')->count(),
            'users_inactive'    => DB::table('users')->where('is_active', 0)->whereNull('deleted_at')->count(),
            'users_no_role'     => DB::table('users')->whereNull('deleted_at')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('model_has_roles')->whereColumn('model_has_roles.model_id', 'users.id'))
                ->count(),
            'system_admins'     => DB::table('users')->where('is_system_admin', 1)->count(),
            'roles_total'       => DB::table('roles')->count(),
            'permissions_total' => DB::table('permissions')->count(),
            'schools_total'     => DB::table('schools')->count(),
            'schools_active'    => DB::table('schools')->where('is_active', 1)->count(),
            'migrations_total'  => DB::table('migrations')->count(),
            'activity_total'    => DB::table('activity_log')->count(),
            'activity_today'    => DB::table('activity_log')->whereDate('created_at', today())->count(),
            'jobs_pending'      => DB::table('jobs')->count(),
            'jobs_failed'       => DB::table('failed_jobs')->count(),

            // Ringkasan operasional lintas unit
            'students_active'   => Student::where('status', 'active')->count(),
            'study_groups_total' => StudyGroup::count(),
            'dormitories_total' => Dormitory::where('is_active', true)->count(),
            'permits_pending'   => DormitoryPermit::where('status', 'pending')->count(),
            'violations_total'  => DormitoryViolation::count(),
            'gtk_total'         => GtkEmployment::count(),
        ];

        // ── Aktivitas 7 Hari ──────────────────────────────────────
        $activityLabels = [];
        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $activityLabels[] = $day->translatedFormat('d M');
            $activityData[] = DB::table('activity_log')->whereDate('created_at', $day->toDateString())->count();
        }

        // ── Aktivitas per Jenis (event) ───────────────────────────
        $activityEventLabels = [];
        $activityEventData = [];
        foreach (DB::table('activity_log')->selectRaw("COALESCE(NULLIF(event, ''), 'lainnya') as event, COUNT(*) as total")
            ->groupBy('event')->orderByDesc('total')->limit(8)->get() as $row) {
            $activityEventLabels[] = ucfirst((string) $row->event);
            $activityEventData[] = (int) $row->total;
        }

        // ── Distribusi User per Role ──────────────────────────────
        $roleLabels = [];
        $roleData = [];
        foreach (DB::table('roles as r')
            ->leftJoin('model_has_roles as mr', 'mr.role_id', '=', 'r.id')
            ->groupBy('r.id', 'r.name')
            ->selectRaw('r.name, COUNT(mr.model_id) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->get() as $row) {
            $roleLabels[] = $row->name;
            $roleData[] = (int) $row->total;
        }

        // ── Tabel: User Terbaru & Tanpa Role ──────────────────────
        $recentUsers = DB::table('users')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['name', 'email', 'is_active', 'created_at']);

        $usersWithoutRole = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('model_has_roles')->whereColumn('model_has_roles.model_id', 'users.id'))
            ->orderBy('name')
            ->limit(8)
            ->get(['name', 'email', 'created_at']);

        // ── Tabel: Role & Hak Akses ───────────────────────────────
        $roleStats = DB::table('roles as r')
            ->leftJoin('model_has_roles as mr', 'mr.role_id', '=', 'r.id')
            ->leftJoin('role_has_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->groupBy('r.id', 'r.name', 'r.level')
            ->selectRaw('r.name, r.level, COUNT(DISTINCT mr.model_id) as users, COUNT(DISTINCT rp.permission_id) as permissions')
            ->orderBy('r.name')
            ->get();

        // ── Tabel: Log Aktivitas Terbaru ──────────────────────────
        $recentActivities = DB::table('activity_log')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['log_name', 'description', 'event', 'created_at']);

        // ── Tabel: Migrasi Terbaru ────────────────────────────────
        $recentMigrations = DB::table('migrations')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['migration', 'batch']);

        return view('system.dashboard', compact(
            'stats',
            'activityLabels', 'activityData',
            'activityEventLabels', 'activityEventData',
            'roleLabels', 'roleData',
            'recentUsers', 'usersWithoutRole', 'roleStats',
            'recentActivities', 'recentMigrations',
        ));
    }

    public function features()
    {
        // Dashboard per role yang sudah tersedia
        $roleDashboards = [
            ['label' => 'Satuan Pendidikan', 'role' => 'Satuan Pendidikan', 'route' => 'user.dashboard.satuan-pendidikan', 'icon' => 'ri-book-open-line', 'color' => 'primary'],
            ['label' => 'Pimpinan', 'role' => 'Pimpinan', 'route' => 'user.dashboard.pimpinan', 'icon' => 'ri-vip-crown-2-line', 'color' => 'warning'],
            ['label' => 'Keuangan', 'role' => 'Keuangan', 'route' => 'user.dashboard.keuangan', 'icon' => 'ri-wallet-3-line', 'color' => 'success'],
            ['label' => 'Asrama', 'role' => 'Asrama', 'route' => 'user.dashboard.asrama', 'icon' => 'ri-home-smile-2-line', 'color' => 'info'],
            ['label' => 'UKS', 'role' => 'UKS', 'route' => 'user.uks.dashboard', 'icon' => 'ri-heart-pulse-line', 'color' => 'danger'],
            ['label' => 'Departemen Tahfidz', 'role' => 'Departemen Tahfidz', 'route' => 'user.dashboard.tahfidz', 'icon' => 'ri-book-2-line', 'color' => 'primary'],
            ['label' => 'Humas Personalia', 'role' => 'Humas Personalia', 'route' => 'user.dashboard.personalia', 'icon' => 'ri-team-line', 'color' => 'success'],
            ['label' => 'Unit Rumah Tangga', 'role' => 'Unit Rumah Tangga', 'route' => 'user.dashboard.unit-rumah-tangga', 'icon' => 'ri-home-gear-line', 'color' => 'warning'],
            ['label' => 'Konsol Sistem', 'role' => 'Super Admin', 'route' => 'system.dashboard', 'icon' => 'ri-shield-user-line', 'color' => 'dark'],
        ];

        // Status modul berdasarkan keberadaan tabel skema
        $moduleDefinitions = [
            'Kepegawaian & GTK'    => ['gtk_profiles', 'gtk_employments', 'gtk_educations'],
            'Kesiswaan'            => ['students', 'student_class_histories', 'violation_points'],
            'Asrama'               => ['dormitories', 'dormitory_permits', 'dormitory_attendances'],
            'UKS'                  => ['uks_patients', 'uks_beds', 'student_medicine_inventory'],
            'Tahfidz'              => ['tahfidz_setorans', 'tahfidz_mutabaah', 'tahfidz_groups'],
            'Keuangan'             => ['payroll', 'invoice_approvals', 'division_budgets'],
            'Sarpras & URT'        => ['assets', 'work_orders', 'warehouses', 'spareparts'],
            'Perizinan & Tata Tertib' => ['dormitory_permits', 'dormitory_violations'],
            'Rekrutmen'            => ['gtk_recruitments', 'recruitment_jobs', 'recruitment_applications'],
            'Agenda & Kegiatan'    => ['agendas', 'agenda_categories'],
            'Departemen Bahasa'    => ['language_vocabularies', 'language_violations'],
            'Perpustakaan'         => ['library_books', 'library_loans'],
            'Satuan Keamanan'      => ['security_posts', 'security_guest_logs'],
            'Teknologi Informasi'  => ['it_devices', 'it_helpdesk_tickets'],
            'Pelayanan Gizi'       => ['kitchen_menus', 'food_stocks'],
        ];

        $modules = [];
        foreach ($moduleDefinitions as $label => $tables) {
            $available = collect($tables)->filter(fn ($t) => Schema::hasTable($t))->count();
            $modules[] = [
                'label'   => $label,
                'tables'  => $tables,
                'found'   => $available,
                'total'   => count($tables),
                'status'  => $available === count($tables) ? 'aktif' : ($available > 0 ? 'sebagian' : 'belum'),
            ];
        }

        return view('system.features', compact('roleDashboards', 'modules'));
    }

    public function monitoring()
    {
        $dbName = DB::getDatabaseName();

        $tableCount = DB::table('information_schema.tables')
            ->where('table_schema', $dbName)
            ->count();

        $dbSizeMb = (float) DB::table('information_schema.tables')
            ->where('table_schema', $dbName)
            ->sum(DB::raw('ROUND((data_length + index_length) / 1024 / 1024, 2)'));

        $logPath = storage_path('logs/laravel.log');

        $monitoring = [
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'environment'      => app()->environment(),
            'debug_mode'       => config('app.debug'),
            'timezone'         => config('app.timezone'),
            'db_driver'        => config('database.default'),
            'db_database'      => $dbName,
            'db_tables'        => $tableCount,
            'db_size_mb'       => $dbSizeMb,
            'cache_driver'     => config('cache.default'),
            'session_driver'   => config('session.driver'),
            'queue_driver'     => config('queue.default'),
            'jobs_pending'     => DB::table('jobs')->count(),
            'jobs_failed'      => DB::table('failed_jobs')->count(),
            'sessions_active'  => DB::table('sessions')->count(),
            'log_size_kb'      => file_exists($logPath) ? round(filesize($logPath) / 1024, 1) : 0,
            'log_modified'     => file_exists($logPath) ? date('d M Y H:i', filemtime($logPath)) : null,
            'disk_total_gb'    => round(disk_total_space(base_path()) / 1024 / 1024 / 1024, 1),
            'disk_free_gb'     => round(disk_free_space(base_path()) / 1024 / 1024 / 1024, 1),
        ];

        // Aktivitas 7 hari
        $activityLabels = [];
        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $activityLabels[] = $day->translatedFormat('d M');
            $activityData[] = DB::table('activity_log')->whereDate('created_at', $day->toDateString())->count();
        }

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get(['id', 'queue', 'failed_at']);

        return view('system.monitoring', compact('monitoring', 'activityLabels', 'activityData', 'failedJobs'));
    }

    public function maintenance()
    {
        $compiledViews = is_dir(storage_path('framework/views'))
            ? count(glob(storage_path('framework/views/*.php')))
            : 0;

        $logPath = storage_path('logs/laravel.log');

        $maintenance = [
            'down'             => app()->isDownForMaintenance(),
            'config_cached'    => file_exists(base_path('bootstrap/cache/config.php')),
            'routes_cached'    => file_exists(base_path('bootstrap/cache/routes-v7.php')) || file_exists(base_path('bootstrap/cache/routes.php')),
            'compiled_views'   => $compiledViews,
            'cache_driver'     => config('cache.default'),
            'queue_driver'     => config('queue.default'),
            'jobs_pending'     => DB::table('jobs')->count(),
            'jobs_failed'      => DB::table('failed_jobs')->count(),
            'log_size_kb'      => file_exists($logPath) ? round(filesize($logPath) / 1024, 1) : 0,
            'log_modified'     => file_exists($logPath) ? date('d M Y H:i', filemtime($logPath)) : null,
            'last_migration'   => DB::table('migrations')->orderByDesc('id')->value('migration'),
        ];

        $commands = [
            ['command' => 'php artisan optimize:clear', 'desc' => 'Bersihkan cache config, route, view, event'],
            ['command' => 'php artisan cache:clear', 'desc' => 'Hapus isi application cache'],
            ['command' => 'php artisan config:cache', 'desc' => 'Optimasi config untuk produksi'],
            ['command' => 'php artisan route:cache', 'desc' => 'Cache daftar route (produksi)'],
            ['command' => 'php artisan view:clear', 'desc' => 'Hapus view terkompilasi'],
            ['command' => 'php artisan queue:work', 'desc' => 'Jalankan worker queue'],
            ['command' => 'php artisan queue:retry all', 'desc' => 'Ulangi semua job gagal'],
            ['command' => 'php artisan migrate --force', 'desc' => 'Jalankan migrasi di produksi'],
            ['command' => 'php artisan storage:link', 'desc' => 'Buat symlink storage publik'],
            ['command' => 'php artisan down --secret="TOKEN"', 'desc' => 'Aktifkan maintenance mode dengan bypass'],
            ['command' => 'php artisan up', 'desc' => 'Matikan maintenance mode'],
        ];

        return view('system.maintenance', compact('maintenance', 'commands'));
    }

    public function config()
    {
        $mask = function (?string $value): string {
            if (! $value) {
                return '—';
            }
            $len = strlen($value);
            if ($len <= 4) {
                return str_repeat('•', $len);
            }

            return substr($value, 0, 2) . str_repeat('•', max(3, $len - 4)) . substr($value, -2);
        };

        $groups = [
            'Aplikasi' => [
                ['name' => 'Nama Aplikasi', 'value' => config('app.name')],
                ['name' => 'Environment', 'value' => app()->environment(), 'badge' => app()->environment() === 'production' ? 'danger' : 'warning'],
                ['name' => 'Debug Mode', 'value' => config('app.debug') ? 'AKTIF' : 'Nonaktif', 'badge' => config('app.debug') ? 'danger' : 'success'],
                ['name' => 'URL', 'value' => config('app.url')],
                ['name' => 'Timezone', 'value' => config('app.timezone')],
                ['name' => 'Locale', 'value' => config('app.locale') . ' (fallback: ' . config('app.fallback_locale') . ')'],
            ],
            'Database' => [
                ['name' => 'Driver', 'value' => config('database.default')],
                ['name' => 'Host', 'value' => config("database.connections." . config('database.default') . ".host")],
                ['name' => 'Port', 'value' => (string) config("database.connections." . config('database.default') . ".port")],
                ['name' => 'Database', 'value' => config("database.connections." . config('database.default') . ".database")],
                ['name' => 'Username', 'value' => $mask(config("database.connections." . config('database.default') . ".username"))],
                ['name' => 'Password', 'value' => config("database.connections." . config('database.default') . ".password") ? '••••••••' : '(kosong)'],
            ],
            'Layanan' => [
                ['name' => 'Cache', 'value' => config('cache.default')],
                ['name' => 'Session', 'value' => config('session.driver') . ' · lifetime ' . config('session.lifetime') . ' menit'],
                ['name' => 'Queue', 'value' => config('queue.default')],
                ['name' => 'Broadcast', 'value' => config('broadcasting.default', '—')],
                ['name' => 'Filesystem', 'value' => config('filesystems.default')],
            ],
            'Mail' => [
                ['name' => 'Driver', 'value' => config('mail.default')],
                ['name' => 'Host', 'value' => config('mail.mailers.smtp.host', '—')],
                ['name' => 'Port', 'value' => (string) config('mail.mailers.smtp.port', '—')],
                ['name' => 'Pengirim', 'value' => config('mail.from.address', '—') . ' (' . config('mail.from.name', '—') . ')'],
            ],
        ];

        return view('system.config', compact('groups'));
    }

    public function devtools()
    {
        $routes = app('router')->getRoutes();

        $routesByMethod = ['GET' => 0, 'POST' => 0, 'PUT' => 0, 'PATCH' => 0, 'DELETE' => 0, 'OTHER' => 0];
        $prefixCounts = [];

        foreach ($routes as $route) {
            $methods = $route->methods();
            if (in_array('GET', $methods, true) && in_array('HEAD', $methods, true)) {
                $routesByMethod['GET']++;
            } elseif (isset($routesByMethod[$methods[0] ?? 'OTHER'])) {
                $routesByMethod[$methods[0]]++;
            } else {
                $routesByMethod['OTHER']++;
            }

            $segment = explode('/', trim($route->uri(), '/'))[0] ?? '';
            $segment = $segment === '' ? '(root)' : $segment;
            // normalisasi parameter dinamis
            $segment = str_starts_with($segment, '{') ? '{param}' : $segment;
            $prefixCounts[$segment] = ($prefixCounts[$segment] ?? 0) + 1;
        }

        arsort($prefixCounts);
        $topPrefixes = array_slice($prefixCounts, 0, 12, true);

        $extensions = collect(get_loaded_extensions())->sort()->values()->all();

        $commands = [
            ['command' => 'php artisan route:list', 'desc' => 'Lihat seluruh route'],
            ['command' => 'php artisan route:list --name=dashboard', 'desc' => 'Filter route dashboard'],
            ['command' => 'php artisan migrate:status', 'desc' => 'Status migrasi'],
            ['command' => 'php artisan tinker', 'desc' => 'REPL untuk eksplorasi data'],
            ['command' => 'php artisan queue:failed', 'desc' => 'Lihat job gagal'],
            ['command' => 'php artisan db:table users', 'desc' => 'Inspeksi struktur tabel'],
            ['command' => 'php artisan model:show User', 'desc' => 'Info model & relasi'],
            ['command' => 'php artisan about', 'desc' => 'Ringkasan environment'],
        ];

        $recentMigrations = DB::table('migrations')->orderByDesc('id')->limit(10)->get(['migration', 'batch']);

        return view('system.devtools', compact('routesByMethod', 'topPrefixes', 'extensions', 'commands', 'recentMigrations'));
    }
}
