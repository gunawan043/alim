@extends('cetak.layout')

@php
    $semesterLabel = ucfirst(['1' => 'Ganjil', '2' => 'Genap'][$supervisi->semester] ?? $supervisi->semester);
@endphp

@section('title', 'Instrumen Supervisi')
@section('doc_title', 'INSTRUMEN SUPERVISI PEMBELAJARAN')
@section('doc_subtitle')
    {{ $supervisi->school?->name ?? '' }}
    @if($supervisi->instrumen) &bull; {{ $supervisi->instrumen->nama }} @endif
@endsection

@section('content')
    <div class="summary-box">
        1. Nama Sekolah / Madrasah: <strong>{{ $supervisi->school?->name ?? '-' }}</strong>
        &nbsp;&nbsp; 2. Nama Guru: <strong>{{ $supervisi->gtk?->user?->name ?? $supervisi->gtk_name ?? '-' }}</strong><br>
        3. Mata Pelajaran: <strong>{{ $supervisi->mata_pelajaran ?: '-' }}</strong>
        &nbsp;&nbsp; 4. Kelas/Semester: <strong>{{ $supervisi->mata_pelajaran ? '' : '' }}{{ $semesterLabel }}</strong><br>
        5. Hari/Tanggal: <strong>{{ $supervisi->tanggal_supervisi?->translatedFormat('l, d F Y') ?? '-' }}</strong>
        &nbsp; Jam: <strong>{{ $supervisi->jam_mulai ?: '-' }} – {{ $supervisi->jam_selesai ?: '-' }}</strong><br>
        6. Tahun Ajaran: <strong>{{ $supervisi->academicYear?->name ?? '-' }}</strong>
        &nbsp;&nbsp; 7. Supervisor: <strong>{{ $supervisi->observer?->user?->name ?? $supervisi->observer_name ?? '-' }}</strong>
    </div>

    @if($supervisi->instrumen)
        <table class="data">
            <thead>
                <tr>
                    <th style="width:34px">No</th>
                    <th>Aspek yang dinilai</th>
                    <th style="width:80px">Kondisi<br>Ya / Tidak</th>
                    <th style="width:60px">A<br>4</th>
                    <th style="width:60px">B<br>3</th>
                    <th style="width:60px">C<br>2</th>
                    <th style="width:60px">D<br>1</th>
                    <th style="width:130px">Ket</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supervisi->instrumen->bagian as $bagian)
                    <tr>
                        <td colspan="8" style="background:#e2e8f0;font-weight:bold;">
                            {{ $bagian->kode ? $bagian->kode.' — ' : '' }}{{ strtoupper($bagian->nama) }}
                        </td>
                    </tr>

                    @foreach($bagian->items as $idx => $item)
                        @php $p = $penilaian[$item->id] ?? null; @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>{{ $item->teks }}</td>
                            <td class="center">{{ $p && $p->kondisi !== null ? ($p->kondisi ? 'Ya' : 'Tidak') : '' }}</td>
                            <td class="center">{{ $p && (int) $p->skor === 4 ? '✓' : '' }}</td>
                            <td class="center">{{ $p && (int) $p->skor === 3 ? '✓' : '' }}</td>
                            <td class="center">{{ $p && (int) $p->skor === 2 ? '✓' : '' }}</td>
                            <td class="center">{{ $p && (int) $p->skor === 1 ? '✓' : '' }}</td>
                            <td class="small">{{ $p->catatan ?? '' }}</td>
                        </tr>

                        @foreach($item->subItems as $subIdx => $sub)
                            @php $ps = $penilaian[$sub->id] ?? null; @endphp
                            <tr>
                                <td class="center">{{ chr(97 + $subIdx) }}.</td>
                                <td style="padding-left:14px;">{{ $sub->teks }}</td>
                                <td class="center">{{ $ps && $ps->kondisi !== null ? ($ps->kondisi ? 'Ya' : 'Tidak') : '' }}</td>
                                <td class="center">{{ $ps && (int) $ps->skor === 4 ? '✓' : '' }}</td>
                                <td class="center">{{ $ps && (int) $ps->skor === 3 ? '✓' : '' }}</td>
                                <td class="center">{{ $ps && (int) $ps->skor === 2 ? '✓' : '' }}</td>
                                <td class="center">{{ $ps && (int) $ps->skor === 1 ? '✓' : '' }}</td>
                                <td class="small">{{ $ps->catatan ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach

                <tr>
                    <th colspan="7" style="text-align:right">JUMLAH SKOR</th>
                    <th class="center">{{ number_format((float) $supervisi->skor_total, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="7" style="text-align:right">SKOR MAKSIMUM</th>
                    <th class="center">{{ number_format((float) $supervisi->skor_maks, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="7" style="text-align:right">NILAI AKHIR</th>
                    <th class="center">{{ number_format((float) $supervisi->nilai_akhir, 2) }}</th>
                </tr>
            </tbody>
        </table>

        <table class="data" style="width:70%">
            <thead>
                <tr>
                    <th colspan="2">Ketercapaian</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $nilai = (float) ($supervisi->nilai_akhir ?? 0);
                    $rows = [
                        ['label' => '86 – 100', 'text' => 'Amat baik', 'ok' => $nilai >= 86],
                        ['label' => '70 – 85', 'text' => 'Baik', 'ok' => $nilai >= 70 && $nilai < 86],
                        ['label' => '55 – 69', 'text' => 'Cukup', 'ok' => $nilai >= 55 && $nilai < 70],
                        ['label' => 'di bawah 55', 'text' => 'Kurang', 'ok' => $nilai > 0 && $nilai < 55],
                    ];
                @endphp
                @foreach($rows as $row)
                    <tr>
                        <td style="width:130px" class="center">{{ $row['label'] }}</td>
                        <td>{{ $row['text'] }} {!! $row['ok'] ? '<strong>&larr; pencapaian</strong>' : '' !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Instrumen belum dipilih. Isi penilaian terlebih dahulu melalui halaman penilaian.</p>
    @endif

    <h4>CATATAN PENILAI</h4>
    <div style="min-height:70px; border:1px solid #444; padding:6px 8px; font-size:10px;">
        @if($supervisi->catatan_temuan)<div><strong>Temuan:</strong> {{ $supervisi->catatan_temuan }}</div>@endif
        @if($supervisi->rekomendasi)<div><strong>Rekomendasi:</strong> {{ $supervisi->rekomendasi }}</div>@endif
        @if($supervisi->tindak_lanjut)<div><strong>Tindak lanjut:</strong> {{ $supervisi->tindak_lanjut }}</div>@endif
        @if(! $supervisi->catatan_temuan && ! $supervisi->rekomendasi && ! $supervisi->tindak_lanjut)
            <span class="muted">&nbsp;</span>
        @endif
    </div>

    <table style="width:100%; margin-top:24px;" class="small">
        <tr>
            <td style="width:33%; text-align:center;">
                Guru Mata Pelajaran
                <br><br><br><br>
                <strong>{{ $supervisi->gtk?->user?->name ?? $supervisi->gtk_name ?? '................................' }}</strong>
            </td>
            <td style="width:33%; text-align:center;">
                Supervisor
                <br><br><br><br>
                <strong>{{ $supervisi->observer?->user?->name ?? $supervisi->observer_name ?? '................................' }}</strong>
            </td>
            <td style="width:33%; text-align:center;">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <br><br><br>
                <strong>................................</strong>
            </td>
        </tr>
    </table>
@endsection
