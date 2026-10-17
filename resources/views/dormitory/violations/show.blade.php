@extends('layouts.master')
@section('title') Detail Pelanggaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Pelanggaran</a> @endslot
        @slot('title') Detail @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- Left: Violation Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-error-warning-line me-2 text-danger"></i>
                            Detail Pelanggaran
                        </h5>
                        @if($violation->violation_category === 'ringan')
                            <span class="badge bg-info-subtle text-info">Ringan</span>
                        @elseif($violation->violation_category === 'sedang')
                            <span class="badge bg-warning-subtle text-warning">Sedang</span>
                        @elseif($violation->violation_category === 'berat')
                            <span class="badge bg-danger-subtle text-danger">Berat</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Category + Type --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Kategori</div>
                                @if($violation->violation_category === 'ringan')
                                    <span class="badge bg-info-subtle text-info">Ringan</span>
                                @elseif($violation->violation_category === 'sedang')
                                    <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                @elseif($violation->violation_category === 'berat')
                                    <span class="badge bg-danger-subtle text-danger">Berat</span>
                                @else
                                    <span class="badge bg-secondary-subtle">{{ $violation->violation_category }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Poin</div>
                                <span class="badge bg-danger-subtle text-danger">{{ $violation->points }} poin</span>
                            </div>
                        </div>
                        {{-- Violation Type --}}
                        <div class="col-12">
                            <div class="bg-light rounded p-3">
                                <div class="text-muted small mb-1">Jenis Pelanggaran</div>
                                <div class="fw-semibold">{{ $violation->violation_type ?: '—' }}</div>
                            </div>
                        </div>
                        {{-- Description --}}
                        @if($violation->description)
                        <div class="col-12">
                            <div class="bg-light rounded p-3">
                                <div class="text-muted small mb-1">Deskripsi Kejadian</div>
                                <div class="fw-semibold">{{ $violation->description }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Dates --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Tanggal Pelanggaran</div>
                                <div class="fw-semibold">
                                    {{ $violation->violation_date ? $violation->violation_date->format('d/m/Y') : '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Dicatat oleh</div>
                                <div class="fw-semibold">{{ $violation->recordedBy?->name ?? '—' }}</div>
                            </div>
                        </div>
                        {{-- Action Taken --}}
                        @if($violation->action_taken)
                        <div class="col-12">
                            <div class="bg-warning-subtle rounded p-3">
                                <div class="text-muted small mb-1">Tindakan yang Diberikan</div>
                                <div class="fw-semibold">{{ $violation->action_taken }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Follow Up --}}
                        @if($violation->follow_up)
                        <div class="col-12">
                            <div class="bg-info-subtle rounded p-3">
                                <div class="text-muted small mb-1">Tindak Lanjut</div>
                                <div class="fw-semibold">{{ $violation->follow_up }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Witness --}}
                        @if($violation->witness)
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Saksi</div>
                                <div class="fw-semibold">{{ $violation->witness->name }}</div>
                            </div>
                        </div>
                        @endif
                        {{-- Created --}}
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="text-muted small mb-1">Dicatat pada</div>
                                <div class="fw-semibold">{{ $violation->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Student Info + Actions --}}
        <div class="col-lg-4">
            {{-- Student Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-line me-2 text-primary"></i>Data Santri</h5>
                </div>
                <div class="card-body">
                    @if($violation->student)
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="avatar-md">
                                <span class="avatar-title rounded-circle bg-{{ $violation->student->gender === 'P' ? 'danger' : 'primary' }}-subtle
                                             text-{{ $violation->student->gender === 'P' ? 'danger' : 'primary' }} fs-5 fw-bold">
                                    {{ strtoupper(substr($violation->student->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold fs-5">{{ $violation->student->name }}</div>
                                @if($violation->student->nisn)
                                    <div class="text-muted small">NISN: {{ $violation->student->nisn }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="row g-2">
                            @if($violation->room)
                            <div class="col-6">
                                <div class="text-muted small">Kamar</div>
                                <div class="fw-semibold">{{ $violation->room->name }}</div>
                            </div>
                            @endif
                            <div class="col-6">
                                <div class="text-muted small">Jenis Kelamin</div>
                                <div class="fw-semibold">{{ $violation->student->gender_text }}</div>
                            </div>
                            @if($violation->student->mobile_phone)
                            <div class="col-12">
                                <div class="text-muted small">No. HP</div>
                                <div class="fw-semibold">{{ $violation->student->mobile_phone }}</div>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="text-muted">Data pelanggaran tidak tersedia.</div>
                    @endif
                </div>
            </div>

            {{-- Parent Notification Status --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-notification-3-line me-2 text-primary"></i>Notifikasi Wali</h5>
                </div>
                <div class="card-body">
                    @if($violation->parent_notified_at)
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0">
                            <i class="ri-checkbox-circle-line fs-5"></i>
                            <div>
                                <div class="fw-semibold">Notifikasi sudah dikirim</div>
                                <div class="small text-muted">
                                    {{ $violation->parent_notified_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                            <i class="ri-error-warning-line fs-5"></i>
                            <div class="small">Belum ada notifikasi ke wali murid.</div>
                        </div>
                        <form method="POST"
                              action="{{ route('user.asrama.violations.notify', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'violationUuid' => $violation->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100"
                                    onclick="return confirm('Kirim notifikasi pelanggaran ke wali murid?')">
                                <i class="ri-notification-3-line me-1"></i> Kirim Notifikasi Wali
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Back Button --}}
            <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
               class="btn btn-light w-100">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection