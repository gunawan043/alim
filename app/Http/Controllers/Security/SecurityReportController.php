<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityGuestLog;
use App\Models\Security\SecurityIncident;
use App\Models\Security\SecurityPatrolSchedule;
use App\Models\Security\SecurityVehicleLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SecurityReportController extends Controller
{
    public function index(Request $request, string $userId)
    {
        [$from, $to] = $this->range($request);

        $guestTotal = SecurityGuestLog::query()
            ->whereBetween(DB::raw('DATE(check_in_at)'), [$from->toDateString(), $to->toDateString()])
            ->count();

        $vehicleTotal = SecurityVehicleLog::query()
            ->whereBetween(DB::raw('DATE(check_in_at)'), [$from->toDateString(), $to->toDateString()])
            ->count();

        $incidentTotal = SecurityIncident::query()
            ->whereBetween(DB::raw('DATE(occurred_at)'), [$from->toDateString(), $to->toDateString()])
            ->count();

        $patrolTotal = SecurityPatrolSchedule::query()
            ->whereBetween(DB::raw('DATE(patrol_date)'), [$from->toDateString(), $to->toDateString()])
            ->count();

        $patrolDone = SecurityPatrolSchedule::query()
            ->whereBetween(DB::raw('DATE(patrol_date)'), [$from->toDateString(), $to->toDateString()])
            ->where('status', SecurityPatrolSchedule::STATUS_DONE)
            ->count();

        $patrolRate = $patrolTotal > 0 ? (int) round($patrolDone / $patrolTotal * 100) : 0;

        $dailyGuests = DB::table('security_guest_logs')
            ->selectRaw('DATE(check_in_at) as day, COUNT(*) as total')
            ->whereBetween(DB::raw('DATE(check_in_at)'), [$from->toDateString(), $to->toDateString()])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $incidentByType = DB::table('security_incidents')
            ->leftJoin('security_incident_types', 'security_incident_types.id', '=', 'security_incidents.incident_type_id')
            ->selectRaw('COALESCE(security_incident_types.name, \'Tanpa Jenis\') as label, COUNT(*) as total')
            ->whereBetween(DB::raw('DATE(security_incidents.occurred_at)'), [$from->toDateString(), $to->toDateString()])
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $incidentByStatus = DB::table('security_incidents')
            ->selectRaw('status, COUNT(*) as total')
            ->whereBetween(DB::raw('DATE(occurred_at)'), [$from->toDateString(), $to->toDateString()])
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $guestByPurpose = DB::table('security_guest_logs')
            ->selectRaw('purpose, COUNT(*) as total')
            ->whereBetween(DB::raw('DATE(check_in_at)'), [$from->toDateString(), $to->toDateString()])
            ->groupBy('purpose')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $maxDaily = max(1, (int) ($dailyGuests->max('total') ?? 1));

        return view('security.reports.index', compact(
            'userId', 'from', 'to', 'guestTotal', 'vehicleTotal', 'incidentTotal',
            'patrolTotal', 'patrolDone', 'patrolRate', 'dailyGuests', 'maxDaily',
            'incidentByType', 'incidentByStatus', 'guestByPurpose'
        ));
    }

    public function guests(Request $request, string $userId)
    {
        [$from, $to] = $this->range($request);

        $rows = SecurityGuestLog::query()
            ->with('post')
            ->whereBetween(DB::raw('DATE(check_in_at)'), [$from->toDateString(), $to->toDateString()])
            ->orderBy('check_in_at')
            ->get();

        return $this->csv('laporan-tamu-'.$from->format('Ymd').'-'.$to->format('Ymd'), [
            'Kode', 'Nama Tamu', 'No Identitas', 'Instansi', 'Keperluan', 'Bertemu',
            'Jumlah', 'Pos', 'Masuk', 'Keluar', 'Status',
        ], $rows->map(fn ($row) => [
            $row->code,
            $row->visitor_name,
            $row->id_number,
            $row->institution,
            $row->purpose,
            $row->person_to_meet,
            $row->visitor_count,
            $row->post?->name,
            optional($row->check_in_at)->format('Y-m-d H:i'),
            optional($row->check_out_at)->format('Y-m-d H:i'),
            $row->isInside() ? 'Di dalam' : 'Keluar',
        ]));
    }

    public function incidents(Request $request, string $userId)
    {
        [$from, $to] = $this->range($request);

        $rows = SecurityIncident::query()
            ->with(['type', 'post', 'reporter', 'assignee'])
            ->whereBetween(DB::raw('DATE(occurred_at)'), [$from->toDateString(), $to->toDateString()])
            ->orderBy('occurred_at')
            ->get();

        return $this->csv('laporan-insiden-'.$from->format('Ymd').'-'.$to->format('Ymd'), [
            'Kode', 'Waktu', 'Jenis', 'Judul', 'Lokasi', 'Tingkat', 'Status',
            'Pelapor', 'Penanggung Jawab', 'Penyelesaian',
        ], $rows->map(fn ($row) => [
            $row->code,
            optional($row->occurred_at)->format('Y-m-d H:i'),
            $row->type?->name,
            $row->title,
            $row->location,
            $row->severityLabel(),
            $row->statusLabel(),
            $row->reporter?->name,
            $row->assignee?->name,
            $row->resolution,
        ]));
    }

    public function patrols(Request $request, string $userId)
    {
        [$from, $to] = $this->range($request);

        $rows = SecurityPatrolSchedule::query()
            ->with(['team', 'shift', 'post'])
            ->withCount('logs')
            ->whereBetween(DB::raw('DATE(patrol_date)'), [$from->toDateString(), $to->toDateString()])
            ->orderBy('patrol_date')
            ->get();

        return $this->csv('laporan-patroli-'.$from->format('Ymd').'-'.$to->format('Ymd'), [
            'Kode', 'Tanggal', 'Regu', 'Shift', 'Pos', 'Checkpoint Dipindai', 'Status',
        ], $rows->map(fn ($row) => [
            $row->code,
            optional($row->patrol_date)->format('Y-m-d'),
            $row->team?->name,
            $row->shift?->label(),
            $row->post?->name,
            $row->logs_count,
            $row->statusLabel(),
        ]));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  Collection<int, array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
