<?php

namespace App\Services\Ai;

use App\Models\AcademicYear;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Membangun konteks AI dari user + request.
 *
 * Semua akses data harus melewati konteks ini: sekolah, izin, kelas yang
 * diampu, dan cakupan luas untuk Pimpinan/Kepala Satuan.
 */
class AiContextService
{
    public const BROAD_ROLES = [
        'Pimpinan',
        'Satuan Pendidikan',
        'Kepala Sekolah',
        'Kepala Satuan',
        'Mudir',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Request $request): array
    {
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : [];
        $isSuper = $user->isSystemAdmin()
            || $user->isSuperAdmin()
            || canPermission('super-admin-only');

        $isBroad = $isSuper || collect($roles)->intersect(self::BROAD_ROLES)->isNotEmpty();

        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $assignments = TeachingAssignment::query()
            ->with(['studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,name', 'subject:id,name'])
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->get();

        $classes = $assignments->pluck('studyGroup')->filter()->unique('id')->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'grade_level' => $group->gradeLevel?->name,
        ])->values()->all();

        $subjects = $assignments->pluck('subject')->filter()->unique('id')->pluck('name')->values()->all();

        return [
            'school_id' => $schoolId,
            'global' => (bool) $request->attributes->get('isGlobalView', false),
            'roles' => $roles,
            'is_broad' => $isBroad,
            'is_super' => $isSuper,
            'user_id' => $user->id,
            'my_study_group_ids' => $assignments->pluck('study_group_id')->filter()->unique()->values()->all(),
            'my_teaching' => [
                'academic_year' => $activeYear?->name,
                'classes' => $classes,
                'subjects' => $subjects,
            ],
            'can_view_students' => $isSuper
                || $isBroad
                || canPermission('student_view')
                || canPermission('student-all-access'),
            'can_view_gtk' => $isSuper
                || $isBroad
                || canPermission('gtk_view')
                || canPermission('gtk_detail_view')
                || canPermission('gtk-view-all')
                || canPermission('gtk-view-school'),
            'can_view_gtk_detail' => $isSuper
                || $isBroad
                || canPermission('gtk_detail_view')
                || canPermission('gtk-view-all'),
            'can_view_health' => $isSuper || $isBroad || canPermission('health_view') || canPermission('uks_patient_view'),
            'blocked_domains' => [
                'users', 'roles', 'permissions', 'system_settings',
                'ai_providers', 'api_keys', 'integrations', 'audit_logs',
            ],
        ];
    }

    /**
     * Label cakupan data untuk UI.
     *
     * @param  array<string, mixed>  $context
     */
    public function scopeLabel(array $context): string
    {
        if (($context['is_super'] ?? false) && empty($context['school_id'])) {
            return 'Semua sekolah (Super Admin)';
        }

        if (! empty($context['school_id'])) {
            return 'Sekolah aktif';
        }

        if ($context['is_broad'] ?? false) {
            return 'Lintas sekolah (Pimpinan)';
        }

        return 'Kelas & data yang diizinkan';
    }
}
