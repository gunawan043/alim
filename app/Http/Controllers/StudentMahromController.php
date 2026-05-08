<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentMahrom;
use Illuminate\Http\Request;

class StudentMahromController extends Controller
{
    /**
     * GET /{userId}/santri/{santriUuid}/mahrom
     */
    public function index(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $mahroms = StudentMahrom::where('student_id', $santriUuid)->orderByDesc('is_primary')->orderBy('name')->get();

        return view('students.mahroms.index', compact('student', 'mahroms', 'userId'));
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/tambah
     */
    public function create(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $existingCount = StudentMahrom::where('student_id', $santriUuid)->count();

        if ($existingCount >= 4) {
            return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
                ->with('error', 'Maksimal 4 mahrom per santri sudah tercapai.');
        }

        return view('students.mahroms.create', compact('student', 'userId'));
    }

    /**
     * POST /{userId}/santri/{santriUuid}/mahrom
     */
    public function store(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $existingCount = StudentMahrom::where('student_id', $santriUuid)->count();

        if ($existingCount >= 4) {
            return back()->withInput()->withErrors(['max' => 'Maksimal 4 mahrom per santri.']);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'id_number'     => 'nullable|string|max:30|unique:student_mahroms,id_number',
            'relationship'  => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,anak, Lainnya',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'photo_path'    => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            'is_active'     => 'boolean',
            'is_primary'    => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        if ($request->hasFile('photo_path')) {
            $path = $request->file('photo_path')->store('students/mahroms', 'public');
            $data['photo_path'] = $path;
        }

        $data['student_id'] = $santriUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        // Pastikan hanya 1 mahrom primer
        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $santriUuid)->update(['is_primary' => false]);
        }

        StudentMahrom::create($data);

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Data mahrom berhasil ditambahkan.');
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function show(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        return view('students.mahroms.show', compact('student', 'mahrom', 'userId'));
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}/edit
     */
    public function edit(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        return view('students.mahroms.edit', compact('student', 'mahrom', 'userId'));
    }

    /**
     * PUT /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function update(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'id_number'     => 'nullable|string|max:30|unique:student_mahroms,id_number,' . $mahromUuid,
            'relationship'  => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,anak, Lainnya',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'photo_path'    => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            'is_active'     => 'boolean',
            'is_primary'    => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        if ($request->hasFile('photo_path')) {
            $path = $request->file('photo_path')->store('students/mahroms', 'public');
            $data['photo_path'] = $path;
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $santriUuid)->where('id', '!=', $mahromUuid)->update(['is_primary' => false]);
        }

        $mahrom->update($data);

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Data mahrom berhasil diperbarui.');
    }

    /**
     * DELETE /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function destroy(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);
        $mahrom->delete();

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Mahrom berhasil dihapus.');
    }
}
