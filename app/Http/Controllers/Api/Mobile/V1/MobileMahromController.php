<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreMahromRequest;
use App\Http\Requests\Mobile\UpdateMahromRequest;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileMahromController extends Controller
{
    // GET /api/mobile/v1/students/{student}/mahrom

    public function index(Request $request, string $student): JsonResponse
    {
        if (! $this->waliCanAccessStudent($request, $student)) {
            return $this->errorResponse('STUDENT_NOT_FOUND', 'Santri tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        $mahroms = StudentMahrom::where('student_id', $student)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn (StudentMahrom $m) => $this->serialize($m));

        return $this->successResponse(['records' => $mahroms]);
    }

    // POST /api/mobile/v1/students/{student}/mahrom

    public function store(StoreMahromRequest $request, string $student): JsonResponse
    {
        if (! $this->waliCanAccessStudent($request, $student)) {
            return $this->errorResponse('STUDENT_NOT_FOUND', 'Santri tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        $data = $request->validated();

        $payload = [
            'student_id' => $student,
            'name' => $data['nama'],
            'relationship' => $data['hubungan'],
            'id_number' => $data['nik'] ?? null,
            'phone' => $data['nomor_hp'] ?? null,
            'address' => $data['alamat'] ?? null,
            'notes' => $data['catatan'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'is_primary' => (bool) ($data['is_primary'] ?? false),
        ];

        if ($request->hasFile('foto')) {
            $payload['photo_path'] = $request->file('foto')->store('students/mahroms', 'public');
        }

        // Enforce single primary
        if ($payload['is_primary']) {
            StudentMahrom::where('student_id', $student)->update(['is_primary' => false]);
        }

        $mahrom = StudentMahrom::create($payload);

        return $this->successResponse(['mahrom' => $this->serialize($mahrom)], 201);
    }

    // PUT /api/mobile/v1/students/{student}/mahrom/{mahrom}

    public function update(UpdateMahromRequest $request, string $student, string $mahrom): JsonResponse
    {
        if (! $this->waliCanAccessStudent($request, $student)) {
            return $this->errorResponse('STUDENT_NOT_FOUND', 'Santri tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        $mahromModel = StudentMahrom::where('student_id', $student)->where('id', $mahrom)->first();
        if (! $mahromModel) {
            return $this->errorResponse('MAHROM_NOT_FOUND', 'Mahrom tidak ditemukan.', 404);
        }

        $data = $request->validated();

        $mahromModel->name = $data['nama'];
        $mahromModel->relationship = $data['hubungan'];
        $mahromModel->id_number = $data['nik'] ?? null;
        $mahromModel->phone = $data['nomor_hp'] ?? null;
        $mahromModel->address = $data['alamat'] ?? null;
        $mahromModel->notes = $data['catatan'] ?? null;

        if (array_key_exists('is_active', $data)) {
            $mahromModel->is_active = (bool) $data['is_active'];
        }

        $newPrimary = array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : $mahromModel->is_primary;

        if ($request->hasFile('foto')) {
            $mahromModel->photo_path = $request->file('foto')->store('students/mahroms', 'public');
        }

        if ($newPrimary) {
            StudentMahrom::where('student_id', $student)
                ->where('id', '!=', $mahromModel->id)
                ->update(['is_primary' => false]);
            $mahromModel->is_primary = true;
        } else {
            $mahromModel->is_primary = false;
        }

        $mahromModel->save();

        return $this->successResponse(['mahrom' => $this->serialize($mahromModel)]);
    }

    // DELETE /api/mobile/v1/students/{student}/mahrom/{mahrom}

    public function destroy(Request $request, string $student, string $mahrom): JsonResponse
    {
        if (! $this->waliCanAccessStudent($request, $student)) {
            return $this->errorResponse('STUDENT_NOT_FOUND', 'Santri tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        $mahromModel = StudentMahrom::where('student_id', $student)->where('id', $mahrom)->first();
        if (! $mahromModel) {
            return $this->errorResponse('MAHROM_NOT_FOUND', 'Mahrom tidak ditemukan.', 404);
        }

        $mahromModel->delete();

        return $this->successResponse(['deleted' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function waliCanAccessStudent(Request $request, string $studentId): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $schoolId = $request->attributes->get('schoolContextId');

        $query = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $studentId);

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $link = $query->active()->first();
        if (! $link) {
            return false;
        }

        if ($schoolId !== null) {
            return Student::where('id', $studentId)->where('school_id', $schoolId)->exists();
        }

        return Student::where('id', $studentId)->exists();
    }

    private function serialize(StudentMahrom $m): array
    {
        return [
            'id' => $m->id,
            'student_id' => $m->student_id,
            'nama' => $m->name,
            'hubungan' => $m->relationship,
            'jenis_kelamin' => null,
            'nik' => $m->id_number,
            'nomor_hp' => $m->phone,
            'alamat' => $m->address,
            'foto_url' => $m->photo_url,
            'is_primary' => (bool) $m->is_primary,
            'is_active' => (bool) $m->is_active,
            'catatan' => $m->notes,
        ];
    }

    private function successResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'request_id' => request()->header('X-Request-ID') ?: (string) Str::uuid(),
            'data' => $data,
        ], $status);
    }

    private function errorResponse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'request_id' => request()->header('X-Request-ID') ?: (string) Str::uuid(),
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
