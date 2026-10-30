@extends('layouts.master')
@section('title', 'Petugas Santri Asrama')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') Pengaturan @endslot
        @slot('title') Petugas Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if(session('operator_credentials'))
        @php $credential = session('operator_credentials'); @endphp
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="fw-semibold mb-1"><i class="ri-key-2-line me-1"></i>Akun petugas asrama dibuat</div>
            <div class="small">Nama: <strong>{{ $credential['name'] }}</strong> · Asrama: {{ $credential['unit'] }}</div>
            <div class="small">Username/NIS: <code>{{ $credential['username'] }}</code> · Password awal: <code>{{ $credential['password'] }}</code></div>
            <div class="small mt-1">Santri wajib mengganti password saat login pertama.</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-user-star-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Petugas</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-hotel-line me-1"></i>Petugas asrama {{ $dormitory->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Aktif</p>
                            <h3 class="stat-value">{{ number_format($stats['active'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-login-box-line me-1"></i>Dapat login dan bertugas</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded">
                                <i class="ri-close-circle-line text-secondary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Nonaktif</p>
                            <h3 class="stat-value">{{ number_format($stats['inactive'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-close-line me-1"></i>Akses dicabut</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-hotel-line text-primary me-1"></i>Petugas Santri Asrama</h5>
                            <p class="text-muted mb-0 small">Petugas hanya dapat mencatat kunjungan dan check-in. Tidak ada approval, edit, atau hapus.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <form method="GET" class="d-inline">
                                    <select name="dormitory_id" class="form-select form-select-sm" onchange="this.form.submit()" title="Pilih asrama">
                                        @foreach($dormitories as $item)
                                            <option value="{{ $item->id }}" @selected($dormitory->id === $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#operatorModal" onclick="openOperatorCreate()">
                                    <i class="ri-user-add-line me-1"></i> Tambah Petugas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $assignments->count() }}</strong> petugas
                            @if($assignments instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                dari <strong>{{ $assignments->total() }}</strong>
                            @endif
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Nama Santri</th>
                                    <th>NIS</th>
                                    <th>Username</th>
                                    <th>Terakhir Login</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $i => $assignment)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ ($assignments instanceof \Illuminate\Pagination\LengthAwarePaginator ? $assignments->firstItem() : 1) + $i }}
                                        </td>
                                        <td class="fw-semibold">{{ $assignment->student?->name ?? '—' }}</td>
                                        <td><code class="text-dark">{{ $assignment->student?->nis ?? '—' }}</code></td>
                                        <td><code class="text-dark">{{ $assignment->user?->username ?? '—' }}</code></td>
                                        <td>{{ $assignment->user?->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $assignment->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                <i class="ri-{{ $assignment->is_active ? 'checkbox-circle-line' : 'pause-circle-line' }} me-1"></i>
                                                {{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <form method="POST"
                                                      action="{{ route('user.asrama.operators.reset-password', ['userId' => $userId, 'id' => $assignment->id]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Reset password ke NIS/NISN?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset password">
                                                        <i class="ri-key-2-line"></i>
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                      action="{{ route('user.asrama.operators.toggle', ['userId' => $userId, 'id' => $assignment->id]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Ubah status petugas?');">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm {{ $assignment->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                            title="{{ $assignment->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <i class="{{ $assignment->is_active ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Belum ada petugas santri"
                                        message="Belum ada petugas santri di asrama ini." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL: Tambah Petugas
    ============================================================ --}}
    <div class="modal fade" id="operatorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.asrama.operators.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="dormitory_id" value="{{ $dormitory->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-user-add-line me-2 text-primary"></i>Tambah Petugas Asrama</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fs-12 text-muted mb-1">Santri Penghuni</label>
                                <select name="student_id" id="dormOperatorStudent" class="form-select" required></select>
                                <div class="form-text">Hanya penghuni aktif asrama ini.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fs-12 text-muted mb-1">Asrama</label>
                                <input class="form-control" value="{{ $dormitory->name }}" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-12 text-muted mb-1">Catatan</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">
                                    <i class="ri-information-line me-1"></i>Username memakai NIS, password awal NIS, wajib diganti saat login.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Buat Akun Petugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    const dormOperatorSearch = @json(route('user.asrama.operators.students', ['userId' => $userId]));
    const dormOperatorId     = @json($dormitory->id);

    function initDormOperatorSearch() {
        if (!window.jQuery || !$.fn.select2) return;
        $('#dormOperatorStudent').select2({
            width: '100%',
            dropdownParent: $('#operatorModal'),
            placeholder: 'Cari nama, NIS, atau NISN...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: dormOperatorSearch,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '', dormitory_id: dormOperatorId }; },
                processResults: function (data) { return data; },
                cache: true
            }
        });
    }

    function openOperatorCreate() {
        if (window.jQuery && $.fn.select2) {
            $('#dormOperatorStudent').val(null).trigger('change');
        }
    }

    document.addEventListener('DOMContentLoaded', initDormOperatorSearch);
</script>
@endsection