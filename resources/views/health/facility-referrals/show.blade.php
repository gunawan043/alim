@extends('layouts.master')
@section('title') Detail Faskes Rujukan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}">Faskes Rujukan</a> @endslot
        @slot('title') Detail Faskes @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Faskes Rujukan</h5>
                        <div>
                            <a href="{{ route('user.uks.facility-referrals.edit', ['userId' => $userId, 'uuid' => $facility->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.facility-referrals.destroy', ['userId' => $userId, 'uuid' => $facility->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-2 text-center">
                            <div class="fs-1"><i class="ri-hospital-line text-primary"></i></div>
                        </div>
                        <div class="col-md-10">
                            <h4>{{ $facility->facility_name }}</h4>
                            <span class="badge bg-{{ $facility->is_active ? 'success' : 'secondary' }}">
                                {{ $facility->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <span class="badge bg-info">{{ $facility->facility_type_text }}</span>
                            @if($facility->is_available_24h)
                                <span class="badge bg-warning"><i class="ri-time-line me-1"></i> 24 Jam</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-center p-3 border rounded">
                            <div class="text-muted small">Jarak</div>
                            <div class="fs-4 fw-bold">{{ $facility->distance_km ? $facility->distance_km . ' km' : '-' }}</div>
                        </div>
                        <div class="col-md-4 text-center p-3 border rounded">
                            <div class="text-muted small">Jam Operasional</div>
                            <div class="fs-6 fw-semibold">{{ $facility->operating_hours ?? '-' }}</div>
                        </div>
                        <div class="col-md-4 text-center p-3 border rounded">
                            <div class="text-muted small">Telepon</div>
                            <div class="fs-6 fw-semibold">{{ $facility->phone ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Alamat</td><td>{{ $facility->address ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Email</td><td>{{ $facility->email ?? '-' }}</td></tr>
                            @if($facility->services)
                                <tr><td class="fw-semibold text-muted">Layanan</td><td>{{ $facility->services }}</td></tr>
                            @endif
                            @if($facility->notes)
                                <tr><td class="fw-semibold text-muted">Catatan</td><td>{{ $facility->notes }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection