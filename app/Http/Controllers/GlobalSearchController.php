<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Pencarian global (topbar): menu/fitur + data santri (scoped satuan pendidikan).
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

        // ── Menu/fitur ──────────────────────────────────────────────
        $menus = collect(config('global_search', []))
            ->filter(function (array $item) use ($needle) {
                $haystack = mb_strtolower(($item['label'] ?? '').' '.($item['keywords'] ?? '').' '.($item['group'] ?? ''));

                return str_contains($haystack, $needle);
            })
            ->take(8)
            ->map(function (array $item) use ($userId) {
                $url = null;

                if (Route::has($item['route'])) {
                    try {
                        $url = route($item['route'], array_merge(['userId' => $userId], $item['params'] ?? []));
                    } catch (\Throwable) {
                        // Route butuh parameter lain → lewati, pencarian tetap jalan.
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
}
