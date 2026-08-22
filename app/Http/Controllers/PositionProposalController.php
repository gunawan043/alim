<?php

namespace App\Http\Controllers;

use App\Models\GtkEmployment;
use App\Models\GtkPositionProposal;
use App\Models\Position;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class PositionProposalController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $isPersonalia = $currentUser->roles->pluck('name')->contains('Personalia');
        $isSuperAdmin = $currentUser->roles->pluck('name')->contains('Super Admin');

        $query = GtkPositionProposal::with(['user', 'proposer', 'proposedPosition', 'proposedSchool', 'reviewer']);

        if ($isPersonalia || $isSuperAdmin) {
            // Personalia & Super Admin see all proposals
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereHas('proposedSchool', fn ($q) => $q->where('id', $schoolId));
            }
        } else {
            // Kepala only see proposals made by them
            $query->byProposer($currentUser->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('proposal_type')) {
            $query->where('proposal_type', $request->proposal_type);
        }

        $proposals = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $canViewAll = $isPersonalia || $isSuperAdmin;
        $canApprove = $isPersonalia || $isSuperAdmin;

        return view('gtk-position-proposals.index', compact('proposals', 'canViewAll', 'canApprove'));
    }

    public function create(Request $request)
    {
        $currentUser = auth()->user();
        $currentUserJob = $currentUser->gtkEmployment?->jabatan;

        // Kepala roles only
        $isKepalaSP = in_array($currentUserJob, [
            'Kepala Satuan Pendidikan',
            'Kepala Sekolah',
        ]);
        $isKepalaDept = in_array($currentUserJob, [
            'Kepala Departemen Tahfidz',
            'Kepala Departemen Bahasa',
            'Kepala Departemen Kesiswaan',
            'Kepala Asrama',
        ]);

        abort_unless($isKepalaSP || $isKepalaDept, 403, 'Hanya Kepala Departemen/Satuan Pendidikan yang dapat mengajukan kenaikan jabatan.');

        $proposers = $this->getProposableUsers($request);
        $positions = Position::active()->orderBy('jenis_gtk_id')->orderBy('nama')->get();
        $proposalTypes = GtkPositionProposal::TYPE_LABELS;
        $schools = School::orderBy('name')->get();

        return view('gtk-position-proposals.create', compact('proposers', 'positions', 'proposalTypes', 'currentUserJob', 'schools'));
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();
        $currentUserJob = $currentUser->gtkEmployment?->jabatan;

        $isKepalaSP = in_array($currentUserJob, ['Kepala Satuan Pendidikan', 'Kepala Sekolah']);
        $isKepalaDept = in_array($currentUserJob, [
            'Kepala Departemen Tahfidz',
            'Kepala Departemen Bahasa',
            'Kepala Departemen Kesiswaan',
            'Kepala Asrama',
        ]);

        abort_unless($isKepalaSP || $isKepalaDept, 403, 'Hanya Kepala Departemen/Satuan Pendidikan yang dapat mengajukan kenaikan jabatan.');

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'current_employment_id' => 'nullable|exists:gtk_employments,id',
            'proposed_position_id' => 'nullable|exists:positions,id',
            'proposed_jabatan_text' => 'nullable|string|max:150',
            'proposed_school_id' => 'nullable|exists:schools,id',
            'proposed_work_unit' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:1000',
            'proposal_type' => 'required|in:'.implode(',', array_keys(GtkPositionProposal::TYPE_LABELS)),
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
        ]);

        GtkPositionProposal::create([
            'user_id' => $data['user_id'],
            'current_employment_id' => $data['current_employment_id'],
            'proposed_position_id' => $data['proposed_position_id'] ?? null,
            'proposed_jabatan_text' => $data['proposed_jabatan_text'] ?? null,
            'proposed_school_id' => $data['proposed_school_id'] ?? null,
            'proposed_work_unit' => $data['proposed_work_unit'] ?? null,
            'reason' => $data['reason'] ?? null,
            'proposal_type' => $data['proposal_type'],
            'status' => 'submitted',
            'proposed_by' => $currentUser->id,
            'proposer_role_at_submit' => $currentUserJob,
            'nomor_sk' => $data['nomor_sk'] ?? null,
            'tmt' => $data['tmt'] ?? null,
        ]);

        return redirect()->route('user.gtk-position-proposals.index')
            ->with('success', 'Pengajuan kenaikan jabatan berhasil dikirim.');
    }

    public function show(string $id)
    {
        $proposal = GtkPositionProposal::with([
            'user',
            'proposer',
            'proposedPosition',
            'proposedSchool',
            'reviewer',
            'currentEmployment',
        ])->findOrFail($id);

        $currentUser = auth()->user();
        $isPersonalia = $currentUser->roles->pluck('name')->contains('Personalia');
        $isSuperAdmin = $currentUser->roles->pluck('name')->contains('Super Admin');
        $isProposer = $proposal->proposed_by === $currentUser->id;

        $canView = $isPersonalia || $isSuperAdmin || $isProposer;
        $canApprove = $isPersonalia || $isSuperAdmin;

        if (! $canView) {
            abort(403, 'Akses ditolak.');
        }

        return view('gtk-position-proposals.show', compact('proposal', 'canApprove'));
    }

    public function approve(Request $request, string $id)
    {
        $currentUser = auth()->user();
        $isPersonalia = $currentUser->roles->pluck('name')->contains('Personalia');
        $isSuperAdmin = $currentUser->roles->pluck('name')->contains('Super Admin');

        abort_unless($isPersonalia || $isSuperAdmin, 403, 'Hanya Personalia atau Super Admin yang dapat menyetujui pengajuan.');

        $proposal = GtkPositionProposal::where('id', $id)
            ->where('status', 'submitted')
            ->firstOrFail();

        $data = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
        ]);

        $proposal->update([
            'status' => 'approved',
            'reviewed_by' => $currentUser->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'nomor_sk' => $data['nomor_sk'] ?? null,
            'tmt' => $data['tmt'] ?? null,
        ]);

        // Optionally update gtk_employments jabatan if position is linked
        if ($proposal->proposed_position_id && $proposal->current_employment_id) {
            GtkEmployment::where('id', $proposal->current_employment_id)
                ->update([
                    'jabatan' => $proposal->proposed_jabatan_text ?: $proposal->proposedPosition?->nama,
                    'jabatan_id' => $proposal->proposed_position_id,
                ]);
        }

        return redirect()->route('user.gtk-position-proposals.index')
            ->with('success', 'Pengajuan jabatan berhasil disetujui.');
    }

    public function reject(Request $request, string $id)
    {
        $currentUser = auth()->user();
        $isPersonalia = $currentUser->roles->pluck('name')->contains('Personalia');
        $isSuperAdmin = $currentUser->roles->pluck('name')->contains('Super Admin');

        abort_unless($isPersonalia || $isSuperAdmin, 403, 'Hanya Personalia atau Super Admin yang dapat menolak pengajuan.');

        $proposal = GtkPositionProposal::where('id', $id)
            ->where('status', 'submitted')
            ->firstOrFail();

        $data = $request->validate([
            'review_notes' => 'required|string|max:1000',
        ]);

        $proposal->update([
            'status' => 'rejected',
            'reviewed_by' => $currentUser->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        return redirect()->route('user.gtk-position-proposals.index')
            ->with('success', 'Pengajuan jabatan berhasil ditolak.');
    }

    public function cancel(string $id)
    {
        $currentUser = auth()->user();
        $proposal = GtkPositionProposal::where('id', $id)
            ->where('status', 'submitted')
            ->where('proposed_by', $currentUser->id)
            ->firstOrFail();

        $proposal->update(['status' => 'cancelled']);

        return redirect()->route('user.gtk-position-proposals.index')
            ->with('success', 'Pengajuan jabatan berhasil dibatalkan.');
    }

    private function getProposableUsers(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $currentUser = auth()->user();
        $currentUserJob = $currentUser->gtkEmployment?->jabatan;

        // Kepala SP can propose for users in their school
        // Kepala Departemen can propose for GTK in their department/school
        $query = User::whereHas('employments', function ($q) use ($currentUser, $request) {
            $q->where('school_id', $request->attributes->get('schoolContextId') ?? $currentUser->employments->first()?->school_id);
        })
            ->whereDoesntHave('employments', function ($q) {
                $q->where('jabatan', '=', null)
                    ->orWhereNull('jabatan');
            })
            ->orderBy('name')
            ->get();

        return $query;
    }
}
