<?php

namespace App\Http\Controllers;

use App\Exceptions\ServiceErrorCode;
use App\Models\School;
use App\Models\User;
use App\Services\Wali\WaliLinkService;
use App\Support\WaliLinkAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman sekolah untuk mengelola penghubungan wali–santri.
 * Super Admin/System Admin atau role Satuan Pendidikan dengan jabatan Tata Usaha.
 */
class WaliLinkController extends Controller
{
    public function __construct(private readonly WaliLinkService $links) {}

    public function index(Request $request): View
    {
        $this->authorizePage($request);

        $isSuperAdmin = $this->isSuperAdmin($request->user());
        $status = (string) $request->input('status', 'pending');
        $allowedStatuses = ['pending', 'active', 'suspended', 'revoked', 'all'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        if ($isSuperAdmin) {
            // Super Admin bebas: tanpa filter = semua sekolah.
            $schoolId = $this->requestedSchoolFilter($request);
        } else {
            $schoolId = $this->tuSchoolScope($request);

            if ($schoolId === null) {
                abort(403, 'Satuan pendidikan untuk akun Anda belum ditentukan.');
            }
        }

        $schools = $isSuperAdmin
            ? School::active()->orderBy('name')->get(['id', 'name'])
            : collect();

        $search = trim((string) $request->input('search', ''));

        $links = $this->links->adminLinks([
            'status' => $status,
            'search' => $search,
        ], $schoolId);

        $counts = $this->links->statusCounts($schoolId);

        return view('wali-links.index', [
            'userId' => $request->route('userId'),
            'links' => $links,
            'counts' => $counts,
            'status' => $status,
            'search' => $search,
            'schools' => $schools,
            'schoolId' => $schoolId,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function approve(Request $request, string $userId, string $id): RedirectResponse
    {
        return $this->runAction($request, $id, fn () => $this->links->approve($request->user(), $id), 'Penghubungan wali disetujui.');
    }

    public function reject(Request $request, string $userId, string $id): RedirectResponse
    {
        return $this->runAction($request, $id, fn () => $this->links->reject($request->user(), $id), 'Pengajuan penghubungan ditolak.');
    }

    public function suspend(Request $request, string $userId, string $id): RedirectResponse
    {
        return $this->runAction(
            $request,
            $id,
            fn () => $this->links->suspend($request->user(), $id),
            'Hubungan wali ditangguhkan.',
            requireActive: true,
        );
    }

    public function revoke(Request $request, string $userId, string $id): RedirectResponse
    {
        return $this->runAction($request, $id, fn () => $this->links->revoke($request->user(), $id), 'Hubungan wali dicabut.');
    }

    private function authorizePage(Request $request): void
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

    private function requestedSchoolFilter(Request $request): ?string
    {
        $schoolId = $request->input('school_id');

        if (is_string($schoolId) && $schoolId !== '' && School::whereKey($schoolId)->exists()) {
            return $schoolId;
        }

        return null;
    }

    private function tuSchoolScope(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if (is_string($schoolId) && $schoolId !== '') {
            return $schoolId;
        }

        return $request->user()?->employment?->school_id;
    }

    /**
     * @param  callable(): mixed  $action
     */
    private function runAction(
        Request $request,
        string $id,
        callable $action,
        string $successMessage,
        bool $requireActive = false,
    ): RedirectResponse {
        $this->authorizePage($request);

        $isSuperAdmin = $this->isSuperAdmin($request->user());
        $schoolId = $isSuperAdmin ? null : $this->tuSchoolScope($request);

        if (! $isSuperAdmin && $schoolId === null) {
            return back()->with('error', 'Satuan pendidikan untuk akun Anda belum ditentukan.');
        }

        try {
            $link = $this->links->findForAdmin($id, $schoolId);

            if ($requireActive && ! $link->isActive()) {
                return back()->with('error', 'Hanya link aktif yang dapat ditangguhkan.');
            }

            $action();

            return back()->with('success', $successMessage);
        } catch (ServiceErrorCode $e) {
            return back()->with('error', $e->getMessage());
        } catch (ModelNotFoundException) {
            return back()->with('error', 'Pengajuan penghubungan tidak ditemukan.');
        }
    }
}
