<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Subject::with('school');

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $subjects = $query->orderBy('school_id')->orderBy('name')->get();

        // Group by kelompok mapel: Agama → Arab → Hadits → Umum
        $grouped = $subjects->groupBy(function ($s) {
            $name = strtolower($s->name);
            if (preg_match('/(aqidah|adab|fiqih|tahfidz|hafalan|pendidikan agama)/i', $name)) {
                return 'agama';
            }
            if (preg_match('/(bahasa arab|b\.? ?arab|qowaid|ta[\'"]?bir|sharaf)/i', $name)) {
                return 'arab';
            }
            if (preg_match('/(hadits|hadist)/i', $name)) {
                return 'hadits';
            }

            return 'umum';
        });

        $kelompokLabels = [
            'agama' => ['label' => 'Mapel Agama',            'icon' => 'ri-moon-line',       'color' => 'primary'],
            'arab' => ['label' => 'Mapel Bahasa Arab',       'icon' => 'ri-quill-pen-line', 'color' => 'success'],
            'hadits' => ['label' => 'Mapel Hadits',             'icon' => 'ri-book-mark-line', 'color' => 'info'],
            'umum' => ['label' => 'Mapel Umum',               'icon' => 'ri-global-line',    'color' => 'warning'],
        ];

        $schools = School::orderBy('name')->get();

        return view('subjects.index', compact('subjects', 'schools', 'userId', 'grouped', 'kelompokLabels'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            $schools = School::orderBy('name')->get();
        }
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('subjects.create', compact('schools', 'userId', 'schoolContext'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'category' => 'required|in:nasional,lokal,muatan_lokal',
            'credit_hours' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = Subject::where('school_id', $data['school_id'])
            ->where('code', $data['code'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode mata pelajaran ini sudah ada untuk sekolah tersebut.');
        }

        $subject = Subject::create($data);

        return redirect()->route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id])
            ->with('success', 'Mata pelajaran berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $subject = Subject::with('school')->findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('subjects.show', compact('subject', 'userId'));
    }

    public function edit(string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
        $schools = School::orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('subjects.edit', compact('subject', 'schools', 'userId', 'schoolContext'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'category' => 'required|in:nasional,lokal,muatan_lokal',
            'credit_hours' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = Subject::where('school_id', $data['school_id'])
            ->where('code', $data['code'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode mata pelajaran ini sudah ada untuk sekolah tersebut.');
        }

        $subject->update($data);

        return redirect()->route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id])
            ->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $subject->delete();

        return redirect()->route('user.subjects.index', ['userId' => $userId])
            ->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
