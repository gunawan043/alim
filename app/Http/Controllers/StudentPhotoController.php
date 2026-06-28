<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UploadsSecure;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentPhotoController extends Controller
{
    use UploadsSecure;

    public function upload(Request $request, string $userId, string $santriUuid): JsonResponse
    {
        $student = $this->resolveStudent($request, $santriUuid);

        $request->validate([
            'photo' => 'required|file|max:2048',
        ], [
            'photo.required' => 'Pilih file foto terlebih dahulu.',
            'photo.file' => 'File foto tidak valid.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $path = $this->storeSecureFile(
            $request,
            'photo',
            'public',
            'students/photos',
            null
        );

        if (! $path) {
            return response()->json([
                'success' => false,
                'message' => 'Upload foto gagal. Periksa tipe dan ukuran file (JPG/PNG/WEBP, maks 2MB).',
            ], 422);
        }

        $student->update(['photo_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto siswa berhasil diperbarui.',
            'photo_url' => asset('storage/'.$path),
            'photo_path' => $path,
        ]);
    }

    public function destroy(Request $request, string $userId, string $santriUuid): JsonResponse
    {
        $student = $this->resolveStudent($request, $santriUuid);

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
            $student->update(['photo_path' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto siswa berhasil dihapus.',
        ]);
    }

    protected function resolveStudent(Request $request, string $santriUuid): Student
    {
        $student = Student::withoutGlobalScope('school_context')->findOrFail($santriUuid);

        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        if ($user->hasRole('super_admin')) {
            return $student;
        }

        $userSchoolId = $request->attributes->get('schoolContextId') ?? $user->school_id;
        if (! $userSchoolId) {
            abort(403, 'Akun Anda tidak terikat pada sekolah.');
        }

        if ($student->school_id !== $userSchoolId) {
            abort(403, 'Anda tidak memiliki akses ke siswa dari sekolah lain.');
        }

        return $student;
    }
}
