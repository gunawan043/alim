@extends('layouts.master')
@section('title') Detail Konseling @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}">Konseling</a> @endslot
        @slot('title') Detail Konseling @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Catatan Konseling</h5>
                        <div>
                            <a href="{{ route('user.uks.counseling-records.edit', ['userId' => $userId, 'uuid' => $record->id]) }}"
                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i> Edit</a>
                            <form method="POST" action="{{ route('user.uks.counseling-records.destroy', ['userId' => $userId, 'uuid' => $record->id]) }}"
                                  class="d-inline" >
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr><td class="fw-semibold text-muted" style="width:160px">Nama Santri</td><td class="fw-semibold">{{ $record->student?->name ?? '-' }}</td></tr>
                                    <tr><td class="fw-semibold text-muted">Tahun Ajaran</td><td>{{ $record->academicYear?->name ?? '-' }}</td></tr>
                                    <tr><td class="fw-semibold text-muted">Konselor</td><td>{{ $record->counselor?->name ?? '-' }}</td></tr>
                                    <tr><td class="fw-semibold text-muted">Tipe Sesi</td><td><span class="badge bg-info">{{ ucfirst($record->session_type) }}</span></td></tr>
                                    <tr><td class="fw-semibold text-muted">Tanggal</td><td>{{ $record->session_date?->format('d/m/Y') }}</td></tr>
                                    <tr><td class="fw-semibold text-muted">Topik</td><td>{{ $record->topic ?? '-' }}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-semibold text-muted" style="width:160px">Perlu Rujukan</td>
                                        <td>
                                            @if($record->referral_needed)
                                                <span class="badge bg-danger">Ya — {{ $record->referred_to ?? '' }}</span>
                                            @else
                                                <span class="badge bg-success">Tidak</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Konfirmasi Wali</td>
                                        <td>
                                            @if($record->parent_informed)
                                                <span class="badge bg-success">Sudah — {{ $record->parent_informed_at?->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span class="badge bg-light text-dark">Belum</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Status Rahasia</td>
                                        <td>
                                            @if($record->is_confidential)
                                                <span class="badge bg-dark"><i class="ri-lock-line me-1"></i> Rahasia</span>
                                            @else
                                                <span class="badge bg-light text-dark">Terbuka</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><td class="fw-semibold text-muted">Sesi Berikutnya</td><td>{{ $record->next_session_date?->format('d/m/Y') ?? '-' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($record->description)
                    <hr>
                    <h6 class="text-muted">Deskripsi / Kronologi</h6>
                    <p class="mb-0">{!! nl2br(e($record->description)) !!}</p>
                    @endif

                    @if($record->analysis)
                    <hr>
                    <h6 class="text-muted">Analisis</h6>
                    <p class="mb-0">{!! nl2br(e($record->analysis)) !!}</p>
                    @endif

                    @if($record->follow_up_plan)
                    <hr>
                    <h6 class="text-muted">Rencana Tindak Lanjut</h6>
                    <p class="mb-0">{!! nl2br(e($record->follow_up_plan)) !!}</p>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection