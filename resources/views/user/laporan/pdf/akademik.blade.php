@include('user.laporan.pdf._header', [
    'title' => 'Laporan Analisis Nilai Murni STS/SAS',
    'filterText' => $analysis['current_label'].' · Pembanding: '.($analysis['previous_label'] ?? '-'),
])

@php
    $summary = $analysis['summary'];
    $previous = $analysis['previous_summary'];
    $comparison = $analysis['comparison'];
    $trendLabel = fn (string $trend) => [
        'increased' => 'Meningkat',
        'decreased' => 'Menurun',
        'stable' => 'Tetap',
        'missing' => 'Belum lengkap',
    ][$trend] ?? $trend;
@endphp

<table style="margin-bottom:8px;">
    <tr>
        <th style="width:18%">Santri</th><td style="width:15%">{{ number_format($summary['students']) }}</td>
        <th style="width:18%">Rata-rata STS</th><td style="width:15%">{{ $summary['avg_sts'] !== null ? number_format($summary['avg_sts'], 2) : '-' }}</td>
        <th style="width:18%">Rata-rata SAS</th><td>{{ $summary['avg_sas'] !== null ? number_format($summary['avg_sas'], 2) : '-' }}</td>
    </tr>
    <tr>
        <th>Median STS</th><td>{{ $summary['median_sts'] !== null ? number_format($summary['median_sts'], 2) : '-' }}</td>
        <th>Median SAS</th><td>{{ $summary['median_sas'] !== null ? number_format($summary['median_sas'], 2) : '-' }}</td>
        <th>Selisih rata-rata</th><td>{{ $summary['avg_delta'] !== null ? number_format($summary['avg_delta'], 2) : '-' }}</td>
    </tr>
    <tr>
        <th>Meningkat</th><td>{{ number_format($summary['increased']) }}</td>
        <th>Menurun</th><td>{{ number_format($summary['decreased']) }}</td>
        <th>Belum lengkap</th><td>{{ number_format($summary['missing']) }}</td>
    </tr>
    <tr>
        <th>Kelas</th><td>{{ number_format($summary['classes']) }}</td>
        <th>Mapel</th><td>{{ number_format($summary['subjects']) }}</td>
        <th>Di bawah KKTP</th><td>{{ number_format($summary['below_kktp']) }}</td>
    </tr>
    @if($analysis['previous_label'])
        <tr>
            <th>Pembanding STS</th><td>{{ $previous['avg_sts'] !== null ? number_format($previous['avg_sts'], 2) : '-' }}</td>
            <th>Pembanding SAS</th><td>{{ $previous['avg_sas'] !== null ? number_format($previous['avg_sas'], 2) : '-' }}</td>
            <th>Δ STS / Δ SAS</th>
            <td>
                {{ $comparison['delta_avg_sts'] !== null ? number_format($comparison['delta_avg_sts'], 2) : '-' }}
                /
                {{ $comparison['delta_avg_sas'] !== null ? number_format($comparison['delta_avg_sas'], 2) : '-' }}
            </td>
        </tr>
    @endif
</table>

<h3 style="font-size:10pt;margin:12px 0 4px;">Distribusi Nilai</h3>
<table style="margin-bottom:8px;">
    <thead>
        <tr><th>Rentang</th><th>STS</th><th>SAS</th></tr>
    </thead>
    <tbody>
        @foreach($summary['distribution']['sts'] as $index => $bucket)
            <tr>
                <td>{{ $bucket['label'] }}</td>
                <td class="text-center">{{ $bucket['count'] }}</td>
                <td class="text-center">{{ $summary['distribution']['sas'][$index]['count'] ?? 0 }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($analysis['class_stats']->isNotEmpty())
    <h3 style="font-size:10pt;margin:12px 0 4px;">Rata-rata per Kelas</h3>
    <table style="margin-bottom:8px;">
        <thead><tr><th>Kelas</th><th>STS</th><th>SAS</th><th>Selisih</th></tr></thead>
        <tbody>
            @foreach($analysis['class_stats'] as $class)
                <tr>
                    <td>{{ $class['name'] }}</td>
                    <td class="text-center">{{ $class['avg_sts'] !== null ? number_format($class['avg_sts'], 2) : '-' }}</td>
                    <td class="text-center">{{ $class['avg_sas'] !== null ? number_format($class['avg_sas'], 2) : '-' }}</td>
                    <td class="text-center">{{ $class['avg_delta'] !== null ? number_format($class['avg_delta'], 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if(! empty($analysis['findings']))
    <h3 style="font-size:10pt;margin:12px 0 4px;">Temuan Otomatis</h3>
    <table style="margin-bottom:8px;">
        <thead><tr><th>Temuan</th><th style="width:70px">Jumlah</th><th>Keterangan</th></tr></thead>
        <tbody>
            @foreach($analysis['findings'] as $finding)
                <tr>
                    <td>{{ $finding['title'] }}</td>
                    <td class="text-center">{{ $finding['count'] }}</td>
                    <td>{{ $finding['description'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h3 style="font-size:10pt;margin:12px 0 4px;">Detail Nilai</h3>
<table>
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <th style="width:52px">NISN</th>
            <th>Nama Santri</th>
            <th style="width:48px">Kelas</th>
            <th>Mapel</th>
            <th style="width:35px">STS</th>
            <th style="width:35px">SAS</th>
            <th style="width:40px">Rata</th>
            <th style="width:40px">Δ</th>
            <th style="width:52px">Tren</th>
            <th style="width:48px">Pembanding</th>
            <th style="width:38px">KKTP</th>
            <th style="width:56px">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($analysis['rows'] as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row['nisn'] ?: $row['nis'] ?: '-' }}</td>
                <td>{{ $row['student_name'] }}</td>
                <td class="text-center">{{ $row['class_name'] }}</td>
                <td>{{ $row['subject_name'] }}</td>
                <td class="text-center">{{ $row['sts'] !== null ? number_format($row['sts'], 2) : '-' }}</td>
                <td class="text-center">{{ $row['sas'] !== null ? number_format($row['sas'], 2) : '-' }}</td>
                <td class="text-center">{{ $row['average'] !== null ? number_format($row['average'], 2) : '-' }}</td>
                <td class="text-center">{{ $row['delta'] !== null ? number_format($row['delta'], 2) : '-' }}</td>
                <td class="text-center">{{ $trendLabel($row['trend']) }}</td>
                <td class="text-center">{{ $row['previous_avg'] !== null ? number_format($row['previous_avg'], 2) : '-' }}</td>
                <td class="text-center">{{ $row['kktp'] !== null ? number_format($row['kktp'], 2) : '-' }}</td>
                <td>
                    {{ $row['below_kktp'] ? 'Di bawah KKTP' : ($row['missing'] ? 'Belum lengkap' : 'Terisi') }}
                </td>
            </tr>
        @empty
            <tr><td colspan="13" class="text-center muted">Belum ada data nilai pada filter ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dicetak {{ $printedAt->format('d/m/Y H:i') }} · Sumber: admin_nilai_sumatif (STS/SAS murni) · ALIM
</div>
