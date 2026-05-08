@extends('layouts.master')
@section('title') Detail Medical Check-up @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">Medical Check-up</a> @endslot
        @slot('title') Detail Medical Check-up @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Medical Check-up</h5>
                        <div>
                            <a href="{{ route('user.uks.health-checkups.edit', ['userId' => $userId, 'uuid' => $checkup->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.health-checkups.destroy', ['userId' => $userId, 'uuid' => $checkup->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Nama Santri</td><td class="fw-semibold">{{ $checkup->student?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">NISN</td><td>{{ $checkup->student?->nisn ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $checkup->academicYear?->name ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Tanggal Pemeriksaan</td><td>{{ $checkup->checkup_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Jenis Pemeriksaan</td><td>{{ ucfirst($checkup->checkup_type) }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Petugas</td><td>{{ $checkup->examBy?->name ?? '-' }}</td></tr>
                        </table>
                    </div>

                    <hr>
                    <h6>Ukuran Tubuh</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Tinggi Badan</td><td>{{ $checkup->height_cm ? $checkup->height_cm . ' cm' : '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Berat Badan</td><td>{{ $checkup->weight_kg ? $checkup->weight_kg . ' kg' : '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">IMT</td>
                                <td>
                                    @if($checkup->bmi)
                                        <span class="badge bg-{{ $checkup->bmi_category === 'normal' ? 'success' : 'warning' }}">
                                            {{ round($checkup->bmi, 1) }} — {{ $checkup->bmi_category_text }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <hr>
                    <h6>Mata & Pendengaran</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Visus Mata Kiri</td><td>{{ $checkup->vision_left ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Visus Mata Kanan</td><td>{{ $checkup->vision_right ?? '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Status Pendengaran</td><td>{{ $checkup->hearing_status ? ucfirst(str_replace('_',' ',$checkup->hearing_status)) : '-' }}</td></tr>
                            <tr><td class="fw-semibold text-muted">Status Gigi</td><td>{{ $checkup->dental_status ? ucfirst(str_replace('_',' ',$checkup->dental_status)) : '-' }}</td></tr>
                        </table>
                    </div>

                    <hr>
                    <h6>Skrining TBC</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-semibold text-muted" style="width:180px">Hasil</td>
                                <td>
                                    @if($checkup->tb_screening_result)
                                        <span class="badge bg-{{ $checkup->tb_screening_result === 'negative' ? 'success' : ($checkup->tb_screening_result === 'positive' ? 'danger' : 'warning') }}">
                                            {{ ucfirst(str_replace('_',' ',$checkup->tb_screening_result)) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @if($checkup->tb_notes)
                                <tr><td class="fw-semibold text-muted">Catatan TBC</td><td>{{ $checkup->tb_notes }}</td></tr>
                            @endif
                        </table>
                    </div>

                    @if($checkup->notes)
                    <hr>
                    <h6>Catatan</h6>
                    <p class="text-muted">{{ $checkup->notes }}</p>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection