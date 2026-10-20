@extends('layouts.master')
@section('title', 'Bank Tema & Aktivitas Projek')
@section('css')
    @include('kurikulum._styles')
@endsection
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $themeCount = $items->where('kind', 'theme')->count();
        $activityCount = $items->where('kind', 'activity')->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.proyek.index', ['userId' => $userId]) }}">Projek {{ $labels['kokurikuler_label'] ?? 'P5 / P5RA' }}</a> @endslot
        @slot('title') Bank Tema &amp; Aktivitas @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md">
                    <h5 class="card-title mb-2"><i class="ri-archive-line me-1 text-primary"></i>Bank Tema &amp; Aktivitas Projek</h5>
                    <p class="text-muted mb-0 small">Simpan tema dan aktivitas yang sering dipakai agar input projek manual lebih cepat dan konsisten. Bank ini tidak mengubah projek yang sudah ada.</p>
                </div>
                <div class="col-md-auto d-flex flex-wrap gap-2">
                    @if($profile)<span class="badge bg-success-subtle text-success p-2">{{ $profile->name }}</span>@endif
                    @if($canManage)<button class="btn btn-primary btn-sm" onclick="openBankCreate()"><i class="ri-add-line me-1"></i>Tambah Item</button>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat g-2 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-lightbulb-line text-primary"></i></span></div><div><div class="text-muted small text-uppercase">Tema</div><div class="fs-4 fw-bold">{{ $themeCount }}</div></div></div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-list-check-2 text-info"></i></span></div><div><div class="text-muted small text-uppercase">Aktivitas</div><div class="fs-4 fw-bold">{{ $activityCount }}</div></div></div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-database-2-line text-warning"></i></span></div><div><div class="text-muted small text-uppercase">Total Item</div><div class="fs-4 fw-bold">{{ $items->count() }}</div></div></div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-2 align-items-end">
                <div class="col-md-6"><h5 class="card-title mb-0">Daftar Bank</h5><p class="text-muted mb-0 small">{{ $academicYear?->name }} · {{ $profile?->name ?? 'Profil default' }}</p></div>
                <div class="col-md-6">
                    <form method="GET" action="{{ route('user.kurikulum.proyek.bank.index', ['userId' => $userId]) }}" class="d-flex flex-wrap justify-content-md-end gap-2">
                        <select name="academic_year_id" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }}</option>@endforeach
                        </select>
                        <select name="type" class="form-select form-select-sm" style="width:105px" onchange="this.form.submit()">
                            <option value="">Semua jenis</option>
                            @foreach($projectTypes as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ strtoupper($value) }}</option>@endforeach
                        </select>
                        <select name="kind" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
                            <option value="">Semua kategori</option>
                            <option value="theme" @selected(request('kind') === 'theme')>Tema</option>
                            <option value="activity" @selected(request('kind') === 'activity')>Aktivitas</option>
                        </select>
                        <a href="{{ route('user.kurikulum.proyek.bank.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Item</th><th>Kategori</th><th>Jenis</th><th>Dimensi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $editPayload = [
                                'id' => $item->id,
                                'academic_year_id' => $academicYearId,
                                'type' => $item->type,
                                'kind' => $item->kind,
                                'title' => $item->title,
                                'description' => $item->description,
                                'dimensions' => $item->dimensions ?? [],
                                'is_active' => (bool) $item->is_active,
                            ];
                        @endphp
                        <tr>
                            <td><div class="fw-semibold">{{ $item->title }}</div><div class="text-muted small">{{ $item->description ?: '—' }}</div></td>
                            <td><span class="badge {{ $item->kind === 'theme' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ $item->kind === 'theme' ? 'Tema' : 'Aktivitas' }}</span></td>
                            <td><span class="badge bg-light text-dark">{{ strtoupper($item->type) }}</span></td>
                            <td class="small">{{ collect($item->dimensions ?? [])->map(fn ($key) => $values[$key] ?? $key)->implode(', ') ?: '—' }}</td>
                            <td><span class="badge {{ $item->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end">
                                @if($canManage && $item->school_id)
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-soft-warning" data-item="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openBankEdit(this)" title="Edit"><i class="ri-pencil-line"></i></button>
                                        <form method="POST" action="{{ route('user.kurikulum.proyek.bank.destroy', ['userId' => $userId, 'id' => $item->id]) }}" onsubmit="return confirm('Hapus item bank ini?');">
                                            @csrf @method('DELETE')<button class="btn btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted">Global</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-archive-line fs-1 d-block mb-2"></i>Bank masih kosong. Tambahkan tema atau aktivitas pertama.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" id="bankForm" action="{{ route('user.kurikulum.proyek.bank.store', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="_method" id="bankMethod" value="POST">
                        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="bankModalTitle"><i class="ri-archive-line me-1"></i>Tambah Item Bank</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Jenis</label><select name="type" id="bankType" class="form-select" required>@foreach($projectTypes as $value => $label)<option value="{{ $value }}">{{ strtoupper($value) }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label">Kategori</label><select name="kind" id="bankKind" class="form-select" required><option value="theme">Tema</option><option value="activity">Aktivitas</option></select></div>
                                <div class="col-md-4"><label class="form-label">Status</label><select name="is_active" id="bankActive" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                                <div class="col-12"><label class="form-label">Judul <span class="text-danger">*</span></label><input name="title" id="bankTitle" class="form-control" maxlength="191" required placeholder="Mis. Gaya Hidup Berkelanjutan"></div>
                                <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" id="bankDescription" rows="3" class="form-control" maxlength="3000"></textarea></div>
                                <div class="col-12">
                                    <label class="form-label">{{ $labels['values_label'] ?? 'Dimensi / Nilai' }}</label>
                                    <div class="border rounded p-2">
                                        @forelse($values as $key => $label)
                                            <div class="form-check"><input class="form-check-input bank-dimension" type="checkbox" name="dimensions[]" value="{{ $key }}" id="bank-dim-{{ $key }}"><label class="form-check-label" for="bank-dim-{{ $key }}">{{ $label }}</label></div>
                                        @empty
                                            <div class="text-muted small">Profil belum memiliki dimensi/nilai.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
    <script>
        var bankStoreUrl = @json(route('user.kurikulum.proyek.bank.store', ['userId' => $userId]));
        var bankUpdateUrl = @json(route('user.kurikulum.proyek.bank.update', ['userId' => $userId, 'id' => '__ID__']));

        function openBankCreate() {
            document.getElementById('bankModalTitle').innerHTML = '<i class="ri-archive-line me-1"></i>Tambah Item Bank';
            document.getElementById('bankForm').action = bankStoreUrl;
            document.getElementById('bankMethod').value = 'POST';
            document.getElementById('bankTitle').value = '';
            document.getElementById('bankDescription').value = '';
            document.getElementById('bankActive').value = '1';
            document.querySelectorAll('.bank-dimension').forEach(function (el) { el.checked = false; });
            new bootstrap.Modal(document.getElementById('bankModal')).show();
        }

        function openBankEdit(button) {
            var item = JSON.parse(button.dataset.item);
            document.getElementById('bankModalTitle').innerHTML = '<i class="ri-pencil-line me-1"></i>Edit Item Bank';
            document.getElementById('bankForm').action = bankUpdateUrl.replace('__ID__', item.id);
            document.getElementById('bankMethod').value = 'PUT';
            document.getElementById('bankType').value = item.type || 'p5ra';
            document.getElementById('bankKind').value = item.kind || 'theme';
            document.getElementById('bankActive').value = item.is_active ? '1' : '0';
            document.getElementById('bankTitle').value = item.title || '';
            document.getElementById('bankDescription').value = item.description || '';
            document.querySelectorAll('.bank-dimension').forEach(function (el) { el.checked = (item.dimensions || []).indexOf(el.value) !== -1; });
            new bootstrap.Modal(document.getElementById('bankModal')).show();
        }
    </script>
@endsection
