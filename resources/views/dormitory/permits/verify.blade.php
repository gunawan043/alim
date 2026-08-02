@extends('layouts.master')
@section('title') Verifikasi Izin @endsection

@section('css')
<style>
    .qr-result { border-left: 4px solid #0d6efd; padding-left: 1rem; }
    .qr-result.success { border-left-color: #198754; }
    .qr-result.error { border-left-color: #dc3545; }
</style>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card text-center">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Verifikasi QR Izin</h5>
                </div>
                <div class="card-body">
                    @if(isset($success) && $success)
                        {{-- Success --}}
                        <div class="alert alert-success qr-result success">
                            <i class="ri-check-double-line fs-3"></i>
                            <h6 class="fw-bold mt-2">{{ $success }}</h6>
                            @if(isset($permit) && $permit)
                                <table class="table table-sm table-borderless mt-3 mb-0 text-start d-inline-table">
                                    <tr><td>Santri</td><td>: {{ $permit->student?->name ?? '—' }}</td></tr>
                                    <tr><td>Jenis</td><td>: {{ $permit->permit_type_text }}</td></tr>
                                    <tr><td>Tujuan</td><td>: {{ $permit->destination ?? '—' }}</td></tr>
                                    <tr><td>Berangkat</td><td>: {{ $permit->departure_datetime?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                                    <tr><td>Taksiran Kembali</td><td>: {{ $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                                    <tr><td>Status</td><td><strong>{{ $permit->status_text }}</strong></td></tr>
                                    @if($permit->scanned_at)
                                    <tr><td>QR Terakhir Discan</td><td>: {{ $permit->scanned_at->format('d/m/Y H:i') }}</td></tr>
                                    @endif
                                    @if($permit->actual_return_datetime)
                                    <tr><td>Kembali Aktual</td><td>: {{ $permit->actual_return_datetime->format('d/m/Y H:i') }}</td></tr>
                                    @endif
                                </table>
                            @endif
                        </div>

                    @elseif(isset($error) && $error)
                        {{-- Error --}}
                        <div class="alert alert-danger qr-result error">
                            <i class="ri-error-warning-line fs-3"></i>
                            <h6 class="fw-bold mt-2">{{ $error }}</h6>
                        </div>

                    @elseif($errors->any())
                        {{-- Validation errors --}}
                        <div class="alert alert-danger qr-result error">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>

                    @else
                        {{-- Normal form --}}
                        <form method="GET" action="{{ route('permits.verify') }}">
                            <input type="hidden" name="t" value="{{ $token ?? old('token', '') }}">
                            <div class="mb-3">
                                <p class="text-muted">Scan QR Code dari surat izin santri untuk verifikasi pengambilan atau pencatatan kepulangan.</p>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="ri-qr-scan-2-line me-1"></i> Lihat Detail
                            </button>
                        </form>
                        <hr>
                        <a href="{{ url()->previous('/') }}" class="btn btn-link">Kembali</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
