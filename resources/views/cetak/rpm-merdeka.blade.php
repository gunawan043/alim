@extends('cetak.layout')

@php
    $atp = $perangkat->atp;
    $tps = $atp?->items?->map(fn ($item) => $item->tujuanPembelajaran)->filter() ?? collect();
    $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
    $text = fn ($value) => filled($value) ? nl2br(e($value)) : '<span class="muted">—</span>';
    $nilai = fn (string $key) => $perangkat->desainValue($key);
    $matrix = $perangkat->assessmentMatrix();
    $gradeLabel = $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas';
    $fase = $perangkat->gradeLevel?->fase;
@endphp

@section('title', 'Modul Ajar Kurikulum Merdeka')
@section('doc_title', 'MODUL AJAR KURIKULUM MERDEKA')
@section('doc_subtitle', '')

@section('content')
    <div class="section-title">I. Informasi Umum</div>
    <div class="section-body">
        <table class="info">
            <tr><td class="label">Nama Penyusun</td><td class="colon">:</td><td>{{ $perangkat->teacher?->name ?? '—' }}</td>
                <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '—' }}</td></tr>
            <tr><td class="label">Tahun Penyusunan</td><td class="colon">:</td><td>{{ $perangkat->academicYear?->name ?? '—' }}</td>
                <td class="label">Fase / Kelas</td><td class="colon">:</td><td>{{ $fase ? $fase.' / ' : '' }}{{ $gradeLabel }}</td></tr>
            <tr><td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $perangkat->subject?->name ?? '—' }}</td>
                <td class="label">Alokasi Waktu</td><td class="colon">:</td><td>{{ $perangkat->alokasiWaktu() }}</td></tr>
        </table>
        <table class="inside">
            <tr><td class="label">Tema / Materi Pokok</td><td>{!! $text($nilai('topik') ?? $nilai('identifikasi_materi')) !!}</td></tr>
            <tr><td class="label">Kompetensi Awal</td><td>{!! $text($nilai('kompetensi_awal')) !!}</td></tr>
            <tr><td class="label">Profil Pelajar Pancasila</td><td>{!! $text($nilai('profil_lulusan')) !!}</td></tr>
            <tr><td class="label">Sarana dan Prasarana</td><td>{!! $text($nilai('sarana_prasarana')) !!}</td></tr>
            <tr><td class="label">Target Peserta Didik</td><td>{!! $text($nilai('target_peserta_didik')) !!}</td></tr>
            <tr><td class="label">Model Pembelajaran</td><td>{!! $text($nilai('model_pembelajaran')) !!}</td></tr>
        </table>
    </div>

    <div class="section-title">II. Komponen Inti</div>
    <div class="section-body">
        <table class="inside">
            <tr><td class="label">Capaian Pembelajaran</td><td>
                @forelse($cps as $cp)
                    <p><strong>[Fase {{ $cp->fase }}{{ $cp->elemen ? ' — '.$cp->elemen : '' }}]</strong> {{ $cp->deskripsi }}</p>
                @empty <span class="muted">—</span> @endforelse
            </td></tr>
            <tr><td class="label">Tujuan Pembelajaran</td><td>
                @forelse($tps as $tp)
                    <p><strong>{{ $tp->kode_tp }}</strong> — {{ $tp->deskripsi }}</p>
                @empty <span class="muted">—</span> @endforelse
            </td></tr>
            <tr><td class="label">Indikator Ketercapaian (IKTP)</td><td>{!! $text($nilai('iktp')) !!}</td></tr>
            <tr><td class="label">Pemahaman Bermakna</td><td>{!! $text($nilai('merdeka_pemahaman_bermakna') ?? $nilai('pemahaman_bermakna')) !!}</td></tr>
            <tr><td class="label">Pertanyaan Pemantik</td><td>{!! $text($nilai('merdeka_pertanyaan_pemantik') ?? $nilai('pertanyaan_pemantik')) !!}</td></tr>
        </table>

        <h6 class="small fw-semibold mt-3">Kegiatan Pembelajaran</h6>
        <table class="data">
            <thead><tr><th style="width:24%">Tahap</th><th>Aktivitas Pembelajaran</th></tr></thead>
            <tbody>
                <tr><td>Kegiatan Pendahuluan</td><td>{!! $text($nilai('kegiatan_awal')) !!}</td></tr>
                <tr><td rowspan="3">Kegiatan Inti</td><td><strong>Memahami</strong><br>{!! $text($nilai('pengalaman_memahami')) !!}</td></tr>
                <tr><td><strong>Mengaplikasikan</strong><br>{!! $text($nilai('pengalaman_mengaplikasi')) !!}</td></tr>
                <tr><td><strong>Merefleksikan</strong><br>{!! $text($nilai('pengalaman_refleksi')) !!}</td></tr>
                <tr><td>Kegiatan Penutup</td><td>{!! $text($nilai('penutup')) !!}</td></tr>
            </tbody>
        </table>

        <h6 class="small fw-semibold mt-3">Asesmen</h6>
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

        <table class="inside">
            <tr><td class="label">Pengayaan</td><td>{!! $text($nilai('pengayaan')) !!}</td></tr>
            <tr><td class="label">Remedial</td><td>{!! $text($nilai('remedial')) !!}</td></tr>
            <tr><td class="label">Refleksi Guru</td><td>{!! $text($nilai('refleksi_guru')) !!}</td></tr>
            <tr><td class="label">Refleksi Peserta Didik</td><td>{!! $text($nilai('refleksi_peserta_didik')) !!}</td></tr>
        </table>
    </div>

    <div class="section-title">III. Lampiran</div>
    <div class="section-body">
        <table class="inside">
            <tr><td class="label">LKPD</td><td>{!! $text($nilai('lampiran_lkpd')) !!}</td></tr>
            <tr><td class="label">Bahan Bacaan</td><td>{!! $text($nilai('lampiran_bahan_bacaan')) !!}</td></tr>
            <tr><td class="label">Glosarium</td><td>{!! $text($nilai('glosarium')) !!}</td></tr>
            <tr><td class="label">Daftar Pustaka</td><td>{!! $text($nilai('daftar_pustaka')) !!}</td></tr>
        </table>
    </div>

    @include('cetak.partials.projects', [
        'kokurikulerProjects' => $kokurikulerProjects ?? collect(),
        'curriculumProfile' => $curriculumProfile ?? null,
    ])

    <table class="sign">
        <tr>
            <td style="width:50%">Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div><div class="name">__________________________</div>
            </td>
            <td style="width:50%">{{ $school?->city?->name ?? 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                <div class="space"></div><div class="name">__________________________</div>
            </td>
        </tr>
    </table>
@endsection
