@extends('layouts.master')

@section('title', 'Detail Tamu')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId]) }}">Log Tamu</a>
        @endslot
        @slot('title') {{ $guest->visitor_name }} @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Data Kunjungan</h5>
                    @if($guest->isInside())
                        <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>Di dalam</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-logout-box-line me-1"></i>Sudah keluar</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                <tr><th class="text-muted" style="width:220px;">Kode</th><td><span class="badge bg-secondary-subtle text-secondary">{{ $guest->code }}</span></td></tr>
                                <tr><th class="text-muted">Nama Tamu</th><td class="fw-semibold">{{ $guest->visitor_name }}</td></tr>
                                <tr><th class="text-muted">Identitas</th><td>{{ strtoupper($guest->id_type) }} · {{ $guest->id_number ?: '—' }}</td></tr>
                                <tr><th class="text-muted">No HP</th><td>{{ $guest->phone ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Asal</th><td>{{ $guest->origin ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Instansi</th><td>{{ $guest->institution ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Keperluan</th><td>{{ $guest->purpose }}</td></tr>
                                <tr><th class="text-muted">Bertemu</th><td>{{ $guest->person_to_meet ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Santri Dikunjungi</th><td>{{ $guest->student->name ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Asrama Tujuan</th><td>{{ $guest->dormitory->name ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Jumlah Orang</th><td>{{ $guest->visitor_count }} orang</td></tr>
                                <tr><th class="text-muted">No Polisi</th><td>{{ $guest->vehicle_plate ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Pos Jaga</th><td>{{ $guest->post->name ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Masuk</th><td>{{ optional($guest->check_in_at)->translatedFormat('d F Y H:i') }}</td></tr>
                                <tr><th class="text-muted">Keluar</th><td>{{ optional($guest->check_out_at)->translatedFormat('d F Y H:i') ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Durasi</th><td>{{ $guest->durationLabel() }}</td></tr>
                                <tr><th class="text-muted">Dicatat Oleh</th><td>{{ $guest->recorder->name ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Catatan</th><td>{{ $guest->notes ?: '—' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    <a href="{{ route('user.keamanan.guests.card', ['userId' => $userId, 'guest' => $guest->id]) }}" target="_blank" class="btn btn-primary">
                        <i class="ri-id-card-line me-1"></i> Cetak ID Card
                    </a>
                    @if($guest->isInside())
                        <form method="POST" action="{{ route('user.keamanan.guests.checkout', ['userId' => $userId, 'guest' => $guest->id]) }}"
                              data-confirm="Catat tamu ini sudah keluar?" class="ms-auto">
                            @csrf
                            <button type="submit" class="btn btn-danger"><i class="ri-logout-box-line me-1"></i> Catat Keluar</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Kartu Tamu</h5>
                </div>
                <div class="card-body text-center">
                    <div class="border rounded p-3 d-inline-block bg-white">
                        <div class="fw-bold text-uppercase small text-muted">Kartu Tamu</div>
                        <div class="fs-5 fw-bold mt-1">{{ $guest->visitor_name }}</div>
                        <div class="small text-muted">{{ $guest->code }}</div>
                        <div class="my-2">
                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(1)->generate($guest->code) !!}
                        </div>
                        <div class="small">{{ optional($guest->check_in_at)->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
