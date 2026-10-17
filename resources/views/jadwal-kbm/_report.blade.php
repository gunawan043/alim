{{-- Laporan hasil generate jadwal: slot terpasang vs JP diminta + kekurangan JP --}}
@php $report = session('shortage_report'); @endphp
@if($report)
<div class="card border-warning mb-3">
    <div class="card-header bg-warning-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h6 class="mb-0 fw-semibold">
            <i class="ri-bar-chart-box-line text-warning me-1"></i> Laporan Generate Jadwal
        </h6>
        <div class="small">
            <span class="badge bg-success">{{ $report['total_generated'] }} slot terpasang</span>
            <span class="badge bg-secondary">{{ $report['total_requested'] }} JP diminta</span>
            @if($report['total_missing'] > 0)
                <span class="badge bg-danger">{{ $report['total_missing'] }} JP belum dapat slot</span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rombel</th>
                        <th class="text-center">Terpasang</th>
                        <th class="text-center">JP Diminta</th>
                        <th>Kekurangan JP</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['groups'] as $group)
                        <tr>
                            <td class="fw-medium">{{ $group['name'] }}</td>
                            <td class="text-center">
                                <span class="badge {{ $group['generated'] >= $group['requested'] && $group['requested'] > 0 ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $group['generated'] }}
                                </span>
                            </td>
                            <td class="text-center">{{ $group['requested'] }}</td>
                            <td>
                                @forelse($group['shortages'] as $shortage)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1 mb-1">
                                        {{ $shortage['subject'] }} — {{ $shortage['teacher'] }}: kurang {{ $shortage['missing'] }} JP dari {{ $shortage['requested'] }}
                                    </span>
                                @empty
                                    <span class="text-muted small">—</span>
                                @endforelse
                            </td>
                            <td>
                                @if(! $group['has_assignments'])
                                    <div class="small text-warning">
                                        <i class="ri-error-warning-line me-1"></i>Tidak ada assignment (SK guru) aktif
                                    </div>
                                @endif
                                @foreach($group['conflicts'] as $conflict)
                                    <div class="small text-danger"><i class="ri-close-circle-line me-1"></i>{{ $conflict }}</div>
                                @endforeach
                                @if($group['has_assignments'] && empty($group['conflicts']) && empty($group['shortages']))
                                    <span class="small text-success"><i class="ri-checkbox-circle-line me-1"></i>Semua JP terpasang tanpa konflik</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($report['total_missing'] > 0)
            <div class="px-3 py-2 border-top small text-muted">
                <i class="ri-information-line me-1"></i>
                JP yang belum mendapat slot berarti kapasitas slot bebas tidak cukup (bentrok guru/rombel atau slot istirahat).
                Tambahkan slot pada master slot sekolah (<code>class_schedule_slots</code>) atau sesuaikan JP pada SK guru.
            </div>
        @endif
    </div>
</div>
@endif
