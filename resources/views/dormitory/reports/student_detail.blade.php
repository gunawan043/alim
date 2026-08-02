@extends('layouts.master')
@section('title') Detail Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Detail Santri @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-lg rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                                <i class="ri-user-line fs-24"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $student->name }}</h4>
                            <p class="text-muted mb-0">NISN: {{ $student->nisn ?? '-' }} | No. HP: {{ $student->phone ?? '-' }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ route('user.profile.my', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ri-arrow-go-back-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Riwayat Kamar / Resident History --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-building-line me-1"></i> Riwayat Kamar</h4>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kamar</th>
                                <th>Blok</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($residents as $resident)
                            <tr>
                                <td>{{ $resident->room->code ?? '-' }}</td>
                                <td>{{ $resident->room?->wing->name ?? '-' }}</td>
                                <td>{{ $resident->check_in_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $resident->check_out_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if($resident->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum Ada Riwayat Residensi</h6>
                                    <p class="text-muted mb-3 small">Santri ini belum memiliki riwayat residensi.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Izin Terakhir --}}
    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-pass-valid-line me-1"></i> Izin Terakhir</h4>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Tujuan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPermits as $permit)
                            <tr>
                                <td>{{ $permit->created_at?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $permit->permit_type ?? '-' }}</td>
                                <td>{{ Str::limit($permit->destination ?? '-', 30) ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $permit->status === 'approved' ? 'bg-success' : ($permit->status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ ucfirst($permit->status ?? '-') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Izin</h6>
                                    <p class="text-muted mb-3 small">Santri ini belum memiliki riwayat perizinan.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pelanggaran Terakhir --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-error-warning-line me-1"></i> Pelanggaran Terakhir</h4>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Poin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentViolations as $violation)
                            <tr>
                                <td>{{ $violation->violation_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $violation->violation_type ?? '-' }}</td>
                                <td>{{ $violation->points ?? 0 }}</td>
                                <td>{{ Str::limit($violation->action_taken ?? '-', 25) ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Pelanggaran</h6>
                                    <p class="text-muted mb-3 small">Santri ini belum memiliki riwayat pelanggaran.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6 offset-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-medal-line me-1"></i> Penghargaan</h4>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRewards as $reward)
                            <tr>
                                <td>{{ $reward->awarded_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $reward->title ?? '-' }}</td>
                                <td>{{ $reward->category_text ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak Ada Penghargaan</h6>
                                    <p class="text-muted mb-3 small">Santri ini belum menerima penghargaan.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
