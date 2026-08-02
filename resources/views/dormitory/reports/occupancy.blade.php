@extends('layouts.master')
@section('title') Laporan Penghuni & Kapasitas @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan Penghuni & Kapasitas @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-primary border-3">
                <div class="card-body">
                    <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Total Kapasitas</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg">{{ number_format($totalCapacity) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded fs-3">
                                <i class="ri-building-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-success border-3">
                <div class="card-body">
                    <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Terisi</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg text-success">{{ number_format($totalOccupied) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded fs-3">
                                <i class="ri-user-follow-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-animate overflow-hidden border-start border-warning border-3">
                <div class="card-body">
                    <p class="text-uppercase fw-medium font-size-16 text-secondary mb-0">Kosong</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="flex-grow-1">
                            <p class="fs-27 fw-semibold lh-lg text-warning">{{ number_format($totalCapacity - $totalOccupied) }}</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning rounded fs-3">
                                <i class="ri-door-open-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Detail Kapasitas Per Kamar</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kamar</th>
                                    <th>Blok</th>
                                    <th>Lantai</th>
                                    <th>Kapasitas</th>
                                    <th>Penghuni</th>
                                    <th>Ketersediaan</th>
                                    <th>Okupansi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rooms as $room)
                                @php
                                    $available = $room->capacity - $room->occupied_count;
                                    $occupancyPercent = $room->capacity > 0 ? round(($room->occupied_count / $room->capacity) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $room->code }}</strong></td>
                                    <td>{{ $room->wing->name ?? '-' }}</td>
                                    <td>Lantai {{ $room->floor ?? '-' }}</td>
                                    <td>{{ $room->capacity }}</td>
                                    <td>{{ $room->occupied_count }}</td>
                                    <td>
                                        @if($available > 0)
                                            <span class="badge bg-success">{{ $available }} slot</span>
                                        @else
                                            <span class="badge bg-danger">Penuh</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height:20px; min-width:100px;">
                                            <div class="progress-bar {{ $occupancyPercent >= 90 ? 'bg-danger' : ($occupancyPercent >= 70 ? 'bg-warning' : 'bg-success') }}"
                                                 style="width: {{ $occupancyPercent }}%">
                                                {{ $occupancyPercent }}%
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
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="text-muted small">
                <i class="ri-information-line me-1"></i>
                Data Okupansi diperbarui secara real-time berdasarkan penghuni aktif di setiap kamar.
            </div>
        </div>
    </div>
@endsection
