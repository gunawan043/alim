@extends('cetak.layout')

@php
    $atp = $perangkat->atp;
    $tps = $atp?->items?->map(fn ($item) => $item->tujuanPembelajaran)->filter() ?? collect();
    $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
    $text = function ($value) {
        if (! filled($value)) {
            return '<span class="muted">—</span>';
        }

        $value = (string) $value;
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><blockquote><table><thead><tbody><tr><th><td>';

        return preg_match('/<(p|br|ul|ol|li|strong|em|h[1-6]|blockquote|table)\b/i', $value)
            ? strip_tags($value, $allowed)
            : nl2br(e($value));
    };
    $nilai = fn (string $key) => $perangkat->desainValue($key);
    $matrix = $perangkat->assessmentMatrix();
    $gradeLabel = $perangkat->gradeLevel?->name ?? 'Semua Kelas';
    $fase = $perangkat->gradeLevel?->fase;
    $useDigitalSignature = $useDigitalSignature ?? false;
    $ttdKspBase64 = $ttdKspBase64 ?? null;
    $stampBase64 = $stampBase64 ?? null;
@endphp

@section('title', 'Modul Ajar Deep Learning KBC')
@section('doc_title', 'MODUL AJAR DEEP LEARNING')
@section('doc_subtitle', 'INTEGRASI KURIKULUM BERBASIS CINTA')

