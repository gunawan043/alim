@extends('layouts.master')

@section('title', 'Riwayat Kamar Santri')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Riwayat Kamar Santri</h4>
                    <small class="text-muted">{{ $student->nama }} ({{ $student->nis ?? '-' }})</small>
                </div>
                <div>
                    <a href="{{ route('user.students.timeline', ['userId' => $userId, 'studentId' => $student->id]) }}"
                       class="btn btn-outline-primary me-1">
                        <i class="ri-time-line me-1"></i> Timeline
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Saat Ini</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="40%">Asrama</th><td>{{ $student->dormitory->name ?? '-' }}</td></tr>
                        <tr><th>Kamar</th><td>{{ $student->room->kode ?? $student->room->nomor ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Statistik Mutasi</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td>Total Mutasi</td><td class="text-end"><strong>{{ $stats['total'] }}</strong></td></tr>
                        <tr><td>Rotasi</td><td class="text-end">{{ $stats['rotasi'] }}</td></tr>
                        <tr><td>Permintaan</td><td class="text-end">{{ $stats['permintaan'] }}</td></tr>
                        <tr><td>Sanksi</td><td class="text-end">{{ $stats['sanksi'] }}</td></tr>
                        <tr><td>Kondisi Kesehatan</td><td class="text-end">{{ $stats['kondisi_kesehatan'] }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Riwayat Perpindahan</h5></div>
                <div class="card-body">
                    @forelse($moves as $m)
                    <div class="border-start border-3 border-{{ $m->move_type === 'sanksi' ? 'danger' : ($m->move_type === 'permintaan' ? 'info' : 'secondary') }} ps-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    {{ $m->dormitory->name ?? '?' }} —
                                    <span class="text-muted">{{ $m->fromRoom->kode ?? $m->fromRoom->nomor ?? '?' }}</span>
                                    <i class="ri-arrow-right-line mx-1"></i>
                                    <strong>{{ $m->toRoom->kode ?? $m->toRoom->nomor ?? '?' }}</strong>
                                </h6>
                                <small class="text-muted">
                                    <i class="ri-calendar-line"></i> {{ $m->move_date->format('d M Y') }}
                                    &nbsp;|&nbsp;
                                    <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_',' ', $m->move_type)) }}</span>
                                    @if($m->approval_status === 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($m->approval_status === 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </small>
                                @if($m->reason)
                                    <p class="mb-0 mt-1"><small>Alasan: {{ $m->reason }}</small></p>
                                @endif
                                @if($m->notes)
                                    <p class="mb-0 text-muted"><small>Catatan: {{ $m->notes }}</small></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                        <h6 class="text-muted mb-1 mt-3">Belum Ada Riwayat Perpindahan Kamar</h6>
                        <p class="text-muted mb-3 small">Santri ini belum memiliki riwayat perpindahan kamar yang tercatat.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection