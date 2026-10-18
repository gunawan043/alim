@extends('layouts.print')
@section('title', 'Instrumen Tasmi’ — '.($participant->student?->name ?? ''))

@section('content')
<style>
    @page { size: A4 portrait; margin: 1.4cm; }
    body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    h3 { text-align: center; font-size: 12px; margin: 0 0 14px; font-weight: normal; }
    .info { width: 100%; margin-bottom: 10px; font-size: 10.5px; }
    .info td { padding: 1px 4px; vertical-align: top; }
    table.main { width: 100%; border-collapse: collapse; }
    table.main th, table.main td { border: .7px solid #000; padding: 6px; vertical-align: middle; }
    table.main th { background: #eee; text-align: center; }
    .center { text-align: center; }
    .score { font-size: 14px; font-weight: bold; }
    .note { margin-top: 8px; font-size: 9px; text-align: justify; }
    .sign { margin-top: 24px; width: 100%; }
    .sign td { border: 0; text-align: center; font-size: 10.5px; }
    .arabic { font-family: 'Traditional Arabic', 'Amiri', serif; font-size: 15px; }
</style>

<h2>Instrumen Penilaian Tasmi’ Al-Qur’an</h2>
<h3>{{ $participant->session?->session_name ?? '-' }}</h3>

<table class="info">
    <tr><td style="width:18%"><strong>Nama Santri</strong></td><td style="width:32%">: {{ $participant->student?->name }}</td><td style="width:18%"><strong>NIS</strong></td><td>: {{ $participant->student?->nis ?? '-' }}</td></tr>
    <tr><td><strong>Kelas</strong></td><td>: {{ $participant->student?->currentClassHistory?->studyGroup?->name ?? '-' }}</td><td><strong>Juz</strong></td><td>: {{ $participant->juz_label }}</td></tr>
    <tr><td><strong>Sekolah</strong></td><td>: {{ $participant->session?->school?->name ?? 'Departemen Tahfidz' }}</td><td><strong>Tanggal</strong></td><td>: {{ $participant->session?->session_date?->format('d M Y') ?? '-' }}</td></tr>
    <tr><td><strong>Materi</strong></td><td>: {{ $participant->materi_detail ?? '-' }}</td><td><strong>Mustami’</strong></td><td>: {{ $participant->mustami?->name ?? '-' }}</td></tr>
</table>

<table class="main">
    <thead><tr><th style="width:35%">Komponen Penilaian</th><th style="width:15%">Nilai</th><th>Catatan</th></tr></thead>
    <tbody>
        <tr><td>Tahfizh (Hafalan)</td><td class="center score">{{ $participant->score?->tahfizh_score_raw !== null ? number_format((float) $participant->score->tahfizh_score_raw, 2) : '................' }}</td><td>{{ $participant->score?->catatan_penilai }}</td></tr>
        <tr><td>Tajwid</td><td class="center score">{{ $participant->score?->tajwid_score_raw !== null ? number_format((float) $participant->score->tajwid_score_raw, 2) : '................' }}</td><td></td></tr>
        <tr><td>Fashohah</td><td class="center score">{{ $participant->score?->fashohah_score_raw !== null ? number_format((float) $participant->score->fashohah_score_raw, 2) : '................' }}</td><td></td></tr>
        <tr><td><strong>Nilai Akhir (rata-rata)</strong></td><td class="center score">{{ $participant->score?->nilai_akhir !== null ? number_format((float) $participant->score->nilai_akhir, 2) : '................' }}</td><td><strong>{{ $participant->score?->predikat_label ?? '................' }}</strong><br><span class="arabic">{{ $participant->score?->predikat_arabic ?? '' }}</span></td></tr>
    </tbody>
</table>

<div class="note">
    <strong>Keterangan:</strong> Nilai akhir merupakan rata-rata dari komponen Tahfizh, Tajwid, dan Fashohah.
    Predikat: Mumtaz (≥90), Jayyid Jiddan (≥80), Jayyid (≥70), Maqbul (≥60), Rasib (&lt;60).
</div>

<table class="sign">
    <tr>
        <td style="width:50%">Mustami’ / Penguji<br><br><br><br>________________________<br>{{ $participant->score?->evaluator?->name ?? $participant->mustami?->name ?? '' }}</td>
        <td style="width:50%">Koordinator Tahfidz<br><br><br><br>________________________</td>
    </tr>
</table>
@endsection
