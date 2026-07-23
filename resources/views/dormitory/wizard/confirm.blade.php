@extends('layouts.master')
@section('title') Wizard Izin — Konfirmasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Izin Kepulangan</a> @endslot
        @slot('title') Konfirmasi & Submit @endslot
    @endcomponent

    {{-- Wizard Progress --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center position-relative">
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">1. Santri</div></div>
                <div class="progress flex-fill position-absolute" style="height:2px;top:18px;left:0;right:0"><div class="progress-bar bg-primary" style="width:100%"></div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">2. Detail</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">3. Waktu</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px">4</div><div class="mt-1 small fw-semibold text-primary">4. Konfirmasi</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-verified-badge-line me-2 text-primary"></i>Ringkasan & Konfirmasi</h5>
            <span class="badge bg-warning text-dark"><i class="ri-error-warning-line me-1"></i>Menunggu Persetujuan</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted w-25"><i class="ri-user-line me-2"></i>Santri</td><td><strong>{{ $student->name ?? '-' }}</strong></td></tr>
                    <tr><td class="text-muted"><i class="ri-list-check me-2"></i>Jenis Izin</td><td>{{ $permitType ?? '—' }}</td></tr>
                    <tr><td class="text-muted"><i class="ri-map-pin-line me-2"></i>Tujuan</td><td>{{ $request->input('destination') ?? '—' }}</td></tr>
                    <tr><td class="text-muted"><i class="ri-calendar-line me-2"></i>Berangkat</td><td>{{ $request->input('departure_datetime') ? date('d M Y H:i', strtotime($request->input('departure_datetime'))) : '—' }}</td></tr>
                    <tr><td class="text-muted"><i class="ri-home-heart-line me-2"></i>Kembali Estimasi</td><td>{{ $request->input('expected_return_datetime') ? date('d M Y H:i', strtotime($request->input('expected_return_datetime'))) : '—' }}</td></tr>
                    <tr><td class="text-muted"><i class="ri-user-follow-line me-2"></i>Penjemput</td><td>{{ $request->input('companion_name') ?: 'Belum ditentukan' }}</td></tr>
                </table>
            </div>

            <form method="POST" action="{{ route('user.asrama.permit-wizard.submit', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="mt-3">
                @csrf
                @foreach($request->except('_token') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('user.asrama.permit-wizard.step3', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="ri-send-plane-fill me-1"></i> Ajukan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
