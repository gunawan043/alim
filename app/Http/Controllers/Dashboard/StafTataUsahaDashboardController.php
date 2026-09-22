<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\GtkRequest;
use App\Models\Kaldik;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;

class StafTataUsahaDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $suratMasuk = SuratMasuk::whereDate('created_at', $today)->count();
        $suratKeluar = SuratKeluar::whereDate('created_at', $today)->count();
        $dokumenPending = GtkRequest::where('status', 'pending')->count();
        $agendaHariIni = Kaldik::whereDate('start_date', $today)->count();

        $inboxSurat = SuratMasuk::where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $outboxSurat = SuratKeluar::whereDate('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $agendaList = Kaldik::where('category', Kaldik::CATEGORY_AGENDA)
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        $taskStafTU = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'staf tata usaha')
                || str_contains(strtolower($t->nama_tugas), 'staff tata usaha');
        });

        return view('dashboard-pendidikan.jabatan.staf-tata-usaha', compact(
            'user', 'suratMasuk', 'suratKeluar',
            'dokumenPending', 'agendaHariIni',
            'inboxSurat', 'outboxSurat', 'agendaList', 'taskStafTU'
        ));
    }
}
