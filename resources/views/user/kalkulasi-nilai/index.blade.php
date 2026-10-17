@extends('layouts.master')

@section('title') Kalkulasi Nilai @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('li_2') Kalkulasi Nilai @endslot
        @slot('title') Kalkulasi Nilai @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Kalkulasi Nilai STS & SAS</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        Fitur kalkulasi nilai sedang dalam pengembangan. Silakan gunakan menu Data Nilai Kelas untuk melihat rekap nilai.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
