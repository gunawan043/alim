@extends('layouts.master')
@section('title') Detail Penghargaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Detail Penghargaan @endslot
    @endcomponent

    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ $reward->title }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('user.asrama.rewards.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}" class="btn btn-sm btn-warning">
                    <i class="ri-pencil-line"></i> Edit
                </a>
                <a href="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-secondary">
                    <i class="ri-arrow-go-back-line"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="text-muted small">Santri</label>
                    <div class="fw-semibold">{{ $reward->student->name ?? '-' }}</div>
                    <small class="text-muted">NISN: {{ $reward->student->nisn ?? '-' }}</small>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Kategori</label>
                    <div><span class="badge bg-info">{{ $reward->category_text }}</span></div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Level</label>
                    <div>@if($reward->level === 'unggulan')<span class="badge bg-warning">{{ $reward->level_text }}</span>@elseif($reward->level === 'istimewa')<span class="badge bg-danger">{{ $reward->level_text }}</span>@else<span class="badge bg-secondary">{{ $reward->level_text }}</span>@endif</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Tanggal Pemberian</label>
                    <div class="fw-semibold">{{ $reward->awarded_date->format('d F Y') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Diberikan Oleh</label>
                    <div>{{ $reward->givenBy->name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Tahun Akademik</label>
                    <div>{{ $reward->academicYear->name ?? 'Default' }}</div>
                </div>
                @if($reward->description)
                    <div class="col-12">
                        <label class="text-muted small">Deskripsi</label>
                        <p>{{ $reward->description }}</p>
                    </div>
                @endif
                @if($reward->proof_path)
                    <div class="col-12">
                        <label class="text-muted small">Dokumen Pendukung</label>
                        <br>
                        <a href="{{ asset('storage/' . $reward->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="ri-download-line"></i> Download Dokumen
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
