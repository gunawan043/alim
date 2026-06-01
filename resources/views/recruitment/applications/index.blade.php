@extends('layouts.master')
@section('title') Lamaran @endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('title') Lamaran @endslot
@endcomponent

{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-5"><i class="ri-file-list-3-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Total</p>
                        <h5 class="mb-0">{{ $stats['all'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-secondary-subtle text-secondary rounded-2 fs-5"><i class="ri-time-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Menunggu</p>
                        <h5 class="mb-0">{{ $stats['menunggu'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-info-subtle text-info rounded-2 fs-5"><i class="ri-file-check-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Seleksi Adm</p>
                        <h5 class="mb-0">{{ $stats['seleksi_adm'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-5"><i class="ri-edit-2-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Tes</p>
                        <h5 class="mb-0">{{ $stats['tes'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-purple-subtle text-purple rounded-2 fs-5"><i class="ri-user-voice-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Wawancara</p>
                        <h5 class="mb-0">{{ $stats['wawancara'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0"><span class="avatar-title bg-success-subtle text-success rounded-2 fs-5"><i class="ri-checkbox-circle-line"></i></span></div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.7rem">Diterima</p>
                        <h5 class="mb-0">{{ $stats['diterima'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table Card --}}
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <h6 class="card-title mb-0 flex-grow-1">Daftar Lamaran</h6>
            <button class="btn btn-success btn-sm" onclick="exportExcel()"><i class="ri-file-excel-line me-1"></i>Export</button>
        </div>
    </div>

    {{-- Status Tabs + Filter --}}
    <div class="card-body border-bottom pb-0">
        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ !request('tab') ? 'active' : '' }}" href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}">
                    Semua <span class="badge bg-secondary ms-1">{{ $stats['all'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') == 'menunggu' ? 'active' : '' }}"
                    href="{{ route('user.ats.applications.index', ['userId' => $userId, 'tab' => 'menunggu']) }}">
                    Menunggu <span class="badge bg-secondary ms-1">{{ $stats['menunggu'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') == 'seleksi_adm' ? 'active' : '' }}"
                    href="{{ route('user.ats.applications.index', ['userId' => $userId, 'tab' => 'seleksi_adm']) }}">
                    Seleksi Adm <span class="badge bg-info ms-1">{{ $stats['seleksi_adm'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') == 'tes' ? 'active' : '' }}"
                    href="{{ route('user.ats.applications.index', ['userId' => $userId, 'tab' => 'tes']) }}">
                    Tes <span class="badge bg-warning ms-1">{{ $stats['tes'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') == 'diterima' ? 'active' : '' }}"
                    href="{{ route('user.ats.applications.index', ['userId' => $userId, 'tab' => 'diterima']) }}">
                    Diterima <span class="badge bg-success ms-1">{{ $stats['diterima'] ?? 0 }}</span>
                </a>
            </li>
        </ul>
        <form class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" class="form-control" id="searchInput" placeholder="Nama atau no lamaran...">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices id="filterJob">
                    <option value="">Semua Posisi</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" {{ request('job_id') == $j->id ? 'selected' : '' }}>{{ $j->judul }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="menunggu_seleksi">Menunggu</option>
                    <option value="seleksi_administrasi">Seleksi Adm</option>
                    <option value="lolos_administrasi">Lolos Adm</option>
                    <option value="tidak_lolos_administrasi">Tidak Lolos</option>
                    <option value="tes_tertulis">Tes Tertulis</option>
                    <option value="wawancara">Wawancara</option>
                    <option value="diterima">Diterima</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-primary btn-sm" onclick="applyFilters()"><i class="ri-filter-line me-1"></i>Filter</button>
                <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        {{-- Bulk Action Bar (hanya muncul di tab Seleksi Adm) --}}
        <div id="bulkActionBar" class="d-none align-items-center gap-2 px-3 py-2 border-bottom bg-light">
            <span class="fw-semibold text-muted small" id="selectedCount">0 terpilih</span>
            <div class="vr"></div>
            <button class="btn btn-success btn-sm" onclick="bulkSetStatus('lolos_administrasi')">
                <i class="ri-check-line me-1"></i>Lolos Administrasi
            </button>
            <button class="btn btn-danger btn-sm" onclick="bulkSetStatus('tidak_lolos_administrasi')">
                <i class="ri-close-line me-1"></i>Tidak Lolos
            </button>
            <button class="btn btn-primary btn-sm" onclick="bulkAnnounce()">
                <i class="ri-notification-3-line me-1"></i>Kirim Pengumuman
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-nowrap table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAll"></th>
                        <th>No. Lamaran</th>
                        <th>Pelamar</th>
                        <th>Posisi</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th class="text-center" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td><input class="form-check-input" type="checkbox" value="{{ $app->id }}"></td>
                        <td>
                            <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}"
                                class="fw-semibold text-primary">#{{ $app->no_lamaran }}</a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $app->recruitmentProfile->user->avatar ? asset('images/'.$app->recruitmentProfile->user->avatar) : asset('build/images/users/avatar-1.jpg') }}"
                                    class="avatar-xs rounded-circle">
                                <div>
                                    <span class="fw-semibold text-body">{{ $app->recruitmentProfile->user->name }}</span>
                                    <div class="small text-muted">{{ $app->recruitmentProfile->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-info-subtle text-info">{{ $app->recruitmentJob->judul }}</span></td>
                        <td class="text-muted small">{{ $app->tanggal_melamar->format('d M Y') }}</td>
                        <td>
                            @php
                                $sClass = match($app->status) {
                                    'menunggu_seleksi' => 'secondary',
                                    'seleksi_administrasi' => 'info',
                                    'lolos_administrasi' => 'primary',
                                    'tidak_lolos_administrasi' => 'danger',
                                    'tes_tertulis', 'wawancara' => 'warning',
                                    'lolos_tes', 'lolos_wawancara' => 'success',
                                    'diterima' => 'success',
                                    'ditolak' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $sClass }}-subtle text-{{ $sClass }}">{{ str_replace('_',' ',ucfirst($app->status)) }}</span>
                        </td>
                        <td>
                            @if($app->nilai_akhir)
                                <span class="badge bg-success">{{ number_format($app->nilai_akhir,1) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}">
                                            <i class="ri-eye-line me-2 text-muted"></i>Lihat Detail
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.applications.stages', ['userId' => $userId, 'application' => $app->id]) }}">
                                            <i class="ri-timeline-line me-2 text-muted"></i>Proses Seleksi
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteApp('{{ $app->id }}')">
                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="py-4">
                                <i class="ri-file-list-3-line display-5 text-muted"></i>
                                <h6 class="mt-2 mb-1">Belum Ada Lamaran</h6>
                                <p class="text-muted">Belum ada pelamar yang terdaftar.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
        <div class="border-top px-3 py-2">
            @include('shared._pagination', ['paginator' => $applications])
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
function applyFilters() {
    let url = new URL(window.location.href);
    let job = document.getElementById('filterJob').value;
    let status = document.getElementById('filterStatus').value;
    if (job) url.searchParams.set('job_id', job); else url.searchParams.delete('job_id');
    if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
    window.location.href = url.toString();
}
function deleteApp(id) {
    Swal.fire({title:'Hapus?', text:'Data tidak bisa dikembalikan!', icon:'warning', showCancelButton:true, confirmButtonText:'Ya', cancelButtonText:'Batal'})
    .then(r => { if (r.isConfirmed) $.ajax({url:'/{{ $userId }}/ats/applications/'+id, type:'DELETE', data:{_token:'{{ csrf_token() }}'}, success:()=>{Swal.fire('Terhapus','','success').then(()=>location.reload())}}); });
}
document.getElementById('checkAll')?.addEventListener('change', e => {
    document.querySelectorAll('tbody .form-check-input').forEach(cb => cb.checked = e.target.checked);
    updateBulkBar();
});
document.querySelectorAll('tbody .form-check-input').forEach(cb => cb.addEventListener('change', updateBulkBar));

function updateBulkBar() {
    const checked = [...document.querySelectorAll('tbody .form-check-input:checked')];
    const bar = document.getElementById('bulkActionBar');
    const countEl = document.getElementById('selectedCount');
    if (!bar) return;
    const count = checked.length;
    countEl.textContent = count + ' terpilih';
    bar.classList.toggle('d-none', count === 0);
}

function bulkSetStatus(status) {
    const ids = getSelectedIds();
    if (!ids.length) return Swal.fire('Pilih kandidat dulu', '', 'warning');
    const statusText = status === 'lolos_administrasi' ? 'Lolos' : 'Tidak Lolos';
    Swal.fire({
        title: 'Konfirmasi Bulk',
        text: 'Yakin ubah status ' + ids.length + ' kandidat jadi ' + statusText + '?',
        icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch('/{{ $userId }}/ats/applications/bulk-action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ action: 'update_status', ids, status })
        }).then(res => res.json()).then(data => {
            Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
        }).catch(err => Swal.fire('Gagal', err.message, 'error'));
    });
}

function bulkAnnounce() {
    const ids = getSelectedIds();
    if (!ids.length) return Swal.fire('Pilih kandidat dulu', '', 'warning');
    Swal.fire({
        title: 'Kirim Pengumuman?',
        text: 'Kirim pengumuman hasil administrasi ke ' + ids.length + ' kandidat?',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch('/{{ $userId }}/ats/applications/announce-admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ application_ids: ids })
        }).then(res => res.json()).then(data => {
            Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
        }).catch(err => Swal.fire('Gagal', err.message, 'error'));
    });
}

function getSelectedIds() {
    return [...document.querySelectorAll('tbody .form-check-input:checked')].map(cb => cb.value);
}

let st = null;
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    clearTimeout(st);
    st = setTimeout(() => {
        let v = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none'; });
    }, 400);
});
</script>
@endsection