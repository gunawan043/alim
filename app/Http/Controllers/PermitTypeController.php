<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\PermitType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PermitTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:permit_type_view')->only(['index', 'show', 'legacyIndex']);
        $this->middleware('can:permit_type_create')->only(['create', 'store']);
        $this->middleware('can:permit_type_edit')->only(['edit', 'update']);
        $this->middleware('can:permit_type_delete')->only(['destroy']);
        $this->middleware('can:permit_type_toggle_active')->only(['toggleActive']);
    }

    /**
     * Daftar semua jenis izin (API untuk UI).
     */
    public function index(Request $request): JsonResponse
    {
        // Handle query param: jika ada query "?ids=1,2,3", filter
        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $types = PermitType::whereIn('id', $ids)->get();
        } else {
            $types = PermitType::ordered()->get();
        }

        return response()->json([
            'data' => $types->map(fn (PermitType $t) => $this->serialize($t))->all(),
            'count' => $types->count(),
        ]);
    }

    /**
     * Satu jenis izin (untuk modal edit).
     */
    public function show(string $permitId): JsonResponse
    {
        $type = PermitType::findOrFail($permitId);

        return response()->json(['data' => $this->serialize($type)]);
    }

    /**
     * Daftar semua jenis izin (legacy untuk halaman dashboard).
     */
    public function legacyIndex(): JsonResponse
    {
        $types = PermitType::where('is_active', true)->ordered()->pluck('label', 'code');

        return response()->json(['data' => $types->toArray()]);
    }

    /**
     * Form create (rendered as modal di view index).
     */
    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        return view('dormitory.leave-policies.permit-type-form', [
            'mode' => 'create',
            'permitType' => new PermitType([
                'category' => 'custom',
                'is_active' => true,
                'color' => 'primary',
                'icon' => 'ri-file-list-3-line',
                'sort_order' => 100,
            ]),
        ]);
    }

    /**
     * Simpan jenis izin baru.
     */
    public function store(Request $request, string $userId, string $asramaUuid): JsonResponse|RedirectResponse
    {
        $data = $this->validateData($request);

        // Normalisasi code dari label bila tidak dikirim
        if (empty($data['code'])) {
            $data['code'] = PermitType::normalizeCode($data['label']);
        }

        // Pastikan code unik
        if (PermitType::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages([
                'code' => 'Kode sudah digunakan. Gunakan kode lain.',
            ]);
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        PermitType::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Jenis izin baru berhasil ditambahkan.']);
        }

        return redirect()
            ->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success_message', 'Jenis izin baru berhasil ditambahkan.');
    }

    /**
     * Form edit (rendered as modal di view index).
     */
    public function edit(Request $request, string $userId, string $asramaUuid, string $permitTypeId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $permitType = PermitType::findOrFail($permitTypeId);

        return view('dormitory.leave-policies.permit-type-form', [
            'mode' => 'edit',
            'permitType' => $permitType,
        ]);
    }

    /**
     * Update jenis izin.
     */
    public function update(Request $request, string $userId, string $asramaUuid, string $permitTypeId): JsonResponse|RedirectResponse
    {
        $permitType = PermitType::findOrFail($permitTypeId);

        $data = $this->validateData($request, $permitType);

        if (empty($data['code'])) {
            $data['code'] = PermitType::normalizeCode($data['label']);
        }

        if (PermitType::where('code', $data['code'])->where('id', '!=', $permitType->id)->exists()) {
            throw ValidationException::withMessages([
                'code' => 'Kode sudah digunakan. Gunakan kode lain.',
            ]);
        }

        $data['updated_by'] = auth()->id();

        $permitType->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Jenis izin berhasil diperbarui.']);
        }

        return redirect()
            ->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success_message', 'Jenis izin berhasil diperbarui.');
    }

    /**
     * Hapus jenis izin.
     *
     * Tidak melakukan cascade delete ke dormitory_leave_policies maupun
     * histori izin (dormitory_permits). Data lama tetap ada dengan
     * permit_type string code, dan saat ditampilkan akan difallback
     * ke label "Tidak diketahui" jika record master sudah dihapus.
     */
    public function destroy(Request $request, string $userId, string $asramaUuid, string $permitTypeId): JsonResponse|RedirectResponse
    {
        $permitType = PermitType::findOrFail($permitTypeId);

        // Hitung dampak:informasi untuk user (data orphan)
        $policyCount = \App\Models\DormitoryLeavePolicy::where('permit_type', $permitType->code)->count();
        $permitCount = \App\Models\DormitoryPermit::where('permit_type', $permitType->code)->count();

        $permitType->delete();

        $message = "Jenis izin '{$permitType->label}' telah dihapus.";
        if ($policyCount > 0 || $permitCount > 0) {
            $message .= " Data konfigurasi lama ({$policyCount} policy, {$permitCount} histori izin) tetap aman dan masih dapat dibaca.";
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success_message', $message);
    }

    /**
     * Toggle aktif/nonaktif jenis izin (AJAX).
     */
    public function toggleActive(Request $request, string $userId, string $asramaUuid, string $permitTypeId): JsonResponse
    {
        $permitType = PermitType::findOrFail($permitTypeId);
        $permitType->is_active = ! $permitType->is_active;
        $permitType->updated_by = auth()->id();
        $permitType->save();

        return response()->json([
            'success' => true,
            'is_active' => $permitType->is_active,
            'message' => $permitType->is_active
                ? "Jenis izin '{$permitType->label}' diaktifkan."
                : "Jenis izin '{$permitType->label}' dinonaktifkan.",
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function validateData(Request $request, ?PermitType $existing = null): array
    {
        $codeRule = ['nullable', 'string', 'max:50'];

        // Code unique hanya saat create atau code berubah
        if (! $existing || $request->input('code') !== $existing->code) {
            $codeRule[] = Rule::unique('permit_types', 'code');
        }

        $data = $request->validate([
            'code' => $codeRule,
            'label' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category' => ['required', 'string', Rule::in(PermitType::CATEGORIES)],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['is_active'] = $request->filled('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function serialize(PermitType $t): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'label' => $t->label,
            'description' => $t->description,
            'category' => $t->category,
            'icon' => $t->icon,
            'color' => $t->color,
            'is_active' => (bool) $t->is_active,
            'sort_order' => (int) $t->sort_order,
        ];
    }
}
