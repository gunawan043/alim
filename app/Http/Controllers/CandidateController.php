<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentEducation;
use App\Models\RecruitmentProfile;
use App\Models\RecruitmentSkill;
use App\Models\User;
use App\Services\RecruitmentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    /**
     * Display a listing of candidates.
     */
    public function index(Request $request, string $userId)
    {
        $query = RecruitmentProfile::with(['user', 'educations', 'workExperiences', 'skills'])
            ->withCount('applications');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('nik', 'like', "%{$search}%");
        }

        // Filter by education
        if ($request->has('pendidikan')) {
            $query->whereHas('educations', function ($q) use ($request) {
                $q->where('jenjang', $request->pendidikan);
            });
        }

        // Filter by skill
        if ($request->has('skill')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('nama_skill', 'like', "%{$request->skill}%");
            });
        }

        // Filter by experience
        if ($request->has('pengalaman_min')) {
            $query->whereHas('workExperiences', function ($q) use ($request) {
                $q->havingRaw('SUM(lama_bekerja_bulan) >= ?', [$request->pengalaman_min * 12]);
            });
        }

        $candidates = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => RecruitmentProfile::count(),
            'terverifikasi' => RecruitmentProfile::whereNotNull('verified_at')->count(),
            'pending' => RecruitmentProfile::whereNull('verified_at')->count(),
            'ditolak' => RecruitmentProfile::where('status', 'ditolak')->count(),
        ];

        // Get filter options
        $pendidikanOptions = RecruitmentEducation::distinct('jenjang')->pluck('jenjang');
        $skillOptions = RecruitmentSkill::distinct('nama_skill')->limit(50)->pluck('nama_skill');

        return view('recruitment.candidates.index', compact('candidates', 'stats', 'pendidikanOptions', 'skillOptions', 'userId'));
    }

    /**
     * Show candidate profile.
     */
    public function show(string $userId, RecruitmentProfile $candidate)
    {
        $candidate->load([
            'user',
            'educations',
            'workExperiences',
            'skills',
            'trainings',
            'documents',
            'applications.recruitmentJob',
            'applications.stages',
        ]);

        // Sync dokumen dari recruitment.abuhurairah.id jika belum ada atau kadaluarsa
        // Sync hanya jika service configured dan belum sync dalam 1 jam
        if (RecruitmentDocumentService::isConfigured()) {
            $lastSync = $candidate->documents()->whereNotNull('synced_at')->max('synced_at');
            $shouldSync = ! $lastSync || now()->diffInHours($lastSync) >= 1;

            if ($shouldSync) {
                try {
                    RecruitmentDocumentService::syncToLocal($candidate->id);
                    // Reload documents setelah sync
                    $candidate->load('documents');
                } catch (\Exception $e) {
                    // Silent fail — dokumen yang sudah ada tetap ditampilkan
                    logger()->warning('Failed to sync documents from recruitment API', [
                        'profile_id' => $candidate->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return view('recruitment.candidates.show', compact('candidate', 'userId'));
    }

    /**
     * List all applications for a specific candidate.
     */
    public function candidateApplications(string $userId, RecruitmentProfile $candidate)
    {
        $candidate->load([
            'user',
            'applications.recruitmentJob',
            'applications.stages.recruitmentPipelineStage',
        ]);

        $applications = $candidate->applications()
            ->with(['recruitmentJob', 'stages.recruitmentPipelineStage'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('recruitment.candidates.applications', compact('candidate', 'applications', 'userId'));
    }

    /**
     * Download candidate CV.
     */
    public function downloadCv(string $userId, RecruitmentProfile $candidate)
    {
        $cv = $candidate->documents()->where('jenis_dokumen', 'cv')->first();

        if (! $cv) {
            return back()->with('error', 'CV tidak ditemukan');
        }

        // Jika dokumen dari external (recruitment.abuhurairah.id), redirect ke URL external
        if ($cv->is_external && $cv->external_url) {
            return redirect()->away($cv->external_url);
        }

        $path = storage_path('app/public/'.$cv->file_path);

        if (! file_exists($path)) {
            return back()->with('error', 'File CV tidak ditemukan');
        }

        return response()->download($path, $cv->nama_dokumen);
    }

    /**
     * Get candidate timeline.
     */
    public function timeline(string $userId, RecruitmentProfile $candidate)
    {
        $timeline = collect();

        // Applications
        foreach ($candidate->applications as $app) {
            $timeline->push([
                'type' => 'application',
                'title' => 'Melamar sebagai '.$app->recruitmentJob->judul,
                'description' => 'Status: '.$app->status,
                'date' => $app->created_at,
                'icon' => 'ri-file-copy-line',
                'color' => 'primary',
            ]);
        }

        // Status updates
        foreach ($candidate->applications as $app) {
            foreach ($app->stages as $stage) {
                $timeline->push([
                    'type' => 'stage',
                    'title' => $stage->nama_tahapan,
                    'description' => $stage->catatan,
                    'date' => $stage->created_at,
                    'icon' => 'ri-timeline-line',
                    'color' => 'info',
                ]);
            }
        }

        // Sort by date
        $timeline = $timeline->sortByDesc('date')->values();

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Add skill to candidate.
     */
    public function addSkill(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        $validated = $request->validate([
            'kategori' => 'required|in:teknis,non_teknis,bahasa,sertifikasi',
            'nama_skill' => 'required|string|max:255',
            'level' => 'nullable|string',
            'tahun_pengalaman' => 'nullable|integer',
        ]);

        $skill = $candidate->skills()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Skill berhasil ditambahkan',
            'data' => $skill,
        ]);
    }

    /**
     * Remove skill from candidate.
     */
    public function removeSkill(string $userId, RecruitmentProfile $candidate, string $skillId)
    {
        $skill = $candidate->skills()->findOrFail($skillId);
        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skill berhasil dihapus',
        ]);
    }

    /**
     * Verify password to reveal NIK and KK.
     */
    public function verifyPassword(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $candidate->user;

        if (! \Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan tidak valid.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas berhasil diverifikasi.',
            'data' => [
                'nik' => $candidate->nik ?? '-',
                'no_kk' => $candidate->no_kk ?? '-',
            ],
        ]);
    }

    /**
     * Create new candidate manually.
     */
    public function create(string $userId)
    {
        return view('recruitment.candidates.create', compact('userId'));
    }

    /**
     * Store new candidate.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nik' => 'nullable|string|unique:recruitment_profiles,nik',
            'no_hp' => 'nullable|string',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
            'agama' => 'nullable|string',
            'status_perkawinan' => 'nullable|string',
            'alamat' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'avatar' => 'default-avatar.jpg',
                'is_active' => true,
            ]);

            // Assign role
            $user->assignRole('candidate');

            // Create profile
            $profile = RecruitmentProfile::create([
                'user_id' => $user->id,
                'nik' => $validated['nik'] ?? null,
                'no_hp' => $validated['no_hp'] ?? null,
                'tempat_lahir' => $validated['tempat_lahir'] ?? null,
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                'agama' => $validated['agama'] ?? null,
                'status_perkawinan' => $validated['status_perkawinan'] ?? 'belum_kawin',
                'alamat_lengkap' => $validated['alamat'] ?? null,
                'status' => 'draft',
            ]);

            DB::commit();

            return redirect()->route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $profile->id])
                ->with('success', 'Kandidat berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menambah kandidat: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified candidate.
     */
    public function edit(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        $candidate->load(['user', 'educations', 'workExperiences', 'skills']);

        return view('recruitment.candidates.edit', compact('candidate', 'userId'));
    }

    /**
     * Update the specified candidate in storage.
     */
    public function update(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        $validated = $request->validate([
            'nik' => 'nullable|string|unique:recruitment_profiles,nik,'.$candidate->id,
            'no_kk' => 'nullable|string',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'nama_ibu_kandung' => 'nullable|string|max:255',
            'golongan_darah' => 'nullable|string|max:5',
            'jenis_kelamin' => 'nullable|in:L,P',
            'agama' => 'nullable|string|max:50',
            'status_perkawinan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:30',
            'no_whatsapp' => 'nullable|string|max:30',
            'kontak_darurat' => 'nullable|string|max:30',
            'hubungan_kontak_darurat' => 'nullable|string|max:50',
            'alamat_lengkap' => 'nullable|string',
            'rt_rw' => 'nullable|string|max:20',
            'kelurahan_desa' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:50',
        ]);

        try {
            $candidate->fill($validated);
            $candidate->save();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data kandidat berhasil diperbarui.',
                    'data' => $candidate->fresh(),
                ]);
            }

            return redirect()
                ->route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $candidate->id])
                ->with('success', 'Data kandidat berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('CandidateController@update failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui data kandidat.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui data kandidat.');
        }
    }

    /**
     * Remove the specified candidate from storage.
     */
    public function destroy(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        try {
            $candidateId = $candidate->id;
            $candidate->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kandidat berhasil dihapus.',
                ]);
            }

            return redirect()
                ->route('user.ats.candidates.index', ['userId' => $userId])
                ->with('success', 'Kandidat berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('CandidateController@destroy failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus kandidat.',
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus kandidat.');
        }
    }

    /**
     * Sync photo for a candidate (file upload).
     */
    public function syncPhoto(Request $request, string $userId, RecruitmentProfile $candidate)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        try {
            $candidateId = $candidate->id;
            $extension = $request->file('foto')->getClientOriginalExtension();
            $filename = $candidateId.'_'.time().'.'.$extension;
            $path = $request->file('foto')->storeAs(
                'recruitment/candidates/'.$candidateId,
                $filename,
                'public'
            );

            $candidate->foto_url_external = Storage::url($path);
            $candidate->save();

            return response()->json([
                'success' => true,
                'message' => 'Foto kandidat berhasil diperbarui.',
                'data' => [
                    'foto_url' => $candidate->foto_url_external,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CandidateController@syncPhoto failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah foto kandidat.',
            ], 500);
        }
    }

    /**
     * Sync dokumen kandidat dari recruitment.abuhurairah.id
     * Fetch data dokumen & foto via API lalu simpan/upsert ke recruitment_documents.
     */
    public function syncDocuments(string $candidate): \Illuminate\Http\RedirectResponse
    {
        try {
            $profile = RecruitmentProfile::with('documents')->where('id', $candidate)->firstOrFail();

            if (! $profile->external_id) {
                return back()->with('error', 'Kandidat ini belum terhubung ke recruitment.abuhurairah.id (external_id kosong).');
            }

            $service = app(\App\Services\RecruitmentDocumentService::class);
            $result = $service->syncDocumentsForProfile($profile);

            return back()->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Kandidat tidak ditemukan.');
        } catch (\Exception $e) {
            \Log::error('syncDocuments failed', ['candidate' => $candidate, 'error' => $e->getMessage()]);

            return back()->with('error', 'Gagal sinkronisasi dokumen: '.$e->getMessage());
        }
    }
}
