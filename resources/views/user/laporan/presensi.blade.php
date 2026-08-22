@extends('layouts.master')

@section('title') Laporan Presensi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') Presensi @endslot
        @slot('title') Laporan Presensi @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Laporan Presensi GTK & Santri</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Laporan presensi GTK dan santri akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
