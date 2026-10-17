@extends('layouts.master')
@section('title') Detail Imunisasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}">Imunisasi</a> @endslot
        @slot('title') Detail Imunisasi @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Imunisasi</h5>
                        <div>
                            <a href="{{ route('user.uks.immunizations.edit', ['userId' => $userId, 'uuid' => $immunization->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:160px">Nama Santri</td><td>{{ $immunization->student?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">NISN</td><td>{{ $immunization->student?->nisn ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Jenis Imunisasi</td><td>{{ $immunization->immunization_type_text }}</td></tr>
                            @if($immunization->vaccine_name)
                                <tr><td class="fw-semibold text-muted">Nama Vaksin</td><td>{{ $immunization->vaccine_name }}</td></tr>
                            @endif
                            <tr><td class="fw-semibold text-muted">Tanggal Diberikan</td><td>{{ $immunization->date_given?->format('d/m/Y') }}</td></tr>
                            @if($immunization->age_at_vaccination_days)
                                <tr><td class="fw-semibold text-muted">Umur Saat Vaksin</td><td>{{ $immunization->age_at_vaccination_days }} hari</td></tr>
                            @endif
                            <tr><td class="fw-semibold text-muted">Tempat</td><td>{{ $immunization->place ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">No. Batch</td><td>{{ $immunization->batch_number ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Petugas Medis</td><td>{{ $immunization->medical_staff ?? '-' }}</td></tr>
                            @if($immunization->side_effects)
                                <tr><td class="fw-semibold text-muted">Efek Samping</td><td>{{ $immunization->side_effects }}</td></tr>
                            @endif
                            @if($immunization->notes)
                                <tr><td class="fw-semibold text-muted">Catatan</td><td>{{ $immunization->notes }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
