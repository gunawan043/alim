<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AuditLog;
use App\Models\DokumenIso;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Http\Request;

class KaTataUsahaDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $suratMasuk = SuratMasuk::whereDate('created_at', $today)->count();
        $suratKeluar = SuratKeluar::whereDate('created_at', $today)->count();
        $dokumenExpiring = DokumenIso::where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now()->toDateString());
        })
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();

        $gtkBaru = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereHas('employment')
            ->count();

        $suratPending = SuratMasuk::where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $logAktivitas = AuditLog::whereDate('created_at', $today)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $taskKaTU = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'kepala tata usaha')
                || str_contains(strtolower($t->nama_tugas), 'ka tata usaha');
        });

        return view('dashboard-pendidikan.jabatan.ka-tata-usaha', compact(
            'user', 'suratMasuk', 'suratKeluar',
            'dokumenExpiring', 'gtkBaru',
            'suratPending', 'logAktivitas', 'taskKaTU'
        ));
    }
}
