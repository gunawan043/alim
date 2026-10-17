<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryGatepass;
use App\Models\DormitoryResident;
use App\Models\DormitoryStaffAssignment;
use App\Models\RoomSupervisor;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DormitoryGatepassController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizeGatepass($request, $asramaUuid);

        $logs = DormitoryGatepass::with(['student', 'scanner'])
            ->where('dormitory_id', $asramaUuid)
            ->whereDate('scanned_at', today())
            ->latest('scanned_at')
            ->paginate(30);

        $stats = [
            'keluar' => DormitoryGatepass::where('dormitory_id', $asramaUuid)->whereDate('scanned_at', today())->where('type', 'keluar')->count(),
            'masuk' => DormitoryGatepass::where('dormitory_id', $asramaUuid)->whereDate('scanned_at', today())->where('type', 'masuk')->count(),
            'di_luar' => DormitoryGatepass::where('dormitory_id', $asramaUuid)->whereDate('scanned_at', today())
                ->where('type', 'keluar')
                ->whereNotIn('student_id', function ($q) use ($asramaUuid) {
                    $q->select('student_id')->from('dormitory_gatepasses')
                        ->where('dormitory_id', $asramaUuid)->whereDate('scanned_at', today())
                        ->where('type', 'masuk');
                })->distinct('student_id')->count('student_id'),
        ];

        return view('dormitory.gatepass.index', compact('dormitory', 'logs', 'stats', 'userId', 'asramaUuid'));
    }

    public function cards(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizeGatepass($request, $asramaUuid);
        $residents = DormitoryResident::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->room_id))
            ->orderBy('room_id')
            ->get();

        $tokens = $residents->mapWithKeys(fn ($r) => [
            $r->id => Crypt::encryptString($asramaUuid.'|'.$r->student_id),
        ])->all();

        return view('dormitory.gatepass.cards', compact('dormitory', 'residents', 'tokens', 'userId', 'asramaUuid'));
    }

    public function scan(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizeGatepass($request, $asramaUuid);

        return view('dormitory.gatepass.scan', compact('dormitory', 'userId', 'asramaUuid'));
    }

    public function process(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizeGatepass($request, $asramaUuid);

        $data = $request->validate([
            'token' => 'required|string',
            'purpose' => 'nullable|string|max:191',
            'gate' => 'nullable|string|max:100',
        ]);

        try {
            [$dormId, $studentId] = explode('|', Crypt::decryptString($data['token']), 2);
        } catch (\Throwable) {
            return back()->with('error', 'QR gatepass tidak valid.');
        }

        if ($dormId !== $asramaUuid) {
            return back()->with('error', 'QR ini bukan milik asrama ini.');
        }

        $resident = DormitoryResident::where('dormitory_id', $asramaUuid)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if (! $resident) {
            return back()->with('error', 'Santri bukan penghuni aktif asrama ini.');
        }

        $last = DormitoryGatepass::where('dormitory_id', $asramaUuid)
            ->where('student_id', $studentId)
            ->whereDate('scanned_at', today())
            ->latest('scanned_at')
            ->first();

        $type = ($last && $last->type === 'keluar') ? 'masuk' : 'keluar';

        DormitoryGatepass::create([
            'dormitory_id' => $asramaUuid,
            'student_id' => $studentId,
            'type' => $type,
            'purpose' => $data['purpose'] ?? null,
            'gate' => $data['gate'] ?? null,
            'scanned_by' => $request->user()->id,
            'scanned_at' => now(),
        ]);

        $label = $type === 'keluar' ? 'KELUAR' : 'MASUK';
        $name = $resident->student?->name ?? 'Santri';

        return redirect()->route('user.asrama.gatepass.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', "Gatepass {$label}: {$name}.");
    }

    private function authorizeGatepass(Request $request, string $asramaUuid): Dormitory
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $user = $request->user();

        $allowed = $user && (
            (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (string) $dormitory->head_id === (string) $user->id
            || DormitoryStaffAssignment::active()->forUser($user->id)->forDormitory($asramaUuid)->exists()
            || RoomSupervisor::active()->where('user_id', $user->id)->where('dormitory_id', $asramaUuid)->exists()
            || UserJabatan::hasAny($user, ['kepala asrama', 'wakil kepala asrama', 'wakil asrama', 'staf asrama', 'staff asrama', 'wali kamar', 'staf perizinan', 'staff perizinan'])
        );

        abort_unless($allowed, 403, 'Anda tidak memiliki akses gatepass asrama ini.');

        return $dormitory;
    }
}
