@extends('cetak.layout')

@section('title', 'Naskah Soal')
@section('doc_title', 'NASKAH SOAL')
@section('doc_subtitle')
    {{ $paket->kisiKisi?->subject?->name ?? '-' }} &bull;
    Kelas {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }} &bull;
    TA {{ $paket->kisiKisi?->academicYear?->name ?? '-' }} &bull;
    Semester {{ ucfirst($paket->kisiKisi?->semester ?? '-') }} &bull;
    {{ strtoupper($paket->kisiKisi?->jenis_ujian ?? '-') }}
@endsection


@php $layout = $layout ?? \App\Models\PaketSoal::defaultLayout(); @endphp
<style>
    @page {
        size: {{ $layout['paper_size'] }};
        margin: {{ $layout['margin_top'] }}mm {{ $layout['margin_right'] }}mm {{ $layout['margin_bottom'] }}mm {{ $layout['margin_left'] }}mm;
    }
    body { font-size: {{ $layout['font_size'] }}pt; line-height: {{ $layout['line_height'] }}; }
    .doc-title { font-size: {{ $layout['title_font_size'] }}pt; }
    .doc-subtitle, .identitas, .petunjuk, .naskah-options, .summary-box, .naskah-note { font-size: {{ $layout['info_font_size'] }}pt; }
    .naskah-question { font-size: {{ $layout['font_size'] }}pt; }

    .identitas { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
    .identitas td { padding: 3px 4px; vertical-align: bottom; }
    .identitas .line { border-bottom: 1px solid #333; display: inline-block; min-width: 210px; }
    .petunjuk { border: 1px solid #555; padding: 6px 8px; margin-bottom: 12px; font-size: 9.5px; }
    .naskah-question { margin-bottom: 10px; page-break-inside: avoid; }
    .naskah-question-row { display: flex; gap: 8px; align-items: flex-start; }
    .naskah-number { width: 22px; flex-shrink: 0; font-weight: bold; }
    .naskah-question-body { flex: 1; }
    .naskah-options { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4px; }
    .naskah-options td { vertical-align: top; padding: 0 4px 0 0; }
    .naskah-option-label { display: inline-block; min-width: 16px; font-weight: bold; }
    .naskah-wacana { border: 1px solid #999; background: #fafafa; padding: 6px 8px; margin-bottom: 6px; font-size: 9.5px; }
    .naskah-note { text-align: center; margin-top: 18px; font-size: 9px; color: #555; }
</style>
<style>
    body { font-size: {{ $layout['font_size'] }}pt !important; line-height: {{ $layout['line_height'] }} !important; }
    .doc-title { font-size: {{ $layout['title_font_size'] }}pt !important; }
    .doc-subtitle, .identitas, .identitas td, .petunjuk, .naskah-options, .summary-box, .naskah-note { font-size: {{ $layout['info_font_size'] }}pt !important; }
    .naskah-question, .naskah-question-body { font-size: {{ $layout['font_size'] }}pt !important; }
    .naskah-wacana { font-size: {{ $layout['info_font_size'] }}pt !important; }
</style>


@section('content')
    <table class="identitas">
        <tr>
            <td style="width:80px">Nama</td>
            <td>: <span class="line">&nbsp;</span></td>
            <td style="width:90px; padding-left:18px">Kelas</td>
            <td>: <span class="line" style="min-width:80px">&nbsp;</span></td>
        </tr>
        <tr>
            <td>NISN</td>
            <td>: <span class="line">&nbsp;</span></td>
            <td style="padding-left:18px">Tanggal</td>
            <td>: <span class="line" style="min-width:80px">&nbsp;</span></td>
        </tr>
    </table>

    @if($paket->instruksi_umum)
        <div class="petunjuk"><strong>PETUNJUK UMUM:</strong><br>{!! nl2br(e($paket->instruksi_umum)) !!}</div>
    @endif

    @if($paket->waktu_pengerjaan_menit)
        <div class="summary-box" style="text-align:center">
            Waktu pengerjaan: <strong>{{ $paket->waktu_pengerjaan_menit }} menit</strong>
            &nbsp;|&nbsp; Jumlah butir: <strong>{{ $rows->count() }}</strong>
            &nbsp;|&nbsp; Kode paket: <strong>{{ $paket->kode_paket }}</strong>
        </div>
    @endif

    @include('evalusi.paket-soal.partials.naskah-questions', ['rows' => $rows])

    <div class="naskah-note">— Selamat mengerjakan —</div>
@endsection
