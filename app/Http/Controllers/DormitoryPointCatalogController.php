<?php

namespace App\Http\Controllers;

use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryPointRule;
use App\Models\Dormitory;
use Illuminate\Http\Request;

class DormitoryPointCatalogController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $query = DormitoryPointCatalog::query();
        if ($request->filled('kind')) $query->where('kind', $request->kind);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
        }
        return view('dormitory.point-catalog.index', [
            'dormitory' => $dormitory, 'userId' => $userId,
            'catalogs' => $query->orderBy('kind')->orderBy('sort_order')->paginate(50)->withQueryString(),
            'rules' => DormitoryPointRule::orderBy('min_points')->get(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $this->authorizeManager();
        $data = $request->validate([
            'kind' => 'required|in:violation,reward', 'code' => 'required|string|max:30|unique:dormitory_point_catalogs,code',
            'name' => 'required|string|max:191', 'category' => 'required|string|max:80',
            'severity' => 'nullable|string|max:30', 'points_min' => 'required|integer|min:0|max:9999',
            'points_max' => 'nullable|integer|min:0|max:9999', 'is_permanent' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0', 'description' => 'nullable|string',
        ]);
        $data['is_active'] = true; $data['is_permanent'] = $request->boolean('is_permanent');
        DormitoryPointCatalog::create($data);
        return back()->with('success', 'Katalog poin berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $catalogUuid)
    {
        $this->authorizeManager();
        $catalog = DormitoryPointCatalog::findOrFail($catalogUuid);
        $data = $request->validate([
            'name' => 'required|string|max:191', 'category' => 'required|string|max:80',
            'severity' => 'nullable|string|max:30', 'points_min' => 'required|integer|min:0|max:9999',
            'points_max' => 'nullable|integer|min:0|max:9999', 'is_permanent' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0', 'description' => 'nullable|string',
        ]);
        $data['is_permanent'] = $request->boolean('is_permanent');
        $catalog->update($data);
        return back()->with('success', 'Katalog poin diperbarui.');
    }

    public function toggle(Request $request, string $userId, string $asramaUuid, string $catalogUuid)
    {
        $this->authorizeManager();
        $catalog = DormitoryPointCatalog::findOrFail($catalogUuid);
        $catalog->update(['is_active' => ! $catalog->is_active]);
        return back()->with('success', 'Status katalog diperbarui.');
    }

    private function canManage(): bool
    {
        $u = request()->user();
        return $u && (($u->isSystemAdmin() ?? false) || ($u->isSuperAdmin() ?? false));
    }
    private function authorizeManager(): void { abort_unless($this->canManage(), 403, 'Hanya Superadmin yang dapat mengubah katalog poin.'); }
}
