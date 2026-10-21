<?php

namespace App\Services;

use App\Models\GtkFamilyMember;
use App\Models\GtkHousehold;
use App\Models\GtkProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rumah tangga GTK: pasangan sesama GTK berbagi satu household dan daftar
 * anak yang sama, sehingga anak tidak dibuat dua kali pada dua profil GTK.
 */
class GtkFamilyService
{
    public function household(GtkProfile $profile): GtkHousehold
    {
        if ($profile->household_id) {
            return GtkHousehold::findOrFail($profile->household_id);
        }

        $household = GtkHousehold::create([
            'label' => 'Rumah tangga '.($profile->user?->name ?? 'GTK'),
            'created_by' => auth()->id(),
        ]);
        $profile->update(['household_id' => $household->id]);

        return $household;
    }

    public function sync(GtkProfile $profile, array $members): void
    {
        $profile->refresh();
        $this->household($profile);
        $this->reconcileSpouseNupy($profile);
        $profile->refresh();
        $household = $this->household($profile);

        $existing = GtkFamilyMember::where('household_id', $household->id)->get();
        $incomingIds = array_filter(array_column($members, 'id'));
        $keptIds = [];

        foreach ($members as $member) {
            if (empty($member['nama'])) {
                continue;
            }

            $linked = $this->resolveLinkedProfile($member['gtk_id'] ?? null);
            if ($linked) {
                $this->linkSpouse($profile, $linked);
                $profile->refresh();
                $household = $this->household($profile);
                $existing = GtkFamilyMember::where('household_id', $household->id)->get();
            }

            $key = $this->memberKey($member, $linked);
            $data = array_filter([
                'household_id' => $household->id,
                'linked_gtk_profile_id' => $linked?->id,
                'spouse_nupy' => $member['spouse_nupy'] ?? null,
                'member_key' => $key,
                'relationship' => $member['relationship'] ?? null,
                'nama' => trim((string) $member['nama']),
                'jenis_kelamin' => $member['jenis_kelamin'] ?? null,
                'tempat_lahir' => $member['tempat_lahir'] ?? null,
                'tanggal_lahir' => $member['tanggal_lahir'] ?? null,
                'pekerjaan' => $member['pekerjaan'] ?? null,
                'pendidikan_terakhir' => $member['pendidikan_terakhir'] ?? null,
                'alamat' => $member['alamat'] ?? null,
                'nama_satuan_pendidikan' => $member['nama_satuan_pendidikan'] ?? null,
            ], fn ($v) => $v !== null);

            $record = null;
            if (! empty($member['id'])) {
                $record = $existing->firstWhere('id', $member['id']);
            }
            if (! $record && $key) {
                $record = GtkFamilyMember::where('household_id', $household->id)
                    ->where('member_key', $key)->first();
            }

            if ($record) {
                $record->update($data);
                $keptIds[] = $record->id;
            } else {
                $created = GtkFamilyMember::create($data + [
                    'id' => (string) Str::uuid(),
                    'gtk_profile_id' => $profile->id,
                ]);
                $keptIds[] = $created->id;
            }
        }

        // Hanya hapus baris milik profil ini; anggota milik pasangan tetap.
        $toDelete = $existing
            ->whereNotIn('id', array_merge($incomingIds, $keptIds))
            ->reject(fn (GtkFamilyMember $member) => $member->linked_gtk_profile_id
                && in_array($member->relationship, ['suami', 'istri'], true));
        if ($toDelete->isNotEmpty()) {
            GtkFamilyMember::whereIn('id', $toDelete->pluck('id'))->delete();
        }

        $this->dedupe($household);
    }

    public function add(GtkProfile $profile, array $member): GtkFamilyMember
    {
        $this->household($profile);
        $members = GtkFamilyMember::where('household_id', $profile->household_id)
            ->get()->map->toArray()->values()->all();
        $members[] = $member;
        $this->sync($profile, $members);

        return GtkFamilyMember::where('household_id', $profile->household_id)
            ->where('member_key', $this->memberKey($member, $this->resolveLinkedProfile($member['gtk_id'] ?? null)))
            ->firstOrFail();
    }

    public function updateMember(GtkProfile $profile, string $id, array $member): GtkFamilyMember
    {
        $this->household($profile);
        $record = GtkFamilyMember::where('household_id', $profile->household_id)->findOrFail($id);

        $linked = $this->resolveLinkedProfile($member['gtk_id'] ?? null)
            ?: ($record->linked_gtk_profile_id ? GtkProfile::find($record->linked_gtk_profile_id) : null);
        if ($linked) {
            $this->linkSpouse($profile, $linked);
            $profile->refresh();
        }

        $record->update(array_filter([
            'linked_gtk_profile_id' => $linked?->id,
            'spouse_nupy' => $member['spouse_nupy'] ?? $record->spouse_nupy,
            'member_key' => $this->memberKey($member, $linked),
            'relationship' => $member['relationship'] ?? $record->relationship,
            'nama' => $member['nama'] ?? $record->nama,
            'jenis_kelamin' => $member['jenis_kelamin'] ?? $record->jenis_kelamin,
            'tempat_lahir' => $member['tempat_lahir'] ?? $record->tempat_lahir,
            'tanggal_lahir' => $member['tanggal_lahir'] ?? $record->tanggal_lahir,
            'pekerjaan' => $member['pekerjaan'] ?? $record->pekerjaan,
            'pendidikan_terakhir' => $member['pendidikan_terakhir'] ?? $record->pendidikan_terakhir,
            'alamat' => $member['alamat'] ?? $record->alamat,
            'nama_satuan_pendidikan' => $member['nama_satuan_pendidikan'] ?? $record->nama_satuan_pendidikan,
        ], fn ($v) => $v !== null));

        return $record->fresh();
    }

