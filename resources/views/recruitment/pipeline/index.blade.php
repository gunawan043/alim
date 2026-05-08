@extends('layouts.master')
@section('title')Pipeline - {{ $job->title ?? 'Recruitment' }} @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2') Jobs @endslot
        @slot('title') Pipeline @endslot
    @endcomponent
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4>Pipeline</h4>
                    <p class="text-muted">Pipeline view for job: {{ $job->title ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
