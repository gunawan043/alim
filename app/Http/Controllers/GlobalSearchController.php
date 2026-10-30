<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\MenuSearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Pencarian global (topbar): menu/fitur + data santri (scoped satuan pendidikan).
 *
 * Daftar menu diambil dari sidebar yang dirender untuk user tersebut, sehingga
 * hasil pencarian hanya berisi menu yang memang boleh diakses menurut role,
 * jabatan, dan tugas tambahannya.
 */
class GlobalSearchController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = trim((string) $request->input('q', ''));
        $schoolId = $request->attributes->get('schoolContextId');

        if (mb_strlen($query) < 2) {
            return response()->json(['query' => $query, 'menus' => [], 'students' => []]);
        }

        $needle = mb_strtolower($query);
        $tokens = array_values(array_filter(preg_split('/\s+/u', $needle) ?: []));

        $index = MenuSearchIndex::forUser($request->user());

        // Fallback: kalau sidebar tidak bisa dirender, pakai registry statis.
        if ($index === []) {
            $index = $this->fallbackMenus($userId);
        }

        $menus = collect($index)
            ->map(function (array $item) {
                $item['haystack'] = mb_strtolower(($item['label'] ?? '').' '.($item['group'] ?? '').' '.($item['url'] ?? ''));

                return $item;
            })
            ->filter(function (array $item) use ($tokens) {
                foreach ($tokens as $token) {
                    if (! str_contains($item['haystack'], $token)) {
                        return false;
                    }
                }

                return true;
            })
            ->sortBy(fn (array $item) => str_starts_with(mb_strtolower($item['label']), $needle) ? 0 : 1)
            ->take(10)
            ->map(fn (array $item) => [
                'label' => $item['label'],
                'group' => $item['group'] ?? 'Menu',
                'meta' => $item['group'] ?? 'Menu',
                'url' => $item['url'],
            ])
            ->values();

        // ── Data santri (scoped) ────────────────────────────────────
        $students = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('nisn', 'like', '%'.$query.'%')
                    ->orWhere('nis', 'like', '%'.$query.'%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'nisn', 'status'])
            ->map(fn (Student $student) => [
                'label' => $student->name,
                'meta' => 'NISN '.($student->nisn ?: '-').' · '.($student->status ?? '-'),
                'url' => route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]),
            ])
            ->values();

        return response()->json([
            'query' => $query,
            'menus' => $menus,
            'students' => $students,
        ]);
    }

    /**
     * Registry statis (config/global_search.php) — dipakai hanya bila sidebar
     * gagal dirender. Tidak memuat aturan akses, jadi hanya darurat.
     *
     * @return list<array{label: string, group: string, url: string}>
     */
    private function fallbackMenus(string $userId): array
    {
        return collect(config('global_search', []))
            ->map(function (array $item) use ($userId) {
                $url = null;

                if (Route::has($item['route'])) {
                    try {
                        $url = route($item['route'], array_merge(['userId' => $userId], $item['params'] ?? []));
                    } catch (\Throwable) {
                        $url = null;
                    }
                }

                return [
                    'label' => $item['label'],
                    'group' => $item['group'] ?? 'Menu',
                    'url' => $url,
                ];
            })
            ->filter(fn ($item) => $item['url'] !== null)
            ->values()
            ->all();
    }
}
