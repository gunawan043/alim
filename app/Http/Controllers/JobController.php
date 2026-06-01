<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentJob;
use App\Models\WorkUnit;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of jobs.
     */
    public function index(Request $request, string $userId)
    {
        $query = RecruitmentJob::with(['workUnit', 'creator'])
            ->withCount('applications');

        // Search
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('judul', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_lowongan', 'like', '%' . $request->search . '%')
                  ->orWhere('posisi', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('jenis_pegawai')) {
            $query->where('jenis_pegawai', $request->jenis_pegawai);
        }

        // Filter by work unit
        if ($request->has('work_unit')) {
            $query->where('work_unit_id', $request->work_unit);
        }

        // Date range filter
        if ($request->has('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0], $dates[1]]);
            }
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get filter options
        $workUnits = WorkUnit::all();
        $statusCounts = [
            'all' => RecruitmentJob::count(),
            'aktif' => RecruitmentJob::where('status', 'aktif')->count(),
            'ditutup' => RecruitmentJob::where('status', 'ditutup')->count(),
            'draft' => RecruitmentJob::where('status', 'draft')->count(),
        ];

        return view('recruitment.jobs.index', compact('jobs', 'workUnits', 'statusCounts', 'userId'));
    }

    /**
     * Show form for creating new job.
     */
    public function create(string $userId)
    {
        $workUnits = WorkUnit::all();
        return view('recruitment.jobs.create', compact('workUnits', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'judul'                    => 'required|string|max:255',
            'posisi'                   => 'required|string|max:255',
            'work_unit_id'          => 'nullable|exists:work_units,uuid',
            'status_pegawai'           => 'nullable|in:tetap,kontrak,probation',
            'deskripsi_pekerjaan'      => 'required|string',
            'kuota'                    => 'required|integer|min:1',
            'tanggal_mulai'            => 'required|date',
            'tanggal_selesai'          => 'required|date|after_or_equal:tanggal_mulai',
            'status'                   => 'required|in:draft,aktif',
            // Gaji (array field di model)
            'rentang_gaji'             => 'nullable|array',
            'rentang_gaji.min'         => 'nullable|numeric|min:0',
            'rentang_gaji.max'         => 'nullable|numeric|min:0|gte:rentang_gaji.min',
            // Array fields dikirim sebagai JSON string dari JS
            'persyaratan_umum'         => 'nullable|string',
            'persyaratan_khusus'       => 'nullable|string',
            'kualifikasi_pendidikan'   => 'nullable|string',
            'kualifikasi_pengalaman'   => 'nullable|string',
            'kompetensi_dibutuhkan'    => 'nullable|array',
            'kompetensi_dibutuhkan.*'  => 'nullable|string|max:255',
            'fasilitas'                => 'nullable|string',
            // Tahapan seleksi dikirim sebagai array[] dari input
            'tahapan_seleksi'          => 'nullable|array',
            'tahapan_seleksi.*'        => 'nullable|string|max:255',
        ]);

        // Tentukan rentang_gaji: null jika toggle dimatikan (inputs disabled → tidak terkirim)
        $rentangGaji = null;
        if (!empty($request->input('rentang_gaji.min')) || !empty($request->input('rentang_gaji.max'))) {
            $rentangGaji = [
                'min' => $request->input('rentang_gaji.min'),
                'max' => $request->input('rentang_gaji.max'),
            ];
        }

        // Filter tahapan kosong
        $tahapan = collect($request->input('tahapan_seleksi', []))
            ->filter(fn($t) => !empty(trim($t)))
            ->values()
            ->toArray();

        $job = RecruitmentJob::create([
            'kode_lowongan'          => $this->generateKode(),
            'judul'                  => $validated['judul'],
            'posisi'                 => $validated['posisi'],
            'work_unit_id'           => $validated['work_unit_id'] ?? null,
            'jenis_pegawai'          => $validated['jenis_pegawai'] ?? null,
            'status_pegawai'         => $validated['status_pegawai'] ?? null,
            'deskripsi_pekerjaan'    => $validated['deskripsi_pekerjaan'],
            'kuota'                  => $validated['kuota'],
            'kuota_terisi'           => 0,
            'tanggal_mulai'          => $validated['tanggal_mulai'],
            'tanggal_selesai'        => $validated['tanggal_selesai'],
            'status'                 => $validated['status'],
            'rentang_gaji'           => $rentangGaji,
            'persyaratan_umum'       => $this->decodeJsonField($request->input('persyaratan_umum')),
            'persyaratan_khusus'     => $this->decodeJsonField($request->input('persyaratan_khusus')),
            'kualifikasi_pendidikan' => $this->decodeJsonField($request->input('kualifikasi_pendidikan')),
            'kualifikasi_pengalaman' => $this->decodeJsonField($request->input('kualifikasi_pengalaman')),
            'kompetensi_dibutuhkan'  => $this->decodeJsonField($request->input('kompetensi_dibutuhkan')),
            'fasilitas'              => $this->decodeJsonField($request->input('fasilitas')),
            'tahapan_seleksi'        => !empty($tahapan) ? $tahapan : null,
            'created_by'             => Auth::id(),
        ]);

        return redirect()
            ->route('user.ats.jobs.index', ['userId' => $userId])
            ->with('success', "Lowongan \"{$job->judul}\" berhasil dibuat.");
    }

    public function update(Request $request, string $userId, RecruitmentJob $job)
    {
        $validated = $request->validate([
            'judul'                    => 'required|string|max:255',
            'posisi'                   => 'required|string|max:255',
            'work_unit_id'             => 'nullable|exists:work_units,uuid',
            'jenis_pegawai'            => 'nullable|in:pns,pppk,honor,kontrak,magang',
            'status_pegawai'           => 'nullable|in:tetap,kontrak,probation',
            'deskripsi_pekerjaan'      => 'required|string',
            'kuota'                    => 'required|integer|min:1',
            'tanggal_mulai'            => 'required|date',
            'tanggal_selesai'          => 'required|date|after_or_equal:tanggal_mulai',
            'status'                   => 'required|in:draft,aktif,ditutup',
            'rentang_gaji'             => 'nullable|array',
            'rentang_gaji.min'         => 'nullable|numeric|min:0',
            'rentang_gaji.max'         => 'nullable|numeric|min:0|gte:rentang_gaji.min',
            'persyaratan_umum'         => 'nullable|string',
            'persyaratan_khusus'       => 'nullable|string',
            'kualifikasi_pendidikan'   => 'nullable|string',
            'kualifikasi_pengalaman'   => 'nullable|string',
            'kompetensi_dibutuhkan'    => 'nullable|array',
            'kompetensi_dibutuhkan.*'  => 'nullable|string|max:255',
            'fasilitas'                => 'nullable|string',
            'tahapan_seleksi'          => 'nullable|array',
            'tahapan_seleksi.*'        => 'nullable|string|max:255',
        ]);

        // Rentang gaji: null jika toggle off (disabled fields tidak terkirim)
        $rentangGaji = null;
        if (!empty($request->input('rentang_gaji.min')) || !empty($request->input('rentang_gaji.max'))) {
            $rentangGaji = [
                'min' => $request->input('rentang_gaji.min'),
                'max' => $request->input('rentang_gaji.max'),
            ];
        }

        $tahapan = collect($request->input('tahapan_seleksi', []))
            ->filter(fn($t) => !empty(trim($t)))
            ->values()
            ->toArray();

        $job->update([
            'judul'                  => $validated['judul'],
            'posisi'                 => $validated['posisi'],
            'work_unit_id'           => $validated['work_unit_id'] ?? null,
            'jenis_pegawai'          => $validated['jenis_pegawai'] ?? null,
            'status_pegawai'         => $validated['status_pegawai'] ?? null,
            'deskripsi_pekerjaan'    => $validated['deskripsi_pekerjaan'],
            'kuota'                  => $validated['kuota'],
            'tanggal_mulai'          => $validated['tanggal_mulai'],
            'tanggal_selesai'        => $validated['tanggal_selesai'],
            'status'                 => $validated['status'],
            'rentang_gaji'           => $rentangGaji,
            'persyaratan_umum'       => $this->decodeJsonField($request->input('persyaratan_umum'), $job->persyaratan_umum),
            'persyaratan_khusus'     => $this->decodeJsonField($request->input('persyaratan_khusus'), $job->persyaratan_khusus),
            'kualifikasi_pendidikan' => $this->decodeJsonField($request->input('kualifikasi_pendidikan'), $job->kualifikasi_pendidikan),
            'kualifikasi_pengalaman' => $this->decodeJsonField($request->input('kualifikasi_pengalaman'), $job->kualifikasi_pengalaman),
            'kompetensi_dibutuhkan'  => $this->decodeJsonField($request->input('kompetensi_dibutuhkan'), $job->kompetensi_dibutuhkan),
            'fasilitas'              => $this->decodeJsonField($request->input('fasilitas'), $job->fasilitas),
            'tahapan_seleksi'        => !empty($tahapan) ? $tahapan : null,
        ]);

        return redirect()
            ->route('user.ats.jobs.index', ['userId' => $userId])
            ->with('success', "Lowongan \"{$job->judul}\" berhasil diperbarui.");
    }

    public function destroy(string $userId, RecruitmentJob $job)
    {
        $judul = $job->judul;
        $job->delete(); // SoftDeletes

        return redirect()
            ->route('user.ats.jobs.index', ['userId' => $userId])
            ->with('success', "Lowongan \"{$judul}\" berhasil dihapus.");
    }

    // ─── Private Helpers ─────────────────────────────────────

    /**
     * Decode JSON string dari textarea yang sudah diproses JS.
     * Jika bukan JSON valid, kembalikan $fallback (nilai lama dari model).
     */
    private function decodeJsonField(?string $value, mixed $fallback = null): ?array
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        // Coba decode JSON (dikirim oleh JS parseTextarea())
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return empty($decoded) ? null : $decoded;
        }

        // Fallback: anggap sebagai teks biasa per baris (jika JS gagal)
        $lines = collect(explode("\n", $value))
            ->map(fn($l) => preg_replace('/^[-•]\s*/', '', trim($l)))
            ->filter()
            ->values()
            ->toArray();

        return !empty($lines) ? $lines : $fallback;
    }

    /**
     * Generate kode lowongan unik: LOW-2026-0001
     */
    private function generateKode(): string
    {
        $year  = now()->year;
        $count = RecruitmentJob::withTrashed()
                    ->whereYear('created_at', $year)
                    ->count() + 1;

        return sprintf('LOW-%d-%04d', $year, $count);
    }

    /**
     * Display job details.
     */
    public function show(string $userId, RecruitmentJob $job)
    {
        $job->load(['workUnit', 'creator', 'applications' => function($q) {
            $q->with('recruitmentProfile.user');
        }]);

        // Get application statistics
        $applicationStats = [
            'total' => $job->applications->count(),
            'menunggu' => $job->applications->where('status', 'menunggu_seleksi')->count(),
            'seleksi' => $job->applications->whereIn('status', ['seleksi_administrasi', 'tes_tertulis', 'wawancara'])->count(),
            'diterima' => $job->applications->where('status', 'diterima')->count(),
            'ditolak' => $job->applications->where('status', 'ditolak')->count(),
        ];

        return view('recruitment.jobs.show', compact('job', 'applicationStats', 'userId'));
    }

    /**
     * Show form for editing job.
     */
    public function edit(string $userId, RecruitmentJob $job)
    {
        $workUnits = WorkUnit::all();
        return view('recruitment.jobs.edit', compact('job', 'workUnits', 'userId'));
    }

    /**
     * Duplicate job.
     */
    public function duplicate(string $userId, RecruitmentJob $job)
    {
        try {
            DB::beginTransaction();

            $newJob = $job->replicate();
            $newJob->kode_lowongan = $this->generateJobCode();
            $newJob->judul = $job->judul . ' (Copy)';
            $newJob->status = 'draft';
            $newJob->kuota_terisi = 0;
            $newJob->created_at = now();
            $newJob->save();

            DB::commit();

            return redirect()->route('user.ats.jobs.edit', ['userId' => $userId, 'job' => $newJob->id])
                ->with('success', 'Lowongan berhasil digandakan. Silakan edit sesuai kebutuhan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menggandakan lowongan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle job status.
     */
    public function toggleStatus(string $userId, RecruitmentJob $job)
    {
        $job->status = $job->status == 'aktif' ? 'ditutup' : 'aktif';
        $job->closed_at = $job->status == 'ditutup' ? now() : null;
        $job->closed_reason = $job->status == 'ditutup' ? 'manual' : null;
        $job->save();

        $message = $job->status == 'aktif' ? 'Lowongan dibuka kembali.' : 'Lowongan ditutup.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $job->status
        ]);
    }

    /**
     * Show applications for a specific job.
     */
    public function applications(Request $request, string $userId, RecruitmentJob $job)
    {
        $query = $job->applications()
            ->with(['recruitmentProfile.user', 'recruitmentJob'])
            ->latest('tanggal_melamar');

        if ($search = $request->string('q')->toString()) {
            $query->whereHas('recruitmentProfile.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();

        $stats = [
            'total'      => $job->applications()->count(),
            'diterima'   => $job->applications()->where('status', 'diterima')->count(),
            'ditolak'    => $job->applications()->where('status', 'ditolak')->count(),
            'proses'     => $job->applications()->whereNotIn('status', ['diterima', 'ditolak'])->count(),
        ];

        return view('recruitment.jobs.applications', [
            'job'          => $job,
            'applications' => $applications,
            'stats'        => $stats,
            'userId'       => $userId,
        ]);
    }

    /**
     * Generate unique job code.
     */
    protected function generateJobCode()
    {
        $prefix = 'JOB';
        $year = date('Y');
        $month = date('m');
        
        $lastJob = RecruitmentJob::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = intval(substr($lastJob->kode_lowongan, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$year}{$month}{$newNumber}";
    }
}