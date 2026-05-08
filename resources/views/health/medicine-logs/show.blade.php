@extends('layouts.master')
@section('title') Detail Pemberian Obat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}">Pemberian Obat</a> @endslot
        @slot('title') Detail Pemberian Obat @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Pemberian Obat</h5>
                        <form method="POST" action="{{ route('user.uks.medicine-logs.destroy', ['userId' => $userId, 'uuid' => $log->id]) }}"
                              class="d-inline" >
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger delete-btn" data-message="Yakin hapus? Stok obat tidak akan dikembalikan."><i class="ri-delete-bin-line"></i> Hapus</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Nama Santri</td><td class="fw-semibold">{{ $log->student?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $log->academicYear?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Obat</td><td>{{ $log->inventory?->medicine_name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Jumlah</td><td>{{ $log->quantity_given }} {{ $log->inventory?->unit ?? '' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Dosis</td><td>{{ $log->dosage ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Waktu</td><td>{{ $log->time_given ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal</td><td>{{ $log->log_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tujuan</td><td>{{ $log->purpose ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Follow-up</td><td>{{ $log->follow_up_date?->format('d/m/Y') ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Petugas</td><td>{{ $log->administeredBy?->name ?? '-' }}</td></tr>
                            @if($log->notes)
                                <tr><td class="fw-semibold text-muted">Catatan</td><td>{{ $log->notes }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection