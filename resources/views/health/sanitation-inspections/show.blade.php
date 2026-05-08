@extends('layouts.master')
@section('title') Detail Inspeksi Sanitasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}">Inspeksi Sanitasi</a> @endslot
        @slot('title') Detail Inspeksi @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Inspeksi Sanitasi</h5>
                        <div>
                            <a href="{{ route('user.uks.sanitation-inspections.edit', ['userId' => $userId, 'uuid' => $inspection->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.sanitation-inspections.destroy', ['userId' => $userId, 'uuid' => $inspection->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 text-center p-3 border rounded">
                            <div class="text-muted small">Skor</div>
                            <div class="fs-2 fw-bold text-{{ $inspection->score >= 80 ? 'success' : ($inspection->score >= 60 ? 'warning' : 'danger') }}">
                                {{ $inspection->score }}
                            </div>
                            <div class="progress mt-2" style="height: 6px">
                                <div class="progress-bar bg-{{ $inspection->score >= 80 ? 'success' : ($inspection->score >= 60 ? 'warning' : 'danger') }}"
                                     style="width: {{ $inspection->score }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center p-3 border rounded">
                            <div class="text-muted small">Lokasi</div>
                            <div class="fs-5 fw-semibold">{{ $inspection->location_type_text }}</div>
                        </div>
                        <div class="col-md-3 text-center p-3 border rounded">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if($inspection->is_passed)
                                    <span class="badge bg-success">Lulus</span>
                                @else
                                    <span class="badge bg-danger">Tidak Lulus</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 text-center p-3 border rounded">
                            <div class="text-muted small">Follow-up</div>
                            <div>
                                @if($inspection->follow_up_completed_at)
                                    <span class="badge bg-success">Selesai</span>
                                @elseif($inspection->follow_up_deadline && $inspection->follow_up_deadline->isPast())
                                    <span class="badge bg-danger">Terlambat</span>
                                @elseif($inspection->follow_up_deadline)
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-light text-dark">-</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Tanggal Inspeksi</td><td>{{ $inspection->inspection_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $inspection->academicYear?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Petugas</td><td>{{ $inspection->inspectedBy?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Deadline Follow-up</td><td>{{ $inspection->follow_up_deadline?->format('d/m/Y') ?? '-' }}</td></tr>
                            @if($inspection->follow_up_completed_at)
                                <tr><td class="fw-semibold text-muted">Follow-up Selesai</td><td>{{ $inspection->follow_up_completed_at?->format('d/m/Y H:i') }}</td></tr>
                            @endif
                            @if($inspection->findings)
                                <tr><td class="fw-semibold text-muted">Temuan</td><td>{!! nl2br(e($inspection->findings)) !!}</td></tr>
                            @endif
                            @if($inspection->recommendations)
                                <tr><td class="fw-semibold text-muted">Rekomendasi</td><td>{!! nl2br(e($inspection->recommendations)) !!}</td></tr>
                            @endif
                        </table>
                    </div>

                    @if(!$inspection->follow_up_completed_at && $inspection->follow_up_deadline)
                        <hr>
                        <form method="POST" action="{{ route('user.uks.sanitation-inspections.mark-complete', ['userId' => $userId, 'uuid' => $inspection->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="ri-check-line me-1"></i> Tandai Follow-up Selesai
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection