<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Exceptions\ServiceErrorCode;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\Wali\WaliLinkService;
use App\Support\ApiResponse;
use App\Support\WaliLinkAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengelolaan penghubungan wali oleh sekolah:
 * Super Admin/System Admin atau role Satuan Pendidikan dengan jabatan Tata Usaha.
 */
class WaliLinkAdminController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WaliLinkService $links) {}

    public function pending(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = $this->schoolScope($request);

        if ($schoolId === null && ! $this->isSuperAdmin($request->user())) {
            return $this->fail('Konteks satuan pendidikan diperlukan. Pilih sekolah terlebih dahulu.', 403, [
                'code' => 'SCHOOL_CONTEXT_REQUIRED',
            ]);
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $paginator = $this->links->adminLinks(['status' => 'pending'], $schoolId, $perPage)->through(fn ($link) => [
            'id' => $link->id,
            'status' => $link->status,
            'relationship' => $link->role,
            'created_at' => $link->created_at?->toIso8601String(),
            'wali' => [
                'id' => $link->user?->id,
                'name' => $link->user?->name,
                'email' => $link->user?->email,
                'no_hp' => $link->user?->no_hp,
            ],
            'student' => [
                'id' => $link->student?->id,
                'name' => $link->student?->name,
                'nisn' => $link->student?->nisn,
                'nik' => $link->student?->nik,
                'school' => $link->student?->school?->name,
            ],
        ]);

        return $this->paginated($paginator, null, 'Daftar pengajuan penghubungan berhasil diambil.');
    }

    public function approve(Request $request, string $userId, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->runAction($request, $id, fn () => $this->links->approve($request->user(), $id), 'Penghubungan wali disetujui.');
    }

    public function reject(Request $request, string $userId, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->runAction($request, $id, fn () => $this->links->reject($request->user(), $id), 'Pengajuan penghubungan ditolak.');
    }

    public function suspend(Request $request, string $userId, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->runAction($request, $id, fn () => $this->links->suspend($request->user(), $id), 'Hubungan wali ditangguhkan.');
    }

    public function revoke(Request $request, string $userId, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->runAction($request, $id, fn () => $this->links->revoke($request->user(), $id), 'Hubungan wali dicabut.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            WaliLinkAccess::canManage($request->user()),
            403,
            'Hanya Super Admin atau Tata Usaha yang dapat mengelola penghubungan wali.'
        );
    }

    private function isSuperAdmin(?User $user): bool
    {
        return (bool) ($user?->isSystemAdmin() || $user?->isSuperAdmin());
    }

    private function schoolScope(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if (is_string($schoolId) && $schoolId !== '') {
            return $schoolId;
        }

        $user = $request->user();

        if ($user?->isSystemAdmin() || $user?->isSuperAdmin()) {
            $requested = $request->input('school_id') ?: $request->header('X-School-Id');

            if (is_string($requested) && $requested !== '' && School::whereKey($requested)->exists()) {
                return $requested;
            }

            return null;
        }

        return $user?->employment?->school_id;
    }

    /**
     * @param  callable(): WaliSantri  $action
     */
    private function runAction(Request $request, string $id, callable $action, string $message): JsonResponse
    {
        $schoolId = $this->schoolScope($request);

        if ($schoolId === null && ! $this->isSuperAdmin($request->user())) {
            return $this->fail('Konteks satuan pendidikan diperlukan. Pilih sekolah terlebih dahulu.', 403, [
                'code' => 'SCHOOL_CONTEXT_REQUIRED',
            ]);
        }

        try {
            $this->links->findForAdmin($id, $schoolId);
            $link = $action();

            return $this->ok([
                'wali_santri' => [
                    'id' => $link->id,
                    'status' => $link->status,
                    'relationship' => $link->role,
                    'is_primary' => (bool) $link->is_primary,
                ],
                'wali' => [
                    'id' => $link->user?->id,
                    'name' => $link->user?->name,
                ],
                'student' => [
                    'id' => $link->student?->id,
                    'name' => $link->student?->name,
                ],
            ], $message);
        } catch (ServiceErrorCode $e) {
            return $this->fail($e->getMessage(), $e->getStatus(), $e->getDetails());
        } catch (ModelNotFoundException) {
            return $this->notFound('Pengajuan penghubungan tidak ditemukan.');
        }
    }
}
