@extends('layouts.master')

@section('title', 'System Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">System Dashboard</h4>
                <span class="badge bg-danger-subtle text-danger">
                    <i class="bx bx-shield"></i> System Admin Mode
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm rounded-circle bg-primary-subtle text-primary">
                            <i class="bx bx-user fs-20"></i>
                        </div>
                        <div class="ms-3">
                            <p class="text-muted mb-1">Users (Total)</p>
                            <h4 class="mb-0">{{ $stats['users_total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm rounded-circle bg-success-subtle text-success">
                            <i class="bx bx-check-circle fs-20"></i>
                        </div>
                        <div class="ms-3">
                            <p class="text-muted mb-1">Users (Active)</p>
                            <h4 class="mb-0">{{ $stats['users_active'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm rounded-circle bg-danger-subtle text-danger">
                            <i class="bx bx-shield-quarter fs-20"></i>
                        </div>
                        <div class="ms-3">
                            <p class="text-muted mb-1">System Admins</p>
                            <h4 class="mb-0">{{ $stats['system_admins'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm rounded-circle bg-info-subtle text-info">
                            <i class="bx bx-group fs-20"></i>
                        </div>
                        <div class="ms-3">
                            <p class="text-muted mb-1">Roles / Permissions</p>
                            <h4 class="mb-0">{{ $stats['roles_total'] }} / {{ $stats['permissions_total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Schools</h5>
                    <p class="display-6">{{ $stats['schools_total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Dormitories</h5>
                    <p class="display-6">{{ $stats['dormitories_total'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
