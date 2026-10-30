<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\Student;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Data santri untuk integrasi POS / Kasir.
 *
 * Hanya memuat identitas santri yang dibutuhkan kasir (NIS/NISN, nama,
 * kelas, sekolah, status). Data orang tua/wali dan dokumen sensitif
 * tidak dikirim.
 *
 * GET /api/pos/v1/santri
 * GET /api/pos/v1/santri/{id}
 */
class SantriController extends PosApiController
{
    private const WITH_RELATIONS = [
        'school:id,name',
        'currentClassHistory',
        'currentClassHistory.studyGroup:id,name,code,grade_level_id',
        'currentClassHistory.studyGroup.gradeLevel:id,level,name',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Student::query()->with(self::WITH_RELATIONS);

        $this->applyFilters($query, $request);

        $paginator = $query
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->through(fn (Student $student) => $this->format($student));

        return $this->paginated($paginator, null, 'Data santri berhasil diambil.');
    }

    public function show(string $id): JsonResponse
    {
        $student = Student::query()->with(self::WITH_RELATIONS)->find($id);

        if (! $student) {
            return $this->notFound('Santri tidak ditemukan.');
        }

        return $this->ok($this->format($student), 'Detail santri berhasil diambil.');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $status = $request->input('status', 'active');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('sekolah')) {
            $query->where('school_id', $request->string('sekolah')->toString());
        }

        if ($request->filled('kelas')) {
            $studyGroupId = $request->string('kelas')->toString();
            $query->whereHas('currentClassHistory', fn (Builder $q) => $q->where('study_group_id', $studyGroupId));
        }

        if ($request->filled('jenis_kelamin')) {
            $query->where('gender', $request->string('jenis_kelamin')->toString());
        }

        if (($search = $this->searchTerm($request)) !== null) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $since = $this->dateFilter($request, 'updated_since');

        if ($since instanceof Carbon) {
            $query->where('updated_at', '>=', $since);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Student $student): array
    {
        $studyGroup = $student->currentClassHistory?->studyGroup;

        return [
            'id' => $student->id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'nama' => $student->name,
            'jenis_kelamin' => $student->gender,
            'jenis_kelamin_text' => match ($student->gender) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => null,
            },
            'tempat_lahir' => $student->birth_place,
            'tanggal_lahir' => $student->birth_date?->toDateString(),
            'status' => $student->status,
            'status_text' => $student->status_text,
            'sekolah' => $student->school ? [
                'id' => $student->school->id,
                'nama' => $student->school->name,
            ] : null,
            'kelas' => $studyGroup ? [
                'id' => $studyGroup->id,
                'nama' => $studyGroup->name,
                'kode' => $studyGroup->code,
                'tingkat' => $studyGroup->gradeLevel?->level,
            ] : null,
            'foto_url' => $student->photo_url,
            'diperbarui_pada' => $student->updated_at?->toIso8601String(),
        ];
    }
}
