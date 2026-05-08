<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkUnit;
use App\Models\GtkCareerPath;
use App\Models\GtkTransferRequest;
use App\Models\PromosiDemosi;
use App\Models\TalentPool;
use App\Models\SuccessionPlan;
use App\Models\SuccessionPlanKandidat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JenjangKarirController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1. CAREER PATH
    |--------------------------------------------------------------------------
    */

    public function careerPathIndex(Request $request)
    {
        $query = GtkCareerPath::with('user.gtkProfile')
            ->when($request->search, fn($q) => $q->where('jabatan_fungsi', 'like', "%{$request->search}%"))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->orderByDesc('tmt');

        $careerPaths = $query->paginate(15)->withQueryString();
        $gtkList = User::whereHas('employment')->orderBy('name')->get();

        return view('jenjang-karir.career-path', compact('careerPaths', 'gtkList'));
    }

    public function careerPathStore(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'jabatan_fungsi' => 'required|string|max:150',
            'nomor_sk'       => 'nullable|string|max:100',
            'tmt'            => 'nullable|date',
            'tst'            => 'nullable|date|after_or_equal:tmt',
        ]);

        GtkCareerPath::create($validated);

        return back()->with('success', 'Data career path berhasil disimpan.');
    }

    public function careerPathUpdate(Request $request, $id)
    {
        $careerPath = GtkCareerPath::findOrFail($id);

        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'jabatan_fungsi' => 'required|string|max:150',
            'nomor_sk'       => 'nullable|string|max:100',
            'tmt'            => 'nullable|date',
            'tst'            => 'nullable|date|after_or_equal:tmt',
        ]);

        $careerPath->update($validated);

        return back()->with('success', 'Data career path berhasil diperbarui.');
    }

    public function careerPathDestroy($id)
    {
        GtkCareerPath::findOrFail($id)->delete();
        return back()->with('success', 'Data career path berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2. MUTASI & ROTASI
    |--------------------------------------------------------------------------
    */

    public function mutasiIndex(Request $request)
    {
        $query = GtkTransferRequest::with([
            'user.gtkProfile',
            'fromWorkUnit',
            'toWorkUnit',
            'requestedByUser',
        ])
        ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->orderByDesc('created_at');

        $mutasi = $query->paginate(15)->withQueryString();
        $gtkList = User::whereHas('employment')->orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();

        return view('jenjang-karir.mutasi-rotasi', compact('mutasi', 'gtkList', 'workUnits'));
    }

    public function mutasiStore(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'from_work_unit_id'=> 'nullable|exists:work_units,id',
            'to_work_unit_id'  => 'required|exists:work_units,id',
            'jabatan'          => 'nullable|string|max:191',
            'reason'           => 'nullable|string',
        ]);

        $validated['status']       = 'PENDING';
        $validated['requested_by'] = Auth::id();
        $validated['request_ip']   = request()->ip();
        $validated['request_user_agent'] = request()->userAgent();

        GtkTransferRequest::create($validated);

        return back()->with('success', 'Permintaan mutasi/rotasi berhasil diajukan.');
    }

    public function mutasiUpdate(Request $request, $id)
    {
        $mutasi = GtkTransferRequest::findOrFail($id);

        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'from_work_unit_id'=> 'nullable|exists:work_units,id',
            'to_work_unit_id'  => 'required|exists:work_units,id',
            'jabatan'          => 'nullable|string|max:191',
            'reason'           => 'nullable|string',
            'status'           => 'required|in:PENDING,APPROVED,REJECTED,CANCELLED',
        ]);

        $mutasi->update($validated);

        return back()->with('success', 'Data mutasi/rotasi berhasil diperbarui.');
    }

    public function mutasiApprove(Request $request, $id)
    {
        $mutasi = GtkTransferRequest::findOrFail($id);
        $request->validate(['approval_note' => 'nullable|string']);

        $mutasi->approve(Auth::id(), $request->approval_note);

        return back()->with('success', 'Mutasi/rotasi telah disetujui.');
    }

    public function mutasiReject(Request $request, $id)
    {
        $mutasi = GtkTransferRequest::findOrFail($id);
        $request->validate(['approval_note' => 'nullable|string']);

        $mutasi->reject(Auth::id(), $request->approval_note);

        return back()->with('success', 'Mutasi/rotasi telah ditolak.');
    }

    public function mutasiDestroy($id)
    {
        GtkTransferRequest::findOrFail($id)->delete();
        return back()->with('success', 'Data mutasi/rotasi berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | 3. PROMOSI & DEMOSI
    |--------------------------------------------------------------------------
    */

    public function promosiIndex(Request $request)
    {
        $query = PromosiDemosi::with(['user.gtkProfile', 'dibuatOleh'])
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->when($request->jenis, fn($q) => $q->where('jenis', $request->jenis))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('tmt');

        $promosiList = $query->paginate(15)->withQueryString();
        $gtkList = User::whereHas('employment')->orderBy('name')->get();

        return view('jenjang-karir.promosi-demosi', compact('promosiList', 'gtkList'));
    }

    public function promosiStore(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'jenis'          => 'required|in:promosi,demosi',
            'jabatan_lama'   => 'nullable|string|max:150',
            'jabatan_baru'   => 'required|string|max:150',
            'unit_kerja_lama'=> 'nullable|string|max:191',
            'unit_kerja_baru'=> 'nullable|string|max:191',
            'nomor_sk'       => 'nullable|string|max:100',
            'tanggal_sk'     => 'nullable|date',
            'tmt'            => 'required|date',
            'alasan'         => 'nullable|string',
        ]);

        $validated['status']     = 'draft';
        $validated['dibuat_oleh'] = Auth::id();

        PromosiDemosi::create($validated);

        return back()->with('success', 'Data promosi/demosi berhasil disimpan.');
    }

    public function promosiUpdate(Request $request, $id)
    {
        $promosi = PromosiDemosi::findOrFail($id);

        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'jenis'          => 'required|in:promosi,demosi',
            'jabatan_lama'   => 'nullable|string|max:150',
            'jabatan_baru'   => 'required|string|max:150',
            'unit_kerja_lama'=> 'nullable|string|max:191',
            'unit_kerja_baru'=> 'nullable|string|max:191',
            'nomor_sk'       => 'nullable|string|max:100',
            'tanggal_sk'     => 'nullable|date',
            'tmt'            => 'required|date',
            'alasan'         => 'nullable|string',
            'status'         => 'required|in:draft,diajukan,disetujui,ditolak',
        ]);

        $promosi->update($validated);

        return back()->with('success', 'Data promosi/demosi berhasil diperbarui.');
    }

    public function promosiDestroy($id)
    {
        PromosiDemosi::findOrFail($id)->delete();
        return back()->with('success', 'Data promosi/demosi berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | 4. TALENT POOL
    |--------------------------------------------------------------------------
    */

    public function talentPoolIndex(Request $request)
    {
        $query = TalentPool::with(['user.gtkProfile', 'dinominasikanOleh'])
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->when($request->kategori, fn($q) => $q->where('kategori', $request->kategori))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('tanggal_masuk_pool');

        $talentList = $query->paginate(15)->withQueryString();
        $gtkList = User::whereHas('employment')->orderBy('name')->get();

        return view('jenjang-karir.talent-pool', compact('talentList', 'gtkList'));
    }

    public function talentPoolStore(Request $request)
    {
        $validated = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'kategori'             => 'required|in:high_potential,high_performer,key_talent,emerging_talent',
            'skor_potensi'         => 'nullable|integer|min:0|max:100',
            'skor_kinerja'         => 'nullable|integer|min:0|max:100',
            'kompetensi_unggulan'  => 'nullable|string',
            'area_pengembangan'    => 'nullable|string',
            'jabatan_target'       => 'nullable|string|max:191',
            'estimasi_siap_tahun'  => 'nullable|integer|min:0|max:10',
            'tanggal_masuk_pool'   => 'required|date',
            'catatan'              => 'nullable|string',
        ]);

        $validated['status']           = 'aktif';
        $validated['dinominasikan_oleh'] = Auth::id();

        TalentPool::create($validated);

        return back()->with('success', 'Talent berhasil ditambahkan ke pool.');
    }

    public function talentPoolUpdate(Request $request, $id)
    {
        $talent = TalentPool::findOrFail($id);

        $validated = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'kategori'             => 'required|in:high_potential,high_performer,key_talent,emerging_talent',
            'status'               => 'required|in:aktif,tidak_aktif,dipromosikan,keluar',
            'skor_potensi'         => 'nullable|integer|min:0|max:100',
            'skor_kinerja'         => 'nullable|integer|min:0|max:100',
            'kompetensi_unggulan'  => 'nullable|string',
            'area_pengembangan'    => 'nullable|string',
            'jabatan_target'       => 'nullable|string|max:191',
            'estimasi_siap_tahun'  => 'nullable|integer|min:0|max:10',
            'tanggal_masuk_pool'   => 'required|date',
            'tanggal_keluar_pool'  => 'nullable|date|after_or_equal:tanggal_masuk_pool',
            'catatan'              => 'nullable|string',
        ]);

        $talent->update($validated);

        return back()->with('success', 'Data talent pool berhasil diperbarui.');
    }

    public function talentPoolDestroy($id)
    {
        TalentPool::findOrFail($id)->delete();
        return back()->with('success', 'Talent berhasil dihapus dari pool.');
    }

    /*
    |--------------------------------------------------------------------------
    | 5. SUCCESSION PLAN
    |--------------------------------------------------------------------------
    */

    public function successionIndex(Request $request)
    {
        $query = SuccessionPlan::with(['pemegangJabatan.gtkProfile', 'kandidat.user.gtkProfile', 'dibuatOleh'])
            ->when($request->search, fn($q) => $q->where('jabatan_kunci', 'like', "%{$request->search}%"))
            ->when($request->urgensi, fn($q) => $q->where('urgensi', $request->urgensi))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('urgensi', 'desc')
            ->orderByDesc('created_at');

        $successionList = $query->paginate(15)->withQueryString();
        $gtkList = User::whereHas('employment')->orderBy('name')->get();

        return view('jenjang-karir.succession-plan', compact('successionList', 'gtkList'));
    }

    public function successionStore(Request $request)
    {
        $validated = $request->validate([
            'jabatan_kunci'           => 'required|string|max:191',
            'unit_kerja'              => 'nullable|string|max:191',
            'pemegang_jabatan_id'     => 'nullable|exists:users,id',
            'perkiraan_kekosongan'    => 'nullable|date',
            'urgensi'                 => 'required|in:rendah,sedang,tinggi,kritis',
            'deskripsi_jabatan'       => 'nullable|string',
            'persyaratan_kompetensi'  => 'nullable|string',
            'catatan'                 => 'nullable|string',
        ]);

        $validated['status']     = 'aktif';
        $validated['dibuat_oleh'] = Auth::id();

        SuccessionPlan::create($validated);

        return back()->with('success', 'Succession plan berhasil dibuat.');
    }

    public function successionUpdate(Request $request, $id)
    {
        $plan = SuccessionPlan::findOrFail($id);

        $validated = $request->validate([
            'jabatan_kunci'           => 'required|string|max:191',
            'unit_kerja'              => 'nullable|string|max:191',
            'pemegang_jabatan_id'     => 'nullable|exists:users,id',
            'perkiraan_kekosongan'    => 'nullable|date',
            'urgensi'                 => 'required|in:rendah,sedang,tinggi,kritis',
            'status'                  => 'required|in:aktif,selesai,dibatalkan',
            'deskripsi_jabatan'       => 'nullable|string',
            'persyaratan_kompetensi'  => 'nullable|string',
            'catatan'                 => 'nullable|string',
        ]);

        $plan->update($validated);

        return back()->with('success', 'Succession plan berhasil diperbarui.');
    }

    public function successionDestroy($id)
    {
        SuccessionPlan::findOrFail($id)->delete();
        return back()->with('success', 'Succession plan berhasil dihapus.');
    }

    public function successionKandidatStore(Request $request, $planId)
    {
        $plan = SuccessionPlan::findOrFail($planId);

        $validated = $request->validate([
            'user_id'             => 'required|exists:users,id',
            'kesiapan'            => 'required|in:siap_sekarang,siap_1_2_tahun,siap_3_5_tahun',
            'skor_kesiapan'       => 'nullable|integer|min:0|max:100',
            'prioritas'           => 'required|integer|min:1',
            'kekuatan'            => 'nullable|string',
            'area_pengembangan'   => 'nullable|string',
            'rencana_pengembangan'=> 'nullable|string',
        ]);

        $validated['succession_plan_id'] = $plan->id;
        $validated['status'] = 'aktif';

        SuccessionPlanKandidat::create($validated);

        return back()->with('success', 'Kandidat berhasil ditambahkan.');
    }

    public function successionKandidatDestroy($id)
    {
        SuccessionPlanKandidat::findOrFail($id)->delete();
        return back()->with('success', 'Kandidat berhasil dihapus.');
    }
}
