<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BankSoalController extends Controller
{
    /**
     * Display a paginated list of BankSoal for the current school context.
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = BankSoal::withCount(['soal' => fn ($q) => $q->where('status', 'approved')])
            ->with(['school', 'subject', 'owner', 'creator'])
            ->where('school_id', $schoolId);

        // Scope filtering: owner can see their own + public/internal_school banks
        Gate::authorize('viewAny', BankSoal::class);
        $query->where(function ($q) use ($userId, $schoolId) {
            $q->where('owner_user_id', $userId)
                ->orWhere('is_public', true)
                ->where(function ($q2) use ($schoolId) {
                    $q2->where('shared_scope', 'internal_school')
                        ->where('school_id', $schoolId);
                });
        });

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('jenis_soal')) {
            $query->where('jenis_soal', $request->jenis_soal);
        }
        if ($request->filled('shared_scope')) {
            $query->where('shared_scope', $request->shared_scope);
        }
        if ($request->filled('is_public') !== null) {
            $query->where('is_public', (bool) $request->is_public);
        }

        $banks = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();

        return view('evalusi.bank-soal.index', compact('banks', 'subjects', 'userId'));
    }

    /**
     * Show the form for creating a new BankSoal.
     */
    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // Get subjects for this school (or global if admin)
        $subjects = Subject::where('school_id', $schoolId)
            ->orWhereNull('school_id')
            ->orderBy('name')
            ->get();

        // Suggest current user as owner
        $currentUser = User::where('id', $userId)->first();

        // Available shared_scope options
        $sharedScopes = [
            'private' => 'Privat (hanya pemilik)',
            'internal_school' => 'Internal Sekolah',
            'public_pool' => 'Kolom Publik (global)',
        ];

        // Jenis soal options
        $jenisSoal = [
            'pilihan_ganda' => 'Pilihan Ganda',
            'multiple_choice_complex' => 'Multiple Choice Complex',
            'benar_salah' => 'Benar/Salah',
            'menjodohkan' => 'Menjodohkan',
            'isian_singkat' => 'Isian Singkat',
            'uraian' => 'Uraian',
            'campuran' => 'Campuran',
        ];

        // Tingkat kesulitan
        $tingkatKesulitan = [
            'mudah' => 'Mudah',
            'sedang' => 'Sedang',
            'sulit' => 'Sulit',
            'campuran' => 'Campuran',
        ];

        return view('evalusi.bank-soal.create', compact(
            'subjects',
            'currentUser',
            'sharedScopes',
            'jenisSoal',
            'tingkatKesulitan'
        ));
    }

    /**
     * Store a newly created BankSoal.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'nullable|string|max:5',
            'jenis_soal' => 'required|in:pilihan_ganda,multiple_choice_complex,benar_salah,menjodohkan,isian_singkat,uraian,campuran',
            'tingkat_kesulitan_target' => 'required|in:mudah,sedang,sulit,campuran',
            'is_public' => 'nullable|boolean',
            'shared_scope' => 'required|in:private,internal_school,public_pool',
            'allow_cross_teacher_clone' => 'nullable|boolean',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');

        $validated['school_id'] = $schoolId;
        $validated['owner_user_id'] = $userId;
        $validated['created_by'] = $userId;
        $validated['total_soal'] = 0;

        $bank = BankSoal::create($validated);

        // Attach Tujuan Pembelajaran if provided
        if ($request->filled('tp_ids') && is_array($request->tp_ids)) {
            $bank->tujuanPembelajaran()->sync($request->tp_ids);
        }

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Bank Soal berhasil dibuat.');
    }

    /**
     * Display a single BankSoal with its Soal list.
     */
    public function show(string $userId, string $id)
    {
        $bank = BankSoal::with(['school', 'subject', 'owner', 'creator', 'tujuanPembelajaran',
            'soal' => fn ($q) => $q->with('tujuanPembelajaran')
                ->orderBy('created_at')
                ->select('id', 'bank_soal_id', 'tipe_soal', 'pertanyaan',
                    'status', 'bobot_default', 'created_at')])
            ->findOrFail($id);

        return view('evalusi.bank-soal.show', compact('bank', 'userId'));
    }

    /**
     * Show the edit form for an existing BankSoal.
     */
    public function edit(string $userId, string $id)
    {
        $bank = BankSoal::findOrFail($id);

        // Authorization: only owner or school admin can edit
        Gate::authorize('update', $bank);

        $schoolId = request()->attributes->get('schoolContextId');

        $subjects = Subject::where('school_id', $schoolId)
            ->orWhereNull('school_id')
            ->orderBy('name')
            ->get();

        $sharedScopes = [
            'private' => 'Privat (hanya pemilik)',
            'internal_school' => 'Internal Sekolah',
            'public_pool' => 'Kolom Publik (global)',
        ];

        $jenisSoal = [
            'pilihan_ganda' => 'Pilihan Ganda',
            'multiple_choice_complex' => 'Multiple Choice Complex',
            'benar_salah' => 'Benar/Salah',
            'menjodohkan' => 'Menjodohkan',
            'isian_singkat' => 'Isian Singkat',
            'uraian' => 'Uraian',
            'campuran' => 'Campuran',
        ];

        $tingkatKesulitan = [
            'mudah' => 'Mudah',
            'sedang' => 'Sedang',
            'sulit' => 'Sulit',
            'campuran' => 'Campuran',
        ];

        return view('evalusi.bank-soal.edit', compact(
            'bank',
            'subjects',
            'sharedScopes',
            'jenisSoal',
            'tingkatKesulitan'
        ));
    }

    /**
     * Update an existing BankSoal.
     */
    public function update(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::findOrFail($id);

        Gate::authorize('update', $bank);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'nullable|string|max:5',
            'jenis_soal' => 'required|in:pilihan_ganda,multiple_choice_complex,benar_salah,menjodohkan,isian_singkat,uraian,campuran',
            'tingkat_kesulitan_target' => 'required|in:mudah,sedang,sulit,campuran',
            'is_public' => 'nullable|boolean',
            'shared_scope' => 'required|in:private,internal_school,public_pool',
            'allow_cross_teacher_clone' => 'nullable|boolean',
        ]);

        $bank->update($validated);

        // Sync Tujuan Pembelajaran
        if ($request->filled('tp_ids') && is_array($request->tp_ids)) {
            $bank->tujuanPembelajaran()->sync($request->tp_ids);
        } else {
            $bank->tujuanPembelajaran()->detach();
        }

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Bank Soal berhasil diperbarui.');
    }

    /**
     * Remove the specified BankSoal.
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::findOrFail($id);

        Gate::authorize('delete', $bank);

        $bank->delete();

        return redirect()
            ->route('user.bank-soal.index', ['userId' => $userId])
            ->with('success', 'Bank Soal berhasil dihapus.');
    }

    /**
     * API: Return Soal list within a BankSoal (JSON).
     */
    public function soalList(string $userId, string $bankId)
    {
        $soals = Soal::where('bank_soal_id', $bankId)
            ->orderBy('created_at')
            ->select('id', 'bank_soal_id', 'tipe_soal', 'pertanyaan', 'status', 'bobot_default')
            ->get();

        return response()->json($soals);
    }

    /**
     * API: Clone a BankSoal to another subject or reuse in same school.
     */
    public function clone(Request $request, string $userId, string $id)
    {
        $source = BankSoal::findOrFail($id);

        Gate::authorize('clone', $source);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'nama_baru' => 'required|string|max:150',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');

        $cloned = BankSoal::create([
            'school_id' => $schoolId,
            'subject_id' => $validated['subject_id'],
            'fase' => $source->fase,
            'nama' => $validated['nama_baru'],
            'deskripsi' => $source->deskripsi,
            'jenis_soal' => $source->jenis_soal,
            'tingkat_kesulitan_target' => $source->tingkat_kesulitan_target,
            'is_public' => false,
            'shared_scope' => 'private',
            'owner_user_id' => $userId,
            'created_by' => $userId,
            'total_soal' => 0,
        ]);

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $cloned->id])
            ->with('success', 'Bank Soal berhasil di-cloning.');
    }
}
