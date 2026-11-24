@extends('layouts.master')

@section('title', 'Preview Cetak Kartu Pelajar')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Data Santri @endslot
        @slot('li_2') <a href="{{ route('user.students.cards.index', ['userId' => $userId]) }}">Kartu Pelajar</a> @endslot
        @slot('title') Preview Cetak @endslot
    @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0">Preview Kartu Pelajar</h5>
                <p class="text-muted mb-0">
                    {{ number_format(count($cards)) }} kartu · ukuran {{ $setting->card_width_mm }} × {{ $setting->card_height_mm }} mm
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('user.students.cards.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
                <a href="{{ route('user.students.cards.print', ['userId' => $userId] + $params + ['download' => 1]) }}" class="btn btn-danger btn-sm">
                    <i class="ri-file-pdf-line me-1"></i>Download PDF
                </a>
                <a href="{{ route('user.students.cards.png', ['userId' => $userId] + $params) }}" class="btn btn-outline-primary btn-sm">
                    <i class="ri-image-line me-1"></i>Download PNG (ZIP)
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <iframe src="{{ route('user.students.cards.print', ['userId' => $userId] + $params) }}"
                    title="Preview PDF Kartu Pelajar"
                    style="width:100%;height:75vh;border:0;background:#e2e8f0"></iframe>
        </div>
        <div class="card-footer text-muted small">
            PDF dibuat {{ number_format($setting->card_width_mm * $printScale, 2) }} × {{ number_format($setting->card_height_mm * $printScale, 2) }} mm (2× ukuran kartu). Saat mencetak, gunakan skala 50% untuk hasil ukuran kartu. Setiap halaman berisi satu sisi kartu.
        </div>
    </div>
@endsection
