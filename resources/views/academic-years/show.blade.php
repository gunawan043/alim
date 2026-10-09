@extends('layouts.master')
@section('title') Detail Tahun Ajaran @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">Tahun Ajaran</a> @endslot
        @slot('title') {{ $academicYear->name }} — {{ $academicYear->semester_text }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-{{ $academicYear->is_active ? 'success' : 'secondary' }}-subtle rounded fs-2">
                                <i class="ri-{{ $academicYear->is_active ? 'checkbox-circle' : 'pause-circle' }}-line text-{{ $academicYear->is_active ? 'success' : 'secondary' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Status</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $academicYear->is_active ? 'Aktif' : 'Nonaktif' }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Periode dipakai modul akademik</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-{{ $academicYear->semester === 'ganjil' ? 'primary' : 'info' }}-subtle rounded fs-2">
                                <i class="ri-{{ $academicYear->semester === 'ganjil' ? 'sun' : 'moon' }}-line text-{{ $academicYear->semester === 'ganjil' ? 'primary' : 'info' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Semester</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $academicYear->semester_text }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-calendar-2-line me-1"></i>{{ $academicYear->name }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Durasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">
                                @if($academicYear->start_date && $academicYear->end_date)
                                    {{ $academicYear->start_date->diffInDays($academicYear->end_date) + 1 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">hari</small>
                                @else
                                    —
                                @endif
                            </h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        @if($academicYear->start_date)
                            {{ $academicYear->start_date->format('d M Y') }} – {{ $academicYear->end_date?->format('d M Y') ?? '-' }}
                        @else
                            Periode belum diatur
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-user-add-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Masa Pendaftaran</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:15px;">
                                @if($academicYear->registration_start)
                                    {{ $academicYear->registration_start->format('d M Y') }} – {{ $academicYear->registration_end?->format('d M Y') ?? '-' }}
                                @else
                                    Belum diatur
                                @endif
                            </h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Penerimaan peserta didik</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Info --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ri-calendar-event-line text-primary me-1"></i>
                        {{ $academicYear->name }} — {{ $academicYear->semester_text }}
                    </h5>
                    <div class="d-flex gap-2">
                        @if(!$academicYear->is_active)
                            <a href="{{ route('user.academic-years.toggle-active', ['userId' => $userId, 'id' => $academicYear->id]) }}"
                                class="btn btn-sm btn-success"
                                onclick="return confirm('Yakin ingin mengaktifkan tahun ajaran ini?')">
                                <i class="ri-check-line me-1"></i> Aktifkan
                            </a>
                        @endif
                        <a href="{{ route('user.academic-years.edit', ['userId' => $userId, 'id' => $academicYear->id]) }}" class="btn btn-sm btn-warning">
                            <i class="ri-pencil-line me-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @php
                            $details = [
                                ['label' => 'Status', 'icon' => 'ri-checkbox-circle-line', 'value' => $academicYear->is_active ? 'Aktif' : 'Nonaktif'],
                                ['label' => 'Semester', 'icon' => 'ri-stack-line', 'value' => $academicYear->semester_text],
                                ['label' => 'Periode Tahun Ajaran', 'icon' => 'ri-calendar-line', 'value' => $academicYear->start_date ? $academicYear->start_date->format('d M Y').' — '.($academicYear->end_date?->format('d M Y') ?? '-') : '-'],
                                ['label' => 'Masa Pendaftaran', 'icon' => 'ri-user-add-line', 'value' => $academicYear->registration_start ? $academicYear->registration_start->format('d M Y').' — '.($academicYear->registration_end?->format('d M Y') ?? '-') : 'Belum diatur'],
                            ];
                        @endphp
                        @foreach($details as $detail)
                            <div class="col-md-6">
                                <div class="p-3 rounded border h-100">
                                    <small class="text-muted text-uppercase stat-label"><i class="{{ $detail['icon'] }} me-1"></i>{{ $detail['label'] }}</small>
                                    <div class="fw-semibold mt-1">{{ $detail['value'] }}</div>
                                </div>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <hr>
                            <div class="row text-muted small">
                                <div class="col-md-4">
                                    <i class="ri-calendar-line me-1"></i> Dibuat:
                                    {{ $academicYear->created_at->format('d M Y H:i') }}
                                </div>
                                <div class="col-md-4">
                                    <i class="ri-update-line me-1"></i> Diperbarui:
                                    {{ $academicYear->updated_at->format('d M Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card border-danger">
                <div class="card-header border-bottom-dashed bg-danger-subtle">
                    <h5 class="card-title mb-0 text-danger"><i class="ri-alert-line me-1"></i>Zona Berbahaya</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Menghapus tahun ajaran akan menghapus semua data yang terkait (rombongan belajar, mata pelajaran, dll).</p>
                    <button class="btn btn-sm btn-danger" onclick="deleteAY()">
                        <i class="ri-delete-bin-line me-1"></i> Hapus Tahun Ajaran
                    </button>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-links-line text-primary me-1"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('user.academic-years.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-list-check me-2"></i> Daftar Tahun Ajaran
                        </a>
                        <a href="{{ route('user.academic-years.create', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-add-line me-2"></i> Tambah Tahun Ajaran Lain
                        </a>
                        <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-book-open-line me-2"></i> Peta Kurikulum
                        </a>
                        <a href="{{ route('user.kaldik.index', ['userId' => $userId]) }}" class="btn btn-light w-100 text-start">
                            <i class="ri-calendar-event-line me-2"></i> Kalender Pendidikan
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-information-line text-info me-1"></i>Informasi</h5>
                </div>
                <div class="card-body">
                    <div class="vstack gap-2">
                        <div>
                            <small class="text-muted text-uppercase stat-label">ID</small>
                            <div class="font-monospace small">{{ $academicYear->id }}</div>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase stat-label">Nama TA</small>
                            <div>{{ $academicYear->name }}</div>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase stat-label">Semester</small>
                            <div>{{ $academicYear->semester_text }}</div>
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
        function deleteAY() {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Tahun ajaran \"{{ $academicYear->name }} — {{ $academicYear->semester_text }}\" akan dihapus permanen. Data terkait akan ikut terhapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("user.academic-years.destroy", ['userId' => $userId, 'id' => $academicYear->id]) }}';
                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';
                    var method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(token);
                    form.appendChild(method);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection
