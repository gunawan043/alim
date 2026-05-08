@extends('layouts.master')
@section('title') Application Stages @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Applications @endslot
@slot('li_2') Job Applications @endslot
@slot('title') Tahapan Seleksi #{{ $application->no_lamaran }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-lg mb-3 mx-auto">
                    <img src="{{ $application->recruitmentProfile->user->avatar ? URL::asset('images/'.$application->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}" 
                         alt="" class="img-thumbnail rounded-circle">
                </div>
                <h5>{{ $application->recruitmentProfile->user->name }}</h5>
                <p class="text-muted">{{ $application->recruitmentJob->judul }}</p>
                
                <div class="d-flex justify-content-between mt-3">
                    <span class="text-muted">No. Lamaran:</span>
                    <span class="fw-medium">#{{ $application->no_lamaran }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Tanggal Melamar:</span>
                    <span class="fw-medium">{{ $application->tanggal_melamar->format('d M Y') }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Status:</span>
                    @php
                        $statusClass = [
                            'menunggu_seleksi' => 'secondary',
                            'seleksi_administrasi' => 'info',
                            'lolos_administrasi' => 'primary',
                            'tidak_lolos_administrasi' => 'danger',
                            'tes_tertulis' => 'warning',
                            'lolos_tes' => 'success',
                            'tidak_lolos_tes' => 'danger',
                            'wawancara' => 'purple',
                            'lolos_wawancara' => 'success',
                            'diterima' => 'success',
                            'ditolak' => 'danger',
                        ][$application->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ str_replace('_', ' ', $application->status) }}</span>
                </div>
                
                <hr>
                
                <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]) }}" class="btn btn-primary w-100">
                    <i class="ri-arrow-left-line"></i> Kembali ke Detail
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tahapan Seleksi</h5>
            </div>
            <div class="card-body">
                <!-- Timeline Progress -->
                <div class="position-relative ms-2">
                    <div class="progress" style="width: 4px; height: 100%; position: absolute; left: 8px; top: 0; bottom: 0;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: 100%; height: {{ $application->stages->count() > 0 ? ($application->stages->where('status', 'lolos')->count() / max(1, $application->stages->count()) * 100) : 0 }}%;" 
                             aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    @forelse($application->stages->sortBy('urutan') as $stage)
                    <div class="d-flex align-items-start mb-4 position-relative" style="z-index: 1;">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <div class="avatar-title rounded-circle bg-{{ $stage->status == 'lolos' ? 'success' : ($stage->status == 'tidak_lolos' ? 'danger' : 'warning') }}-subtle 
                                             text-{{ $stage->status == 'lolos' ? 'success' : ($stage->status == 'tidak_lolos' ? 'danger' : 'warning') }} border border-2 border-white">
                                    <i class="ri-timeline-line fs-18"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <h6 class="card-title mb-0 flex-grow-1">{{ $stage->recruitmentPipelineStage->nama_tahapan }}</h6>
                                        <span class="badge bg-{{ $stage->status == 'lolos' ? 'success' : ($stage->status == 'tidak_lolos' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($stage->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if($stage->jadwal_mulai)
                                    <div class="row mb-2">
                                        <div class="col-md-3 text-muted">Jadwal:</div>
                                        <div class="col-md-9">
                                            {{ $stage->jadwal_mulai->format('d M Y H:i') }}
                                            @if($stage->jadwal_selesai)
                                                - {{ $stage->jadwal_selesai->format('H:i') }}
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if($stage->lokasi)
                                    <div class="row mb-2">
                                        <div class="col-md-3 text-muted">Lokasi:</div>
                                        <div class="col-md-9">{{ $stage->lokasi }}</div>
                                    </div>
                                    @endif
                                    
                                    @if($stage->penilai)
                                    <div class="row mb-2">
                                        <div class="col-md-3 text-muted">Penilai:</div>
                                        <div class="col-md-9">{{ $stage->penilai->name }}</div>
                                    </div>
                                    @endif
                                    
                                    @if($stage->nilai)
                                    <div class="row mb-2">
                                        <div class="col-md-3 text-muted">Nilai:</div>
                                        <div class="col-md-9">
                                            <span class="badge bg-success">{{ $stage->nilai }}</span>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if($stage->catatan)
                                    <div class="row">
                                        <div class="col-md-3 text-muted">Catatan:</div>
                                        <div class="col-md-9">{{ $stage->catatan }}</div>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-light text-end">
                                    <small class="text-muted">{{ $stage->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" style="width:72px;height:72px"></lord-icon>
                        <h5 class="mt-3">Belum Ada Tahapan</h5>
                        <p class="text-muted">Belum ada tahapan seleksi untuk lamaran ini.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection