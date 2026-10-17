<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profil sekolah / lembaga untuk integrasi POS / Kasir.
 *
 * GET /api/pos/v1/sekolah
 * GET /api/pos/v1/sekolah/{id}
 */
class SekolahController extends PosApiController
{
    private const WITH_RELATIONS = [
        'workUnit:id,name,code,type',
        'province:code,name',
        'city:code,name',
        'district:code,name',
        'village:code,name',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = School::query()->with(self::WITH_RELATIONS);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('npsn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('school_level')) {
            $query->where('school_level', $request->string('school_level')->toString());
        }

        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->string('work_unit_id')->toString());
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        $paginator = $query
            ->orderBy('name')
            ->paginate($this->perPage($request, 100))
            ->through(fn (School $school) => $this->format($school));

        return $this->paginated($paginator, null, 'Daftar sekolah berhasil diambil.');
    }

    public function show(string $id): JsonResponse
    {
        $school = School::query()->with(self::WITH_RELATIONS)->find($id);

        if (! $school) {
            return $this->notFound('Sekolah tidak ditemukan.');
        }

        return $this->ok($this->format($school), 'Detail sekolah berhasil diambil.');
    }

    /**
     * @return array<string, mixed>
     */
    private function format(School $school): array
    {
        return [
            'id' => $school->id,
            'npsn' => $school->npsn,
            'nss' => $school->nss,
            'nama' => $school->name,
            'jenjang' => $school->school_level,
            'jenjang_text' => $school->level_text,
            'gender' => $school->school_gender,
            'status' => $school->school_status,
            'status_text' => $school->status_text,
            'alamat' => $school->full_address,
            'provinsi' => $school->province?->name,
            'kota' => $school->city?->name,
            'kecamatan' => $school->district?->name,
            'desa' => $school->village?->name,
            'kode_pos' => $school->postal_code,
            'telepon' => $school->phone,
            'email' => $school->email,
            'website' => $school->website,
            'akreditasi' => $school->accreditation,
            'tahun_akreditasi' => $school->accreditation_year,
            'kepala_sekolah' => $school->principal_name,
            'satuan_kerja' => $school->workUnit ? [
                'id' => $school->workUnit->id,
                'kode' => $school->workUnit->code,
                'nama' => $school->workUnit->name,
            ] : null,
            'logo_url' => $school->logo_url,
            'is_active' => (bool) $school->is_active,
            'diperbarui_pada' => $school->updated_at?->toIso8601String(),
        ];
    }
}
