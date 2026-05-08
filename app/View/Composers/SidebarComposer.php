<?php

namespace App\View\Composers;

use App\Models\SidebarMenu;
use App\Models\School;
use App\Models\Dormitory;
use App\Models\StudyGroup;
use App\Models\GtkEmployment;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;

class SidebarComposer
{
    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('sidebarMenu', collect());
            return;
        }

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $isSuperAdmin = $user->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $dbMenus = SidebarMenu::topLevel()
                ->with([
                    'children' => fn ($q) => $q
                        ->with(['children' => fn ($q2) => $q2->orderBy('order')])
                        ->orderBy('order'),
                ])
                ->orderBy('order')
                ->get();

            $staticMenus = $this->superAdminStaticMenus();

            // Inject per-school children into "Satuan Pendidikan" static menu
            $staticMenus = $this->injectSchoolChildren($staticMenus, $isSuperAdmin);

            $staticNames = $staticMenus->pluck('name')->toArray();
            $filteredDb = $dbMenus->reject(fn ($m) => in_array($m->name, $staticNames));
            $allMenus = $staticMenus->merge($filteredDb)->values();
        } else {
            $allMenus = SidebarMenu::topLevel()
                ->accessibleBy($roleIds)
                ->with(['children' => fn ($q) => $q
                    ->accessibleBy($roleIds)
                    ->with(['children' => fn ($q2) => $q2
                        ->accessibleBy($roleIds)
                        ->with(['children' => fn ($q3) => $q3
                            ->accessibleBy($roleIds)
                            ->orderBy('order')])
                        ->orderBy('order')])
                    ->orderBy('order')])
                ->orderBy('order')
                ->get();

            // Inject per-school children into "Satuan Pendidikan" from DB
            $allMenus = $this->injectSchoolChildren($allMenus, $isSuperAdmin, $user);
        }

        $allMenus->each(function ($menu) use ($isSuperAdmin) {
            $menu->html_id = 'menu_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($menu->name ?? $menu->id));

            if ($isSuperAdmin) {
                $menu->route_with_role = $menu->route;
                if ($menu->children) {
                    foreach ($menu->children as $child) {
                        $child->route_with_role = $child->route ?? $child->name;
                        // Grandchildren
                        if ($child->children) {
                            foreach ($child->children as $gc) {
                                $gc->route_with_role = $gc->route ?? $gc->name;
                            }
                        }
                    }
                }
            } else {
                // All DB routes now use user.* prefix (after migration)
                // buildMenuUrl detects 'user.' prefix and auto-adds userId param
                if ($menu->route) {
                    $menu->route_with_role = $menu->route;
                }
                if ($menu->children) {
                    foreach ($menu->children as $child) {
                        if ($child->route) {
                            $child->route_with_role = $child->route;
                        }
                        // Grandchildren
                        if ($child->children) {
                            foreach ($child->children as $gc) {
                                if ($gc->route) {
                                    $gc->route_with_role = $gc->route;
                                }
                            }
                        }
                    }
                }
            }
        });

        // Study groups for sidebar rombel listing
        $studyGroups = $this->getStudyGroupsForUser($user);

        // First admin book for GTK quick-link
        $firstBookId = null;
        if (!$isSuperAdmin && $user->hasRole('GTK')) {
            $schoolId = $this->getUserSchoolId($user);
            $firstBook = \App\Models\TeacherAdminBook::withoutGlobalScope('school_context')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('teacher_id', $user->id)
                ->where('is_active', true)
                ->first();
            $firstBookId = $firstBook?->id;
        }

        // ── Super Admin: school switcher context ────────────────────────
        $saSchoolId = null;
        $saSchoolName = null;
        $saSchoolScoped = false;
        $asramaContext = null;
        $currentAsramaModule = null;

        if ($isSuperAdmin) {
            $saSchoolId = session('sa_school_id');
            $saSchoolName = session('sa_school_name');
            $saSchoolScoped = session('sa_school_scoped', false);

            // Load asrama context if asramaUuid is in route
            $routeParams = request()->route()?->parameters() ?? [];
            $asramaUuid = $routeParams['asramaUuid'] ?? null;
            if ($asramaUuid) {
                $asramaContext = Dormitory::with('school')->find($asramaUuid);

                // Build readable module name from route
                $routeName = request()->route()?->getName() ?? '';
                $moduleMap = [
                    'user.asrama.residents.' => 'Penghuni',
                    'user.asrama.attendance.' => 'Absensi',
                    'user.asrama.permits.' => 'Perizinan',
                    'user.asrama.violations.' => 'Pelanggaran',
                    'user.asrama.visits.' => 'Kunjungan',
                    'user.asrama.room-moves.' => 'Mutasi Kamar',
                    'user.asrama.inventories.' => 'Inventaris',
                    'user.asrama.posts.' => 'Informasi',
                    'user.asrama.broadcasts.' => 'Broadcast',
                    'user.asrama.activities.' => 'Log Kegiatan',
                    'user.asrama.templates.' => 'Template Kegiatan',
                ];
                foreach ($moduleMap as $prefix => $label) {
                    if (str_starts_with($routeName, $prefix)) {
                        $currentAsramaModule = $label;
                        break;
                    }
                }
            }
        }

        // ── Schools list for Super Admin school switcher ─────────────────
        $schoolsForSwitcher = null;
        if ($isSuperAdmin) {
            $schoolsForSwitcher = School::active()
                ->orderBy('school_level')
                ->orderBy('name')
                ->get(['id', 'name', 'school_level', 'school_gender']);
        }

        $view->with('sidebarMenu', $allMenus)
             ->with('isSidebarSuperAdmin', $isSuperAdmin)
             ->with('sidebarStudyGroups', $studyGroups)
             ->with('firstBookId', $firstBookId)
             ->with('saSchoolId', $saSchoolId)
             ->with('saSchoolName', $saSchoolName)
             ->with('saSchoolScoped', $saSchoolScoped)
             ->with('schools', $schoolsForSwitcher)
             ->with('asramaContext', $asramaContext)
             ->with('currentAsramaModule', $currentAsramaModule);
    }

    private function getStudyGroupsForUser($user): \Illuminate\Support\Collection
    {
        $schoolId = $this->getUserSchoolId($user);
        if (!$schoolId) {
            return collect();
        }

        $activeSemester = \App\Models\AcademicYear::where('is_active', true)->value('semester');

        return StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('academicYear', fn($q) => $q->where('semester', $activeSemester))
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Inject per-school children into "Satuan Pendidikan" menu.
     */
    private function injectSchoolChildren($menus, bool $isSuperAdmin, $user = null): \Illuminate\Support\Collection
    {
        // Find "Satuan Pendidikan" menu item
        $idx = $menus->search(fn ($m) => Str::slug($m->name ?? '') === 'satuan-pendidikan');
        if ($idx === false) return $menus;

        // Get schools to show
        if ($isSuperAdmin) {
            $schools = School::active()->with('workUnit')->orderBy('name')->get();
        } else {
            // Regular user: only their school
            $schoolId = $this->getUserSchoolId($user);
            if (!$schoolId) return $menus;
            $schools = School::where('id', $schoolId)->with('workUnit')->get();
        }

        if ($schools->isEmpty()) return $menus;

        // Build school children
        $schoolChildren = $schools->map(function ($school) {
            $child = new SidebarMenu([
                'name' => $school->name,
                'icon' => 'ri-government-line',
                'route' => 'user.schools.satuan-kerja.show',
            ]);
            $child->id = 'school-' . Str::slug($school->id);
            $child->html_id = 'school_' . Str::slug($school->id);
            $child->route_with_role = 'user.schools.satuan-kerja.show';
            $child->route_params = [
                'workUnitId' => $school->work_unit_id,
                'schoolId' => $school->id,
            ];
            return $child;
        });

        $menu = $menus->get($idx);
        $menu->children = $schoolChildren;

        return $menus;
    }

    /**
     * Get the school ID that a user is associated with.
     */
    private function getUserSchoolId($user): ?string
    {
        // Try GtkEmployment first (gtk_employments table)
        $employment = $user->employment ?? null;
        if ($employment && $employment->school_id) {
            return $employment->school_id;
        }

        // Try GtkWorkUnits (primary work unit → work_unit_id → schools)
        $primaryUnit = $user->primaryWorkUnit;
        if ($primaryUnit && $primaryUnit->work_unit_id) {
            $school = School::where('work_unit_id', $primaryUnit->work_unit_id)
                ->active()->first();
            if ($school) return $school->id;
        }

        return null;
    }

    private function superAdminStaticMenus(): \Illuminate\Support\Collection
    {
        return collect([
            $this->makeSection('Umum', 'bx bx-menu', [
                ['name' => 'Notifikasi',            'route' => 'user.notifications.index',       'icon' => 'bx bx-bell'],
            ]),
            $this->makeSection('Super Admin', 'ri-shield-star-line', [
                ['name' => 'Manajemen User',       'route' => 'user.sa.users.index',               'icon' => 'ri-user-settings-line'],
                ['name' => 'Roles & Permissions',   'route' => 'user.sa.roles.index',               'icon' => 'ri-shield-check-line'],
                ['name' => 'Permissions',           'route' => 'user.sa.permissions.index',          'icon' => 'ri-key-line'],
                ['name' => 'Audit Log',             'route' => 'user.sa.audit-logs.index',          'icon' => 'ri-file-history-line'],
                ['name' => 'Token & Sesi',          'route' => 'user.sa.tokens.index',              'icon' => 'ri-key-2-line'],
                ['name' => 'Failed Jobs',           'route' => 'user.sa.failed-jobs.index',          'icon' => 'ri-error-warning-line'],
                ['name' => 'Notifikasi Universal',  'route' => 'user.sa.notifications.index',        'icon' => 'ri-notification-3-line'],
                ['name' => 'Kelola Menu Sidebar',   'route' => 'user.sa.sidebar-menus.index',       'icon' => 'ri-menu-add-line'],
                ['name' => 'Password Reset Logs',    'route' => 'user.sa.password-reset-logs.index', 'icon' => 'ri-lock-password-line'],
                ['name' => 'Pengaturan Sistem',      'route' => 'user.sa.system-settings.index',      'icon' => 'ri-settings-3-line'],
            ]),
            $this->makeSection('Data GTK', 'bx bx-group', [
                ['name' => 'Semua GTK',              'route' => 'user.gtk.index',                  'icon' => 'ri-list-check'],
                ['name' => 'Import / Export',        'route' => 'user.gtk.import',                  'icon' => 'ri-upload-cloud-line'],
            ]),
            $this->makeSection('Master Data', 'bx bx-slider', [
                ['name' => 'Jenis GTK',              'route' => 'user.master-data.jenis-gtk.index',   'icon' => 'ri-stack-line'],
                ['name' => 'Jabatan',               'route' => 'user.master-data.jabatan.index',     'icon' => 'ri-briefcase-line'],
                ['name' => 'Satuan Kerja',           'route' => 'user.master-data.satuan-kerja.index','icon' => 'ri-government-line'],
            ]),
            $this->makeSection('Recruitment', 'ri-team-line', [
                ['name' => 'Lowongan',               'route' => 'user.ats.jobs.index',            'icon' => 'ri-briefcase-2-line'],
                ['name' => 'Kandidat',               'route' => 'user.ats.candidates.index',      'icon' => 'ri-user-star-line'],
                ['name' => 'Lamaran',                'route' => 'user.ats.applications.index',   'icon' => 'ri-file-list-3-line'],
                ['name' => 'Jadwal Interview',       'route' => 'user.ats.interviews.index',     'icon' => 'ri-calendar-event-line'],
                ['name' => 'Reports',                'route' => 'user.ats.reports.index',        'icon' => 'ri-bar-chart-box-line'],
                ['name' => 'Settings',               'route' => 'user.ats.settings.index',       'icon' => 'ri-settings-line'],
            ]),
            $this->makeSection('Jenjang Karir', 'bx bx-rocket', [
                ['name' => 'Career Path',            'route' => 'user.jenjang-karir.career-path.index', 'icon' => 'ri-road-map-line'],
                ['name' => 'Mutasi & Rotasi',       'route' => 'user.jenjang-karir.mutasi.index',     'icon' => 'ri-arrow-left-right-line'],
                ['name' => 'Promosi & Demosi',       'route' => 'user.jenjang-karir.promosi.index',   'icon' => 'ri-trending-up-line'],
                ['name' => 'Talent Pool',            'route' => 'user.jenjang-karir.talent.index',     'icon' => 'ri-user-follow-line'],
                ['name' => 'Succession Plan',        'route' => 'user.jenjang-karir.succession.index','icon' => 'ri-team-line'],
            ]),
            $this->makeSection('GTK Requests & Approvals', 'ri-git-pull-request-line', [
                ['name' => 'Daftar Request GTK',     'route' => 'user.gtk-requests.index',        'icon' => 'ri-list-ordered'],
                ['name' => 'Pengadaan GTK',          'route' => 'user.gtk-requests.create',       'query' => '?type=procurement',    'icon' => 'ri-file-add-line'],
                ['name' => 'Pengangkatan Percobaan','route' => 'user.gtk-requests.create',       'query' => '?type=trial',         'icon' => 'ri-user-add-line'],
                ['name' => 'Kenaikan Status GTK',   'route' => 'user.gtk-requests.create',       'query' => '?type=status_increase','icon' => 'ri-arrow-up-line'],
                ['name' => 'Approval',              'route' => 'user.approvals.index',           'icon' => 'ri-checkbox-multiple-line'],
            ]),
            $this->makeSection('Satuan Pendidikan', 'ri-school-line', [
                // Children injected dynamically via injectSchoolChildren()
            ]),
            $this->makeSection('Akademik', 'ri-graduation-cap-line', [
                ['name' => 'Tahun Ajaran',          'route' => 'user.academic-years.index',   'icon' => 'ri-calendar-event-line'],
                ['name' => 'Tingkat Kelas',         'route' => 'user.grade-levels.index',     'icon' => 'ri-stack-line'],
                ['name' => 'Rombongan Belajar',      'route' => 'user.study-groups.index',     'icon' => 'ri-group-line'],
                ['name' => 'Data Santri',            'route' => 'user.students.index',         'icon' => 'ri-user-heart-line'],
            ]),
        ]);
    }

    private function makeSection(string $name, string $icon, array $children): SidebarMenu
    {
        $section = new SidebarMenu(['name' => $name, 'icon' => $icon, 'is_group_header' => false]);
        $section->id = 'section-' . Str::slug($name);
        $section->html_id = 'menu_' . Str::slug($name);
        $section->children = collect($children)->map(fn ($c) => $this->makeChild($c['name'], $c['route'], $c['icon'], $c['query'] ?? null));
        return $section;
    }

    private function makeChild(string $name, string $route, string $icon, ?string $query = null): SidebarMenu
    {
        $child = new SidebarMenu(['name' => $name, 'route' => $route, 'icon' => $icon]);
        $child->id = 'child-' . Str::slug($name);
        $child->html_id = 'sub_' . Str::slug($name);
        $child->route_with_role = $route;
        $child->route_query = $query;
        return $child;
    }
}
