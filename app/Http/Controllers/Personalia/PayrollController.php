<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\GtkProfile;
use App\Models\Payroll;
use App\Models\PayrollSetting;
use App\Models\Payroll\Masakerja;
use App\Models\Payroll\Pendidikan;
use App\Models\Payroll\PersentaseGaji;
use App\Models\Payroll\SalaryScal;
use App\Models\Payroll\SkalaGajiGuru;
use App\Models\Payroll\SkalaGajiTendik;
use App\Models\Payroll\Tunjangan;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    public function index(Request $request, string $userId)
    {
        $base = Payroll::query()
            ->when($request->get('gtk_id'), fn ($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('bulan'), fn ($q, $b) => $q->where('bulan', $b))
            ->when($request->get('tahun'), fn ($q, $t) => $q->where('tahun', $t))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s));

        $payrolls = (clone $base)->with(['gtk.user', 'gtk.latestEmployment', 'pembuat'])
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        $gtkList = GtkProfile::with('user')->orderBy('id')->get()
            ->sortBy(fn ($g) => $g->user?->name ?? '')->values();

        $stats = [
            'total' => (clone $base)->count(),
            'gaji_bersih_total' => (clone $base)->whereNotIn('status', ['void'])->sum('gaji_bersih'),
            'draft_count' => (clone $base)->where('status', 'draft')->count(),
            'paid_count' => (clone $base)->where('status', 'paid')->count(),
        ];

        $totalGtk = GtkProfile::count();
        $totalGaji = (clone $base)->whereNotIn('status', ['void'])->sum('gaji_bersih');
        $avgGaji = (clone $base)->whereNotIn('status', ['void'])->avg('gaji_bersih') ?? 0;

        return view('personalia.payroll.index', compact(
            'userId', 'payrolls', 'gtkList', 'stats', 'totalGtk', 'totalGaji', 'avgGaji',
        ));
    }

    public function create(Request $request, string $userId)
    {
        $gtkList = GtkProfile::with('user')->get()
            ->sortBy(fn ($g) => $g->user?->name ?? '')->values();

        return view('personalia.payroll.create', compact('userId', 'gtkList'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $this->validatePayroll($request);

        $existing = Payroll::where('gtk_id', $validated['gtk_id'])
            ->where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->first();

        if ($existing && in_array($existing->status, ['published', 'paid'], true)) {
            return back()->withInput()->with('error', 'Slip untuk GTK dan periode ini sudah dipublikasikan/dibayar.');
        }

        [$tunjangan, $potongan, $totalTunjangan, $totalPotongan, $gajiBersih] = $this->totals($validated);

        Payroll::updateOrCreate(
            ['gtk_id' => $validated['gtk_id'], 'bulan' => $validated['bulan'], 'tahun' => $validated['tahun']],
            [
                'gaji_pokok' => (float) $validated['gaji_pokok'],
                'total_tunjangan' => $totalTunjangan,
                'total_potongan' => $totalPotongan,
                'gaji_bersih' => $gajiBersih,
                'detail_tunjangan' => $tunjangan,
                'detail_potongan' => $potongan,
                'status' => 'draft',
                'dibuat_oleh' => Auth::id(),
            ],
        );

        return redirect()->route('user.payroll.index', $userId)
            ->with('success', 'Slip gaji berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with('gtk.user')->findOrFail($id);
        $gtkList = GtkProfile::with('user')->get()
            ->sortBy(fn ($g) => $g->user?->name ?? '')->values();

        return view('personalia.payroll.edit', compact('userId', 'payroll', 'gtkList'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        if (in_array($payroll->status, ['published', 'paid'], true)) {
            return back()->with('error', 'Slip yang sudah dipublikasikan/dibayar tidak dapat diubah.');
        }

        $validated = $this->validatePayroll($request);
        [$tunjangan, $potongan, $totalTunjangan, $totalPotongan, $gajiBersih] = $this->totals($validated);

        $payroll->update([
            'gtk_id' => $validated['gtk_id'],
            'bulan' => $validated['bulan'],
            'tahun' => $validated['tahun'],
            'gaji_pokok' => (float) $validated['gaji_pokok'],
            'total_tunjangan' => $totalTunjangan,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $gajiBersih,
            'detail_tunjangan' => $tunjangan,
            'detail_potongan' => $potongan,
        ]);

        return redirect()->route('user.payroll.index', $userId)
            ->with('success', 'Slip gaji berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);
        if (in_array($payroll->status, ['published', 'paid'], true)) {
            return back()->with('error', 'Slip yang sudah dipublikasikan/dibayar tidak dapat dihapus.');
        }

        $payroll->delete();

        return redirect()->route('user.payroll.index', $userId)
            ->with('success', 'Slip gaji berhasil dihapus.');
    }

    // ── Generate massal ────────────────────────────────────────────────

    public function generateForm(Request $request, string $userId)
    {
        $gtkList = GtkProfile::with(['user', 'latestEmployment'])->get()
            ->sortBy(fn ($g) => $g->user?->name ?? '')->values();

        return view('personalia.payroll.generate', compact('userId', 'gtkList'));
    }

    public function generate(Request $request, string $userId)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:'.(date('Y') + 1),
            'gtk_id' => 'nullable|uuid|exists:gtk_profiles,id',
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $query = GtkProfile::with(['user', 'latestEmployment'])
            ->whereHas('latestEmployment')
            ->when($validated['gtk_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->orderBy('id');

        foreach ($query->cursor() as $gtk) {
            $calc = $this->calculator->calculate($gtk, (int) $validated['bulan'], (int) $validated['tahun']);
            $name = $gtk->user?->name ?? 'GTK';

            if (! $calc['ok']) {
                $errors[] = $name.': '.implode(' ', $calc['errors']);
                continue;
            }

            $existing = Payroll::where('gtk_id', $gtk->id)
                ->where('bulan', $validated['bulan'])
                ->where('tahun', $validated['tahun'])
                ->first();

            if ($existing && in_array($existing->status, ['published', 'paid'], true)) {
                $skipped++;
                continue;
            }

            Payroll::updateOrCreate(
                ['gtk_id' => $gtk->id, 'bulan' => $validated['bulan'], 'tahun' => $validated['tahun']],
                [
                    'gaji_pokok' => $calc['gaji_pokok'],
                    'total_tunjangan' => $calc['total_tunjangan'],
                    'total_potongan' => $calc['total_potongan'],
                    'gaji_bersih' => $calc['gaji_bersih'],
                    'detail_tunjangan' => $calc['tunjangan'],
                    'detail_potongan' => $calc['potongan'],
                    'calculation_meta' => $calc['meta'],
                    'status' => 'draft',
                    'dibuat_oleh' => Auth::id(),
                ],
            );

            $existing ? $updated++ : $created++;
        }

        return redirect()->route('user.payroll.index', ['userId' => $userId, 'bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', "Generate payroll selesai: {$created} baru, {$updated} diperbarui, {$skipped} dilewati.")
            ->with('generate_errors', $errors);
    }

    public function publish(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Hanya slip draft yang dapat dipublikasikan.');
        }

        $payroll->update(['status' => 'published', 'published_at' => now()]);

        return back()->with('success', 'Slip gaji dipublikasikan.');
    }

    public function pay(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status !== 'published') {
            return back()->with('error', 'Slip harus dipublikasikan sebelum dibayar.');
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => Auth::id(),
            'tanggal_bayar' => now()->toDateString(),
        ]);

        return back()->with('success', 'Slip gaji ditandai sudah dibayar.');
    }

    // ── Master gaji & aturan ───────────────────────────────────────────

    public function master(Request $request, string $userId)
    {
        $tab = $request->get('tab', 'aturan');
        $editing = $request->filled('edit') ? Tunjangan::find($request->get('edit')) : null;

        $rules = [
            'payroll_workdays' => (int) PayrollSetting::get('payroll_workdays', 25),
            'payroll_attendance_allowance_enabled' => (bool) PayrollSetting::get('payroll_attendance_allowance_enabled', false),
            'payroll_attendance_allowance_per_day' => (float) PayrollSetting::get('payroll_attendance_allowance_per_day', 0),
            'payroll_late_deduction_per_minute' => (float) PayrollSetting::get('payroll_late_deduction_per_minute', 0),
            'payroll_absent_deduction_per_day' => (float) PayrollSetting::get('payroll_absent_deduction_per_day', 0),
            'payroll_leave_deduction_per_day' => (float) PayrollSetting::get('payroll_leave_deduction_per_day', 0),
            'payroll_use_percentage' => (bool) PayrollSetting::get('payroll_use_percentage', false),
            'payroll_bpjs_tk_enabled' => (bool) PayrollSetting::get('payroll_bpjs_tk_enabled', false),
            'payroll_bpjs_tk_percent' => (float) PayrollSetting::get('payroll_bpjs_tk_percent', 2),
            'payroll_bpjs_kes_enabled' => (bool) PayrollSetting::get('payroll_bpjs_kes_enabled', false),
            'payroll_bpjs_kes_percent' => (float) PayrollSetting::get('payroll_bpjs_kes_percent', 1),
        ];

        $tunjangan = Tunjangan::orderBy('Kode')->get();
        $scalesGuru = SkalaGajiGuru::orderBy('masa_kerja')->orderBy('level')->limit(100)->get();
        $scalesTendik = SkalaGajiTendik::orderBy('masa_kerja')->orderBy('level')->limit(100)->get();

        $counts = [
            'masakerja' => Masakerja::count(),
            'pendidikan' => Pendidikan::count(),
            'salary_scal' => SalaryScal::count(),
            'guru' => SkalaGajiGuru::count(),
            'tendik' => SkalaGajiTendik::count(),
            'persentase' => PersentaseGaji::count(),
        ];

        return view('personalia.payroll.master', compact(
            'userId', 'tab', 'editing', 'rules', 'tunjangan',
            'scalesGuru', 'scalesTendik', 'counts',
        ));
    }

    public function updateRules(Request $request, string $userId)
    {
        $validated = $request->validate([
            'payroll_workdays' => 'required|integer|min:1|max:31',
            'payroll_attendance_allowance_per_day' => 'nullable|numeric|min:0',
            'payroll_late_deduction_per_minute' => 'nullable|numeric|min:0',
            'payroll_absent_deduction_per_day' => 'nullable|numeric|min:0',
            'payroll_leave_deduction_per_day' => 'nullable|numeric|min:0',
            'payroll_bpjs_tk_percent' => 'nullable|numeric|min:0|max:100',
            'payroll_bpjs_kes_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        PayrollSetting::set('payroll_workdays', $validated['payroll_workdays'], 'int', 'Hari kerja efektif per bulan');
        PayrollSetting::set('payroll_attendance_allowance_enabled', $request->boolean('payroll_attendance_allowance_enabled'), 'bool', 'Aktifkan tunjangan kehadiran');
        PayrollSetting::set('payroll_attendance_allowance_per_day', $validated['payroll_attendance_allowance_per_day'] ?? 0, 'float', 'Tunjangan kehadiran per hari');
        PayrollSetting::set('payroll_late_deduction_per_minute', $validated['payroll_late_deduction_per_minute'] ?? 0, 'float', 'Potongan per menit terlambat');
        PayrollSetting::set('payroll_absent_deduction_per_day', $validated['payroll_absent_deduction_per_day'] ?? 0, 'float', 'Potongan per hari alpa');
        PayrollSetting::set('payroll_leave_deduction_per_day', $validated['payroll_leave_deduction_per_day'] ?? 0, 'float', 'Potongan per hari izin/sakit');
        PayrollSetting::set('payroll_use_percentage', $request->boolean('payroll_use_percentage'), 'bool', 'Gunakan persentase golongan');
        PayrollSetting::set('payroll_bpjs_tk_enabled', $request->boolean('payroll_bpjs_tk_enabled'), 'bool', 'Aktifkan potongan BPJS TK');
        PayrollSetting::set('payroll_bpjs_tk_percent', $validated['payroll_bpjs_tk_percent'] ?? 0, 'float', 'Persentase BPJS TK');
        PayrollSetting::set('payroll_bpjs_kes_enabled', $request->boolean('payroll_bpjs_kes_enabled'), 'bool', 'Aktifkan potongan BPJS Kesehatan');
        PayrollSetting::set('payroll_bpjs_kes_percent', $validated['payroll_bpjs_kes_percent'] ?? 0, 'float', 'Persentase BPJS Kesehatan');

        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'aturan'])
            ->with('success', 'Aturan payroll disimpan.');
    }

    public function tunjanganStore(Request $request, string $userId)
    {
        $validated = $this->validateTunjangan($request);
        Tunjangan::create($validated);

        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan'])
            ->with('success', 'Tunjangan ditambahkan.');
    }

    public function tunjanganUpdate(Request $request, string $userId, string $id)
    {
        Tunjangan::findOrFail($id)->update($this->validateTunjangan($request));

        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan'])
            ->with('success', 'Tunjangan diperbarui.');
    }

    public function tunjanganDestroy(Request $request, string $userId, string $id)
    {
        Tunjangan::findOrFail($id)->delete();

        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan'])
            ->with('success', 'Tunjangan dihapus.');
    }

    public function importMaster(Request $request, string $userId)
    {
        $validated = $request->validate([
            'type' => 'required|in:guru,tendik,salary_scal,pendidikan,masakerja,persentase',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'File tidak dapat dibaca.');
        }

        $header = fgetcsv($handle); // lewati header
        $count = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_map(fn ($v) => trim((string) $v, " \t\n\r\0\xEF\xBB\xBF"), $row);
                if (count(array_filter($row, fn ($v) => $v !== '')) === 0) {
                    continue;
                }

                $this->importRow($validated['type'], $row);
                $count++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        fclose($handle);

        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'skala'])
            ->with('success', "Import selesai: {$count} baris diproses.");
    }

    public function templateMaster(Request $request, string $userId, string $type)
    {
        $headers = match ($type) {
            'guru', 'tendik' => ['masa_kerja', 'level', 'golongan_1', 'golongan_2', 'golongan_3', 'golongan_4', 'golongan_5'],
            'salary_scal' => ['pendidikan', 'masa_kerja', 'gaji'],
            'pendidikan' => ['strata', 'golongan'],
            'masakerja' => ['masa_kerja', 'level'],
            'persentase' => ['persentase_golongan_1', 'persentase_golongan_2', 'persentase_golongan_3', 'persentase_golongan_4'],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            fclose($out);
        }, 'template-'.$type.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function potongan(Request $request, string $userId)
    {
        $settings = PayrollSetting::orderBy('key')->get();

        return view('personalia.payroll.potongan', compact('userId', 'settings'));
    }

    public function tunjangan(Request $request, string $userId)
    {
        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan']);
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
        return redirect()->route('user.payroll.master', ['userId' => $userId, 'tab' => 'aturan']);
    }

    public function datatable(Request $request, string $userId)
    {
        $query = Payroll::with(['gtk.user', 'pembuat'])
            ->when($request->get('gtk_id'), fn ($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('bulan'), fn ($q, $b) => $q->where('bulan', $b))
            ->when($request->get('tahun'), fn ($q, $t) => $q->where('tahun', $t))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s));

        return datatables()->of($query)
            ->addColumn('gtk', fn ($r) => $r->gtk?->nama ?? '-')
            ->addColumn('periode', fn ($r) => str_pad($r->bulan, 2, '0', STR_PAD_LEFT).'/'.$r->tahun)
            ->addColumn('nominal', fn ($r) => 'Rp '.number_format((float) $r->gaji_bersih, 0, ',', '.'))
            ->addColumn('status_badge', function ($r) {
                $map = ['draft' => 'warning', 'published' => 'primary', 'paid' => 'success', 'void' => 'danger'];
                $color = $map[$r->status] ?? 'secondary';

                return '<span class="badge bg-'.$color.'-subtle text-'.$color.'">'.ucfirst($r->status).'</span>';
            })
            ->rawColumns(['status_badge', 'nominal'])
            ->make(true);
    }

    // ── Slip gaji ───────────────────────────────────────────────────────

    public function slipIndex(Request $request, string $userId)
    {
        $slips = Payroll::with(['gtk.user'])
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('created_at')
            ->paginate(20);

        return view('personalia.payroll.slip.index', compact('userId', 'slips'));
    }

    public function slipShow(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with(['gtk.user'])->findOrFail($id);

        return view('personalia.payroll.slip.show', compact('userId', 'payroll'));
    }

    public function slipPdf(Request $request, string $userId, string $id)
    {
        $payroll = Payroll::with(['gtk.user'])->findOrFail($id);

        $pdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $pdf->loadHtml(view('personalia.payroll.slip.pdf', compact('payroll'))->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $filename = 'slip-gaji-'.($payroll->gtk?->nama ?? $id).'-'.$payroll->bulan.'_'.$payroll->tahun.'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validatePayroll(Request $request): array
    {
        return $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:'.(date('Y') + 1),
            'gaji_pokok' => 'required|numeric|min:0',
            'tunjangan.jenis' => 'nullable|array',
            'tunjangan.nominal' => 'nullable|array',
            'potongan.jenis' => 'nullable|array',
            'potongan.nominal' => 'nullable|array',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: array<int, array{jenis: string, nominal: float}>, 1: array<int, array{jenis: string, nominal: float}>, 2: float, 3: float, 4: float}
     */
    private function totals(array $validated): array
    {
        $build = function (array $jenis = [], array $nominal = []): array {
            $result = [];
            foreach ($jenis as $i => $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $result[] = ['jenis' => $label, 'nominal' => (float) ($nominal[$i] ?? 0)];
            }

            return $result;
        };

        $tunjangan = $build($validated['tunjangan']['jenis'] ?? [], $validated['tunjangan']['nominal'] ?? []);
        $potongan = $build($validated['potongan']['jenis'] ?? [], $validated['potongan']['nominal'] ?? []);
        $totalTunjangan = array_sum(array_column($tunjangan, 'nominal'));
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));
        $gajiPokok = (float) $validated['gaji_pokok'];

        return [$tunjangan, $potongan, $totalTunjangan, $totalPotongan, $gajiPokok + $totalTunjangan - $totalPotongan];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTunjangan(Request $request): array
    {
        $validated = $request->validate([
            'Kode' => 'required|string|max:30|unique:tunjangan,Kode,'.($request->route('id') ?? 'NULL').',id',
            'Subjek' => 'required|string|max:191',
            'Nominal' => 'required|numeric|min:0',
            'is_attendance_based' => 'boolean',
            'per_hari' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_attendance_based'] = $request->boolean('is_attendance_based');
        $validated['per_hari'] = $request->boolean('per_hari');

        return $validated;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function importRow(string $type, array $row): void
    {
        if (in_array($type, ['guru', 'tendik'], true)) {
            $data = [
                'masa_kerja' => (int) ($row[0] ?? 0),
                'level' => (int) ($row[1] ?? 1),
                'golongan_1' => (float) ($row[2] ?? 0),
                'golongan_2' => (float) ($row[3] ?? 0),
                'golongan_3' => (float) ($row[4] ?? 0),
                'golongan_4' => (float) ($row[5] ?? 0),
                'golongan_5' => (float) ($row[6] ?? 0),
            ];
            $model = $type === 'guru' ? SkalaGajiGuru::class : SkalaGajiTendik::class;
            $model::updateOrCreate(
                ['masa_kerja' => $data['masa_kerja'], 'level' => $data['level']],
                $data
            );

            return;
        }

        if ($type === 'salary_scal') {
            SalaryScal::updateOrCreate(
                ['pendidikan' => strtoupper((string) ($row[0] ?? '')), 'masa_kerja' => (int) ($row[1] ?? 0)],
                ['gaji' => (float) ($row[2] ?? 0)]
            );

            return;
        }

        if ($type === 'pendidikan') {
            Pendidikan::updateOrCreate(
                ['Strata' => strtoupper((string) ($row[0] ?? ''))],
                ['Golongan' => (int) ($row[1] ?? 1)]
            );

            return;
        }

        if ($type === 'masakerja') {
            Masakerja::updateOrCreate(
                ['masa_kerja' => (int) ($row[0] ?? 0)],
                ['level' => (int) ($row[1] ?? 1)]
            );

            return;
        }

        // persentase
        $data = [
            'persentase_golongan_1' => (float) ($row[0] ?? 100),
            'persentase_golongan_2' => (float) ($row[1] ?? 100),
            'persentase_golongan_3' => (float) ($row[2] ?? 100),
            'persentase_golongan_4' => (float) ($row[3] ?? 100),
        ];
        $existing = PersentaseGaji::query()->first();
        if ($existing) {
            $existing->update($data);
        } else {
            PersentaseGaji::create($data);
        }
    }
}
