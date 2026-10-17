<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class WaliSantriController extends Controller
{
    /**
     * Ambil santri + validasi akses satuan pendidikan.
     */
    protected function resolveStudent(Request $request, string $santriUuid): Student
    {
        $student = Student::withoutGlobalScope('school_context')->findOrFail($santriUuid);

        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        if (canPermission('super-admin-only')) {
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

    public function edit(Request $request, string $userId, string $santriUuid)
    {
        $student = $this->resolveStudent($request, $santriUuid);

        return view('students.wali.edit', compact('student', 'userId'));
    }

    public function update(Request $request, string $userId, string $santriUuid)
    {
        $student = $this->resolveStudent($request, $santriUuid);

        $messages = [
            'father_name.string' => 'Nama :attribute harus berupa teks.',
            'father_nik.max' => 'NIK :attribute maksimal 30 karakter.',
            'father_birth_year.integer' => 'Tahun lahir :attribute harus berupa angka.',
            'father_income.numeric' => 'Penghasilan :attribute harus berupa angka.',
            'mother_name.string' => 'Nama :attribute harus berupa teks.',
            'mother_nik.max' => 'NIK :attribute maksimal 30 karakter.',
            'mother_birth_year.integer' => 'Tahun lahir :attribute harus berupa angka.',
            'mother_income.numeric' => 'Penghasilan :attribute harus berupa angka.',
            'guardian_name.string' => 'Nama :attribute harus berupa teks.',
            'guardian_nik.max' => 'NIK :attribute maksimal 30 karakter.',
            'guardian_birth_year.integer' => 'Tahun lahir :attribute harus berupa angka.',
            'guardian_income.numeric' => 'Penghasilan :attribute harus berupa angka.',
        ];

        $data = $request->validate([
            'father_name' => 'nullable|string|max:255',
            'father_nik' => 'nullable|string|max:30',
            'father_birth_year' => 'nullable|integer|min:1900|max:'.(date('Y') - 10),
            'father_education' => 'nullable|string|max:50',
            'father_occupation' => 'nullable|string|max:100',
            'father_income' => 'nullable|numeric|min:0',
            'mother_name' => 'nullable|string|max:255',
            'mother_nik' => 'nullable|string|max:30',
            'mother_birth_year' => 'nullable|integer|min:1900|max:'.(date('Y') - 10),
            'mother_education' => 'nullable|string|max:50',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_income' => 'nullable|numeric|min:0',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_nik' => 'nullable|string|max:30',
            'guardian_birth_year' => 'nullable|integer|min:1900|max:'.(date('Y') - 10),
            'guardian_education' => 'nullable|string|max:50',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_income' => 'nullable|numeric|min:0',
        ], $messages);

        $student->update($data);

        return redirect()
            ->route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id])
            ->with('success', 'Data wali/orang tua berhasil diperbarui.');
    }
}
