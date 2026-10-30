<?php

namespace App\Http\Controllers;

use App\Models\GtkEducation;
use App\Models\User;
use App\Support\GtkAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD riwayat pendidikan GTK.
 *
 * Semua aksi dibatasi: pemilik data (self-service) atau pengelola GTK.
 * Hanya field yang diizinkan yang ditulis (tidak memakai $request->all())
 * dan `user_id` tidak pernah bisa diubah lewat update.
 */
class GTKEducationController extends Controller
{
    private const WRITABLE_FIELDS = [
        'jenjang_pendidikan',
        'nama_satuan_pendidikan',
        'jurusan',
        'fakultas',
        'tahun_masuk',
        'tahun_lulus',
        'no_ijazah',
        'nama_kepala_sekolah',
        'nama_rektor',
        'nilai_akhir',
        'skala_nilai',
        'status',
        'keterangan',
        'urutan',
    ];

    public function store(Request $request, string $userId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'jenjang_pendidikan' => 'required|string|max:20',
            'nama_satuan_pendidikan' => 'required|string|max:255',
            'jurusan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'tahun_lulus' => 'required|numeric',
            'no_ijazah' => 'nullable|string|max:100',
            'nama_kepala_sekolah' => 'nullable|string|max:255',
            'nama_rektor' => 'nullable|string|max:255',
            'nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'skala_nilai' => 'nullable|string|max:10',
            'status' => 'required|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH',
            'keterangan' => 'nullable|string|max:1000',
            'urutan' => 'nullable|integer|min:0|max:100',
        ]);

        $targetUserId = (string) ($validated['user_id'] ?? $request->user()->id);

        $this->authorizeManage($request, $targetUserId);

        $education = GtkEducation::create([
            'user_id' => $targetUserId,
            ...array_intersect_key($validated, array_flip(self::WRITABLE_FIELDS)),
        ]);

        return response()->json([
            'success' => true,
            'data' => $education,
        ]);
    }

    public function show(Request $request, string $userId, string $id): JsonResponse
    {
        $education = GtkEducation::findOrFail($id);

        $this->authorizeView($request, (string) $education->user_id);

        return response()->json($education);
    }

    public function update(Request $request, string $userId, string $id): JsonResponse
    {
        $education = GtkEducation::findOrFail($id);

        $this->authorizeManage($request, (string) $education->user_id);

        $validated = $request->validate([
            'jenjang_pendidikan' => 'sometimes|required|string|max:20',
            'nama_satuan_pendidikan' => 'sometimes|required|string|max:255',
            'jurusan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'tahun_lulus' => 'sometimes|required|numeric',
            'no_ijazah' => 'nullable|string|max:100',
            'nama_kepala_sekolah' => 'nullable|string|max:255',
            'nama_rektor' => 'nullable|string|max:255',
            'nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'skala_nilai' => 'nullable|string|max:10',
            'status' => 'sometimes|required|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH',
            'keterangan' => 'nullable|string|max:1000',
            'urutan' => 'nullable|integer|min:0|max:100',
        ]);

        // `user_id` sengaja tidak ikut: kepemilikan data tidak boleh dipindah.
        $education->update(array_intersect_key($validated, array_flip(self::WRITABLE_FIELDS)));

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $userId, string $id): JsonResponse
    {
        $education = GtkEducation::findOrFail($id);

        $this->authorizeManage($request, (string) $education->user_id);

        $education->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeManage(Request $request, string $targetUserId): void
    {
        $actor = $request->user();

        abort_unless($actor, 403, 'Unauthorized.');

        if ((string) $actor->id === $targetUserId) {
            return;
        }

        abort_unless(
            GtkAccess::isManager($actor),
            403,
            'Anda tidak memiliki akses untuk mengubah data pendidikan GTK ini.'
        );
    }

    private function authorizeView(Request $request, string $targetUserId): void
    {
        $actor = $request->user();

        abort_unless($actor, 403, 'Unauthorized.');

        $target = User::find($targetUserId);

        abort_unless(
            $target && GtkAccess::canView($actor, $target),
            403,
            'Anda tidak memiliki akses ke data pendidikan GTK ini.'
        );
    }
}
