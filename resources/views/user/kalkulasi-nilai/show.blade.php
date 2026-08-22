@extends('layouts.master')

@section('title') Kalkulasi Nilai - Detail @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kalkulasi Nilai @endslot
        @slot('li_2') Detail @endslot
        @slot('title') Kalkulasi Nilai @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Detail kalkulasi nilai akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
