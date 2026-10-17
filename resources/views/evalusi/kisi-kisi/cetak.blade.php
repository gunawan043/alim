<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kisi-Kisi — {{ $kisi->judul }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #000; margin: 0; padding: 16px; }
        .no-print { margin-bottom: 16px; display: flex; gap: 8px; }
        .btn { display: inline-block; padding: 7px 14px; border: 1px solid #333; border-radius: 4px; background: #fff; color: #000; text-decoration: none; font-family: Arial, sans-serif; font-size: 10pt; cursor: pointer; }
        .btn-primary { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h3 { margin: 0; font-size: 15pt; text-transform: uppercase; letter-spacing: .5px; }
        .kop p { margin: 2px 0 0; font-size: 9.5pt; }
        h2 { text-align: center; font-size: 13pt; margin: 10px 0 14px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        .identitas { margin-bottom: 12px; }
        .identitas td { padding: 2px 6px; vertical-align: top; font-size: 10.5pt; }
        .identitas td.label { width: 140px; font-weight: bold; }
        .identitas td.sep { width: 8px; }
        .items th, .items td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; }
        .items th { text-align: center; background: #f1f1f1; font-size: 9.5pt; }
        .items td { font-size: 9.5pt; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; font-size: 10.5pt; }
        .ttd .space { height: 62px; }
        .ttd .name { font-weight: bold; text-decoration: underline; }
        .muted { color: #555; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $bentukSoalLabels = ['pg' => 'Pilihan Ganda', 'mcc' => 'PG Kompleks', 'bs' => 'Benar/Salah', 'jodoh' => 'Menjodohkan', 'isian' => 'Isian', 'uraian' => 'Uraian'];
        $levelLabels = [
            'C1_mengingat' => 'C1 — Mengingat',
            'C2_memahami' => 'C2 — Memahami',
            'C3_menerapkan' => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencita' => 'C6 — Mencipta',
            'C6_mencipta' => 'C6 — Mencipta',
        ];
    @endphp

    <div class="no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('user.kisi-kisi-soal.show', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn">Kembali</a>
    </div>

    <div class="kop">
        <h3>{{ $school->name ?? 'Satuan Pendidikan' }}</h3>
        <p>
            @if($school?->address){{ $school->address }}@endif
            @if($school?->phone) · Telp. {{ $school->phone }}@endif
            @if($school?->email) · {{ $school->email }}@endif
        </p>
        <p>NPSN: {{ $school->npsn ?? '-' }}@if($school?->website) · {{ $school->website }}@endif</p>
    </div>

    <h2>Kisi-Kisi Soal</h2>

    <table class="identitas">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="sep">:</td><td>{{ $school->name ?? '-' }}</td>
            <td class="label">Tahun Ajaran</td><td class="sep">:</td><td>{{ $kisi->academicYear?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td><td class="sep">:</td><td>{{ $kisi->subject?->name ?? '-' }}</td>
            <td class="label">Semester</td><td class="sep">:</td><td>{{ ucfirst($kisi->semester ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Fase</td><td class="sep">:</td><td>{{ $kisi->gradeLevel?->nama ?? $kisi->gradeLevel?->name ?? '-' }}</td>
            <td class="label">Jenis Ujian</td><td class="sep">:</td><td>{{ ucwords(str_replace('_', ' ', $kisi->jenis_ujian ?? '-')) }}</td>
        </tr>
        <tr>
            <td class="label">Judul</td><td class="sep">:</td><td>{{ $kisi->judul }}</td>
            <td class="label">Target Soal</td><td class="sep">:</td><td>{{ $kisi->total_soal_target }} butir</td>
        </tr>
        @if($kisi->deskripsi)
        <tr>
            <td class="label">Deskripsi</td><td class="sep">:</td><td colspan="4">{{ $kisi->deskripsi }}</td>
        </tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:34px">No</th>
                <th>Indikator / Tujuan Pembelajaran</th>
                <th style="width:130px">Materi</th>
                <th style="width:105px">Bentuk Soal</th>
                <th style="width:70px">Nomor Soal</th>
                <th style="width:110px">Level Kognitif</th>
                <th style="width:55px">Jumlah</th>
                <th style="width:70px">Bobot/Soal</th>
                <th style="width:75px">Total Bobot</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kisi->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>
                        @if($item->tujuanPembelajaran?->kode_tp)
                            <strong>{{ $item->tujuanPembelajaran->kode_tp }}</strong> —
                        @endif
                        {{ $item->tujuanPembelajaran->deskripsi ?? $item->tujuanPembelajaran->nama ?? '-' }}
                    </td>
                    <td>{{ $item->materi ?: '—' }}</td>
                    <td>{{ $item->bentuk_soal ? ($bentukSoalLabels[$item->bentuk_soal] ?? $item->bentuk_soal) : '—' }}</td>
                    <td class="text-center">{{ $item->nomor_soal ?: '—' }}</td>
                    <td>{{ $levelLabels[$item->level_kognitif] ?? $item->level_kognitif }}</td>
                    <td class="text-center">{{ $item->jumlah_soal }}</td>
                    <td class="text-center">{{ number_format((float) $item->bobot_per_soal, 2) }}</td>
                    <td class="text-center">{{ number_format($item->jumlah_soal * (float) $item->bobot_per_soal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center muted" style="padding:24px">Belum ada item kisi-kisi.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-end">Total</th>
                <th class="text-center">{{ $kisi->items->sum('jumlah_soal') }}</th>
                <th class="text-center">—</th>
                <th class="text-center">{{ number_format($kisi->items->sum(fn ($i) => $i->jumlah_soal * (float) $i->bobot_per_soal), 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <table class="ttd">
        <tr>
            <td>
                Mengetahui,<br>Kepala Sekolah
                <div class="space"></div>
                <div class="name">{{ $school->principal_name ?? '................................' }}</div>
            </td>
            <td>
                {{ now()->translatedFormat('d F Y') }}<br>Penyusun
                <div class="space"></div>
                <div class="name">{{ $penyusun ?? '................................' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
