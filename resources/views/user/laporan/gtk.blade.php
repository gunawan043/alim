@extends('layouts.master')

@section('title') Laporan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') GTK @endslot
        @slot('title') Laporan GTK @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Laporan GTK</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Laporan data GTK, jadwal mengajar, dan tugas tambahan akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
