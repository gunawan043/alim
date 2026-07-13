@extends('layouts.master')

@section('title', 'Riwayat Pelanggaran')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Riwayat Pelanggaran</h4>
                    <small class="text-muted">{{ $student->nama }} ({{ $student->nis ?? '-' }})</small>
                </div>
                <a href="{{ route('user.students.timeline', ['userId' => $userId, 'studentId' => $student->id]) }}"
                   class="btn btn-outline-primary">
                    <i class="ri-time-line me-1"></i> Timeline
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Ringan</p>
                    <h4 class="text-success">{{ $stats['ringan'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Sedang</p>
                    <h4 class="text-warning">{{ $stats['sedang'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1">Berat / Poin</p>
                    <h4 class="text-danger">{{ $stats['berat'] }} / {{ $stats['total_points'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Daftar Pelanggaran</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Deskripsi</th>
                            <th>Poin</th>
                            <th>Tindakan</th>
                            <th>Ortu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($violations as $v)
                        <tr>
                            <td>{{ $v->violation_date->format('d M Y') }}</td>
                            <td>
                                @php
                                    $badge = match($v->violation_category) {
                                        'ringan' => 'success',
                                        'sedang' => 'warning',
                                        'berat' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($v->violation_category) }}</span>
                            </td>
                            <td><code>{{ $v->violation_type }}</code></td>
                            <td>{{ $v->description ?? '-' }}</td>
                            <td><strong class="text-danger">{{ $v->points }}</strong></td>
                            <td>{{ $v->action_taken ?? '-' }}</td>
                            <td>
                                @if($v->parent_notified_at)
                                    <i class="ri-check-line text-success"></i>
                                    {{ $v->parent_notified_at->format('d M') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            <i class="ri-shield-check-line" style="font-size:2rem"></i>
                            <p class="mt-2">Tidak ada catatan pelanggaran.</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection