@extends('layouts.print')
@section('title', 'Syahadah Tasmi’ — '.($certificate->student?->name ?? ''))

@section('content')
<style>
    @page { size: A4 landscape; margin: 1.2cm; }
    body { font-family: 'Times New Roman', serif; color: #000; }
    .certificate { border: 4px double #1f3b57; padding: 22px 30px; min-height: 175mm; position: relative; }
    .inner { border: 1px solid #b08d57; padding: 24px 30px; text-align: center; }
    .institution { font-size: 13px; letter-spacing: 1px; text-transform: uppercase; }
    .institution-ar { font-family: 'Traditional Arabic', 'Amiri', serif; font-size: 18px; margin-top: 2px; }
    h1 { font-size: 26px; margin: 16px 0 2px; letter-spacing: 3px; text-transform: uppercase; }
    .title-ar { font-family: 'Traditional Arabic', 'Amiri', serif; font-size: 25px; margin: 0 0 14px; }
    .number { font-size: 11px; color: #444; margin-bottom: 14px; }
    .lead { font-size: 12px; margin: 2px 0; }
    .name { font-size: 28px; font-weight: bold; margin: 8px 0 2px; }
    .name-ar { font-family: 'Traditional Arabic', 'Amiri', serif; font-size: 27px; margin: 0 0 8px; direction: rtl; }
    .juz { font-size: 16px; font-weight: bold; margin: 10px 0; }
    .score-box { display: inline-block; border: 1px solid #1f3b57; padding: 8px 22px; margin-top: 8px; }
    .score-box .score { font-size: 24px; font-weight: bold; }
    .predikat { font-size: 14px; font-weight: bold; }
    .predikat-ar { font-family: 'Traditional Arabic', 'Amiri', serif; font-size: 20px; }
    .footer { margin-top: 26px; width: 100%; }
    .footer td { width: 50%; text-align: center; font-size: 11px; vertical-align: top; }
    .footer .space { height: 52px; }
    .small { font-size: 9px; color: #555; }
</style>

<div class="certificate">
    <div class="inner">
        <div class="institution">{{ $certificate->nama_lembaga ?? 'Departemen Tahfidz' }}</div>
        <div class="institution-ar">قسم تحفيظ القرآن الكريم</div>

        <h1>Syahadah Tasmi’</h1>
        <div class="title-ar">شَهَادَةُ التَّسْمِيعِ</div>
        <div class="number">Nomor: {{ $certificate->certificate_number }} &nbsp;|&nbsp; Kode: {{ $certificate->kode_lembaga }} &nbsp;|&nbsp; {{ $certificate->kode_bulan_tahun_cetak }}</div>

        <div class="lead">Diberikan kepada / Granted to:</div>
        <div class="name">{{ $certificate->student?->name }}</div>
        <div class="name-ar" dir="rtl">(A) {{ $certificate->student_name_arabic }}</div>

        <div class="lead">Telah menyelesaikan Tasmi’ Al-Qur’an</div>
        <div class="juz">{{ $certificate->golongan ?? $certificate->certificate_type_label }}</div>
        <div class="lead">Juz: {{ implode(', ', $certificate->juz_detail ?? []) ?: $certificate->total_juz_completed }}</div>

        <div class="score-box">
            <div class="small">Nilai Akhir</div>
            <div class="score">{{ $certificate->nilai_akhir !== null ? number_format((float) $certificate->nilai_akhir, 2) : '—' }}</div>
            <div class="predikat">{{ $certificate->predikat_label }}</div>
            <div class="predikat-ar">{{ $certificate->predikat_arabic }}</div>
        </div>

        <table class="footer">
            <tr>
                <td>Mengetahui,<br>Kepala Departemen Tahfidz<div class="space"></div>________________________</td>
                <td>{{ $certificate->issued_date?->translatedFormat('d F Y') ?? '' }}<br>Mustami’ / Penguji<div class="space"></div>________________________</td>
            </tr>
        </table>
    </div>
</div>
@endsection
