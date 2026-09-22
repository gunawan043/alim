<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\DivisionBudget;
use App\Models\GoodsReceipt;
use App\Models\ProcurementRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class BendaharaDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        // Kas saldo — menggunakan DivisionBudget sebagai proxy (sesuaikan jika ada model keuangan khusus)
        $saldoKas = DivisionBudget::where('is_active', true)
            ->sum('allocated_amount') ?? 0;
        $saldoKas = number_format($saldoKas, 0, ',', '.');

        // Pengeluaran hari ini — dari GoodsReceipt yang diterima hari ini
        $pengeluaranHariIni = GoodsReceipt::whereDate('receipt_date', now()->toDateString())
            ->where('status', 'received')
            ->sum('total_amount');
        $pengeluaranHariIni = number_format($pengeluaranHariIni, 0, ',', '.');

        // Pemasukan hari ini — dari PurchaseOrder yang disetujui hari ini
        $pemasukanHariIni = PurchaseOrder::whereDate('created_at', now()->toDateString())
            ->where('status', 'approved')
            ->sum('total_amount');
        $pemasukanHariIni = number_format($pemasukanHariIni, 0, ',', '.');

        $tagihanPending = ProcurementRequest::where('status', 'pending')
            ->count();

        $pengeluaranPending = GoodsReceipt::where('status', 'pending')
            ->with(['vendor'])
            ->limit(5)
            ->get();

        $tagihanVendor = ProcurementRequest::where('status', 'approved')
            ->with(['vendor'])
            ->limit(5)
            ->get();

        $taskBendahara = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'bendahara')
                || str_contains(strtolower($t->nama_tugas), 'keuangan');
        });

        return view('dashboard-pendidikan.jabatan.bendahara', compact(
            'user', 'saldoKas', 'pengeluaranHariIni',
            'pemasukanHariIni', 'tagihanPending',
            'pengeluaranPending', 'tagihanVendor', 'taskBendahara'
        ));
    }
}
