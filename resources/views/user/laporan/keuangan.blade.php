@extends('layouts.master')

@section('title') Laporan Keuangan @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Keuangan @endslot
    @endcomponent

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Keuangan</h1>
                <p class="lap-head-desc">Laporan tagihan, pembayaran, dan kas pondok.</p>
            </div>
        </div>

        @include('user.laporan.partials.scope')

        <div class="card">
            <div class="card-body">
                <div class="lap-empty" style="min-height: 220px;">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title bg-warning-subtle rounded fs-1">
                            <i class="ri-wallet-3-line text-warning"></i>
                        </span>
                    </div>
                    <div class="lap-empty-title">Modul keuangan belum tersedia</div>
                    <div class="lap-empty-desc">
                        Menu ini aktif setelah modul tagihan, pembayaran, dan kas pondok diimplementasikan.
                        Untuk sementara, gunakan laporan yang sudah tersedia.
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="lap-btn">Data santri</a>
                    <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="lap-btn">Data GTK</a>
                    <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}" class="lap-btn lap-btn-primary">Semua laporan</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
