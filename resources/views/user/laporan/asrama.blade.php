@extends('layouts.master')

@section('title') Laporan Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') Asrama @endslot
        @slot('title') Laporan Asrama @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Laporan Asrama</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Laporan kebersihan, inventaris, dan penghuni asrama akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
