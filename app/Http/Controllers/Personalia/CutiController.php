<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\CutiRequest;
use App\Models\CutiTemplate;
use App\Models\CutiBalance;
use App\Models\CutiPeriod;
use App\Models\User;
use App\Services\HRDNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CutiController extends Controller
{
    public function __construct(private readonly HRDNotificationService $notif) {}

    public function index(Request $request, string $userId)
    {
        $query = CutiRequest::with(['user', 'template'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('jenis'), fn($q, $j) => $q->whereHas('template', fn($qq) => $qq->where('jenis', $j)));

        // Staff hanya lihat miliknya sendiri
        $currentUserId = Auth::id();
        $user = User::find($userId);
        $isOwnProfile = $currentUserId == $userId;

        if ($isOwnProfile) {
            $query->where('user_id', $userId);
        }

        $cutiRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Quota summary
        $period = CutiPeriod::where('is_active', true)->first();
        $quotas = $period
            ? CutiBalance::with('template')->where('user_id', $userId)->where('cuti_period_id', $period->id)->get()
            : collect();

        return view('personalia.cuti.index', compact('userId', 'cutiRequests', 'quotas', 'period'));
    }

    public function create(Request $request, string $userId)
    {
        $templates = CutiTemplate::where('is_active', true)->orderBy('urutan')->get();
        $period = CutiPeriod::where('is_active', true)->first();
        $quotas = $period
            ? CutiBalance::where('user_id', $userId)->where('cuti_period_id', $period->id)->get()->keyBy('cuti_template_id')
            : collect();

        return view('personalia.cuti.create', compact('userId', 'templates', 'period', 'quotas'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'cuti_template_id' => 'required|uuid|exists:cuti_templates,id',
            'tanggal_mulai'    => 'required|date|after_or_equal:today',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'nullable|string|max:1000',
        ]);

        $period = CutiPeriod::where('is_active', true)->first();
        $balance = CutiBalance::where('user_id', $userId)
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->where('cuti_template_id', $validated['cuti_template_id'])
            ->first();

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end   = Carbon::parse($validated['tanggal_selesai']);
        $jumlah = $start->diffInDays($end) + 1;

        if ($balance && $balance->tersisa < $jumlah) {
            return redirect()->back()->withInput()->with('error', 'Sisa quota tidak mencukupi. Tersisa: ' . $balance->tersisa . ' hari.');
        }

        DB::transaction(function () use ($validated, $userId, $jumlah, $balance, $period) {
            $cuti = CutiRequest::create([
                'user_id'          => $userId,
                'cuti_template_id' => $validated['cuti_template_id'],
                'cuti_period_id'   => $period?->id,
                'tanggal_mulai'    => $validated['tanggal_mulai'],
                'tanggal_selesai'  => $validated['tanggal_selesai'],
                'jumlah_hari'      => $jumlah,
                'alasan'           => $validated['alasan'] ?? null,
                'status'           => CutiRequest::STATUS_PENDING,
            ]);
            try { $this->notif->notifyCutiRequest($cuti); } catch (\Throwable $e) {}
        });

        return redirect()->route('user.cuti.index', $userId)
            ->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $cuti = CutiRequest::with(['user', 'template', 'period', 'approver', 'rejector'])
            ->where('id', $id)->firstOrFail();
        $sisaKuota = null;
        if ($cuti->cuti_period_id) {
            $sisaKuota = \App\Models\CutiBalance::where('user_id', $cuti->user_id)
                ->where('cuti_period_id', $cuti->cuti_period_id)
                ->where('cuti_template_id', $cuti->cuti_template_id)
                ->value('tersisa');
        }
        return view('personalia.cuti.show', compact('userId', 'cuti', 'sisaKuota'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $cuti = CutiRequest::where('id', $id)->where('user_id', $userId)->firstOrFail();
        if ($cuti->status !== CutiRequest::STATUS_PENDING) {
            return redirect()->route('user.cuti.index', $userId)->with('error', 'Cuti sudah diproses, tidak dapat diedit.');
        }
        $templates = CutiTemplate::where('is_active', true)->orderBy('urutan')->get();
        $period = CutiPeriod::where('is_active', true)->first();
        return view('personalia.cuti.edit', compact('userId', 'cuti', 'templates', 'period'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $cuti = CutiRequest::where('id', $id)->where('user_id', $userId)->firstOrFail();
        if ($cuti->status !== CutiRequest::STATUS_PENDING) {
            return redirect()->route('user.cuti.index', $userId)->with('error', 'Tidak dapat mengupdate cuti yang sudah diproses.');
        }

        $validated = $request->validate([
            'cuti_template_id' => 'required|uuid|exists:cuti_templates,id',
            'tanggal_mulai'    => 'required|date',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'nullable|string|max:1000',
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end   = Carbon::parse($validated['tanggal_selesai']);
        $jumlah = $start->diffInDays($end) + 1;

        $cuti->update([
            'cuti_template_id' => $validated['cuti_template_id'],
            'tanggal_mulai'    => $validated['tanggal_mulai'],
            'tanggal_selesai'  => $validated['tanggal_selesai'],
            'jumlah_hari'      => $jumlah,
            'alasan'           => $validated['alasan'] ?? null,
        ]);

        return redirect()->route('user.cuti.index', $userId)
            ->with('success', 'Data cuti berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $cuti = CutiRequest::where('id', $id)->where('user_id', $userId)->firstOrFail();
        if ($cuti->status !== CutiRequest::STATUS_PENDING) {
            return redirect()->route('user.cuti.index', $userId)->with('error', 'Tidak dapat menghapus cuti yang sudah diproses.');
        }
        $cuti->delete();
        return redirect()->route('user.cuti.index', $userId)->with('success', 'Pengajuan cuti berhasil dihapus.');
    }

    public function approval(Request $request, string $userId)
    {
        $pending = CutiRequest::with(['user', 'template'])
            ->where('status', CutiRequest::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('personalia.cuti.approval', compact('userId', 'pending'));
    }

    public function approve(Request $request, string $userId, string $id)
    {
        $cuti = CutiRequest::findOrFail($id);
        if ($cuti->status !== CutiRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Cuti sudah diproses.');
        }

        DB::transaction(function () use ($cuti, $userId) {
            $cuti->update([
                'status'       => CutiRequest::STATUS_APPROVED,
                'approved_at'  => now(),
                'approved_by'  => $userId,
                'approval_notes' => request('notes'),
            ]);

            // Kurangi saldo
            CutiBalance::where('user_id', $cuti->user_id)
                ->where('cuti_template_id', $cuti->cuti_template_id)
                ->decrement('tersisa', $cuti->jumlah_hari);
        });

        try { $this->notif->notifyCutiDecision($cuti, 'approved'); } catch (\Throwable $e) {}

        return redirect()->route('user.cuti.approval', $userId)
            ->with('success', 'Cuti berhasil disetujui.');
    }

    public function reject(Request $request, string $userId, string $id)
    {
        $validated = $request->validate(['rejection_reason' => 'required|string|max:500']);
        $cuti = CutiRequest::findOrFail($id);
        if ($cuti->status !== CutiRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Cuti sudah diproses.');
        }

        $cuti->update([
            'status'           => CutiRequest::STATUS_REJECTED,
            'rejected_at'      => now(),
            'rejected_by'      => $userId,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        try { $this->notif->notifyCutiDecision($cuti, 'rejected'); } catch (\Throwable $e) {}

        return redirect()->route('user.cuti.approval', $userId)
            ->with('success', 'Cuti berhasil ditolak.');
    }

    public function rekap(Request $request, string $userId)
    {
        $period = CutiPeriod::where('is_active', true)->first();

        $stats = CutiRequest::with('template')
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->selectRaw('status, COUNT(*) as total, SUM(jumlah_hari) as total_hari')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $byTemplate = CutiRequest::with('template')
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->selectRaw('cuti_template_id, COUNT(*) as total, SUM(jumlah_hari) as total_hari')
            ->groupBy('cuti_template_id')
            ->get();

        $recent = CutiRequest::with(['user', 'template'])
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('personalia.cuti.rekap', compact('userId', 'stats', 'byTemplate', 'recent', 'period'));
    }

    public function quota(Request $request, string $userId)
    {
        $period = CutiPeriod::where('is_active', true)->first();
        $balances = CutiBalance::with('template')
            ->where('user_id', $userId)
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->get();

        // All GTK balances untuk admin view
        $allBalances = CutiBalance::with(['template', 'user'])
            ->when($period, fn($q) => $q->where('cuti_period_id', $period->id))
            ->orderBy('user_id')
            ->get()
            ->groupBy('user_id');

        return view('personalia.cuti.quota', compact('userId', 'balances', 'allBalances', 'period'));
    }

    public function settings(Request $request, string $userId)
    {
        $templates = CutiTemplate::orderBy('urutan')->get();
        $periods = CutiPeriod::orderBy('start_date', 'desc')->get();
        return view('personalia.cuti.settings', compact('userId', 'templates', 'periods'));
    }

    public function settingsStore(Request $request, string $userId)
    {
        $type = $request->get('_type');

        if ($type === 'template') {
            $validated = $request->validate([
                'nama'          => 'required|string|max:100',
                'jenis'         => 'required|in:TAHUNAN,SAKIT,BESAR,LAINNYA',
                'jumlah_hari'   => 'required|integer|min:1|max:365',
                'paid'          => 'boolean',
                'deskripsi'     => 'nullable|string',
                'urutan'        => 'nullable|integer',
            ]);
            $validated['paid'] = $request->boolean('paid');
            CutiTemplate::create($validated);
        } elseif ($type === 'period') {
            $validated = $request->validate([
                'name'       => 'required|string|max:50',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after:start_date',
            ]);
            if ($request->boolean('set_active')) {
                CutiPeriod::query()->update(['is_active' => false]);
            }
            $validated['is_active'] = $request->boolean('set_active', false);
            CutiPeriod::create($validated);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function datatable(Request $request, string $userId)
    {
        $query = CutiRequest::with(['template', 'user'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s));

        if (Auth::id() != $userId) {
            $query->where('user_id', $userId);
        }

        return datatables()->of($query->orderBy('created_at', 'desc'))
            ->addColumn('gtk', fn($r) => $r->user?->name ?? '-')
            ->addColumn('jenis_cuti', fn($r) => $r->template?->nama ?? '-')
            ->addColumn('tanggal', fn($r) => $r->tanggal_mulai->format('d/m') . ' - ' . $r->tanggal_selesai->format('d/m/Y'))
            ->addColumn('status_badge', fn($r) => match($r->status) {
                'PENDING'   => '<span class="badge bg-warning-subtle text-warning">Menunggu</span>',
                'APPROVED'  => '<span class="badge bg-success-subtle text-success">Disetujui</span>',
                'REJECTED'  => '<span class="badge bg-danger-subtle text-danger">Ditolak</span>',
                default     => '<span class="badge bg-secondary-subtle">-</span>',
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}