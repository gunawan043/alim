@extends('layouts.master')
@section('title')Time to Hire - Reports @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2') Reports @endslot
        @slot('title') Time to Hire @endslot
    @endcomponent
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4>Time to Hire</h4>
                    <p class="text-muted">Time to hire report will be displayed here.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
