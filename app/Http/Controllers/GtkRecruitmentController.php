<?php

namespace App\Http\Controllers;

use App\Models\GtkRecruitment;
use App\Models\Jabatan;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GtkRecruitmentController extends Controller
{
    public function store(Request $request, ApprovalService $approvalService)
    {
        $data = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'jabatan' => ['required', 'string', 'max:150', Rule::exists('jabatan', 'nama')],
            'kebutuhan' => 'required|integer|min:1',
            'kualifikasi' => 'required|string',
            'tanggal_dibutuhkan' => 'required|date',
        ]);

        $recruitment = GtkRecruitment::create([
            ...$data,
            'created_by' => Auth::id(),
            'status' => 'submitted',
        ]);

        // START APPROVAL FLOW
        $approvalService->start($recruitment, 'recruitment_gtk');

        return response()->json([
            'message' => 'Pengajuan recruitment berhasil',
            'data' => $recruitment,
        ], 201);
    }

    /**
     * Display a paginated list of GTK recruitments with optional search/filter.
     */
    public function index(Request $request, string $userId)
    {
        $query = GtkRecruitment::with(['workUnit', 'createdBy'])
            ->latest();

        // Search by jabatan or kualifikasi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('jabatan', 'like', "%{$search}%")
                    ->orWhere('kualifikasi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by work unit
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }

        $recruitments = $query->paginate(20)->withQueryString();

        return view('gtk-recruitments.index', compact('recruitments', 'userId'));
    }

    /**
     * Show the form for creating a new GTK recruitment.
     */
    public function create(Request $request, string $userId)
    {
        $jenisGtk = \App\Models\JenisGtk::where('is_active', true)->orderBy('urutan')->get();
        $jabatan = Jabatan::where('is_active', true)->orderBy('urutan')->orderBy('nama')->get();

        return view('gtk-recruitments.create', compact('userId', 'jenisGtk', 'jabatan'));
    }

    /**
     * Display the specified GTK recruitment.
     */
    public function show(Request $request, string $userId, string $recruitmentUuid)
    {
        $recruitment = GtkRecruitment::with(['workUnit', 'createdBy'])
            ->findOrFail($recruitmentUuid);

        return view('gtk-recruitments.show', compact('recruitment', 'userId'));
    }

    /**
     * Show the form for editing the specified GTK recruitment.
     */
    public function edit(Request $request, string $userId, string $recruitmentUuid)
    {
        $recruitment = GtkRecruitment::findOrFail($recruitmentUuid);
        $jenisGtk = \App\Models\JenisGtk::where('is_active', true)->orderBy('urutan')->get();
        $jabatan = Jabatan::where('is_active', true)->orderBy('urutan')->orderBy('nama')->get();

        return view('gtk-recruitments.edit', compact('recruitment', 'userId', 'jenisGtk', 'jabatan'));
    }

    /**
     * Update the specified GTK recruitment in storage.
     */
    public function update(Request $request, string $userId, string $recruitmentUuid)
    {
        $recruitment = GtkRecruitment::findOrFail($recruitmentUuid);

        $validated = $request->validate([
            'work_unit_id' => 'sometimes|required|exists:work_units,id',
            'jabatan' => ['sometimes', 'required', 'string', 'max:150', Rule::exists('jabatan', 'nama')],
            'kebutuhan' => 'sometimes|required|integer|min:1',
            'kualifikasi' => 'sometimes|required|string',
            'tanggal_dibutuhkan' => 'sometimes|required|date',
        ]);

        $recruitment->update($validated);

        return redirect()->route('user.recruitment.index', [$userId])
            ->with('success', 'Data recruitment berhasil diperbarui.');
    }

    /**
     * Remove the specified GTK recruitment from storage.
     */
    public function destroy(Request $request, string $userId, string $recruitmentUuid)
    {
        $recruitment = GtkRecruitment::findOrFail($recruitmentUuid);
        $recruitment->delete();

        return redirect()->route('user.recruitment.index', [$userId])
            ->with('success', 'Data recruitment berhasil dihapus.');
    }
}
