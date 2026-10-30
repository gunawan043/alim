<?php

namespace App\Http\Controllers\Api\Recruitment\V1;

use App\Http\Controllers\Api\Shared\IntegrationController;
use App\Models\GtkEmployment;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data pegawai/GTK untuk aplikasi recruitment.
 *
 * Hanya memuat data yang dibutuhkan untuk penugasan interviewer/penilai:
 * identitas dasar, email/username, nomor WhatsApp, unit kerja, jabatan,
 * status kepegawaian, dan role. Data sensitif (NIK, KK, NPWP, NUPY, nomor SK,
 * kontak darurat, data keluarga) tidak dikirim.
 *
 * GET /api/recruitment/v1/pegawai
 * GET /api/recruitment/v1/pegawai/{id}
 */
class PegawaiController extends IntegrationController
{
    private const WITH_RELATIONS = [
        'roles:id,name',
        'gtkProfile:id,user_id,tempat_lahir,tanggal_lahir,golongan_darah,jenis_kelamin,agama',
        'gtkContact:id,user_id,no_hp,no_whatsapp',
        'employment:id,user_id,jenis_gtk,jenis_gtk_id,jabatan,status_kepegawaian',
        'employment.jenisGtk:id,nama',
        'gtkWorkUnits:id,user_id,work_unit_id,jabatan,is_primary',
        'gtkWorkUnits.workUnit:id,name,code',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery();

        $this->applyFilters($query, $request);

        $paginator = $query
            ->orderBy('users.name')
            ->paginate($this->perPage($request))
            ->through(fn (User $user) => $this->format($user));

        return $this->paginated($paginator, null, 'Data pegawai berhasil diambil.');
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->baseQuery()->find($id);

        if (! $user) {
            return $this->notFound('Data pegawai tidak ditemukan.');
        }

        return $this->ok($this->format($user), 'Detail pegawai berhasil diambil.');
    }

    private function baseQuery(): Builder
    {
        return User::query()
            ->with(self::WITH_RELATIONS)
            ->where(function (Builder $q) {
                $q->whereHas('employment')
                    ->orWhereHas('gtkWorkUnits')
                    ->orWhereHas('gtkProfile');
            });
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('satuan_kerja')) {
            $workUnitId = $request->string('satuan_kerja')->toString();
            $query->whereHas('gtkWorkUnits', fn (Builder $q) => $q->where('gtk_work_unit.work_unit_id', $workUnitId));
        }

        if ($request->filled('jabatan')) {
            $jabatan = $request->string('jabatan')->toString();
            $query->where(function (Builder $q) use ($jabatan) {
                $q->whereHas('employment', fn (Builder $qq) => $qq->where('jabatan', 'like', "%{$jabatan}%"))
                    ->orWhereHas('gtkWorkUnits', fn (Builder $qq) => $qq->where('jabatan', 'like', "%{$jabatan}%"));
            });
        }

        if ($request->filled('jenis_gtk_id')) {
            $jenisGtkId = $request->string('jenis_gtk_id')->toString();
            $query->whereHas('employment', fn (Builder $q) => $q->where('jenis_gtk_id', $jenisGtkId));
        }

        if ($request->filled('jenis_gtk')) {
            $jenisGtk = $request->string('jenis_gtk')->toString();
            $query->whereHas('employment', fn (Builder $q) => $q->where('jenis_gtk', 'like', "%{$jenisGtk}%"));
        }

        if ($request->filled('role')) {
            $role = $request->string('role')->toString();
            $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role));
        }

        if (($search = $this->searchTerm($request)) !== null) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%")
                    ->orWhereHas('gtkContact', function (Builder $qq) use ($search) {
                        $qq->where('no_whatsapp', 'like', "%{$search}%")
                            ->orWhere('no_hp', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('users.is_active', $request->boolean('is_active', true));
        }

        $since = $this->dateFilter($request, 'updated_since');

        if ($since instanceof Carbon) {
            $query->where(function (Builder $q) use ($since) {
                $q->where('users.updated_at', '>=', $since)
                    ->orWhereHas('gtkProfile', fn (Builder $qq) => $qq->where('updated_at', '>=', $since))
                    ->orWhereHas('gtkContact', fn (Builder $qq) => $qq->where('updated_at', '>=', $since))
                    ->orWhereHas('employment', fn (Builder $qq) => $qq->where('updated_at', '>=', $since))
                    ->orWhereHas('gtkWorkUnits', fn (Builder $qq) => $qq->where('gtk_work_unit.updated_at', '>=', $since));
            });
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function format(User $user): array
    {
        $profile = $user->gtkProfile;
        $contact = $user->gtkContact;
        $employment = $user->employment;

        $assignments = $user->gtkWorkUnits
            ->map(fn ($pivot) => [
                'id' => $pivot->workUnit?->id,
                'kode' => $pivot->workUnit?->code,
                'nama' => $pivot->workUnit?->name,
                'jabatan' => $pivot->jabatan,
                'is_primary' => (bool) $pivot->is_primary,
            ])
            ->filter(fn (array $item) => $item['id'] !== null)
            ->values();

        $primary = $assignments->firstWhere('is_primary', true) ?? $assignments->first();

        return [
            'id' => $user->id,
            'nama' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'jenis_kelamin' => $profile?->jenis_kelamin,
            'jenis_kelamin_text' => match ($profile?->jenis_kelamin) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => null,
            },
            'tempat_lahir' => $profile?->tempat_lahir,
            'tanggal_lahir' => $profile?->tanggal_lahir?->toDateString(),
            'usia' => $profile?->tanggal_lahir?->age,
            'no_whatsapp' => $contact?->no_whatsapp,
            'no_whatsapp_normalized' => $this->normalizePhone($contact?->no_whatsapp),
            'no_hp' => $contact?->no_hp,
            'satuan_kerja' => $primary ? [
                'id' => $primary['id'],
                'kode' => $primary['kode'],
                'nama' => $primary['nama'],
                'jabatan' => $primary['jabatan'],
            ] : null,
            'daftar_satuan_kerja' => $assignments,
            'jabatan' => $employment?->jabatan ?? $primary['jabatan'] ?? null,
            'jenis_gtk' => $employment?->jenisGtk?->nama ?? $employment?->jenis_gtk,
            'status_kepegawaian' => $employment?->status_kepegawaian,
            'status_kepegawaian_text' => $employment
                ? (GtkEmployment::STATUS_LABELS[$employment->status_kepegawaian] ?? $employment->status_kepegawaian)
                : null,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'foto_url' => $this->photoUrl($user->avatar),
            'is_active' => (bool) $user->is_active,
            'diperbarui_pada' => $user->updated_at?->toIso8601String(),
        ];
    }

    private function photoUrl(?string $avatar): string
    {
        if ($avatar && $avatar !== 'default-avatar.jpg') {
            return Storage::disk('public')->url('avatars/'.$avatar);
        }

        return asset('images/default-avatar.jpg');
    }
}
