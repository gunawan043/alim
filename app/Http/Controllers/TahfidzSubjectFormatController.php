<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Http\Request;

/**
 * Verifikasi format penilaian mapel tahfidz.
 *
 * Format penilaian menentukan apakah sebuah mapel dinilai dengan format
 * umum (RS/STS/SAS) atau tahfidz (Ziyadah/Rabt/STS/SAS). Penanda disimpan
 * eksplisit di `subjects.penilaian_format`; halaman ini adalah satu-satunya
 * tempat admin mengubahnya, supaya tidak ada mapel yang formatnya bergeser
 * karena nama mapel ditulis berbeda.
 */
class TahfidzSubjectFormatController extends Controller
{
    public function __construct(private TahfidzSubjectResolver $resolver) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $subjects = Subject::with(['school:id,name', 'subjectGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $tahfidz = [];
        $umumMirip = [];

        foreach ($subjects as $subject) {
            $isTahfidz = $this->resolver->isTahfidz($subject);

            if ($isTahfidz) {
                $tahfidz[] = $subject;
                continue;
            }

            // Mapel berformat umum yang namanya mirip tahfidz → sarankan.
            $suggestion = $this->resolver->suggestByName($subject);
            if ($suggestion['score'] !== null || $suggestion['exact']) {
                $subject->setAttribute('saran_skor', $suggestion['score']);
                $umumMirip[] = $subject;
            }
        }

        return view('tahfidz.format-verifikasi', [
            'userId' => $userId,
            'tahfidzSubjects' => $tahfidz,
            'suggestedSubjects' => $umumMirip,
            'schoolName' => $schoolId ? $subjects->first()?->school?->name : null,
        ]);
    }

    public function update(Request $request, string $userId)
    {
        $data = $request->validate([
            'formats' => 'array',
            'formats.*' => 'in:umum,tahfidz',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        $changed = 0;

        foreach ($data['formats'] ?? [] as $subjectId => $format) {
            $subject = Subject::find($subjectId);

            if (! $subject) {
                continue;
            }

            if ($schoolId && $subject->school_id !== $schoolId) {
                continue;
            }

            if ($subject->penilaian_format === $format) {
                continue;
            }

            $subject->update(['penilaian_format' => $format]);
            $changed++;
        }

        $message = $changed > 0
            ? "Format penilaian {$changed} mapel diperbarui."
            : 'Tidak ada perubahan format penilaian.';

        // Nilai buku yang sudah tersimpan tidak diubah — hanya cara mapel
        // dinilai ke depan. Beri tahu agar admin tidak mengira nilai lama
        // ikut berubah.
        if ($changed > 0) {
            $message .= ' Nilai yang sudah tersimpan tidak diubah; periksa kembali buku nilai mapel tersebut.';
        }

        return redirect()->route('user.tahfidz.format.index', ['userId' => $userId])
            ->with('success', $message);
    }
}
