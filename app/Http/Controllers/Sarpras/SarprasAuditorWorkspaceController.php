<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AuditDiscrepancy;
use App\Models\AuditSession;
use App\Models\Room;
use App\Services\Sarpras\AuditorWorkspaceService;
use App\Services\Sarpras\StateMachineRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Auditor Workspace — for the "auditor" role who runs stock opname and condition audits.
 * Thin wrapper over AuditorWorkspaceService.
 */
class SarprasAuditorWorkspaceController extends SarprasBaseController
{
    public function __construct(protected AuditorWorkspaceService $service) {}

    public function dashboard()
    {
        $sessions = AuditSession::with(['room', 'auditor'])
            ->latest()
            ->limit(20)
            ->get();

        $discrepancies = AuditDiscrepancy::where('resolved', false)->count();
        $rooms = Room::orderBy('room_name')->limit(50)->get();

        return view('sarpras.auditor.dashboard', compact('sessions', 'discrepancies', 'rooms'));
    }

    public function startSession(Request $request)
    {
        $data = $request->validate([
            'audit_type' => 'required|in:periodic,stock_opname,spot_check,surveillance',
            'target_room_id' => 'nullable|uuid',
        ]);

        $session = $this->service->startSession(Auth::user(), $data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'session' => $session]);
        }

        return redirect()->route('sarpras.auditor.session.show', $session->id);
    }

    public function showSession(string $sessionId)
    {
        $session = AuditSession::with(['room', 'auditor', 'audits.asset', 'audits.physicalRoom'])
            ->findOrFail($sessionId);

        $progress = $this->service->progress($session);
        $audits = $session->audits;

        return view('sarpras.auditor.show', compact('session', 'progress', 'audits'));
    }

    public function markFound(Request $request, string $sessionId, string $assetId): JsonResponse
    {
        $session = AuditSession::findOrFail($sessionId);
        StateMachineRegistry::assertValidTransition('stock_opname_session', $session->status, 'in_progress');
        $result = $this->service->markFound($session, $assetId, $request->all());

        return response()->json(['success' => true, 'result' => $result]);
    }

    public function markMissing(Request $request, string $sessionId, string $assetId): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $session = AuditSession::findOrFail($sessionId);
        $result = $this->service->markMissing($session, $assetId, $data);

        return response()->json(['success' => true, 'result' => $result]);
    }

    public function updateCondition(Request $request, string $sessionId, string $assetId): JsonResponse
    {
        $data = $request->validate([
            'condition' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $session = AuditSession::findOrFail($sessionId);
        $result = $this->service->updateCondition($session, $assetId, $data);

        return response()->json(['success' => true, 'asset' => $result]);
    }

    public function closeSession(Request $request, string $sessionId): RedirectResponse|JsonResponse
    {
        $session = AuditSession::findOrFail($sessionId);
        StateMachineRegistry::assertValidTransition('stock_opname_session', $session->status, 'closed');
        $closed = $this->service->closeSession($session, Auth::user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'session' => $closed]);
        }

        return redirect()->route('sarpras.auditor.dashboard')->with('success', 'Session audit ditutup.');
    }

    public function progress(string $sessionId): JsonResponse
    {
        $session = AuditSession::findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'progress' => $this->service->progress($session),
        ]);
    }
}
