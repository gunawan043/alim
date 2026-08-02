@extends('layouts.master')
@section('title') Laporan Kebersihan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan Kebersihan @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Evaluasi Kebersihan</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Ruangan</th>
                                    <th>Lantai</th>
                                    <th>Skor</th>
                                    <th>Status</th>
                                    <th>Pemeriksa</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInspections as $si)
                                <tr>
                                    <td>{{ $si->inspection_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $si->room->code ?? $si->building_name ?? '-' }}</td>
                                    <td>{{ $si->floor ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $si->score >= 80 ? 'bg-success' : ($si->score >= 60 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $si->score }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($si->passing_grade)
                                            <span class="badge bg-success">LULUS</span>
                                        @else
                                            <span class="badge bg-danger">TIDAK LULUS</span>
                                        @endif
                                    </td>
                                    <td>{{ $si->inspectedBy->name ?? '-' }}</td>
                                    <td class="text-muted">{{ Str::limit($si->notes ?? '', 50) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h6 class="text-muted mb-1 mt-3">Belum Ada Data Inspections</h6>
                                        <p class="text-muted mb-3 small">Data inspection sanitasi akan muncul di sini.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('user.asrama.sanitation.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ri-edit-line me-1"></i> Kelola Kebersihan
                        </a>
                        <a href="{{ route('user.asrama.sanitation.dashboard', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-layout-grid-line me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
