@php
    /**
     * Banner sambutan: salam tetap ada, tetapi isi utamanya adalah apa yang
     * terjadi hari ini — kegiatan kaldik yang sedang berjalan dan waktu shalat
     * berikutnya. Tidak ada angka yang diulang dari kartu KPI di bawahnya.
     */
    $calendar = $calendar ?? [];
    $kaldikItems = $calendar['kaldik']['items'] ?? [];
    $nextPrayer = $calendar['prayer']['next'] ?? null;

    $todayItems = collect($kaldikItems)
        ->filter(fn ($item) => ! empty($item['is_ongoing']) || ($item['days_left'] ?? null) === 0)
        ->take(3);
@endphp

<div class="welcome-banner mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
        <div class="flex-grow-1" style="min-width: 260px;">
            <span class="banner-eyebrow">{{ $data['date'] ?? '' }}</span>
            <h3 class="banner-title">
                Assalamu'alaikum,
                <span class="banner-name">{{ $data['name'] ?? 'Pengguna' }}</span>
            </h3>
            <div class="banner-meta">
                <span>{{ $data['jabatan'] ?? 'Dashboard' }}</span>
                @if (! empty($data['school']))
                    <span>{{ $data['school'] }}</span>
                @endif
                @foreach (($data['tasks'] ?? []) as $task)
                    <span class="banner-chip">{{ $task }}</span>
                @endforeach
            </div>
        </div>

        <div class="banner-today">
            <div class="banner-today-label">Hari ini</div>

            @forelse ($todayItems as $item)
                <div class="banner-today-item">
                    <span>{{ $item['name'] }}</span>
                    @if (! empty($item['type']))
                        <span class="text-white-50">· {{ $item['type'] }}</span>
                    @endif
                </div>
            @empty
                @if ($nextPrayer)
                    <div class="banner-today-item">
                        <span class="time">{{ $nextPrayer['time'] }}</span>
                        <span>{{ $nextPrayer['label'] }}</span>
                    </div>
                @else
                    <div class="banner-today-empty">Tidak ada agenda terjadwal</div>
                @endif
            @endforelse

            @if ($todayItems->isNotEmpty() && $nextPrayer)
                <div class="banner-today-item">
                    <span class="time">{{ $nextPrayer['time'] }}</span>
                    <span>{{ $nextPrayer['label'] }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
