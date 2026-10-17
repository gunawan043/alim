@extends('layouts.master')

@section('title') Laporan Keuangan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a> @endslot
        @slot('title') Laporan Keuangan @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <x-empty-state
                icon="https://cdn.lordicon.com/gqzfzudq.json"
                title="Modul keuangan pondok belum tersedia"
                message="Menu ini akan aktif setelah modul keuangan (tagihan, pembayaran, dan kas pondok) diimplementasikan. Sementara itu, gunakan laporan yang sudah tersedia di bawah.">
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="btn btn-primary">
                        <i class="ri-user-star-line me-1"></i>Laporan Santri
                    </a>
                    <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="btn btn-outline-primary">
                        <i class="ri-contacts-book-2-line me-1"></i>Laporan GTK
                    </a>
                    <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}" class="btn btn-light">
                        <i class="ri-arrow-go-back-line me-1"></i>Semua Laporan
                    </a>
                </div>
            </x-empty-state>
        </div>
    </div>
@endsection
