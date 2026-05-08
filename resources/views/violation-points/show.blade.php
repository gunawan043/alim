@extends('layouts.master')
@section('title') Detail Poin Pelanggaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK & Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_3') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
        @slot('title') Detail Poin Pelanggaran @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Poin Pelanggaran</h5>
                        <div>
                            <a href="{{ route('user.violation-points.edit', ['userId' => $userId, 'violationUuid' => $violation->id]) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="ri-edit-line me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Peserta Didik</label>
                            <div class="fw-semibold">{{ $violation->student?->name ?? '-' }}</div>
                            @if($violation->student?->nisn)
                                <small class="text-muted">NISN: {{ $violation->student->nisn }}</small>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Rombel</label>
                            <div class="fw-semibold">{{ $violation->studyGroup?->full_name ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Tanggal Pelanggaran</label>
                            <div class="fw-semibold">{{ $violation->violation_date->format('d/m/Y') }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Jenis Pelanggaran</label>
                            <div class="fw-semibold">{{ $violation->violation_type }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Poin</label>
                            <div>
                                <span class="badge bg-danger fs-6">{{ $violation->points }} Poin</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small mb-1">Deskripsi</label>
                            <div>{{ $violation->description ?: '-' }}</div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small mb-1">Tindakan yang Diberikan</label>
                            <div>{{ $violation->action_taken ?: '-' }}</div>
                        </div>

                        <div class="col-12 border-top pt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">Dicatat oleh</label>
                                    <div>{{ $violation->recordedBy?->name ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">Tanggal Pencatatan</label>
                                    <div>{{ $violation->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
