<?php

namespace App\Services\Documents;

use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Identitas kepala sekolah untuk dokumen resmi.
 *
 * Prioritas nama: schools.principal_name → user principal → kosong.
 * Prioritas NUPY: schools.principal_nupy → principal_nip → gtk_employments.nupy.
 * Gelar diambil dari gtk_educations aktif, diurutkan dari jenjang terendah.
 */
class SchoolLeaderService
{
    /**
     * @return array{name: string, display_name: string, nupy: string}
     */
    public function forSchool(?School $school): array
    {
        if (! $school) {
            return ['name' => '', 'display_name' => '', 'nupy' => '', 'is_override' => false];
        }

        $visual = is_array($school->document_visual_settings) ? $school->document_visual_settings : [];
        $override = is_array($visual['leader'] ?? null) ? $visual['leader'] : [];

        if (trim((string) ($override['name'] ?? '')) !== '') {
            return [
                'name' => trim((string) $override['name']),
                'display_name' => trim((string) $override['name']),
                'nupy' => trim((string) ($override['nupy'] ?? '')) ?: $this->autoForSchool($school)['nupy'],
                'is_override' => true,
            ];
        }

        return $this->autoForSchool($school);
    }

    /**
     * Identitas otomatis dari GTK, tanpa override dokumen.
     *
     * @return array{name: string, display_name: string, nupy: string, is_override: bool}
     */
    public function autoForSchool(?School $school): array
    {
        if (! $school) {
            return ['name' => '', 'display_name' => '', 'nupy' => '', 'is_override' => false];
        }

        $user = $school->principalUser
            ?? ($school->principal_user_id ? User::find($school->principal_user_id) : null);
        $employment = $user ? GtkEmployment::where('user_id', $user->id)->first() : null;

        $rawName = (string) ($school->principal_name ?: $user?->name ?: '');
        $nupy = (string) ($school->principal_nupy ?: $school->principal_nip ?: $employment?->nupy ?: '');

        return $this->build($rawName, $nupy, $user?->id);
    }

    /**
     * Untuk penandatangan SK yang bukan selalu kepala sekolah.
     *
     * @return array{name: string, display_name: string, nupy: string}
     */
    public function forUser(?User $user, ?School $school = null, ?string $fallbackName = null, ?string $fallbackNupy = null): array
    {
        $employment = $user ? GtkEmployment::where('user_id', $user->id)->first() : null;
        $rawName = (string) ($fallbackName ?: $user?->name ?: '');
        $nupy = (string) ($fallbackNupy ?: $employment?->nupy ?: '');

        return $this->build($rawName, $nupy, $user?->id);
    }

    /**
     * @return array{name: string, display_name: string, nupy: string}
     */
    private function build(string $rawName, string $nupy, ?string $userId): array
    {
        $name = $this->capitalize($rawName);
        $degrees = $this->degrees($userId);

        if ($name === '') {
            return ['name' => '', 'display_name' => '', 'nupy' => $nupy, 'is_override' => false];
        }

        // Jangan ulangi gelar yang sudah tertulis di nama.
        $degrees = array_values(array_filter($degrees, fn ($degree) => ! str_contains($name, $degree)));

        $display = $name.($degrees !== [] ? ', '.implode(', ', $degrees) : '');

        return [
            'name' => $name,
            'display_name' => $display,
            'nupy' => $nupy,
            'is_override' => false,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function degrees(?string $userId): array
    {
        if (! $userId) {
            return [];
        }

        $educations = GtkEducation::query()
            ->where('user_id', $userId)
            ->where('is_aktif', 1)
            ->orderBy('tahun_lulus')
            ->get();

        $degrees = [];
        $prefixes = [];

        foreach ($educations as $education) {
            $level = strtoupper(trim((string) $education->jenjang_pendidikan));
            $program = Str::lower((string) $education->jurusan);
            $degree = $this->degreeFor($level, $program);

            if ($degree === null) {
                continue;
            }

            if (str_ends_with($degree, '.')) {
                $prefixes[] = $degree;
            } else {
                $degrees[] = $degree;
            }
        }

        $degrees = array_values(array_unique($degrees));
        $prefixes = array_values(array_unique($prefixes));

        return array_merge($prefixes, $degrees);
    }

    private function degreeFor(string $level, string $program): ?string
    {
        $isEducation = str_contains($program, 'pendidikan') || str_contains($program, 'keguruan');

        return match (true) {
            $level === 'S3' => 'Dr.',
            $level === 'S2' && $isEducation => 'M.Pd',
            $level === 'S2' && str_contains($program, 'manajemen') => 'M.M',
            $level === 'S2' && str_contains($program, 'hukum') => 'M.H',
            $level === 'S2' && (str_contains($program, 'komputer') || str_contains($program, 'informatika')) => 'M.Kom',
            $level === 'S2' => 'M.Si',
            $level === 'S1' && $isEducation => 'S.Pd',
            $level === 'S1' && (str_contains($program, 'komputer') || str_contains($program, 'informatika') || str_contains($program, 'sistem informasi')) => 'S.Kom',
            $level === 'S1' && str_contains($program, 'akuntansi') => 'S.Ak',
            $level === 'S1' && (str_contains($program, 'manajemen') || str_contains($program, 'ekonomi')) => 'S.E',
            $level === 'S1' && str_contains($program, 'hukum') => 'S.H',
            default => null,
        };
    }

    private function capitalize(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        return Str::title(Str::lower($name));
    }
}
