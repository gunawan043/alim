@extends('layouts.master')

@section('title') Laporan Keuangan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') Keuangan @endslot
        @slot('title') Laporan Keuangan @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Laporan Keuangan Pondok</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Laporan pemasukan dan pengeluaran pondok pesantren akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
