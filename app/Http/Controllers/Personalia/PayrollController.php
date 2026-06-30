<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\GtkProfile;
use App\Models\Payroll;
use App\Models\PayrollSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Payroll::with(['gtk', 'pembuat'])
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('bulan'), fn($q, $b) => $q->where('bulan', $b))
            ->when($request->get('tahun'), fn($q, $t) => $q->where('tahun', $t))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at');

        $payrolls = $query->paginate(20);

        $gtkList = GtkProfile::orderBy('nama')->get();

        $stats = [
            'total'       => Payroll::count(),
            'gaji_bersih_total' => Payroll::whereNotIn('status', ['draft', 'void'])
                ->sum('gaji_bersih'),
            'draft_count' => Payroll::where('status', 'draft')->count(),
            'paid_count'  => Payroll::where('status', 'paid')->count(),
        ];

        return view('personalia.payroll.index', compact('userId', 'payrolls', 'gtkList', 'stats'));
    }

    public function create(Request $request, string $userId)
    {
        $gtkList = GtkProfile::orderBy('nama')->get();
        return view('personalia.payroll.create', compact('userId', 'gtkList'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id'   => 'required|uuid|exists:gtk_profiles,id',
            'bulan'    => 'required|integer|min:1|max:12',
            'tahun'    => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'gaji_pokok'  => 'required|numeric|min:0',
            'tunjangan.jenis' => 'nullable|array',
            'tunjangan.nominal' => 'nullable|array',
            'potongan.jenis'    => 'nullable|array',
            'potongan.nominal'  => 'nullable|array',
        ]);

        // Calculate totals
        $tunjangan = array_map(
            fn($jenis, $nom) => ['jenis' => $jenis, 'nominal' => (float) ($nom ?? 0)],
            $validated['tunjangan']['jenis'] ?? [],
            $validated['tunjangan']['nominal'] ?? [],
        );
        $potongan = array_map(
            fn($jenis, $nom) => ['jenis' => $jenis, 'nominal' => (float) ($nom ?? 0)],
            $validated['potongan']['jenis'] ?? [],
            $validated['potongan']['nominal'] ?? [],
        );
        $totalTunjangan = array_sum(array_column($tunjangan, 'nominal'));
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));
        $gajiPokok = (float) $validated['gaji_pokok'];
        $gajiBersih = $gajiPokok + $totalTunjangan - $totalPotongan;

        DB::beginTransaction();
        try {
            Payroll::updateOrCreate(
                ['gtk_id' => $validated['gtk_id'], 'bulan' => $validated['bulan'], 'tahun' => $validated['tahun']],
                [
                    'gaji_pokok'      => $gajiPokok,
                    'total_tunjangan' => $totalTunjangan,
                    'total_potongan'  => $totalPotongan,
                    'gaji_bersih'     => $gajiBersih,
                    'detail_tunjangan'=> $tunjangan,
                    'detail_potongan' => $potongan,
                    'status'          => 'draft',
                    'dibuat_oleh'     => Auth::id(),
                ],
            );
            DB::commit();
            return redirect()->route('user.payroll.index', $userId)
                ->with('success', 'Slip gaji berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan slip gaji.');
        }
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with('gtk')->findOrFail($id);
        $gtkList = GtkProfile::orderBy('nama')->get();
        return view('personalia.payroll.edit', compact('userId', 'payroll', 'gtkList'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        $validated = $request->validate([
            'gtk_id'   => 'required|uuid|exists:gtk_profiles,id',
            'bulan'    => 'required|integer|min:1|max:12',
            'tahun'    => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'gaji_pokok'  => 'required|numeric|min:0',
            'tunjangan.jenis' => 'nullable|array',
            'tunjangan.nominal' => 'nullable|array',
            'potongan.jenis'    => 'nullable|array',
            'potongan.nominal'  => 'nullable|array',
        ]);

        $tunjangan = array_map(
            fn($jenis, $nom) => ['jenis' => $jenis, 'nominal' => (float) ($nom ?? 0)],
            $validated['tunjangan']['jenis'] ?? [],
            $validated['tunjangan']['nominal'] ?? [],
        );
        $potongan = array_map(
            fn($jenis, $nom) => ['jenis' => $jenis, 'nominal' => (float) ($nom ?? 0)],
            $validated['potongan']['jenis'] ?? [],
            $validated['potongan']['nominal'] ?? [],
        );
        $totalTunjangan = array_sum(array_column($tunjangan, 'nominal'));
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));
        $gajiPokok = (float) $validated['gaji_pokok'];
        $gajiBersih = $gajiPokok + $totalTunjangan - $totalPotongan;

        DB::beginTransaction();
        try {
            $payroll->update([
                'gtk_id'          => $validated['gtk_id'],
                'bulan'           => $validated['bulan'],
                'tahun'           => $validated['tahun'],
                'gaji_pokok'      => $gajiPokok,
                'total_tunjangan' => $totalTunjangan,
                'total_potongan'  => $totalPotongan,
                'gaji_bersih'     => $gajiBersih,
                'detail_tunjangan'=> $tunjangan,
                'detail_potongan' => $potongan,
            ]);
            DB::commit();
            return redirect()->route('user.payroll.index', $userId)
                ->with('success', 'Slip gaji berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui slip gaji.');
        }
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status === 'paid') {
            return redirect()->back()->with('error', 'Slip yang sudah dibayar tidak dapat dihapus.');
        }
        $payroll->delete();
        return redirect()->route('user.payroll.index', $userId)
            ->with('success', 'Slip gaji berhasil dihapus.');
    }

    public function potongan(Request $request, string $userId)
    {
        $settings = PayrollSetting::orderBy('key')->get();
        return view('personalia.payroll.potongan', compact('userId', 'settings'));
    }

    public function tunjangan(Request $request, string $userId)
    {
        $settings = PayrollSetting::orderBy('key')->get();
        return view('personalia.payroll.tunjangan', compact('userId', 'settings'));
    }

    public function bpjstk(Request $request, string $userId)
    {
        return view('personalia.payroll.bpjstk', compact('userId'));
    }

    public function bpjsKes(Request $request, string $userId)
    {
        return view('personalia.payroll.bpjs-kes', compact('userId'));
    }

    public function settings(Request $request, string $userId)
    {
        $settings = PayrollSetting::orderBy('key')->get();
        return view('personalia.payroll.settings', compact('userId', 'settings'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = Payroll::with(['gtk', 'pembuat'])
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('bulan'), fn($q, $b) => $q->where('bulan', $b))
            ->when($request->get('tahun'), fn($q, $t) => $q->where('tahun', $t))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at');

        return datatables()->of($query)
            ->addColumn('gtk', fn($r) => $r->gtk?->nama ?? '-')
            ->addColumn('periode', fn($r) => str_pad($r->bulan, 2, '0', STR_PAD_LEFT) . '/' . $r->tahun)
            ->addColumn('nominal', fn($r) => 'Rp ' . number_format((float) $r->gaji_bersih, 0, ',', '.'))
            ->addColumn('status_badge', function ($r) {
                $map = [
                    'draft'  => 'warning',
                    'published' => 'primary',
                    'paid'   => 'success',
                    'void'   => 'danger',
                ];
                $color = $map[$r->status] ?? 'secondary';
                $label = ucfirst($r->status);
                return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . $label . '</span>';
            })
            ->rawColumns(['status_badge', 'nominal'])
            ->make(true);
    }

    // Slip Gaji
    public function slipIndex(Request $request, string $userId)
    {
        $slips = Payroll::with(['gtk'])
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('personalia.payroll.slip.index', compact('userId', 'slips'));
    }

    public function slipShow(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with(['gtk'])->findOrFail($id);
        return view('personalia.payroll.slip.show', compact('userId', 'payroll'));
    }

    public function slipPdf(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with(['gtk'])->findOrFail($id);

        $pdf = Pdf::loadView('personalia.payroll.slip.pdf', compact('payroll'));
        $filename = 'slip-gaji-' . ($payroll->gtk?->nama ?? $id) . '-' . $payroll->bulan . '_' . $payroll->tahun . '.pdf';

        return $pdf->stream($filename);
    }
}