@section('content')
    <div class="section-title">A. Identitas Modul</div>
    <div class="section-body">
        <table class="info">
            <tr><td class="label">Madrasah / Sekolah</td><td class="colon">:</td><td>{{ $school?->name ?? '—' }}</td></tr>
            <tr><td class="label">Nama Guru</td><td class="colon">:</td><td>{{ $perangkat->teacher?->name ?? '—' }}</td></tr>
            <tr><td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $perangkat->subject?->name ?? '—' }}</td></tr>
            <tr><td class="label">Fase / Kelas</td><td class="colon">:</td><td>{{ $fase ? $fase.' / ' : '' }}{{ $gradeLabel }}</td></tr>
            <tr><td class="label">Materi Pokok</td><td class="colon">:</td><td>{!! $text($nilai('topik') ?? $nilai('identifikasi_materi')) !!}</td></tr>
            <tr><td class="label">Alokasi Waktu</td><td class="colon">:</td><td>{{ $perangkat->alokasiWaktu() }}</td></tr>
            <tr><td class="label">Tahun Pelajaran</td><td class="colon">:</td><td>{{ $perangkat->academicYear?->name ?? '—' }}</td></tr>
        </table>
    </div>

    <div class="section-title">B. Identifikasi Kesiapan Peserta Didik</div>
    <div class="section-body">
        {!! $text($nilai('kbc_kesiapan_belajar') ?? $nilai('identifikasi_peserta_didik')) !!}
    </div>

    <div class="section-title">C. Tema Kurikulum Berbasis Cinta</div>
    <div class="section-body">
        <table class="inside">
            <tr><td class="label">Topik Panca Cinta</td><td>{!! $text($nilai('pilar_kbc')) !!}</td></tr>
            <tr><td class="label">Tema KBC</td><td>{!! $text($nilai('tema_kbc')) !!}</td></tr>
            <tr><td class="label">Materi Insersi</td><td>{!! $text($nilai('materi_insersi')) !!}</td></tr>
        </table>
    </div>

    <div class="section-title">D. Karakteristik Materi Pelajaran</div>
    <div class="section-body">{!! $text($nilai('kbc_karakteristik_materi')) !!}</div>

    <div class="section-title">E. Dimensi Profil Lulusan</div>
    <div class="section-body">{!! $text($nilai('profil_lulusan')) !!}</div>

    <div class="section-title">F. Desain Pembelajaran</div>
    <div class="section-body">
        <table class="inside">
            <tr>
                <td class="label">Capaian Pembelajaran</td>
                <td>
                    @forelse($cps as $cp)
                        <p><strong>[Fase {{ $cp->fase }}{{ $cp->elemen ? ' — '.$cp->elemen : '' }}]</strong> {{ $cp->deskripsi }}</p>
                    @empty
                        <span class="muted">—</span>
                    @endforelse
                </td>
            </tr>
            <tr><td class="label">Lintas Disiplin Ilmu</td><td>{!! $text($nilai('lintas_disiplin')) !!}</td></tr>
            <tr>
                <td class="label">Tujuan Pembelajaran</td>
                <td>
                    @forelse($tps as $tp)
                        <p><strong>{{ $tp->kode_tp }}</strong> — {{ $tp->deskripsi }}</p>
                    @empty
                        <span class="muted">—</span>
                    @endforelse
                </td>
            </tr>
            <tr><td class="label">Indikator Ketercapaian</td><td>{!! $text($nilai('kbc_indikator') ?? $nilai('iktp')) !!}</td></tr>
            <tr><td class="label">Iklim / Budaya Madrasah</td><td>{!! $text($nilai('kbc_iklim_budaya')) !!}</td></tr>
            <tr><td class="label">Topik Pembelajaran Kontekstual</td><td>{!! $text($nilai('kbc_topik_kontekstual')) !!}</td></tr>
        </table>
    </div>

    <div class="section-title">G. Kerangka Pembelajaran</div>
    <div class="section-body">
        <table class="inside">
            <tr><td class="label">Praktik Pedagogik</td><td>{!! $text($nilai('praktik_pedagogik')) !!}</td></tr>
            <tr><td class="label">Kemitraan Pembelajaran</td><td>{!! $text($nilai('kemitraan_pembelajaran')) !!}</td></tr>
            <tr><td class="label">Lingkungan Pembelajaran</td><td>{!! $text($nilai('lingkungan_pembelajaran')) !!}</td></tr>
            <tr><td class="label">Pemanfaatan Digital</td><td>{!! $text($nilai('pemanfaatan_digital')) !!}</td></tr>
            <tr><td class="label">Diferensiasi Pembelajaran</td><td>{!! $text($nilai('kbc_diferensiasi')) !!}</td></tr>
        </table>
    </div>

    <div class="section-title">H. Langkah-Langkah Pembelajaran</div>
    <div class="section-body">
        <table class="data">
            <thead><tr><th style="width:24%">Tahap</th><th>Aktivitas Pembelajaran</th></tr></thead>
            <tbody>
                <tr><td>Pendahuluan / Awal</td><td>{!! $text($nilai('kegiatan_awal')) !!}</td></tr>
                <tr><td rowspan="3">Kegiatan Inti</td><td><strong>Memahami</strong><br>{!! $text($nilai('pengalaman_memahami')) !!}</td></tr>
                <tr><td><strong>Mengaplikasi</strong><br>{!! $text($nilai('pengalaman_mengaplikasi')) !!}</td></tr>
                <tr><td><strong>Merefleksi</strong><br>{!! $text($nilai('pengalaman_refleksi')) !!}</td></tr>
                <tr><td>Penutup</td><td>{!! $text($nilai('penutup')) !!}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="section-title">I. Asesmen Pembelajaran</div>
    <div class="section-body">
        <table class="data">
            <thead><tr><th style="width:18%">Jenis Asesmen</th><th style="width:20%">Teknik</th><th style="width:28%">Indikator</th><th>Instrumen/Bukti</th></tr></thead>
            <tbody>
                @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_ROWS as $rowKey => $rowLabel)
                    <tr>
                        <td>{{ $matrix[$rowKey]['jenis'] ?: $rowLabel }}</td>
                        @foreach(\App\Models\PerangkatPembelajaran::ASSESSMENT_COLUMNS as $columnKey => $columnLabel)
                            @continue($columnKey === 'jenis')
                            <td>{!! $text($matrix[$rowKey][$columnKey] ?? '') !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('cetak.partials.projects', [
        'kokurikulerProjects' => $kokurikulerProjects ?? collect(),
        'curriculumProfile' => $curriculumProfile ?? null,
    ])

    <table class="sign">
        <tr>
            <td style="width:50%">Mengetahui,<br>Kepala Madrasah
                <div class="space"></div><div class="name">__________________________</div>
            </td>
            <td style="width:50%">{{ $school?->city?->name ?? 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                <div class="space"></div><div class="name">__________________________</div>
            </td>
        </tr>
    </table>
@endsection
