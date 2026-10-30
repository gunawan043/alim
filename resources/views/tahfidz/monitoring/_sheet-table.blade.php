@php
    $isPrint = $print ?? false;
    $no = 1;
@endphp

<table class="table table-bordered table-sm align-middle monitoring-sheet-table mb-0">
    <thead class="table-light">
        <tr>
            <th style="width:42px" class="text-center">No.</th>
            <th style="width:62px" class="text-center">Pekan</th>
            <th style="width:105px">Hari/Tanggal</th>
            <th style="min-width:150px">Materi Hafalan</th>
            <th style="min-width:130px">Metode Pembelajaran</th>
            <th style="width:105px" class="text-center">Penguasaan Materi</th>
            <th style="min-width:130px">Keterangan Target Hafalan</th>
            <th style="min-width:130px">Tugas Tikrar Hafalan</th>
            <th style="width:70px" class="text-center">Nilai</th>
            <th style="min-width:120px">Hasil Hafalan</th>
            <th style="width:112px" class="text-center">Jenis Kesalahan (Lahn)</th>
            <th style="width:70px" class="text-center">Nilai</th>
            @if(! $isPrint && $canInput)
                <th style="width:62px" class="text-center">Aksi</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($weekGroups as $week => $rows)
            <tr class="table-secondary">
                <td colspan="{{ ! $isPrint && $canInput ? 13 : 12 }}" class="fw-semibold">
                    <i class="ri-calendar-line me-1"></i>Pekan {{ $week }}
                </td>
            </tr>

            @forelse($rows as $entry)
                @php
                    $penguasaanColor = match ($entry->capaian_target) {
                        'melampaui' => 'success',
                        'tercapai' => 'primary',
                        'belum_tercapai' => 'warning',
                        default => 'secondary',
                    };
                    $entryPayload = $entry->only([
                        'id', 'setoran_date', 'setoran_type', 'metode_pembelajaran',
                        'juz', 'surah_start_name', 'ayat_start', 'surah_end_name', 'ayat_end',
                        'halaman_start', 'baris_start', 'halaman_end', 'baris_end', 'capaian_target', 'keterangan_capaian',
                        'tikrar_task', 'tikrar_repetitions', 'nilai_tikrar', 'robet_status',
                        'hasil_hafalan_catatan', 'lahn_khofi_count', 'lahn_jali_count', 'nilai_lahn', 'catatan_guru',
                    ]);
                    $entryPayload['setoran_date'] = $entry->setoran_date?->format('Y-m-d');
                @endphp
                <tr>
                    <td class="text-center text-muted">{{ $no++ }}</td>
                    <td class="text-center">{{ $week }}</td>
                    <td>
                        <div>{{ $entry->setoran_date?->translatedFormat('l') }}</div>
                        <div class="text-muted small">{{ $entry->setoran_date?->translatedFormat('d M Y') }}</div>
                    </td>
                    <td class="small">{{ $entry->materi_hafalan }}</td>
                    <td class="small">
                        {{ $entry->metode_pembelajaran ?: '—' }}
                        <div class="text-muted" style="font-size:.7rem">Jenis: {{ $entry->setoran_type_label }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $penguasaanColor }}-subtle text-{{ $penguasaanColor }}"
                              title="{{ $entry->penguasaan_label }}">
                            {{ $entry->penguasaan_symbol }}
                        </span>
                    </td>
                    <td class="small">{{ $entry->keterangan_capaian ?: '—' }}@if($entry->robet_status)<div class="text-muted" style="font-size:.7rem">Robet: {{ $entry->robet_status_label }}</div>@endif</td>
                    <td class="small">{{ $entry->tikrar_task ?: '—' }}@if($entry->tikrar_repetitions)<div class="text-muted" style="font-size:.7rem">{{ $entry->tikrar_repetitions }}× pengulangan</div>@endif</td>
                    <td class="text-center fw-semibold">{{ $entry->nilai_tikrar !== null ? number_format((float) $entry->nilai_tikrar, 2) : '—' }}</td>
                    <td class="small">{{ $entry->hasil_hafalan_catatan ?: ($entry->catatan_guru ?: '—') }}</td>
                    <td class="text-center small">
                        <span class="badge bg-warning-subtle text-warning" title="Lahn Khofi (-1 per kesalahan)">
                            K: {{ (int) $entry->lahn_khofi_count }}
                        </span>
                        <span class="badge bg-danger-subtle text-danger" title="Lahn Jali (-2 per kesalahan)">
                            J: {{ (int) $entry->lahn_jali_count }}
                        </span>
                    </td>
                    <td class="text-center fw-semibold">{{ $entry->nilai_lahn !== null ? number_format((float) $entry->nilai_lahn, 2) : '—' }}</td>
                    @if(! $isPrint && $canInput)
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    title="Edit catatan"
                                    onclick="openMonitoringEntryModal(JSON.parse(this.dataset.entry))"
                                    data-entry="{{ json_encode($entryPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                                <i class="ri-edit-line"></i>
                            </button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ! $isPrint && $canInput ? 13 : 12 }}" class="text-center text-muted py-2 small">
                        Belum ada catatan pada pekan ini.
                    </td>
                </tr>
            @endforelse
        @endforeach
    </tbody>
</table>
