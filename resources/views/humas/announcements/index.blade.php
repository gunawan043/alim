@extends('layouts.master')
@section('title', $pageTitle ?? 'Pengumuman & Publikasi')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $announcementRoutes = $announcementRoutes ?? [
        'store' => 'user.humas.announcements.store',
        'update' => 'user.humas.announcements.update',
        'publish' => 'user.humas.announcements.publish',
        'destroy' => 'user.humas.announcements.destroy',
    ];
    $announcementRouteParams = $announcementRouteParams ?? ['userId' => $userId];
    $announcementRouteId = $announcementRouteId ?? 'id';
    $lockedAudience = $lockedAudience ?? null;
@endphp

@include('personalia._gtk-ui')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') {{ $breadcrumbParent ?? 'Humas' }} @endslot
        @slot('li_2') {{ $breadcrumbMiddle ?? 'Publikasi' }} @endslot
        @slot('title') {{ $pageHeading ?? 'Pengumuman & Publikasi' }} @endslot
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
         STATS CARDS — class sesuai template wajib
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-megaphone-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Seluruh pengumuman</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-send-plane-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terbit</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['published'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-checkbox-circle-line me-1"></i>Sudah dipublikasikan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded fs-2">
                                <i class="ri-draft-line text-secondary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['draft'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Belum diterbitkan</p>
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
                        <div class="col-md">
                            <h5 class="card-title mb-0">Pengumuman</h5>
                            <p class="text-muted mb-0 small">Publikasi ke wali santri dan/atau GTK.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#announcementModal" onclick="openAnnouncementCreate()">
                                    <i class="ri-add-line me-1"></i> Buat Pengumuman
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- QUICK FILTER --}}
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">Cari</label>
                                <input type="text" name="search" class="form-control form-control-md"
                                       placeholder="Cari judul pengumuman..." value="{{ request('search') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">Audiens</label>
                                <select name="audience" class="form-select form-select-md">
                                    <option value="">Semua audiens</option>
                                    <option value="wali" @selected(request('audience') === 'wali')>Wali Santri</option>
                                    <option value="gtk" @selected(request('audience') === 'gtk')>GTK</option>
                                    <option value="all" @selected(request('audience') === 'all')>Wali &amp; GTK</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">Status</label>
                                <select name="status" class="form-select form-select-md">
                                    <option value="">Semua status</option>
                                    <option value="published" @selected(request('status') === 'published')>Terbit</option>
                                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-md flex-shrink-0">
                                    <i class="ri-search-line me-1"></i> Cari
                                </button>
                                <a href="{{ url()->current() }}" class="btn btn-light btn-md flex-shrink-0" title="Reset filter">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    @if(request()->hasAny(['search', 'audience', 'status']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('audience'))
                                <span class="filter-badge active"><i class="ri-group-line"></i> {{ request('audience') === 'all' ? 'Wali & GTK' : ucfirst(request('audience')) }}</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(request('status')) }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $announcements->count() }}</strong> dari
                            <strong>{{ $announcements->total() }}</strong> pengumuman
                        </small>
                    </div>

                    {{-- TABEL --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Judul</th>
                                    <th class="text-center">Audiens</th>
                                    <th class="text-center">Status</th>
                                    <th>Terbit</th>
                                    <th>Dibuat Oleh</th>
                                    <th class="text-center" style="width:150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($announcements as $i => $announcement)
                                    <tr>
                                        <td class="text-center text-muted">{{ $announcements->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $announcement->title }}
                                                @if($announcement->images->count())
                                                    <span class="badge bg-primary-subtle text-primary ms-1">{{ $announcement->images->count() }} banner</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($announcement->excerpt ?: strip_tags($announcement->content), 80) }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info">{{ $announcement->audience === 'all' ? 'Wali & GTK' : ucfirst($announcement->audience) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $announcement->status === 'published' ? 'success' : 'secondary' }}-subtle text-{{ $announcement->status === 'published' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($announcement->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $announcement->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td>{{ $announcement->creator?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                        data-announcement-id="{{ $announcement->id }}"
                                                        data-announcement="{{ json_encode([
                                                            'id' => $announcement->id,
                                                            'title' => $announcement->title,
                                                            'category' => $announcement->category,
                                                            'audience' => $announcement->audience,
                                                            'excerpt' => $announcement->excerpt,
                                                            'content' => $announcement->content,
                                                            'is_pinned' => $announcement->is_pinned,
                                                            'expires_at' => $announcement->expires_at?->format('Y-m-d'),
                                                            'activity_start_date' => $announcement->activity_start_date?->format('Y-m-d'),
                                                            'activity_end_date' => $announcement->activity_end_date?->format('Y-m-d'),
                                                            'images' => $announcement->images->map(fn($image) => ['id' => $image->id, 'url' => $image->url])->values(),
                                                        ], JSON_HEX_APOS|JSON_HEX_QUOT) }}"
                                                        onclick="openAnnouncementEdit(this)" title="Edit">
                                                    <i class="ri-pencil-line"></i>
                                                </button>

                                                @if($announcement->status !== 'published')
                                                    <form method="POST"
                                                          action="{{ route($announcementRoutes['publish'], $announcementRouteParams + [$announcementRouteId => $announcement->id]) }}"
                                                          onsubmit="return confirm('Publikasikan pengumuman ini?');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Terbitkan">
                                                            <i class="ri-send-plane-line"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST"
                                                      action="{{ route($announcementRoutes['destroy'], $announcementRouteParams + [$announcementRouteId => $announcement->id]) }}"
                                                      onsubmit="return confirm('Hapus pengumuman?');"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Belum ada pengumuman"
                                        message="Belum ada pengumuman yang dibuat." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($announcements->hasPages())
                        @include('shared._pagination', ['paginator' => $announcements->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL: Buat / Edit Pengumuman
    ============================================================ --}}
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="announcementForm" action="{{ route($announcementRoutes['store'], $announcementRouteParams) }}">
                    @csrf
                    <input type="hidden" name="_method" id="announcementMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="announcementTitle"><i class="ri-add-circle-line me-2 text-primary"></i>Buat Pengumuman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fs-12 text-muted mb-1">Judul <span class="text-danger">*</span></label>
                                <input name="title" id="announcementTitleInput" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">Kategori</label>
                                <input name="category" id="announcementCategory" class="form-control" value="umum" required>
                            </div>
                            <div class="col-md-6">
                                @if($lockedAudience)
                                    <label class="form-label fs-12 text-muted mb-1">Audiens</label>
                                    <div class="form-control bg-light">Wali Santri penghuni asrama</div>
                                    <input type="hidden" name="audience" id="announcementAudience" value="{{ $lockedAudience }}">
                                @else
                                    <label class="form-label fs-12 text-muted mb-1">Audiens</label>
                                    <select name="audience" id="announcementAudience" class="form-select">
                                        <option value="all">Wali Santri &amp; GTK</option>
                                        <option value="wali">Wali Santri</option>
                                        <option value="gtk">GTK</option>
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Berlaku Sampai</label>
                                <input type="date" name="expires_at" id="announcementExpires" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Tanggal Mulai Kegiatan</label>
                                <input type="date" name="activity_start_date" id="announcementActivityStart" class="form-control">
                                <small class="text-muted">Opsional; otomatis masuk Kalender Pendidikan sebagai Agenda Kegiatan (tidak memengaruhi Pekan Efektif).</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Tanggal Selesai Kegiatan</label>
                                <input type="date" name="activity_end_date" id="announcementActivityEnd" class="form-control">
                                <small class="text-muted">Jenis dan warna dipilih otomatis. Agenda ini tidak dihitung sebagai Kaldik resmi/Pekan Efektif.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-12 text-muted mb-1">Ringkasan</label>
                                <input name="excerpt" id="announcementExcerpt" class="form-control" maxlength="500">
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-12 text-muted mb-1">Isi Pengumuman <span class="text-danger">*</span></label>
                                <textarea name="content" id="announcementContent" rows="6" class="form-control" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-12 text-muted mb-1">Banner Carousel (maks. 5 gambar)</label>
                                <div id="announcementExistingBanners" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <input type="file" name="banners[]" id="announcementBanners" accept="image/jpeg,image/png,image/webp" class="form-control" multiple onchange="previewAnnouncementFiles(this)">
                                <small class="text-muted">Wajib 4:5, minimal 1080×1350 px. Maksimal 5 gambar; urutan upload menjadi urutan carousel.</small>
                                <div id="announcementNewBannersPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_pinned" value="1" id="announcementPinned" class="form-check-input">
                                    <label class="form-check-label" for="announcementPinned">Sematkan di atas</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Simpan Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
