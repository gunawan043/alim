@extends('layouts.master')
@section('title') Approval Center — {{$asrama->name ?? 'Asrama'}} @endsection

@section('breadcrumb')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $asrama->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Approval Center @endslot
    @endcomponent
@endsection

@section('content')

<div class="page-body">

    {{-- Header --}}
    <div class="row mt-4 mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><i class="ri-mail-open-line me-2"></i>Inbox Persetujuan</h4>
                    <p class="text-muted mb-0">Semua permohonan menunggu persetujuan — satu tempat</p>
                </div>
                <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-primary">
                <div class="card-body py-3 text-center">
                    <h2 class="text-primary fw-bold">{{ $counts['total'] }}</h2>
                    <small class="text-muted">Total Menunggu</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-warning">
                <div class="card-body py-3 text-center">
                    <h2 class="text-warning fw-bold">{{ $counts['leave'] }}</h2>
                    <small class="text-muted">Izin Pulang</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-info">
                <div class="card-body py-3 text-center">
                    <h2 class="text-info fw-bold">{{ $counts['visit'] }}</h2>
                    <small class="text-muted">Permohonan Penjengukan</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-success">
                <div class="card-body py-3 text-center">
                    <h2 class="text-success fw-bold">{{ $counts['health'] }}</h2>
                    <small class="text-muted">Izin Sakit</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(empty($items))
        <div class="text-center py-5">
            <i class="ri-checkbox-circle-line text-success" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-muted">Tidak ada permohonan yang menunggu</h5>
            <p class="text-muted">Semua permohonan telah diproses.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($items as $item)
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center gap-3 py-3">
                            <div class="flex-shrink-0">
                                @if($item['type'] === 'leave')
                                    <span class="badge bg-warning rounded-pill p-2"><i class="ri-home-line"></i></span>
                                @elseif($item['type'] === 'visit')
                                    <span class="badge bg-info rounded-pill p-2"><i class="ri-user-heart-line"></i></span>
                                @elseif($item['type'] === 'health')
                                    <span class="badge bg-success rounded-pill p-2"><i class="ri-hospital-line"></i></span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold">{{ $item['title'] }}</h6>
                                <small class="text-muted">
                                    {{ $item['type_label'] }} &middot; Diajukan oleh {{ $item['requester_name'] }}
                                    &middot; <time datetime="{{ $item['started_at'] }}">{{ \Carbon\Carbon::parse($item['started_at'])->diffForHumans() }}</time>
                                </small>
                            </div>
                        </div>
                        <div class="card-body py-3">
                            <p class="text-muted mb-0">{{ $item['detail'] }}</p>
                            @if(!empty($item['extra']))
                                <div class="row g-2 small mt-2">
                                    @if(!empty($item['extra']['return_at']))
                                        <div class="col-auto">
                                            <span class="text-muted">Jadwal Kembali:</span>
                                            <strong>{{ \Carbon\Carbon::parse($item['extra']['return_at'])->isoFormat('D MMM YYYY') }}</strong>
                                        </div>
                                    @endif
                                    @if(!empty($item['extra']['visit_from']))
                                        <div class="col-auto">
                                            <span class="text-muted">Mulai:</span>
                                            <strong>{{ \Carbon\Carbon::parse($item['extra']['visit_from'])->isoFormat('D MMM YYYY HH:mm') }}</strong>
                                        </div>
                                    @endif
                                    @if(!empty($item['extra']['visit_to']))
                                        <div class="col-auto">
                                            <span class="text-muted">Sampai:</span>
                                            <strong>{{ \Carbon\Carbon::parse($item['extra']['visit_to'])->isoFormat('D MMM YYYY HH:mm') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-top py-3">
                            <div class="d-flex justify-content-end gap-2">
                                {{-- Reject Button --}}
                                <button class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectModal-{{ $item['id'] }}">
                                    <i class="ri-close-circle-line me-1"></i>Tolak
                                </button>

                                {{-- Approve Button --}}
                                <form action="{{ route('user.asrama.approval-center.approve', [$userId, $asramaUuid]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Setujui permohonan ini?')">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $item['type'] }}">
                                    <input type="hidden" name="id" value="{{ $item['id'] }}">
                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                        <i class="ri-check-line me-1"></i>Setujui
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Reject Modal --}}
                    <div class="modal fade" id="rejectModal-{{ $item['id'] }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="{{ route('user.asrama.approval-center.reject', [$userId, $asramaUuid]) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="{{ $item['type'] }}">
                                <input type="hidden" name="id" value="{{ $item['id'] }}">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak Permohonan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="fw-semibold">{{ $item['title'] }}</p>
                                        <p class="text-muted small">Berikan alasan penolakan:</p>
                                        <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Kuota tidak mencukupi..."></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

@endsection

@section('styles')
<style>
.card.border-primary .card-body h2,
.card.border-warning .card-body h2,
.card.border-info .card-body h2,
.card.border-success .card-body h2 {
    font-size: 2.2rem;
    margin-bottom: 0;
}
</style>
@endsection
