@extends('layouts.master')
@section('title')
    Schedule Interview
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Interviews
        @endslot
        @slot('li_2')
            Jadwal Interview
        @endslot
        @slot('title')
            Atur Jadwal Interview
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mb-3 mx-auto">
                        <img src="{{ $application->recruitmentProfile->user->avatar ? URL::asset('images/' . $application->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                            alt="" class="img-thumbnail rounded-circle">
                    </div>
                    <h5>{{ $application->recruitmentProfile->user->name }}</h5>
                    <p class="text-muted">{{ $application->recruitmentJob->judul }}</p>

                    <hr>

                    <div class="text-start">
                        <div class="mb-2">
                            <i class="ri-mail-line me-2 text-primary"></i>
                            {{ $application->recruitmentProfile->user->email }}
                        </div>
                        <div class="mb-2">
                            <i class="ri-phone-line me-2 text-primary"></i>
                            {{ $application->recruitmentProfile->no_hp ?? '-' }}
                        </div>
                        <div>
                            <i class="ri-whatsapp-line me-2 text-success"></i>
                            {{ $application->recruitmentProfile->no_whatsapp ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Form Jadwal Interview</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.ats.interviews.store', ['userId' => $userId]) }}" method="POST" id="scheduleForm">
                        @csrf
                        <input type="hidden" name="application_id" value="{{ $application->id }}">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <div>
                                    <label class="form-label">Tahapan <span class="text-danger">*</span></label>
                                    <select class="form-control @error('stage_name') is-invalid @enderror" data-choices
                                        name="stage_name" required>
                                        <option value="">Pilih Tahapan</option>
                                        <option value="Seleksi Administrasi">Seleksi Administrasi</option>
                                        <option value="Tes Tertulis">Tes Tertulis</option>
                                        <option value="Tes Praktek">Tes Praktek</option>
                                        <option value="Wawancara HR">Wawancara HR</option>
                                        <option value="Wawancara User">Wawancara User</option>
                                        <option value="Tes Psikologi">Tes Psikologi</option>
                                        <option value="Medical Checkup">Medical Checkup</option>
                                    </select>
                                    @error('stage_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div>
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="datetime-local"
                                        class="form-control @error('jadwal_mulai') is-invalid @enderror" name="jadwal_mulai"
                                        id="jadwal_mulai" required>
                                    @error('jadwal_mulai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div>
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="datetime-local"
                                        class="form-control @error('jadwal_selesai') is-invalid @enderror"
                                        name="jadwal_selesai" id="jadwal_selesai">
                                    @error('jadwal_selesai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div>
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control @error('lokasi') is-invalid @enderror"
                                        name="lokasi" placeholder="Online / Offline / Link Meeting">
                                    @error('lokasi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div>
                                    <label class="form-label">Penilai</label>
                                    <select class="form-control @error('penilai_id') is-invalid @enderror" data-choices
                                        name="penilai_id">
                                        <option value="">Pilih Penilai</option>
                                        @foreach ($interviewers as $interviewer)
                                            <option value="{{ $interviewer->id }}">{{ $interviewer->name }}
                                                ({{ $interviewer->roles->pluck('name')->join(', ') }})</option>
                                        @endforeach
                                    </select>
                                    @error('penilai_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div>
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control @error('catatan') is-invalid @enderror" name="catatan" rows="3"
                                        placeholder="Tambahkan catatan untuk kandidat..."></textarea>
                                    @error('catatan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="ri-information-line"></i>
                                    Kandidat akan menerima notifikasi via email dan WhatsApp setelah jadwal dibuat.
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="hstack justify-content-end gap-2">
                                    <a href="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}" class="btn btn-ghost-danger">
                                        <i class="ri-close-line align-bottom"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-calendar-line align-bottom"></i> Simpan Jadwal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Validasi tanggal selesai harus setelah tanggal mulai
        document.getElementById('jadwal_mulai').addEventListener('change', function() {
            let selesai = document.getElementById('jadwal_selesai');
            if (selesai.value && selesai.value < this.value) {
                alert('Tanggal selesai harus setelah tanggal mulai');
                selesai.value = '';
            }
        });

        document.getElementById('jadwal_selesai').addEventListener('change', function() {
            let mulai = document.getElementById('jadwal_mulai');
            if (this.value && this.value < mulai.value) {
                alert('Tanggal selesai harus setelah tanggal mulai');
                this.value = '';
            }
        });
    </script>
@endsection
