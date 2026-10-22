<?php

namespace App\Imports;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DormitoryRoomsImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct(
        private readonly string $dormitoryId,
        private readonly string $defaultGender,
        private readonly ?string $userId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $row->toArray();

                if (blank(trim((string) ($data['kode_kamar'] ?? ''))) && blank(trim((string) ($data['nama_kamar'] ?? '')))) {
                    $this->skipped++;

                    continue;
                }

                try {
                    $this->processRow($data, $rowNumber);
                } catch (\Throwable $e) {
                    $this->skipped++;
                    $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Import dibatalkan: '.$e->getMessage();
        }
    }

    private function processRow(array $data, int $rowNumber): void
    {
        $roomCode = trim((string) ($data['kode_kamar'] ?? ''));
        $roomName = trim((string) ($data['nama_kamar'] ?? ''));
        $capacity = (int) ($data['kapasitas'] ?? 0);

        if ($roomCode === '' || $roomName === '') {
            throw new \RuntimeException('Kode kamar dan nama kamar wajib diisi.');
        }

        if (mb_strlen($roomCode) > 20) {
            throw new \RuntimeException('Kode kamar maksimal 20 karakter.');
        }

        if (mb_strlen($roomName) > 100) {
            throw new \RuntimeException('Nama kamar maksimal 100 karakter.');
        }

        if ($capacity < 1) {
            throw new \RuntimeException('Kapasitas kamar minimal 1.');
        }

        $wing = $this->resolveWing(
            trim((string) ($data['kode_gedung'] ?? '')),
            trim((string) ($data['nama_gedung'] ?? '')),
        );

        $room = DormitoryRoom::query()
            ->where('dormitory_id', $this->dormitoryId)
            ->where('code', $roomCode)
            ->first();

        $payload = [
            'wing_id' => $wing->id,
            'code' => $roomCode,
            'name' => $roomName,
            'floor' => filled($data['lantai'] ?? null) ? (int) $data['lantai'] : null,
            'gender' => $this->normalizeGender($data['gender'] ?? null),
            'capacity' => $capacity,
            'room_type' => filled($data['tipe_kamar'] ?? null) ? trim((string) $data['tipe_kamar']) : null,
            'facility_notes' => filled($data['catatan'] ?? null) ? trim((string) $data['catatan']) : null,
            'is_active' => $this->normalizeBoolean($data['aktif'] ?? true),
        ];

        if ($room) {
            $room->update($payload);
            $this->updated++;

            return;
        }

        $this->ensureRoomCodeAvailable($roomCode);
        DormitoryRoom::create(array_merge($payload, ['dormitory_id' => $this->dormitoryId]));
        $this->created++;
    }

    private function resolveWing(string $code, string $name): DormitoryWing
    {
        if ($code === '' && $name === '') {
            throw new \RuntimeException('Kode atau nama gedung wajib diisi.');
        }

        $query = DormitoryWing::query()->where('dormitory_id', $this->dormitoryId);

        $wing = null;
        if ($code !== '') {
            $wing = (clone $query)->where('code', $code)->first();
        }
        if (! $wing && $name !== '') {
            $wing = (clone $query)->where('name', $name)->first();
        }

        if ($wing) {
            return $wing;
        }

        $wingName = $name !== '' ? $name : $code;
        $wingCode = $code !== '' ? $code : Str::upper(Str::slug($wingName, '-'));
        $wingCode = $this->uniqueWingCode($wingCode);

        return DormitoryWing::create([
            'dormitory_id' => $this->dormitoryId,
            'code' => $wingCode,
            'name' => $wingName,
            'gender' => $this->defaultGender,
            'is_active' => true,
            'notes' => 'Dibuat dari import kamar',
        ]);
    }

    private function uniqueWingCode(string $base): string
    {
        $base = Str::upper(Str::limit(trim($base), 18, '')) ?: 'GEDUNG';
        $code = $base;
        $counter = 2;

        while (DormitoryWing::where('dormitory_id', $this->dormitoryId)->where('code', $code)->exists()) {
            $code = $base.'-'.$counter++;
        }

        return $code;
    }

    private function ensureRoomCodeAvailable(string $code): void
    {
        $exists = DormitoryRoom::where('dormitory_id', $this->dormitoryId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            throw new \RuntimeException("Kode kamar '{$code}' sudah digunakan.");
        }
    }

    private function normalizeGender(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'putri', 'p', 'perempuan', 'female' => 'putri',
            'putra', 'l', 'laki-laki', 'male' => 'putra',
            default => $this->defaultGender,
        };
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return ! in_array(strtolower(trim((string) $value)), ['0', 'false', 'nonaktif', 'tidak'], true);
    }
}
