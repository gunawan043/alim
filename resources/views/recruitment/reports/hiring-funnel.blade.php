@extends('layouts.master')
@section('title')Hiring Funnel - Reports @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2') Reports @endslot
        @slot('title') Hiring Funnel @endslot
    @endcomponent
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4>Hiring Funnel</h4>
                    <p class="text-muted">Hiring funnel report will be displayed here.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
