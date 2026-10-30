@extends('layouts.print')

@section('title', 'Lembar Monitoring Hafalan — '.($selectedStudent?->name ?? ''))

@section('content')
<style>
    @page { size: A4 landscape; margin: 1cm; }
    body { font-family: 'Times New Roman', serif; font-size: 8px; color: #000; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    h3 { text-align: center; font-size: 11px; margin: 0 0 8px; font-weight: normal; }
    .info { margin-bottom: 7px; font-size: 9px; }
    .info span { display: inline-block; margin-right: 16px; }
    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grid th, table.grid td { border: .5px solid #000; padding: 2px; vertical-align: middle; }
    table.grid th { background: #eee; font-size: 7px; text-align: center; }
    table.grid td { font-size: 7.5px; }
    table.grid tr.week-row td { background: #f1f1f1; font-weight: bold; }
    .muted { color: #777; }
    .legend { margin-top: 5px; font-size: 7.5px; }
    .sign { margin-top: 18px; width: 100%; }
    .sign td { border: 0; text-align: center; font-size: 9px; }
</style>

<h2>Lembar Monitoring Hafalan Al-Qur’an</h2>
<h3>{{ $monthLabel }}</h3>

<div class="info">
    <span><strong>Nama:</strong> {{ $selectedStudent?->name ?? '-' }}</span>
    <span><strong>NIS:</strong> {{ $selectedStudent?->nis ?? '-' }}</span>
    <span><strong>Halaqah:</strong> {{ $selectedMember?->group?->name ?? '-' }}</span>
    <span><strong>Guru/Pembimbing:</strong> {{ $selectedMember?->group?->teacher?->name ?? '-' }}</span>
</div>

<table class="grid">
    <thead>
        <tr>
            <th style="width:32px">No.</th>
            <th style="width:42px">Pekan</th>
            <th style="width:78px">Hari/Tanggal</th>
            <th>Materi Hafalan</th>
            <th>Metode Pembelajaran</th>
            <th style="width:72px">Penguasaan Materi</th>
            <th>Keterangan Target Hafalan</th>
            <th>Tugas Tikrar Hafalan</th>
            <th style="width:48px">Nilai</th>
            <th>Hasil Hafalan</th>
            <th style="width:80px">Jenis Kesalahan (Lahn)</th>
            <th style="width:48px">Nilai</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($weekGroups as $week => $rows)
            <tr class="week-row">
                <td colspan="12">Pekan {{ $week }}</td>
            </tr>
            @forelse($rows as $entry)
                <tr>
                    <td style="text-align:center">{{ $no++ }}</td>
                    <td style="text-align:center">{{ $week }}</td>
                    <td>
                        {{ $entry->setoran_date?->translatedFormat('l') }},
                        {{ $entry->setoran_date?->translatedFormat('d M Y') }}
                    </td>
                    <td>{{ $entry->materi_hafalan }}</td>
                    <td>
                        {{ $entry->metode_pembelajaran ?: '—' }}
                        <span class="muted">({{ $entry->setoran_type_label }})</span>
                    </td>
                    <td style="text-align:center">{{ $entry->penguasaan_symbol }} — {{ $entry->penguasaan_label }}</td>
                    <td>{{ $entry->keterangan_capaian ?: '—' }}@if($entry->robet_status) <span class="muted">(Robet: {{ $entry->robet_status_label }})</span>@endif</td>
                    <td>{{ $entry->tikrar_task ?: '—' }}@if($entry->tikrar_repetitions) <span class="muted">({{ $entry->tikrar_repetitions }}×)</span>@endif</td>
                    <td style="text-align:center">{{ $entry->nilai_tikrar !== null ? number_format((float) $entry->nilai_tikrar, 2) : '—' }}</td>
                    <td>{{ $entry->hasil_hafalan_catatan ?: ($entry->catatan_guru ?: '—') }}</td>
                    <td style="text-align:center">K: {{ (int) $entry->lahn_khofi_count }} · J: {{ (int) $entry->lahn_jali_count }}</td>
                    <td style="text-align:center">{{ $entry->nilai_lahn !== null ? number_format((float) $entry->nilai_lahn, 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="muted" style="text-align:center">Belum ada catatan pada pekan ini.</td></tr>
            @endforelse
        @endforeach
    </tbody>
</table>

<div class="legend">
    <strong>Keterangan:</strong> &lt; di bawah target · = sesuai target · &gt; di atas target.
    <strong>Lahn:</strong> Khofi = 1 poin per kesalahan · Jali = 2 poin per kesalahan.
    Nilai akhir tidak dihitung otomatis oleh sistem.
</div>

<table class="sign">
    <tr>
        <td>Guru/Pembimbing Tahfidz<br><br><br>__________________________</td>
        <td>Koordinator Tahfidz<br><br><br>__________________________</td>
        <td>Kepala Satuan Pendidikan<br><br><br>__________________________</td>
    </tr>
</table>
@endsection
