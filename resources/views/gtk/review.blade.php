@extends('layouts.master')
@section('title')
Review Data GTK - {{ $gtk->name }}
@endsection
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .page-progress {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .step {
        text-align: center;
        flex: 1;
    }
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #dee2e6;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 5px;
    }
    .step.active .step-number {
        background: #0ab39c;
        color: white;
    }
    .step.completed .step-number {
        background: #198754;
        color: white;
    }
    .step-label {
        font-size: 12px;
        color: #6c757d;
    }
    .step.active .step-label {
        color: #0ab39c;
        font-weight: bold;
    }
    .step.completed .step-label {
        color: #198754;
    }
    .review-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .review-item {
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .review-item:last-child {
        border-bottom: none;
    }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
Data GTK
@endslot
@slot('title')
Review Data GTK - {{ $gtk->name }}
@endslot
@endcomponent

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Review Data GTK - {{ $gtk->name }}</h4>
            </div>
            
            <!-- Progress Bar -->
            <div class="page-progress">
                <div class="step-indicator">
                    <div class="step completed">
                        <div class="step-number">1</div>
                        <div class="step-label">Data Pribadi</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number">2</div>
                        <div class="step-label">Kepegawaian</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number">3</div>
                        <div class="step-label">Alamat</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number">4</div>
                        <div class="step-label">Kontak</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number">5</div>
                        <div class="step-label">Keluarga</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">6</div>
                        <div class="step-label">Review</div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="text-center mb-4">
                    <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#0ab39c,secondary:#005981" style="width:120px;height:120px"></lord-icon>
                    <h5>Review Data GTK</h5>
                    <p class="text-muted">Periksa kembali semua data yang telah diisi sebelum dipublikasikan.</p>
                </div>
                
                <!-- Data Pribadi -->
                <div class="review-section">
                    <h6 class="mb-3"><i class="ri-user-line me-2"></i>Data Pribadi</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>Nama Lengkap:</strong> {{ $gtk->name }}
                            </div>
                            <div class="review-item">
                                <strong>Email:</strong> {{ $gtk->email }}
                            </div>
                            <div class="review-item">
                                <strong>NIK:</strong> {{ $gtk->profile->nik ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Tempat/Tanggal Lahir:</strong> {{ ($gtk->profile->tempat_lahir ?? '-') . ' / ' . ($gtk->profile->tanggal_lahir ? date('d-m-Y', strtotime($gtk->profile->tanggal_lahir)) : '-') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>Jenis Kelamin:</strong> {{ $gtk->profile->jenis_kelamin == 'L' ? 'Laki-laki' : ($gtk->profile->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}
                            </div>
                            <div class="review-item">
                                <strong>Golongan Darah:</strong> {{ $gtk->profile->golongan_darah ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Status Perkawinan:</strong> {{ $gtk->profile->status_perkawinan ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Nama Ibu Kandung:</strong> {{ $gtk->profile->nama_ibu_kandung ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Kepegawaian -->
                <div class="review-section">
                    <h6 class="mb-3"><i class="ri-briefcase-line me-2"></i>Data Kepegawaian</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>NUPY:</strong> {{ $gtk->employment->nupy ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Jenis GTK:</strong> {{ $gtk->employment->jenis_gtk ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Jabatan:</strong> {{ $gtk->employment->jabatan ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Status Kepegawaian:</strong> {{ $gtk->employment->status_kepegawaian ?? '-' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>TMT:</strong> {{ $gtk->employment->tmt ? date('d-m-Y', strtotime($gtk->employment->tmt)) : '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Satuan Kerja:</strong> {{ $gtk->workUnits->pluck('nama')->implode(', ') ?: '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Password:</strong> <span class="badge bg-info">Sama dengan NUPY</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Alamat -->
                <div class="review-section">
                    <h6 class="mb-3"><i class="ri-home-line me-2"></i>Data Alamat</h6>
                    @if($gtk->address && ($gtk->address->jalan || $gtk->address->desa))
                    <div class="row">
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>Jalan:</strong> {{ $gtk->address->jalan ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>RT/RW:</strong> {{ $gtk->address->rt_rw ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Dusun:</strong> {{ $gtk->address->dusun ?? '-' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>Desa/Kelurahan:</strong> {{ $gtk->address->desa ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Kecamatan:</strong> {{ $gtk->address->kecamatan_name ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Kabupaten/Kota:</strong> {{ $gtk->address->kab_kota_name ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Provinsi:</strong> {{ $gtk->address->provinsi_name ?? '-' }}
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-2"></i>Data alamat belum diisi
                    </div>
                    @endif
                </div>
                
                <!-- Data Kontak -->
                <div class="review-section">
                    <h6 class="mb-3"><i class="ri-phone-line me-2"></i>Data Kontak</h6>
                    @if($gtk->contact && ($gtk->contact->no_hp || $gtk->contact->no_whatsapp))
                    <div class="row">
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>No. HP:</strong> {{ $gtk->contact->no_hp ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>No. WhatsApp:</strong> {{ $gtk->contact->no_whatsapp ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Kontak Darurat:</strong> {{ $gtk->contact->kontak_darurat ?? '-' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="review-item">
                                <strong>Instagram:</strong> {{ $gtk->contact->instagram ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Facebook:</strong> {{ $gtk->contact->facebook ?? '-' }}
                            </div>
                            <div class="review-item">
                                <strong>Twitter:</strong> {{ $gtk->contact->twitter ?? '-' }}
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-2"></i>Data kontak belum diisi
                    </div>
                    @endif
                </div>
                
                <!-- Data Keluarga -->
                <div class="review-section">
                    <h6 class="mb-3"><i class="ri-group-line me-2"></i>Data Keluarga</h6>
                    @if($gtk->familyMembers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Hubungan</th>
                                    <th>Nama</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Pekerjaan</th>
                                    <th>Pendidikan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gtk->familyMembers as $member)
                                <tr>
                                    <td>{{ $member->relationship ?? '-' }}</td>
                                    <td>{{ $member->nama ?? '-' }}</td>
                                    <td>{{ $member->jenis_kelamin == 'L' ? 'Laki-laki' : ($member->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}</td>
                                    <td>{{ $member->pekerjaan ?? '-' }}</td>
                                    <td>{{ $member->pendidikan_terakhir ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-2"></i>Data keluarga belum diisi
                    </div>
                    @endif
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}#step-family" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Kembali ke Data Keluarga
                    </a>
                    <div>
                        <form id="publishForm" action="{{ route('user.gtk.update', ['userId' => $userId, 'uuid' => $gtk->id]) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success" id="publishBtn">
                                <span class="spinner-border spinner-border-sm d-none me-1" id="loadingSpinner"></span>
                                <i class="ri-check-double-line me-1"></i> Publikasikan Data GTK
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#publishForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Publikasikan Data GTK?',
            text: 'Data akan dipublikasikan dan dapat diakses oleh sistem',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Publikasikan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                publishGTK();
            }
        });
    });
    
    function publishGTK() {
        // Show loading
        $('#publishBtn').prop('disabled', true);
        $('#loadingSpinner').removeClass('d-none');
        
        $.ajax({
            url: '{{ route("personalia.gtk.publish", $gtk->id) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data GTK berhasil dipublikasikan',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("personalia.gtk.index") }}';
                });
            },
            error: function(xhr) {
                $('#publishBtn').prop('disabled', false);
                $('#loadingSpinner').addClass('d-none');
                
                let message = 'Terjadi kesalahan saat mempublikasikan data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: message
                });
            }
        });
    }
});
</script>
@endsection