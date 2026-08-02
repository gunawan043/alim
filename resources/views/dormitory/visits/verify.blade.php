@extends('layouts.master')
@section('title') Verifikasi QR Kunjungan @endsection

@section('css')
<style>
    .qr-result { border-left: 4px solid #0d6efd; padding-left: 1rem; text-align: left; }
    .qr-result.success { border-left-color: #198754; }
    .qr-result.error { border-left-color: #dc3545; }
</style>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card text-center">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Verifikasi QR Kunjungan</h5>
                </div>
                <div class="card-body">
                    @if(isset($visit) && $visit)
                        <div class="alert alert-success qr-result success">
                            <i class="ri-check-double-line fs-3"></i>
                            <h6 class="fw-bold mt-2">QR Valid</h6>
                            <table class="table table-sm table-borderless mt-3 mb-0 text-start d-inline-table">
                                <tr><td>Tamu</td><td>: {{ $visit->visitor_name }}</td></tr>
                                <tr><td>Santri</td><td>: {{ $visit->student?->name ?? '—' }}</td></tr>
                                <tr><td>Asrama</td><td>: {{ $visit->dormitory?->name ?? '—' }}</td></tr>
                                <tr><td>Tujuan</td><td>: {{ $visit->purpose_text ?? '—' }}</td></tr>
                                <tr><td>Rencana Datang</td><td>: {{ $visit->expected_arrival_datetime?->format('d M Y, H:i') ?? '—' }}</td></tr>
                                <tr><td>Status</td><td>: <strong>{{ ucfirst(str_replace('_', ' ', $visit->status)) }}</strong></td></tr>
                            </table>
                            <hr>
                            <p class="small text-muted mb-0">Tunjukkan QR ini ke pengasuh asrama untuk melakukan check-in/check-out.</p>
                        </div>
                    @else
                        <div class="alert alert-danger qr-result error">
                            <i class="ri-error-warning-line fs-3"></i>
                            <h6 class="fw-bold mt-2">QR Tidak Valid atau Sudah Kadaluarsa</h6>
                            <p class="text-muted small mb-0 mt-2">Tanda tangan QR tidak dikenali atau sudah melampaui masa berlaku.</p>
                        </div>
                    @endif

                    <a href="{{ url()->previous('/') }}" class="btn btn-link mt-3">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection