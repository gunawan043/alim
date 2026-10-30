<div class="mb-3">
    <div class="fw-semibold">{{ $student->name }}</div>
    <div class="small text-muted">NISN/NIS: {{ $student->nisn ?: $student->nis ?: '—' }}</div>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Tahun / Semester</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th class="text-center">STS</th>
                <th class="text-center">SAS</th>
                <th class="text-center">Δ</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($values as $row)
                @php
                    $sts = $row->sts_murni !== null ? (float) $row->sts_murni : null;
                    $sas = $row->sas_murni !== null ? (float) $row->sas_murni : null;
                    $delta = $sts !== null && $sas !== null ? round($sas - $sts, 2) : null;
                @endphp
                <tr>
                    <td>
                        <div>{{ $row->adminBook?->academicYear?->name ?? '—' }}</div>
                        <div class="small text-muted">{{ $row->semester ? ucfirst($row->semester) : '—' }}</div>
                    </td>
                    <td>{{ $row->adminBook?->studyGroup?->name ?? '—' }}</td>
                    <td>{{ $row->adminBook?->subject?->name ?? '—' }}</td>
                    <td class="text-center">{{ $sts !== null ? number_format($sts, 2) : '—' }}</td>
                    <td class="text-center">{{ $sas !== null ? number_format($sas, 2) : '—' }}</td>
                    <td class="text-center {{ $delta !== null && $delta < 0 ? 'text-danger' : '' }}">
                        {{ $delta !== null ? (($delta > 0 ? '+' : '').number_format($delta, 2)) : '—' }}
                    </td>
                    <td>
                        @if($delta === null)
                            <span class="badge bg-warning-subtle text-warning">Belum lengkap</span>
                        @elseif($delta > 0)
                            <span class="badge bg-success-subtle text-success">Meningkat</span>
                        @elseif($delta < 0)
                            <span class="badge bg-danger-subtle text-danger">Menurun</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Tetap</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat nilai.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
