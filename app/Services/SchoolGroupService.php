<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Collection;

/**
 * SchoolGroupService
 *
 * Groups schools by level label for sidebar navigation.
 * Groups: SD IT, SMP IT, SMA IT / MA, PPS
 * Each group has 'putra' and 'putri' children.
 *
 * Output shape:
 * [
 *   'SD IT' => [
 *     'putra' => Collection<School>,
 *     'putri' => Collection<School>,
 *   ],
 *   ...
 * ]
 */
class SchoolGroupService
{
    /**
     * Map school_level → sidebar label
     */
    private const LEVEL_LABELS = [
        'sd' => 'SD IT',
        'smp' => 'SMP IT',
        'sma' => 'SMA IT / MA',
        'smk' => 'SMA IT / MA',
    ];

    /**
     * Map school_level + school_gender → route prefix
     */
    private const GENDER_ICONS = [
        'putra' => 'ri-men-line',     // laki-laki
        'putri' => 'ri-women-line',    // perempuan
    ];

    private const GENDER_LABELS = [
        'putra' => 'Putra',
        'putri' => 'Putri',
    ];

    /**
     * Build the full school-grouped structure.
     * Only includes schools that belong to the given work units.
     *
     * @param  Collection|array  $schools  Active schools to group
     */
    public function build($schools = null): array
    {
        $schools = $schools ?? School::active()->get();

        $groups = [];

        // Group schools by level + gender
        foreach ($schools as $school) {
            $level = $school->school_level ?? 'sd';
            $gender = $school->school_gender ?? 'putra';
            $label = self::LEVEL_LABELS[$level] ?? strtoupper($level);

            if (! isset($groups[$label])) {
                $groups[$label] = [
                    'putra' => [],
                    'putri' => [],
                ];
            }

            $groups[$label][$gender][] = [
                'id' => $school->id,
                'name' => $school->name,
                'npsn' => $school->npsn,
                'address' => $school->address,
                'city_name' => $school->city?->name,
                'status' => $school->school_status,
                'logo_url' => $school->logo_url,
                'principal_name' => $school->principalUser?->name ?? $school->principal_name,
                'level' => $level,
                'gender' => $gender,
                'gender_label' => self::GENDER_LABELS[$gender],
                'gender_icon' => self::GENDER_ICONS[$gender],
                'school_level' => $school->school_level,
                'work_unit_id' => $school->work_unit_id,
                'route' => $this->buildSchoolRoute($school),
            ];
        }

        return $groups;
    }

    /**
     * Build child menu items for a specific school group (e.g. "SD IT > Putra").
     *
     * @param  string  $schoolId  UUID of the school
     * @param  string  $workUnitId  UUID of the school's Work Unit (for scoped route)
     * @param  string  $label  Group label (e.g. "SD IT")
     * @param  string  $gender  'putra' | 'putri'
     */
    public function buildChildren(string $schoolId, string $workUnitId, string $label, string $gender): array
    {
        return [
            [
                'name' => 'Info Sekolah',
                'route' => 'role.schools.satuan-kerja.show',
                'params' => ['roleId' => '__roleId__', 'workUnitId' => $workUnitId, 'schoolId' => $schoolId],
                'icon' => 'ri-government-line',
                'permission' => 'school_view',
            ],
            [
                'name' => 'Guru',
                'route' => 'role.gtk.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-user-settings-line',
                'permission' => 'gtk_view',
            ],
            [
                'name' => 'Tingkat Kelas',
                'route' => 'role.grade-levels.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-stack-line',
                'permission' => 'grade_level_view',
            ],
            [
                'name' => 'Rombongan Belajar',
                'route' => 'role.study-groups.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-group-line',
                'permission' => 'study_group_view',
            ],
            [
                'name' => 'Data Santri',
                'route' => 'role.students.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-user-heart-line',
                'permission' => 'student_view',
            ],
            [
                'name' => 'Mutasi Keluar',
                'route' => 'role.mutations-out.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-logout-box-line',
                'permission' => 'student_mutation_view',
            ],
            [
                'name' => 'Mutasi Masuk',
                'route' => 'role.mutations-in.index',
                'params' => ['roleId' => '__roleId__', 'school_id' => $schoolId],
                'icon' => 'ri-login-box-line',
                'permission' => 'student_mutation_view',
            ],
        ];
    }

    /**
     * Get the school detail route for a school.
     */
    public function buildSchoolRoute(School $school): string
    {
        return 'schools.show';
    }

    /**
     * Get the icon class for a level label.
     */
    public static function levelIcon(string $label): string
    {
        return match ($label) {
            'SD IT' => 'ri-football-line',
            'SMP IT' => 'ri-book-open-line',
            'SMA IT / MA' => 'ri-bachelor-line',
            'PPS' => 'ri-government-line',
            default => 'ri-government-line',
        };
    }

    /**
     * Get level label from school_level value.
     */
    public static function levelLabel(string $level): string
    {
        return self::LEVEL_LABELS[$level] ?? strtoupper($level);
    }

    /**
     * Get the school that a user is currently associated with via gtk_employments.
     * Returns null if user has no school assignment.
     */
    public static function getUserSchool(\App\Models\User $user): ?School
    {
        // Try gtk_employments first (most specific)
        $employment = $user->employment;
        if ($employment && $employment->school_id && $employment->school) {
            return $employment->school;
        }

        // Try gtkWorkUnits → workUnit → schools
        $workUnitId = $user->primaryWorkUnit?->work_unit_id;
        if ($workUnitId) {
            $school = School::where('work_unit_id', $workUnitId)->active()->first();
            if ($school) {
                return $school;
            }
        }

        return null;
    }

    /**
     * Check if user can view global (cross-school) data.
     */
    public static function userCanGlobalView(\App\Models\User $user): bool
    {
        return canUserPermission($user, 'view_global_school_data');
    }
}
