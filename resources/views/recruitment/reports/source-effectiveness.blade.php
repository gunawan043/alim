@extends('layouts.master')
@section('title')Source Effectiveness - Reports @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2') Reports @endslot
        @slot('title') Source Effectiveness @endslot
    @endcomponent
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4>Source Effectiveness</h4>
                    <p class="text-muted">Source effectiveness report will be displayed here.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
