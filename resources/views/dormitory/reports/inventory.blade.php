@extends('layouts.master')
@section('title') Laporan Inventaris @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan Inventaris @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-primary border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 text-truncate">
                            <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Total Items</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4 mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg">{{ number_format($totalItems) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded fs-3">
                                <i class="ri-tools-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-success border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 text-truncate">
                            <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Kondisi Baik</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4 mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg text-success">{{ number_format($goodItems) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded fs-3">
                                <i class="ri-checkbox-circle-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-danger border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 text-truncate">
                            <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Kondisi Rusak</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4 mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg text-danger">{{ number_format($damagedItems) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger rounded fs-3">
                                <i class="ri-error-warning-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0">Kondisi Inventaris</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Kondisi</th>
                                <th>Jumlah Item</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $conditionLabels = [
                                    'baik' => 'Baik',
                                    'rusak_ringan' => 'Rusak Ringan',
                                    'rusak_berat' => 'Rusak Berat',
                                    'hilang' => 'Hilang',
                                ];
                            @endphp
                            @foreach($byCondition as $condition => $count)
                            <tr>
                                <td>{{ $conditionLabels[$condition] ?? $condition }}</td>
                                <td>{{ number_format($count) }}</td>
                                <td>
                                    @php $pct = $totalItems > 0 ? round(($count / $totalItems) * 100, 1) : 0; @endphp
                                    <div class="progress" style="height:20px;">
                                        <div class="progress-bar {{ $condition === 'baik' ? 'bg-success' : ($condition === 'rusak_ringan' ? 'bg-warning' : 'bg-danger') }}"
                                             style="width: {{ $pct }}%">
                                            {{ $pct }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Detail Inventaris</h4>
                </div>
                <div class="card-body">
                    <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-outline-primary">
                        <i class="ri-edit-line me-1"></i> Kelola Inventaris
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
