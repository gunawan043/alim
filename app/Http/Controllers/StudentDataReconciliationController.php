<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rekonsiliasi data santri.
 *
 * Menampilkan data yang berpotensi tidak valid: jalur masuk tidak tercatat,
 * santri aktif tanpa rombel, santri keluar yang rombelnya masih aktif,
 * riwayat ganda, dan identitas (NIK/NISN) yang duplikat.
 */
class StudentDataReconciliationController extends Controller
{
    private const PREVIEW_LIMIT = 15;

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $issues = collect($this->definitions($schoolId))
            ->map(function (array $issue) {
                $count = $issue['query']()->count();

                return [
                    'key' => $issue['key'],
                    'title' => $issue['title'],
                    'description' => $issue['description'],
                    'severity' => $issue['severity'],
                    'count' => $count,
                    'rows' => $count === 0
                        ? collect()
                        : $issue['query']()->with('school:id,name')->orderBy('name')->limit(self::PREVIEW_LIMIT)->get(),
                ];
            })
            ->all();

        return view('students.reconciliation', [
            'userId' => $userId,
            'issues' => $issues,
            'duplicateNik' => $this->duplicates($schoolId, 'nik'),
            'duplicateNisn' => $this->duplicates($schoolId, 'nisn'),
            'totalIssues' => collect($issues)->sum('count'),
        ]);
    }

    /**
     * @return array<int, array{key: string, title: string, description: string, severity: string, query: callable}>
     */
    private function definitions(?string $schoolId): array
    {
        return [
            [
                'key' => 'no_entry_source',
                'title' => 'Jalur masuk belum tercatat',
                'description' => 'Santri tanpa sumber masuk (manual/import/mutasi/wali) sehingga asal-usulnya tidak dapat ditelusuri.',
                'severity' => 'warning',
                'query' => fn () => $this->base($schoolId)->whereNull('entry_source'),
            ],
            [
                'key' => 'active_without_rombel',
                'title' => 'Aktif tanpa rombel',
                'description' => 'Status santri aktif tetapi tidak punya riwayat rombel aktif — tidak akan muncul di daftar kelas, presensi, dan rapor.',
                'severity' => 'danger',
                'query' => fn () => $this->base($schoolId)
                    ->whereIn('status', ['active', 'transfer_in'])
                    ->whereDoesntHave('classHistories', fn ($q) => $q->where('is_active', true)),
            ],
            [
                'key' => 'exited_with_rombel',
                'title' => 'Sudah keluar tapi rombel masih aktif',
                'description' => 'Santri berstatus lulus/mutasi keluar/drop out tetapi masih tercatat di rombel aktif — kuota kelas dan rekap menjadi tidak valid.',
                'severity' => 'danger',
                'query' => fn () => $this->base($schoolId)
                    ->whereIn('status', ['graduate', 'transfer_out', 'dropped'])
                    ->whereHas('classHistories', fn ($q) => $q->where('is_active', true)),
            ],
            [
                'key' => 'exit_flag_on_active',
                'title' => 'Jejak keluar pada santri aktif',
                'description' => 'Ada alasan/tanggal keluar tetapi status santri masih aktif.',
                'severity' => 'warning',
                'query' => fn () => $this->base($schoolId)
                    ->whereIn('status', ['active', 'transfer_in'])
                    ->where(fn ($q) => $q->whereNotNull('exit_reason')->orWhereNotNull('exit_date')),
            ],
            [
                'key' => 'without_history',
                'title' => 'Tanpa riwayat rombel sama sekali',
                'description' => 'Santri belum pernah ditempatkan di rombel mana pun.',
                'severity' => 'info',
                'query' => fn () => $this->base($schoolId)->whereDoesntHave('classHistories'),
            ],
            [
                'key' => 'double_active_rombel',
                'title' => 'Rombel aktif ganda',
                'description' => 'Satu santri punya lebih dari satu riwayat rombel aktif pada saat yang sama.',
                'severity' => 'danger',
                'query' => fn () => $this->base($schoolId)->whereIn(
                    'id',
                    DB::table('student_class_histories')
                        ->select('student_id')
                        ->where('is_active', true)
                        ->groupBy('student_id')
                        ->havingRaw('COUNT(*) > 1')
                ),
            ],
            [
                'key' => 'archived_with_rombel',
                'title' => 'Data terarsip masih di rombel',
                'description' => 'Santri yang diarsipkan (dihapus dari daftar) masih memiliki riwayat rombel aktif.',
                'severity' => 'warning',
                'query' => fn () => $this->base($schoolId)
                    ->onlyTrashed()
                    ->whereHas('classHistories', fn ($q) => $q->where('is_active', true)),
            ],
        ];
    }

    private function base(?string $schoolId): Builder
    {
        return Student::withoutGlobalScope('school_context')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
    }

    /**
     * @return array<int, array{value: string, total: int, students: array<int, string>}>
     */
    private function duplicates(?string $schoolId, string $column): array
    {
        $groups = $this->base($schoolId)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return $groups->map(function ($group) use ($schoolId, $column) {
            $students = $this->base($schoolId)
                ->where($column, $group->{$column})
                ->orderBy('name')
                ->limit(5)
                ->pluck('name')
                ->all();

            return [
                'value' => (string) $group->{$column},
                'total' => (int) $group->total,
                'students' => $students,
            ];
        })->all();
    }
}
