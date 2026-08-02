@extends('layouts.master')

@section('title', $title ?? 'System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ $title ?? 'System' }}</h4>
                <span class="badge bg-danger-subtle text-danger">
                    <i class="bx bx-shield"></i> System Admin Mode
                </span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bx bx-cog bx-spin fs-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">{{ $message ?? 'Module in development.' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
