<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryVisitorRestriction;
use Illuminate\Http\Request;

class DormitoryVisitorRestrictionController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizedDormitory($asramaUuid);

        $query = DormitoryVisitorRestriction::with('creator')
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('search')) {
            $q = trim((string) $request->search);
            $query->where(fn ($sq) => $sq
                ->where('visitor_name', 'like', "%{$q}%")
                ->orWhere('id_number', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return view('dormitory.visitor-restrictions.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'restrictions' => $query->latest()->paginate(20)->withQueryString(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $this->authorizeManager();
        $dormitory = $this->authorizedDormitory($asramaUuid);

        $data = $request->validate([
            'visitor_name' => 'required|string|max:191',
            'id_number' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'reason' => 'required|string|max:1000',
        ]);

        DormitoryVisitorRestriction::create([
            'dormitory_id' => $asramaUuid,
            'visitor_name' => $data['visitor_name'],
            'id_number' => $data['id_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'reason' => $data['reason'],
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tamu masuk daftar blacklist asrama.');
    }

    public function toggle(Request $request, string $userId, string $asramaUuid, string $restrictionUuid)
    {
        $this->authorizeManager();
        $restriction = DormitoryVisitorRestriction::where('dormitory_id', $asramaUuid)->findOrFail($restrictionUuid);
        $restriction->update(['is_active' => ! $restriction->is_active]);

        return back()->with('success', $restriction->is_active ? 'Blacklist diaktifkan kembali.' : 'Blacklist dinonaktifkan.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $restrictionUuid)
    {
        $this->authorizeManager();
        $restriction = DormitoryVisitorRestriction::where('dormitory_id', $asramaUuid)->findOrFail($restrictionUuid);
        $restriction->delete();

        return back()->with('success', 'Data blacklist dihapus.');
    }

    private function authorizedDormitory(string $asramaUuid): Dormitory
    {
        $user = request()->user();

        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        return Dormitory::findOrFail($asramaUuid);
    }

    private function canManage(): bool
    {
        $user = request()->user();

        return $user
            && app(\App\Services\Boarding\DormitoryPolicyAccess::class)
                ->canManageQuota($user, (string) request()->route('asramaUuid'));
    }

    private function authorizeManager(): void
    {
        abort_unless($this->canManage(), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengelola blacklist tamu.');
    }
}
