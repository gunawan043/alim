<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pintu gerbang — pantauan santri keluar/masuk.
 *
 * Tidak menulis data: seluruh angka diambil dari modul perizinan asrama
 * (dormitory_permits) yang sudah punya alur scan keluar & scan kembali.
 * Petugas keamanan hanya membaca.
 */
class SecurityGateController extends Controller
{
    public const TABS = [
        'log' => 'Log Keluar-Masuk',
        'late' => 'Santri Terlambat',
        'outside' => 'Status Santri',
    ];

    public function index(Request $request, string $userId)
    {
        $tab = (string) $request->query('tab', 'log');

        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'log';
        }

        $query = DormitoryPermit::query()
            ->with(['student:id,name,nisn', 'dormitory:id,name']);

        if ($dormitoryId = $request->query('dormitory_id')) {
            $query->where('dormitory_id', $dormitoryId);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%');
            });
        }

        if ($from = $request->query('from')) {
            $query->whereDate('departure_datetime', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('departure_datetime', '<=', $to);
        }

        if ($tab === 'log') {
            $query->where(function ($q) {
                $q->whereNotNull('pickup_scanned_at')
                    ->orWhereNotNull('return_scanned_at');
            })->orderByDesc(DB::raw('COALESCE(return_scanned_at, pickup_scanned_at)'));
        } elseif ($tab === 'late') {
            $query->where(function ($q) {
                $q->where('status', DormitoryPermit::STATUS_OVERDUE)
                    ->orWhere(function ($inner) {
                        $inner->where('status', DormitoryPermit::STATUS_PICKED_UP)
                            ->where('expected_return_datetime', '<', now());
                    });
            })->orderBy('expected_return_datetime');
        } else {
            $query->where('status', DormitoryPermit::STATUS_PICKED_UP)
                ->orderBy('expected_return_datetime');
        }

        $permits = $query->paginate(25)->withQueryString();

        $base = DormitoryPermit::query();

        $stats = [
            'outside' => (clone $base)->where('status', DormitoryPermit::STATUS_PICKED_UP)->count(),
            'late' => (clone $base)->where(function ($q) {
                $q->where('status', DormitoryPermit::STATUS_OVERDUE)
                    ->orWhere(function ($inner) {
                        $inner->where('status', DormitoryPermit::STATUS_PICKED_UP)
                            ->where('expected_return_datetime', '<', now());
                    });
            })->count(),
            'out_today' => (clone $base)->whereDate('pickup_scanned_at', today())->count(),
            'in_today' => (clone $base)->whereDate('return_scanned_at', today())->count(),
        ];

        $dormitories = Dormitory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('security.gate.index', compact(
            'userId', 'permits', 'stats', 'dormitories', 'tab'
        ));
    }
}
