@extends('layouts.master')

@section('title', 'Application Detail')

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <style>
        .acitivity-timeline {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .acitivity-item {
            border-left: 2px solid #e2e8f0;
            padding-left: 20px;
            margin-left: 10px;
            position: relative;
        }
        .acitivity-item:before {
            content: '';
            position: absolute;
            left: -6px;
            top: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Recruitment @endslot
        @slot('li_2') Applications @endslot
        @slot('title') Detail Lamaran #{{ $application->no_lamaran }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <!-- Timeline Progress -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Progress Seleksi</h5>
                </div>
                <div class="card-body">
                    @include('recruitment.applications.progress-tracker', ['application' => $application])
                </div>
            </div>

            <!-- Status Update Form (Non-AJAX) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Status & Nilai</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.ats.applications.update-status', ['userId' => $userId, 'application' => $application->id]) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="menunggu_seleksi" {{ $application->status == 'menunggu_seleksi' ? 'selected' : '' }}>Menunggu Seleksi</option>
                                    <option value="seleksi_administrasi" {{ $application->status == 'seleksi_administrasi' ? 'selected' : '' }}>Seleksi Administrasi</option>
                                    <option value="lolos_administrasi" {{ $application->status == 'lolos_administrasi' ? 'selected' : '' }}>Lolos Administrasi</option>
                                    <option value="tidak_lolos_administrasi" {{ $application->status == 'tidak_lolos_administrasi' ? 'selected' : '' }}>Tidak Lolos Administrasi</option>
                                    <option value="tes_tertulis" {{ $application->status == 'tes_tertulis' ? 'selected' : '' }}>Tes Tertulis</option>
                                    <option value="lolos_tes" {{ $application->status == 'lolos_tes' ? 'selected' : '' }}>Lolos Tes</option>
                                    <option value="tidak_lolos_tes" {{ $application->status == 'tidak_lolos_tes' ? 'selected' : '' }}>Tidak Lolos Tes</option>
                                    <option value="wawancara" {{ $application->status == 'wawancara' ? 'selected' : '' }}>Wawancara</option>
                                    <option value="lolos_wawancara" {{ $application->status == 'lolos_wawancara' ? 'selected' : '' }}>Lolos Wawancara</option>
                                    <option value="tidak_lolos_wawancara" {{ $application->status == 'tidak_lolos_wawancara' ? 'selected' : '' }}>Tidak Lolos Wawancara</option>
                                    <option value="penawaran_kerja" {{ $application->status == 'penawaran_kerja' ? 'selected' : '' }}>Penawaran Kerja</option>
                                    <option value="diterima" {{ $application->status == 'diterima' ? 'selected' : '' }}>Diterima</option>
                                    <option value="ditolak" {{ $application->status == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Skor Administrasi (0-100)</label>
                                <input type="number" step="0.01" class="form-control" name="skor_administrasi" value="{{ $application->skor_administrasi }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nilai Tes (0-100)</label>
                                <input type="number" step="0.01" class="form-control" name="nilai_tes" value="{{ $application->nilai_tes }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nilai Wawancara (0-100)</label>
                                <input type="number" step="0.01" class="form-control" name="nilai_wawancara" value="{{ $application->nilai_wawancara }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Catatan Rekruter</label>
                                <textarea class="form-control" name="catatan" rows="3" placeholder="Tambahkan catatan...">{{ $application->catatan_rekruter }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line"></i> Update Status
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                    <i class="ri-calendar-line"></i> Jadwalkan Interview
                                </button>
                                <button type="button" class="btn btn-info" onclick="sendMessage()">
                                    <i class="ri-mail-send-line"></i> Kirim Pesan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Timeline Activities -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktivitas</h5>
                </div>
                <div class="card-body">
                    <div class="acitivity-timeline">
                        @forelse($application->stages as $stage)
                            <div class="acitivity-item d-flex py-2">
                                <div class="flex-shrink-0 avatar-xs acitivity-avatar">
                                    <div class="avatar-title rounded-circle bg-{{ $stage->status == 'lolos' ? 'success' : ($stage->status == 'tidak_lolos' ? 'danger' : 'info') }}-subtle text-{{ $stage->status == 'lolos' ? 'success' : ($stage->status == 'tidak_lolos' ? 'danger' : 'info') }}">
                                        <i class="ri-timeline-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">{{ $stage->recruitmentPipelineStage->nama_tahapan }}
                                        @if ($stage->nilai)
                                            <span class="badge bg-success ms-2">Nilai: {{ $stage->nilai }}</span>
                                        @endif
                                    </h6>
                                    <p class="text-muted mb-1">{{ $stage->catatan }}</p>
                                    @if ($stage->jadwal_mulai)
                                        <p class="text-muted mb-1">
                                            <i class="ri-calendar-line"></i>
                                            {{ \Carbon\Carbon::parse($stage->jadwal_mulai)->format('d M Y H:i') }}
                                            @if ($stage->lokasi)
                                                - {{ $stage->lokasi }}
                                            @endif
                                        </p>
                                    @endif
                                    <small class="text-muted">{{ $stage->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-muted">Belum ada aktivitas</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Candidate Profile Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block">
                        <div class="avatar-xl">
                            <img src="{{ $application->recruitmentProfile->user->avatar ? URL::asset('images/' . $application->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}" alt="" class="img-fluid rounded-circle">
                        </div>
                    </div>
                    <h5 class="mt-3 mb-1">{{ $application->recruitmentProfile->user->name }}</h5>
                    <p class="text-muted">{{ $application->recruitmentJob->judul }}</p>

                    <div class="hstack gap-2 justify-content-center">
                        <a href="{{ route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $application->recruitmentProfile->id]) }}" class="btn btn-primary btn-sm">
                            <i class="ri-user-line"></i> Lihat Profile
                        </a>
                        <a href="#" class="btn btn-soft-success btn-sm" onclick="downloadCV()">
                            <i class="ri-file-pdf-line"></i> Download CV
                        </a>
                    </div>
                </div>
                <div class="card-body border-top">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">No. Lamaran</span>
                        <span class="fw-medium">#{{ $application->no_lamaran }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Tanggal Melamar</span>
                        <span class="fw-medium">{{ $application->tanggal_melamar->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Posisi</span>
                        <span class="fw-medium">{{ $application->recruitmentJob->judul }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Status</span>
                        <span class="badge bg-success">{{ str_replace('_', ' ', $application->status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Skor Administrasi</span>
                        <span class="fw-medium">{{ $application->skor_administrasi ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Nilai Tes</span>
                        <span class="fw-medium">{{ $application->nilai_tes ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Nilai Wawancara</span>
                        <span class="fw-medium">{{ $application->nilai_wawancara ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Nilai Akhir</span>
                        <span class="fw-medium">{{ $application->nilai_akhir ? number_format($application->nilai_akhir, 2) : '-' }}</span>
                    </div>
                    @if($application->ranking)
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Ranking</span>
                        <span class="fw-medium">{{ $application->ranking }}</span>
                    </div>
                    @endif
                    @if($application->processedBy)
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Diproses Oleh</span>
                        <span class="fw-medium">{{ $application->processedBy->name }}</span>
                    </div>
                    @endif
                    @if($application->diproses_at)
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Diproses Pada</span>
                        <span class="fw-medium">{{ $application->diproses_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                    @if($application->selesai_at)
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Selesai Pada</span>
                        <span class="fw-medium">{{ $application->selesai_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted">Pendidikan Terakhir</span>
                        <h6>
                            @php
                                $pendidikan = $application->recruitmentProfile->educations->where('jenjang', 's1')->first() ?? $application->recruitmentProfile->educations->last();
                            @endphp
                            {{ $pendidikan->jenjang ?? '-' }} - {{ $pendidikan->jurusan ?? '-' }}
                            <br><small class="text-muted">{{ $pendidikan->nama_satuan_pendidikan ?? '' }}</small>
                        </h6>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Total Pengalaman</span>
                        <h6>{{ $application->recruitmentProfile->workExperiences->sum('lama_bekerja_bulan') / 12 }} Tahun</h6>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Skill</span>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach ($application->recruitmentProfile->skills->take(5) as $skill)
                                <span class="badge bg-primary-subtle text-primary">{{ $skill->nama_skill }}</span>
                            @endforeach
                            @if ($application->recruitmentProfile->skills->count() > 5)
                                <span class="badge bg-secondary-subtle text-secondary">+{{ $application->recruitmentProfile->skills->count() - 5 }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Jadwalkan Interview -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Jadwalkan Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('user.ats.interviews.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tahapan <span class="text-danger">*</span></label>
                            <select class="form-control" name="stage_name" required>
                                <option value="">Pilih Tahapan</option>
                                <option value="Seleksi Administrasi">Seleksi Administrasi</option>
                                <option value="Tes Tertulis">Tes Tertulis</option>
                                <option value="Wawancara HR">Wawancara HR</option>
                                <option value="Wawancara User">Wawancara User</option>
                                <option value="Tes Praktek">Tes Praktek</option>
                                <option value="Medical Checkup">Medical Checkup</option>
                                <option value="Psychological Test">Psychological Test</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="jadwal_mulai" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="datetime-local" class="form-control" name="jadwal_selesai">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi</label>
                                <input type="text" class="form-control" name="lokasi" placeholder="Online / Offline">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Penilai</label>
                                <select class="form-control" name="penilai_id">
                                    <option value="">Pilih Penilai</option>
                                    @foreach ($interviewers ?? [] as $interviewer)
                                        <option value="{{ $interviewer->id }}">{{ $interviewer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function sendMessage() {
            Swal.fire({
                title: 'Kirim Pesan ke Pelamar',
                html: `
                    <textarea id="messageText" class="swal2-textarea" placeholder="Tulis pesan Anda..."></textarea>
                    <small class="text-muted">Pesan akan dikirim ke email dan notifikasi pelamar.</small>
                `,
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    const message = document.getElementById('messageText').value;
                    if (!message || message.trim() === '') {
                        Swal.showValidationMessage('Pesan tidak boleh kosong');
                        return false;
                    }
                    return message;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("user.ats.applications.send-message", $application->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: result.value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Terkirim!', data.message, 'success');
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Gagal!', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        }

        function downloadCV() {
            window.location.href = '{{ route('user.ats.candidates.download-cv', ['userId' => $userId, 'candidate' => $application->recruitmentProfile->id]) }}';
        }
    </script>
@endsection