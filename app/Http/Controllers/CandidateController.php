<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentProfile;
use App\Models\RecruitmentSkill;
use App\Models\RecruitmentEducation;
use App\Models\User;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('nik', 'like', "%{$search}%");
        }

        // Filter by education
        if ($request->has('pendidikan')) {
            $query->whereHas('educations', function($q) use ($request) {
                $q->where('jenjang', $request->pendidikan);
            });
        }

        // Filter by skill
        if ($request->has('skill')) {
            $query->whereHas('skills', function($q) use ($request) {
                $q->where('nama_skill', 'like', "%{$request->skill}%");
            });
        }

        // Filter by experience
        if ($request->has('pengalaman_min')) {
            $query->whereHas('workExperiences', function($q) use ($request) {
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

        if (!$cv) {
            return back()->with('error', 'CV tidak ditemukan');
        }

        $path = storage_path('app/public/' . $cv->file_path);

        if (!file_exists($path)) {
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
                'title' => 'Melamar sebagai ' . $app->recruitmentJob->judul,
                'description' => 'Status: ' . $app->status,
                'date' => $app->created_at,
                'icon' => 'ri-file-copy-line',
                'color' => 'primary'
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
                    'color' => 'info'
                ]);
            }
        }

        // Sort by date
        $timeline = $timeline->sortByDesc('date')->values();

        return response()->json([
            'success' => true,
            'data' => $timeline
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
            'tahun_pengalaman' => 'nullable|integer'
        ]);

        $skill = $candidate->skills()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Skill berhasil ditambahkan',
            'data' => $skill
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
            'message' => 'Skill berhasil dihapus'
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

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan tidak valid.'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas berhasil diverifikasi.',
            'data' => [
                'nik' => $candidate->nik ?? '-',
                'no_kk' => $candidate->no_kk ?? '-',
            ]
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
                'is_active' => true
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
                'status' => 'draft'
            ]);

            DB::commit();

            return redirect()->route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $profile->id])
                ->with('success', 'Kandidat berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah kandidat: ' . $e->getMessage());
        }
    }
}