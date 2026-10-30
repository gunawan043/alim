@extends('layouts.print')

@section('title', 'Buku Nilai Tahfidz — '.($group?->name ?? ''))

@section('content')
<style>
    @page { size: A4 landscape; margin: 1cm; }
    body { font-family: 'Times New Roman', serif; font-size: 9px; color: #000; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    h3 { text-align: center; font-size: 10px; margin: 0 0 8px; font-weight: normal; }
    .info { margin-bottom: 6px; font-size: 9px; }
    .info span { display: inline-block; margin-right: 16px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: .5px solid #000; padding: 3px; vertical-align: middle; }
    table.grid th { background: #eee; text-align: center; font-size: 8px; }
    table.grid td.center { text-align: center; }
    .muted { color: #777; }
    .sign { margin-top: 18px; width: 100%; }
    .sign td { border: 0; text-align: center; font-size: 9px; }
</style>

<h2>Buku Nilai Tahfidz</h2>
<h3>{{ $group?->name }} · {{ ucfirst($semester) }} · {{ $activeYear?->name }}</h3>

<div class="info">
    <span><strong>Halaqah:</strong> {{ $group?->name ?? '-' }}</span>
    <span><strong>Kelas:</strong> {{ $group?->studyGroup?->name ?? '-' }}</span>
    <span><strong>Guru:</strong> {{ $group?->teacher?->name ?? '-' }}</span>
    <span><strong>Santri:</strong> {{ $stats['students'] }}</span>
</div>

<table class="grid">
    <thead>
        <tr>
            <th>No</th><th>Santri</th><th>Ziyadah</th><th>Tikrar</th><th>Murajaah</th><th>Robet</th>
            <th>Nilai Tikrar</th><th>Lahn Khofi</th><th>Lahn Jali</th><th>Nilai Lahn</th>
            <th>Rata Setoran</th><th>Tasmi'</th><th>Nilai Akhir</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['student']?->name ?? '—' }}<br><span class="muted">{{ $row['student']?->nis }}</span></td>
                <td class="center">{{ $row['ziyadah'] }}</td>
                <td class="center">{{ $row['tikrar'] }}</td>
                <td class="center">{{ $row['murajaah'] }}</td>
                <td class="center">{{ $row['robet'] }}</td>
                <td class="center">{{ $row['tikrar_avg'] !== null ? number_format((float) $row['tikrar_avg'], 2) : '—' }}</td>
                <td class="center">{{ $row['lahn_khofi'] }}</td>
                <td class="center">{{ $row['lahn_jali'] }}</td>
                <td class="center">{{ $row['lahn_avg'] !== null ? number_format((float) $row['lahn_avg'], 2) : '—' }}</td>
                <td class="center">{{ $row['setoran_avg'] !== null ? number_format((float) $row['setoran_avg'], 2) : '—' }}</td>
                <td class="center">{{ $row['tasmi_avg'] !== null ? number_format((float) $row['tasmi_avg'], 2) : '—' }}</td>
                <td class="center muted">—</td>
            </tr>
        @empty
            <tr><td colspan="13" class="center muted">Belum ada data.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="muted">Nilai akhir tidak dihitung otomatis. Kolom nilai akhir diisi setelah aturan penilaian resmi pondok ditetapkan.</p>

<table class="sign">
    <tr>
        <td>Guru Tahfidz<br><br><br>________________________</td>
        <td>Koordinator Tahfidz<br><br><br>________________________</td>
        <td>Kepala Satuan Pendidikan<br><br><br>________________________</td>
    </tr>
</table>
@endsection