const announcementStore = @json(route($announcementRoutes['store'], $announcementRouteParams));
const announcementUpdate = @json(route($announcementRoutes['update'], $announcementRouteParams + [$announcementRouteId => '__ID__']));

function renderAnnouncementBanners(images) {
    const box = document.getElementById('announcementExistingBanners');
    box.innerHTML = '';
    (images || []).forEach(function (image) {
        const label = document.createElement('label');
        label.className = 'border rounded p-1 text-center small';
        const img = document.createElement('img');
        img.src = image.url;
        img.alt = 'Banner';
        img.style = 'width:80px;height:100px;object-fit:cover;display:block;margin:0 auto 4px;';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'remove_images[]';
        checkbox.value = image.id;
        checkbox.className = 'form-check-input me-1';
        label.appendChild(img);
        label.appendChild(checkbox);
        label.appendChild(document.createTextNode('Hapus'));
        box.appendChild(label);
    });
}

function previewAnnouncementFiles(input) {
    const box = document.getElementById('announcementNewBannersPreview');
    box.innerHTML = '';
    Array.from(input.files || []).forEach(function (file) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        img.style = 'width:80px;height:100px;object-fit:cover;';
        img.className = 'rounded border';
        box.appendChild(img);
    });
}

function openAnnouncementCreate() {
    document.getElementById('announcementTitle').innerHTML = '<i class="ri-add-circle-line me-2 text-primary"></i>Buat Pengumuman';
    document.getElementById('announcementForm').action = announcementStore;
    document.getElementById('announcementMethod').value = 'POST';
    ['announcementTitleInput', 'announcementExcerpt', 'announcementContent', 'announcementExpires', 'announcementActivityStart', 'announcementActivityEnd'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('announcementCategory').value = 'umum';
    document.getElementById('announcementAudience').value = 'all';
    document.getElementById('announcementPinned').checked = false;
    document.getElementById('announcementBanners').value = '';
    document.getElementById('announcementNewBannersPreview').innerHTML = '';
    renderAnnouncementBanners([]);
}

@if(session('open_create'))
document.addEventListener('DOMContentLoaded', function () {
    openAnnouncementCreate();
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
});
@endif

@if(session('open_edit'))
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.querySelector('[data-announcement-id="{{ session('open_edit') }}"]');
    if (btn) btn.click();
});
@endif

function openAnnouncementEdit(btn) {
    const d = JSON.parse(btn.dataset.announcement);
    document.getElementById('announcementTitle').innerHTML = '<i class="ri-pencil-line me-2 text-primary"></i>Edit Pengumuman';
    document.getElementById('announcementForm').action = announcementUpdate.replace('__ID__', d.id);
    document.getElementById('announcementMethod').value = 'PUT';
    document.getElementById('announcementTitleInput').value = d.title;
    document.getElementById('announcementCategory').value = d.category;
    document.getElementById('announcementAudience').value = d.audience;
    document.getElementById('announcementExcerpt').value = d.excerpt || '';
    document.getElementById('announcementContent').value = d.content;
    document.getElementById('announcementExpires').value = d.expires_at || '';
    document.getElementById('announcementActivityStart').value = d.activity_start_date || '';
    document.getElementById('announcementActivityEnd').value = d.activity_end_date || '';
    document.getElementById('announcementPinned').checked = !!d.is_pinned;
    document.getElementById('announcementBanners').value = '';
    document.getElementById('announcementNewBannersPreview').innerHTML = '';
    renderAnnouncementBanners(d.images || []);
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}
</script>
@endsection