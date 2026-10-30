<?php

namespace App\Services\Dashboard;

use App\Models\School;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kartu kalender pendidikan bulan berjalan dan jadwal shalat.
 *
 * Kaldik: hanya kegiatan yang beririsan dengan bulan ini.
 * Jadwal shalat: diambil dari API Aladhan lalu di-cache per tanggal + kota.
 * Bila jaringan gagal, nilai dikembalikan null (bukan angka karangan).
 */
class DashboardCalendarService
{
    private const PRAYER_CACHE_HOURS = 12;

    /**
     * Kegiatan kaldik yang beririsan dengan bulan berjalan.
     *
     * @return array{month_label: string, items: array<int, array<string, mixed>>, total: int}
     */
    public function kaldik(array $filters): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $yearIds = [];

        if (! empty($filters['academic_year'])) {
            $yearIds = DB::table('academic_years')
                ->where('name', $filters['academic_year']->name)
                ->pluck('id')
                ->all();
        }

        try {
            $rows = DB::table('kaldik')
                ->whereNull('deleted_at')
                ->where('is_active', 1)
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString())
                ->when($yearIds !== [], fn ($query) => $query->whereIn('academic_year_id', $yearIds))
                ->orderBy('start_date')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('DashboardCalendarService::kaldik — '.$e->getMessage());
            $rows = collect();
        }

        $items = $rows->map(function ($row) use ($start, $end) {
            $from = Carbon::parse($row->start_date);
            $to = Carbon::parse($row->end_date ?? $row->start_date);
            $clampedFrom = $from->lt($start) ? $start->copy() : $from;
            $clampedTo = $to->gt($end) ? $end->copy() : $to;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'type' => $row->type,
                'category' => $row->category,
                'color' => $row->color ?: '#405189',
                'start_date' => $clampedFrom->toDateString(),
                'end_date' => $clampedTo->toDateString(),
                'date_label' => $clampedFrom->isSameDay($clampedTo)
                    ? $clampedFrom->translatedFormat('d M')
                    : $clampedFrom->translatedFormat('d M').' – '.$clampedTo->translatedFormat('d M'),
                'is_ongoing' => $from->lte(now()) && $to->gte(now()->startOfDay()),
                'is_past' => $to->lt(now()->startOfDay()),
                'days_left' => $to->gte(now()) ? (int) now()->startOfDay()->diffInDays($to, false) : null,
                'description' => $row->description,
            ];
        })->values();

        return [
            'month_label' => now()->translatedFormat('F Y'),
            'items' => $items->all(),
            'total' => $items->count(),
            'ongoing' => $items->where('is_ongoing', true)->count(),
        ];
    }

    /**
     * Jadwal shalat hari ini untuk kota unit pengguna.
     *
     * @return array{
     *   available: bool, city: ?string, date_label: ?string,
     *   hijri: ?string, times: array<int, array{key: string, label: string, time: string, is_next: bool, is_past: bool}>,
     *   next: ?array{label: string, time: string}, now: string, source: string
     * }
     */
    public function prayerTimes(array $filters): array
    {
        $school = $filters['school'] ?? $this->defaultSchoolFor($filters);
        $city = $this->resolveCity($school) ?? $this->defaultCity();

        $empty = [
            'available' => false,
            'city' => $city,
            'date_label' => now()->translatedFormat('l, d F Y'),
            'hijri' => null,
            'times' => [],
            'next' => null,
            'now' => now()->format('H:i'),
            'source' => 'Aladhan API',
            'reason' => $city ? 'Jadwal shalat belum dapat dimuat.' : 'Kota unit belum terdeteksi.',
        ];

        if (! $city) {
            return $empty;
        }

        $date = now()->toDateString();
        $cacheKey = 'dashboard.prayer.'.str($city)->slug().'.'.$date;

        $data = Cache::remember($cacheKey, now()->addHours(self::PRAYER_CACHE_HOURS), function () use ($city, $date) {
            try {
                $response = Http::timeout(8)->retry(2, 300)->get(
                    'https://api.aladhan.com/v1/timingsByCity/'.$date,
                    ['city' => $city, 'country' => 'Indonesia', 'method' => 20],
                );

                if (! $response->successful()) {
                    return null;
                }

                $payload = $response->json('data');

                return [
                    'timings' => $payload['timings'] ?? [],
                    'hijri' => $payload['date']['hijri'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning('DashboardCalendarService::prayerTimes — '.$e->getMessage());

                return null;
            }
        });

        if (! $data || empty($data['timings'])) {
            return $empty;
        }

        $labels = [
            'Fajr' => 'Subuh',
            'Dhuhr' => 'Dzuhur',
            'Asr' => 'Ashar',
            'Maghrib' => 'Maghrib',
            'Isha' => 'Isya',
        ];

        $nowTime = now()->format('H:i');
        $times = [];
        $next = null;

        foreach ($labels as $key => $label) {
            $time = substr((string) ($data['timings'][$key] ?? ''), 0, 5);

            if ($time === '') {
                continue;
            }

            $isPast = $time <= $nowTime;
            $times[] = [
                'key' => $key,
                'label' => $label,
                'time' => $time,
                'is_past' => $isPast,
                'is_next' => false,
            ];

            if (! $isPast && $next === null) {
                $next = ['label' => $label, 'time' => $time];
            }
        }

        foreach ($times as $index => $row) {
            $times[$index]['is_next'] = $next !== null && $row['label'] === $next['label'];
        }

        $hijri = null;
        if (! empty($data['hijri'])) {
            $hijri = trim(($data['hijri']['day'] ?? '').' '.($data['hijri']['month']['en'] ?? '').' '.($data['hijri']['year'] ?? ''));
            $hijri = $hijri !== '' ? $hijri.' H' : null;
        }

        return [
            'available' => true,
            'city' => $city,
            'date_label' => now()->translatedFormat('l, d F Y'),
            'hijri' => $hijri,
            'times' => $times,
            'next' => $next,
            'now' => $nowTime,
            'source' => 'Aladhan API',
        ];
    }

    /**
     * Unit cadangan bila pengguna belum terikat satu sekolah (level yayasan):
     * pakai unit pertama yang memiliki alamat, supaya jadwal shalat tetap terisi.
     */
    private function defaultSchoolFor(array $filters): ?School
    {
        $ids = $filters['school_ids'] ?? [];

        $query = School::query()->whereNotNull('address')->where('address', '!=', '');

        if (count($ids) === 1) {
            return $query->whereKey($ids[0])->first();
        }

        return $query->orderBy('name')->first();
    }

    /**
     * Kota cadangan dari konfigurasi aplikasi.
     */
    private function defaultCity(): ?string
    {
        // Kota default opsional: diisi administrator pada system_settings.
        try {
            $configured = SystemSetting::query()
                ->where('key', 'dashboard_prayer_city')
                ->value('value');
        } catch (\Throwable) {
            $configured = null;
        }

        return $configured ?: null;
    }

    /**
     * Kota dari data unit, atau dari alamat bila kolom kota kosong.
     */
    private function resolveCity($school): ?string
    {
        // Tabel `cities` belum tentu ada pada instalasi lokal; pakai alamat unit.
        $address = (string) ($school?->getRawOriginal('address') ?? $school?->address ?? '');
        $name = (string) ($school?->name ?? '');

        if ($address === '' && $name === '') {
            return null;
        }

        $known = ['Mataram', 'Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang', 'Malang', 'Bogor', 'Denpasar', 'Makassar'];

        foreach ($known as $candidate) {
            if (stripos($address, $candidate) !== false) {
                return $candidate;
            }
        }

        // Fallback: nama unit biasanya memuat kota ("... Abu Hurairah Mataram").
        foreach ($known as $candidate) {
            if (stripos($name, $candidate) !== false) {
                return $candidate;
            }
        }

        $segments = array_filter(array_map('trim', explode(',', $address)));

        return $segments !== [] ? end($segments) : null;
    }
}