    public function deleteMember(GtkProfile $profile, string $id): void
    {
        $this->household($profile);
        $record = GtkFamilyMember::where('household_id', $profile->household_id)->findOrFail($id);

        if ($record->linked_gtk_profile_id) {
            GtkProfile::where('id', $record->linked_gtk_profile_id)->update(['spouse_gtk_profile_id' => null]);
            GtkProfile::where('id', $profile->id)->update(['spouse_gtk_profile_id' => null]);
        }

        $record->delete();
    }

    public function linkSpouse(GtkProfile $a, GtkProfile $b): GtkHousehold
    {
        if ($a->is($b)) {
            throw ValidationException::withMessages(['gtk_id' => 'Pasangan tidak boleh diri sendiri.']);
        }

        return DB::transaction(function () use ($a, $b) {
            $this->household($a);
            $this->household($b);
            $a->refresh();
            $b->refresh();

            $target = GtkHousehold::findOrFail($a->household_id);
            $source = GtkHousehold::findOrFail($b->household_id);

            if ($target->id !== $source->id) {
                GtkFamilyMember::where('household_id', $source->id)
                    ->update(['household_id' => $target->id]);
                GtkHousehold::where('id', $source->id)->delete();
            }

            GtkProfile::where('household_id', $source->id)->update([
                'household_id' => $target->id,
                'spouse_gtk_profile_id' => null,
            ]);
            GtkProfile::whereIn('id', [$a->id, $b->id])->update([
                'household_id' => $target->id,
                'spouse_gtk_profile_id' => null,
            ]);
            $a->update(['spouse_gtk_profile_id' => $b->id]);
            $b->update(['spouse_gtk_profile_id' => $a->id]);

            $this->ensureSpouseMember($target, $a, $b);
            $this->dedupe($target);

            $target->update([
                'label' => 'Keluarga '.($a->user?->name ?? 'GTK').' & '.($b->user?->name ?? 'GTK'),
            ]);

            return $target;
        });
    }

    private function ensureSpouseMember(GtkHousehold $household, GtkProfile $a, GtkProfile $b): void
    {
        $existing = GtkFamilyMember::where('household_id', $household->id)
            ->whereIn('relationship', ['suami', 'istri'])
            ->where(function ($q) use ($a, $b) {
                $q->where('linked_gtk_profile_id', $a->id)
                    ->orWhere('linked_gtk_profile_id', $b->id);
            })->first();

        $data = [
            'gtk_profile_id' => $a->id,
            'household_id' => $household->id,
            'linked_gtk_profile_id' => $b->id,
            'member_key' => 'spouse|'.$b->id,
            'relationship' => $b->jenis_kelamin === 'P' ? 'istri' : 'suami',
            'nama' => $b->user?->name ?? 'Pasangan',
            'jenis_kelamin' => $b->jenis_kelamin,
            'tempat_lahir' => $b->tempat_lahir,
            'tanggal_lahir' => $b->tanggal_lahir,
            'pekerjaan' => $b->latestEmployment?->jabatan,
        ];

        $existing ? $existing->update($data) : GtkFamilyMember::create($data + ['id' => (string) Str::uuid()]);
    }

    private function resolveLinkedProfile(?string $userOrProfileId): ?GtkProfile
    {
        if (! $userOrProfileId) {
            return null;
        }

        return GtkProfile::where('id', $userOrProfileId)->first()
            ?? GtkProfile::where('user_id', $userOrProfileId)->first();
    }

    public function reconcileSpouseNupy(GtkProfile $profile): void
    {
        $nupy = $profile->latestEmployment?->nupy;
        if (! $nupy) {
            return;
        }

        $members = GtkFamilyMember::where('spouse_nupy', $nupy)
            ->whereNull('linked_gtk_profile_id')
            ->where('gtk_profile_id', '!=', $profile->id)
            ->get();

        foreach ($members as $member) {
            $owner = GtkProfile::find($member->gtk_profile_id);
            if (! $owner) {
                continue;
            }
            $this->linkSpouse($owner, $profile);
            $member->update([
                'linked_gtk_profile_id' => $profile->id,
                'member_key' => 'spouse|'.$profile->id,
            ]);
        }
    }

    private function memberKey(array $member, ?GtkProfile $linked = null): string
    {
        if ($linked) {
            return substr(hash('sha256', 'spouse|'.$linked->id), 0, 64);
        }

        $name = strtolower(trim((string) ($member['nama'] ?? '')));
        $birth = (string) ($member['tanggal_lahir'] ?? '');
        $relationship = strtolower((string) ($member['relationship'] ?? ''));

        return substr(hash('sha256', $relationship.'|'.$name.'|'.$birth), 0, 64);
    }

    private function dedupe(GtkHousehold $household): void
    {
        $seen = [];
        $seenSpouse = [];
        foreach (GtkFamilyMember::where('household_id', $household->id)->orderBy('created_at')->get() as $member) {
            if ($member->linked_gtk_profile_id && in_array($member->relationship, ['suami', 'istri'], true)) {
                if (isset($seenSpouse[$member->linked_gtk_profile_id])) {
                    $member->delete();
                    continue;
                }
                $seenSpouse[$member->linked_gtk_profile_id] = true;
                $key = 'spouse|'.$member->linked_gtk_profile_id;
                if ($member->member_key !== $key) {
                    $member->updateQuietly(['member_key' => $key]);
                }
            }

            if (! $member->member_key) {
                continue;
            }
            if (isset($seen[$member->member_key])) {
                $member->delete();
                continue;
            }
            $seen[$member->member_key] = true;
        }
    }
}
