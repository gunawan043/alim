<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\KisiKisiItem;
use App\Models\KisiKisiSoal;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KisiKisiController extends Controller
{
    /**
     * List kisi-kisi (filters by school context).
     */
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = KisiKisiSoal::with(['subject', 'gradeLevel', 'academicYear', 'items'])
            ->where('school_id', $schoolId);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('jenis_ujian')) {
            $query->where('jenis_ujian', $request->jenis_ujian);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $kisis = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $subjects = Subject::where('school_id', $schoolId)->orWhereNull('school_id')->get();
        $gradeLevels = GradeLevel::where('school_id', $schoolId)->orWhereNull('school_id')->get();
        $years = AcademicYear::where('school_id', $schoolId)->orWhereNull('school_id')->get();

        return view('evalusi.kisi-kisi.index', compact('kisis', 'subjects', 'gradeLevels', 'years'));
    }

    /**
     * Show create form.
     */
    public function create(string $userId, Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $subjects = Subject::where('school_id', $schoolId)->orWhereNull('school_id')->orderBy('name')->get();
        $gradeLevels = GradeLevel::where('school_id', $schoolId)->orWhereNull('school_id')->get();
        $years = AcademicYear::where('school_id', $schoolId)->orWhereNull('school_id')->get();
        $banks = BankSoal::where('school_id', $schoolId)->with('subject')->get();

        $jenisUjian = [
            'sts' => 'STK / SAS (Sumatif Tengah Semester)',
            'sas' => 'Sumatif Akhir Semester',
            'ulangan_harian' => 'Ulangan Harian',
            'try_out' => 'Try Out',
            'latihan' => 'Latihan',
        ];

        return view('evalusi.kisi-kisi.create', compact('subjects', 'gradeLevels', 'years', 'banks', 'jenisUjian'));
    }

    /**
     * Store kisi-kisi and its items.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'tingkat_sekolah' => 'required|in:sd,smp,sma',
            'peminatan' => 'nullable|in:ipa,ips,bahasa',
            'total_soal_target' => 'required|integer|min:0',
            'total_bobot_target' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.tp_id' => 'required|exists:tujuan_pembelajaran,id',
            'items.*.level_kognitif' => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'items.*.jumlah_soal' => 'required|integer|min:1',
            'items.*.bobot_per_soal' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $userId, $request) {
            $schoolId = $request->attributes->get('schoolContextId');

            $kisi = new KisiKisiSoal;
            $kisi->fill(array_filter($validated, fn ($key) => ! in_array($key, ['items']), ARRAY_FILTER_USE_KEY));
            $kisi->subject_id = $validated['subject_id'];
            $kisi->school_id = $schoolId;
            $kisi->created_by = $userId;
            $kisi->save();

            foreach ($validated['items'] as $idx => $item) {
                $item['kisi_kisi_soal_id'] = $kisi->id;
                KisiKisiItem::create($item);
            }

            return redirect()->route('user.kisi-kisi.show', $kisi->id)->with('success', 'Kisi-kisi berhasil dibuat.');
        });
    }

    /**
     * Show kisi-kisi detail.
     */
    public function show(string $id)
    {
        $kisi = KisiKisiSoal::with(['subject', 'gradeLevel', 'academicYear',
            'items.tujuanPembelajaran', 'items.kisiKisi'])
            ->findOrFail($id);

        return view('evalusi.kisi-kisi.show', compact('kisi'));
    }

    /**
     * Edit form.
     */
    public function edit(string $id)
    {
        $kisi = KisiKisiSoal::with(['subject', 'gradeLevel', 'academicYear', 'items.tujuanPembelajaran'])
            ->findOrFail($id);

        $subjects = Subject::all();
        $gradeLevels = GradeLevel::all();
        $years = AcademicYear::all();
        $banks = BankSoal::all();

        $jenisUjian = [
            'sts' => 'STK / SAS', 'sas' => 'SAK',
            'ulangan_harian' => 'Ulangan Harian', 'try_out' => 'Try Out', 'latihan' => 'Latihan',
        ];

        return view('evalusi.kisi-kisi.edit', compact('kisi', 'subjects', 'gradeLevels', 'years', 'banks', 'jenisUjian'));
    }

    /**
     * Update kisi-kisi and items.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'total_soal_target' => 'required|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:kisi_kisi_soal_items,id',
            'items.*.tp_id' => 'required|exists:tujuan_pembelajaran,id',
            'items.*.level_kognitif' => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'items.*.jumlah_soal' => 'required|integer|min:1',
            'items.*.bobot_per_soal' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $kisi = KisiKisiSoal::findOrFail($id);
            $kisi->fill(array_filter($validated, fn ($key) => ! in_array($key, ['items']), ARRAY_FILTER_USE_KEY));
            $kisi->save();

            // Upsert or delete items
            $existingIds = [];
            foreach ($validated['items'] as $idx => $item) {
                $itemId = $item['id'] ?? null;
                if ($itemId) {
                    $record = KisiKisiItem::findOrFail($itemId);
                    $record->update([
                        'tp_id' => $item['tp_id'],
                        'level_kognitif' => $item['level_kognitif'],
                        'jumlah_soal' => $item['jumlah_soal'],
                        'bobot_per_soal' => $item['bobot_per_soal'],
                    ]);
                    $existingIds[] = $itemId;
                } else {
                    KisiKisiItem::create([
                        'kisi_kisi_soal_id' => $id,
                        'tp_id' => $item['tp_id'],
                        'level_kognitif' => $item['level_kognitif'],
                        'jumlah_soal' => $item['jumlah_soal'],
                        'bobot_per_soal' => $item['bobot_per_soal'],
                    ]);
                }
            }

            // Delete items that weren't in the update
            KisiKisiItem::where('kisi_kisi_soal_id', $id)
                ->whereNotIn('id', $existingIds)
                ->delete();

            return redirect()->back()->with('success', 'Kisi-kisi berhasil diperbarui.');
        });
    }

    /**
     * Delete kisi-kisi.
     */
    public function destroy(string $id)
    {
        $kisi = KisiKisiSoal::findOrFail($id);
        $kisi->delete();

        return redirect()->route('user.kisi-kisi.index')->with('success', 'Kisi-kisi dihapus.');
    }
}
