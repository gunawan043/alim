<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\AcademicOperationsExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Dashboard\DashboardAnalyticsService;
use App\Services\Dashboard\DashboardContextService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Aksi lintas dashboard jabatan: ekspor KPI, konfirmasi broadcast,
 * dan penanda baca broadcast.
 */
class DashboardActionController extends Controller
{
    public function __construct(
        private DashboardContextService $context,
        private DashboardAnalyticsService $analytics,
    ) {}

    /**
     * Ekspor ringkasan KPI dashboard sesuai filter aktif.
     */
    public function export(Request $request, string $userId, string $role)
    {
        $user = $request->user();
        abort_unless((string) $userId === (string) $user->id, 403);
        $filters = $this->context->filters($request, $user);
        $data = $this->analytics->build($role, $filters, $user);

        $rows = $this->kpiRows($role, $data);

        return Excel::download(
            new AcademicOperationsExport(
                'Ringkasan Dashboard '.$this->roleLabel($role),
                $filters['period_label'],
                [
                    ['Unit', $filters['school']?->name ?? 'Semua unit'],
                    ['Jenjang', $filters['grade_level']?->name ?? 'Semua jenjang'],
                    ['Diperbarui', $filters['updated_at'] ? Carbon::parse($filters['updated_at'])->format('d/m/Y H:i') : 'Belum tersedia'],
                ],
                ['Indikator', 'Nilai', 'Sumber'],
                $rows,
                $filters['school']?->name,
            ),
            'Dashboard-'.str($role)->slug().'-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function exportPdf(Request $request, string $userId, string $role)
    {
        $user = $request->user();
        abort_unless((string) $userId === (string) $user->id, 403);
        $filters = $this->context->filters($request, $user);
        $data = $this->analytics->build($role, $filters, $user);
        $rows = $this->kpiRows($role, $data);

        return app(DocumentPdfService::class)->fromView(
            'user.laporan.pdf.akademik-operasional',
            [
                'title' => 'Ringkasan Dashboard '.$this->roleLabel($role),
                'subtitle' => $filters['period_label'],
                'summaryRows' => [
                    ['Unit', $filters['school']?->name ?? 'Semua unit'],
                    ['Jenjang', $filters['grade_level']?->name ?? 'Semua jenjang'],
                ],
                'headings' => ['Indikator', 'Nilai', 'Sumber'],
                'rows' => $rows,
                'sourceNote' => 'Sumber: data operasional ALIM. Nilai kosong berarti data belum tersedia.',
                'school' => $filters['school'],
                'printedAt' => now(),
            ],
            'Dashboard-'.str($role)->slug().'-'.now()->format('Ymd-His'),
            'portrait',
        );
    }

    /**
     * Konfirmasi baca broadcast (wajib dibaca).
     */
    public function acknowledgeBroadcast(Request $request, string $userId, string $announcementId)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless((string) $userId === (string) $user->id, 403);

        $ok = $this->context->acknowledge($user, $announcementId);

        if (! $ok) {
            return response()->json(['success' => false, 'message' => 'Broadcast tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Ambil daftar broadcast aktif (dipakai polling ringan / refresh).
     */
    public function broadcasts(Request $request, string $userId)
    {
        $user = $request->user();
        abort_unless((string) $userId === (string) $user->id, 403);
        $filters = $this->context->filters($request, $user);

        return response()->json([
            'items' => $this->context->pendingBroadcasts($user, $filters)->values(),
        ]);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function kpiRows(string $role, array $data): array
    {
        $format = fn ($value) => $value === null ? '—' : (is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value);

        $rows = [];
        $students = $data['students'] ?? [];
        $gtk = $data['gtk'] ?? [];
        $mutations = $data['mutations'] ?? [];
        $rombel = $data['rombel'] ?? [];
        $attendance = $data['attendance'] ?? [];

        $rows[] = ['Santri aktif', $format($students['total'] ?? null), 'students'];
        $rows[] = ['Santri baru', $format($students['new'] ?? null), 'students.entry_date'];
        $rows[] = ['Mutasi masuk (12 bln)', $format($mutations['total_in'] ?? null), 'student_mutations_in'];
        $rows[] = ['Mutasi keluar (12 bln)', $format($mutations['total_out'] ?? null), 'student_mutations_out'];
        $rows[] = ['GTK', $format($gtk['total'] ?? null), 'gtk_employments'];
        $rows[] = ['Rombel', $format($rombel['total'] ?? null), 'study_groups'];
        $rows[] = ['Rombel tanpa wali', $format($rombel['without_homeroom'] ?? null), 'study_groups.homeroom_teacher_id'];
        $rows[] = ['Kehadiran hari ini (%)', $format($attendance['today_rate'] ?? null), 'admin_presensi_harian'];

        return $rows;
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'satuan-pendidikan' => 'Satuan Pendidikan',
            'pimpinan' => 'Pimpinan',
            'asrama' => 'Asrama',
            'tahfidz' => 'Tahfidz',
            'keuangan' => 'Keuangan',
            'personalia' => 'Humas & Personalia',
            'uks' => 'UKS',
            'unit-rumah-tangga' => 'Unit Rumah Tangga',
            'super-admin' => 'Super Admin',
            default => ucwords(str_replace('-', ' ', $role)),
        };
    }
}
