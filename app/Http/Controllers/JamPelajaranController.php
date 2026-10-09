<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JamPelajaranController extends Controller
{
    public const DAYS = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    /**
     * Template default jam pelajaran (6 slot, slot 3 istirahat).
     */
    private const TEMPLATE = [
        [1, 'Jam 1', '07:00', '07:45', 0],
        [2, 'Jam 2', '07:45', '08:30', 0],
        [3, 'Istirahat', '08:30', '09:00', 1],
        [4, 'Jam 3', '09:00', '09:45', 0],
        [5, 'Jam 4', '09:45', '10:30', 0],
        [6, 'Jam 5', '10:30', '11:15', 0],
    ];

    /**
     * Daftar master jam pelajaran sekolah.
     */
    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $schools = $this->availableSchools($request);

        $slots = collect();
        if ($schoolId) {
            $slots = DB::table('class_schedule_slots')
                ->where('school_id', $schoolId)
                ->orderBy('day_of_week')
                ->orderBy('slot_number')
                ->get();
        }

        $slotsByDay = $slots->groupBy('day_of_week');

        $stats = [
            'total' => $slots->count(),
            'kbm' => $slots->where('is_break', 0)->count(),
            'istirahat' => $slots->where('is_break', 1)->count(),
            'hari' => $slotsByDay->keys()->count(),
        ];

        return view('jam-pelajaran.index', [
            'userId' => $userId,
            'days' => self::DAYS,
            'slotsByDay' => $slotsByDay,
            'stats' => $stats,
            'school' => $schoolId ? School::find($schoolId) : null,
            'schools' => $schools,
            'schoolId' => $schoolId,
            'canManage' => $this->canManage($request),
        ]);
    }

    /**
     * Tambah satu slot jam pelajaran.
     */
    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId) {
            return back()->with('error', 'Konteks sekolah tidak ditemukan. Pilih sekolah terlebih dahulu.');
        }

        $data = $this->validated($request, $schoolId);
        $this->checkOverlap($schoolId, $data['day_of_week'], $data['time_start'], $data['time_end']);
        $this->checkSlotNumber($schoolId, $data['day_of_week'], $data['slot_number']);

        DB::table('class_schedule_slots')->insert(array_merge($data, [
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Jam pelajaran berhasil ditambahkan.');
    }

    /**
     * Perbarui satu slot jam pelajaran.
     */
    public function update(Request $request, string $userId, string $slotId)
    {
        $this->authorizeManage($request);

        $slot = DB::table('class_schedule_slots')->where('id', $slotId)->first();
        abort_unless($slot, 404, 'Jam pelajaran tidak ditemukan.');

        $schoolId = $this->resolveSchoolId($request) ?: $slot->school_id;
        abort_unless($slot->school_id === $schoolId, 403, 'Slot ini bukan milik sekolah Anda.');

        $data = $this->validated($request, $schoolId, $slot->id);
        $this->checkOverlap($schoolId, $data['day_of_week'], $data['time_start'], $data['time_end'], $slot->id);
        $this->checkSlotNumber($schoolId, $data['day_of_week'], $data['slot_number'], $slot->id);

        DB::table('class_schedule_slots')->where('id', $slot->id)->update(array_merge($data, [
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Jam pelajaran berhasil diperbarui.');
    }

    /**
     * Hapus satu slot jam pelajaran.
     */
    public function destroy(Request $request, string $userId, string $slotId)
    {
        $this->authorizeManage($request);

        $slot = DB::table('class_schedule_slots')->where('id', $slotId)->first();
        abort_unless($slot, 404, 'Jam pelajaran tidak ditemukan.');

        $schoolId = $this->resolveSchoolId($request) ?: $slot->school_id;
        abort_unless($slot->school_id === $schoolId, 403, 'Slot ini bukan milik sekolah Anda.');

        DB::table('class_schedule_slots')->where('id', $slot->id)->delete();

        return back()->with('success', 'Jam pelajaran "'.($slot->label ?? '').'" berhasil dihapus.');
    }

    /**
     * Terapkan template jam pelajaran default untuk satu hari.
     */
    public function template(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId) {
            return back()->with('error', 'Konteks sekolah tidak ditemukan.');
        }

        $day = (int) $request->input('day_of_week');
        if (! isset(self::DAYS[$day])) {
            return back()->with('error', 'Hari tidak valid.');
        }

        $existing = DB::table('class_schedule_slots')
            ->where('school_id', $schoolId)
            ->where('day_of_week', $day)
            ->count();

        if ($existing > 0) {
            return back()->with('warning', self::DAYS[$day].' sudah memiliki '.$existing.' slot. Hapus atau ubah slot yang ada terlebih dahulu.');
        }

        $rows = [];
        foreach (self::TEMPLATE as [$slotNumber, $label, $start, $end, $isBreak]) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'school_id' => $schoolId,
                'slot_number' => $slotNumber,
                'label' => $label,
                'time_start' => $start,
                'time_end' => $end,
                'is_break' => $isBreak,
                'day_of_week' => $day,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('class_schedule_slots')->insert($rows);

        return back()->with('success', 'Template jam pelajaran diterapkan untuk '.self::DAYS[$day].'.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $schoolId, ?string $ignoreId = null): array
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:1,7',
            'slot_number' => 'required|integer|between:1,30',
            'label' => 'required|string|max:30',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'is_break' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        return [
            'day_of_week' => (int) $validated['day_of_week'],
            'slot_number' => (int) $validated['slot_number'],
            'label' => $validated['label'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'is_break' => $request->boolean('is_break') ? 1 : 0,
            'is_active' => $request->boolean('is_active', true) ? 1 : 0,
        ];
    }

    private function checkSlotNumber(string $schoolId, int $day, int $slotNumber, ?string $ignoreId = null): void
    {
        $exists = DB::table('class_schedule_slots')
            ->where('school_id', $schoolId)
            ->where('day_of_week', $day)
            ->where('slot_number', $slotNumber)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'slot_number' => 'Nomor jam '.$slotNumber.' sudah dipakai pada hari '.self::DAYS[$day].'.',
            ]);
        }
    }

    private function checkOverlap(string $schoolId, int $day, string $start, string $end, ?string $ignoreId = null): void
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);

        $existing = DB::table('class_schedule_slots')
            ->where('school_id', $schoolId)
            ->where('day_of_week', $day)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get(['label', 'time_start', 'time_end']);

        foreach ($existing as $slot) {
            if ($startTs < strtotime($slot->time_end) && $endTs > strtotime($slot->time_start)) {
                throw ValidationException::withMessages([
                    'time_start' => "Rentang waktu bentrok dengan \"{$slot->label}\" (".substr($slot->time_start, 0, 5).'–'.substr($slot->time_end, 0, 5).').',
                ]);
            }
        }
    }

    private function resolveSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId')
            ?? $request->session()->get('sa_school_id')
            ?? $request->input('school_id');

        return $schoolId ? (string) $schoolId : null;
    }

    /**
     * @return Collection<int, School>
     */
    private function availableSchools(Request $request): Collection
    {
        if ($request->attributes->get('isGlobalView')) {
            return School::orderBy('name')->get(['id', 'name']);
        }

        return collect();
    }

    private function canManage(Request $request): bool
    {
        return canPermission('jadwalkbm.write')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.read')
            || canPermission('jadwal_kbm_view')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access')
        ), 403, 'Anda tidak memiliki akses ke pengaturan jam pelajaran.');
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.write')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access')
        ), 403, 'Anda tidak memiliki akses untuk mengubah jam pelajaran.');
    }
}
