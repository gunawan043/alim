@extends('layouts.master')
@section('title') Detail Kaldik / Agenda Kegiatan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') Kaldik & Agenda @endslot
        @slot('title') Detail @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-calendar-event-line me-2"></i>{{ $kaldik->name }}
                        </h5>
                        <div>
                            @can('update', $kaldik)
                                <a href="{{ route('user.kaldik.edit', ['userId' => $userId, 'kaldikId' => $kaldik->id]) }}"
                                   class="btn btn-soft-primary btn-sm">
                                    <i class="ri-pencil-line me-1"></i> Edit
                                </a>
                            @endcan
                            <a href="{{ route('user.kaldik.index', ['userId' => $userId]) }}"
                               class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Kategori</label>
                            <div>
                                @if($kaldik->category === 'kaldik')
                                    <span class="badge bg-primary-subtle text-primary">Kaldik (Pondok)</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Agenda Kegiatan</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Tipe</label>
                            <div>{{ $kaldik->type ? (\App\Models\Kaldik::TYPE_OPTIONS[$kaldik->type] ?? '-') : '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Tahun Ajaran</label>
                            <div>{{ $kaldik->academicYear?->name ?? '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Satuan Kerja</label>
                            <div>{{ $kaldik->workUnit?->name ?? 'Pondok (Semua)' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Tanggal Mulai</label>
                            <div>{{ $kaldik->start_date->format('d M Y') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Tanggal Selesai</label>
                            <div>{{ $kaldik->end_date->format('d M Y') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Status</label>
                            <div>
                                @if($kaldik->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </div>
                        </div>

                        @if($kaldik->description)
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">Deskripsi</label>
                            <div class="p-2 bg-light rounded">{{ $kaldik->description }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection