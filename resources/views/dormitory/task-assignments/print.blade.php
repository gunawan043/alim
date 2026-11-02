@extends('layouts.master')
@section('title', 'Cetak Pembagian Tugas Asrama')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.task-assignments.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Pembagian Tugas</a> @endslot
        @slot('title') Cetak Pembagian Tugas @endslot
    @endcomponent

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('user.asrama.task-assignments.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Kembali
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="ri-printer-line me-1"></i>Cetak / Simpan PDF
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="text-center mb-4">
                <h5 class="mb-1 text-uppercase fw-bold">Pembagian Tugas Staf Asrama</h5>
                <div class="fw-semibold">{{ $dormitory->name }}</div>
                <div class="text-muted small">
                    Tahun Ajaran {{ $academicYear?->name ?? '—' }}
                    @if($dormitory->school)
                        · {{ $dormitory->school->name }}
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45px;" class="text-center">No</th>
                            <th>Nama Staf</th>
                            <th>Jabatan Asrama</th>
                            <th>Shift</th>
                            <th>Bidang Tugas</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['user']->name }}</div>
                                    <div class="small text-muted">{{ $row['user']->email }}</div>
                                </td>
                                <td>{{ $row['position_label'] }}</td>
                                <td>{{ \App\Support\DormitoryPosition::shiftLabel($row['shift']) }}</td>
                                <td>
                                    @forelse($row['duties'] as $duty)
                                        {{ \App\Support\DormitoryPosition::dutyLabel($duty) }}@if(! $loop->last), @endif
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td>{{ $row['notes'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada pembagian tugas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-5">
                <div class="col-6"></div>
                <div class="col-6 text-center">
                    <div>Mataram, {{ $printedAt->translatedFormat('d F Y') }}</div>
                    <div class="mt-4">Kepala Asrama</div>
                    <div style="height:70px;"></div>
                    <div class="fw-semibold text-decoration-underline">{{ $dormitory->head?->name ?? '..............................' }}</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print, .app-menu, .navbar-menu, .topbar, .footer, .breadcrumb, .card-header {
                display: none !important;
            }
            .card {
                border: 0 !important;
                box-shadow: none !important;
            }
            .card-body {
                padding: 0 !important;
            }
            body {
                background: #fff !important;
            }
        }
    </style>
@endsection
