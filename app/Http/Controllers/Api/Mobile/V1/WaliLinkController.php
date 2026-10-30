<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Exceptions\ServiceErrorCode;
use App\Http\Controllers\Controller;
use App\Services\Wali\WaliLinkService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Penghubungan wali–santri (dua langkah: preview → confirm).
 *
 * Endpoint berada di luar school-context group karena wali baru belum punya
 * relasi/sekolah; pencocokan dilakukan lintas sekolah.
 */
class WaliLinkController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WaliLinkService $links) {}

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identity_number' => ['required', 'string', 'digits_between:10,16'],
            'no_kk' => ['required', 'string', 'digits:16'],
            'mother_name' => ['required', 'string', 'min:3', 'max:255'],
            'relationship' => ['required', Rule::in(WaliLinkService::VALID_RELATIONSHIPS)],
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
        ]);

        try {
            $result = $this->links->preview($request->user(), $validated);

            if (! empty($result['ambiguous'])) {
                return $this->fail($result['message'], 409, [
                    'code' => 'WALI_LINK_AMBIGUOUS',
                    'candidates' => $result['candidates'],
                ]);
            }

            return $this->ok($result, $result['already_linked']
                ? $result['message']
                : 'Data santri ditemukan. Periksa kembali sebelum menghubungkan.');
        } catch (ServiceErrorCode $e) {
            return $this->fail($e->getMessage(), $e->getStatus(), $e->getDetails());
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_token' => ['required', 'string', 'size:64'],
        ]);

        try {
            $result = $this->links->confirm($request->user(), $validated['verification_token']);

            $message = $result['status'] === 'active'
                ? 'Santri berhasil dihubungkan.'
                : 'Pengajuan penghubungan dikirim dan menunggu persetujuan sekolah.';

            return $this->ok($result, $message, $result['status'] === 'active' ? 201 : 202);
        } catch (ServiceErrorCode $e) {
            return $this->fail($e->getMessage(), $e->getStatus(), $e->getDetails());
        }
    }

    public function children(Request $request): JsonResponse
    {
        return $this->ok([
            'children' => $this->links->children($request->user()),
        ], 'Daftar santri berhasil diambil.');
    }

    public function siblings(Request $request, string $userId, string $student): JsonResponse
    {
        try {
            return $this->ok([
                'candidates' => $this->links->siblingsFor($request->user(), $student),
            ], 'Kandidat saudara berhasil diambil.');
        } catch (ServiceErrorCode $e) {
            return $this->fail($e->getMessage(), $e->getStatus(), $e->getDetails());
        }
    }

    public function siblingPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'identity_number' => ['required', 'string', 'digits_between:10,16'],
            'relationship' => ['required', Rule::in(WaliLinkService::VALID_RELATIONSHIPS)],
        ]);

        try {
            $result = $this->links->previewSibling($request->user(), $validated);

            return $this->ok($result, 'Identitas anak cocok. Periksa kembali sebelum menghubungkan.');
        } catch (ServiceErrorCode $e) {
            return $this->fail($e->getMessage(), $e->getStatus(), $e->getDetails());
        }
    }
}
