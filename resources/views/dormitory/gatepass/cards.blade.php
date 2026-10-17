@extends('layouts.master')
@section('title') Kartu QR Gatepass @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.gatepass.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">Gatepass</a> @endslot
        @slot('title') Kartu QR @endslot
    @endcomponent

    <div class="d-print-none d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0 small">Cetak dan tempel kartu ini pada kartu santri.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.asrama.gatepass.cards.thermal', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'size' => '58']) }}"
               target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="ri-printer-line me-1"></i>Thermal 58mm
            </a>
            <a href="{{ route('user.asrama.gatepass.cards.thermal', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'size' => '80']) }}"
               target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="ri-printer-line me-1"></i>Thermal 80mm
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="ri-printer-line me-1"></i>Cetak A4</button>
        </div>
    </div>

    <div class="row g-3">
        @forelse($residents as $resident)
            <div class="col-md-4 col-lg-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-1">{{ $resident->student?->name ?? '-' }}</div>
                        <div class="small text-muted mb-2">{{ $resident->room?->name ?? '-' }} · #{{ $resident->bed_number }}</div>
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(140)->margin(1)->generate($tokens[$resident->id] ?? '') !!}
                        <div class="small text-muted mt-2">{{ $dormitory->name }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><x-empty-state title="Belum ada penghuni aktif" message="Tambahkan penghuni asrama terlebih dahulu." /></div>
        @endforelse
    </div>
@endsection
