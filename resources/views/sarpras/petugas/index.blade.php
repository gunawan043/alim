@extends('layouts.master')
@section('title') Petugas Sarpras Satuan Kerja @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Petugas Sarpras Satuan Kerja
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Petugas Aktif', $stats['active'] ?? 0, 'success', 'ri-user-star-line'],
            ['Petugas Utama', $stats['primary'] ?? 0, 'primary', 'ri-shield-user-line'],
            ['Satuan Kerja Terlayani', $stats['units'] ?? 0, 'info', 'ri-building-2-line'],
            ['Total Penugasan', $stats['total'] ?? 0, 'secondary', 'ri-team-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.petugas.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama GTK / satuan kerja…"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="is_active" class="form-select"><option value="">Semua</option><option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option><option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option></select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="{{ route('sarpras.petugas.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line me-1"></i>Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-user-star-line me-2 text-primary"></i>Petugas Sarpras per Satuan Kerja</h5>
                <p class="text-muted small mb-0">Satu GTK mendata seluruh aset unit; wali kelas/wali asrama mendata ruangnya masing-masing.</p>
            </div>
            @if($canManage)
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addOfficerModal"><i class="ri-add-line me-1"></i>Tugaskan Petugas</button>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No</th><th>GTK</th><th>Satuan Kerja</th><th>Peran</th><th>Status</th><th>Ditugaskan</th><th>Catatan</th>@if($canManage)<th class="text-end">Aksi</th>@endif</tr>
                    </thead>
                    <tbody>
                    @forelse($officers as $i => $officer)
                        <tr>
                            <td>{{ $officers->firstItem() + $i }}</td>
                            <td><div class="fw-medium">{{ $officer->user?->name ?? '-' }}</div><small class="text-muted">{{ $officer->user?->email }}</small></td>
                            <td>{{ $officer->workUnit?->name ?? '-' }}</td>
                            <td>@if($officer->is_primary)<span class="badge bg-primary-subtle text-primary"><i class="ri-shield-star-line me-1"></i>Utama</span>@else<span class="badge bg-secondary-subtle text-secondary">Petugas</span>@endif</td>
                            <td>@if($officer->is_active)<span class="badge bg-success-subtle text-success">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>@endif</td>
                            <td><div class="small">{{ $officer->assigned_at?->format('d/m/Y') ?? '-' }}</div><small class="text-muted">{{ $officer->assigner?->name }}</small></td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($officer->notes, 60) ?: '-' }}</td>
                            @if($canManage)
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        @if($officer->is_active && ! $officer->is_primary)
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#primaryOfficerModal" data-action="{{ route('sarpras.petugas.primary', $officer->id) }}" data-name="{{ $officer->user?->name }}" title="Jadikan utama"><i class="ri-star-line"></i></button>
                                        @endif
                                        <button type="button" class="btn btn-sm {{ $officer->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" data-bs-toggle="modal" data-bs-target="#toggleOfficerModal" data-action="{{ route('sarpras.petugas.toggle', $officer->id) }}" data-name="{{ $officer->user?->name }}" data-active="{{ $officer->is_active ? '1' : '0' }}" title="{{ $officer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="{{ $officer->is_active ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}"></i></button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <x-empty-state :colspan="$canManage ? 8 : 7" title="Belum ada petugas Sarpras" message="Tugaskan satu GTK di setiap satuan kerja untuk mendata aset.">
                            @if($canManage)<button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#addOfficerModal"><i class="ri-add-line me-1"></i>Tugaskan Petugas</button>@endif
                        </x-empty-state>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($officers->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $officers])</div>@endif
    </div>

    @if($canManage)
        <div class="modal fade" id="addOfficerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('sarpras.petugas.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-user-add-line me-1"></i>Tugaskan Petugas Sarpras</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Satuan Kerja <span class="text-danger">*</span></label><select name="work_unit_id" class="form-select" required><option value="">— Pilih —</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ old('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">GTK <span class="text-danger">*</span></label><select name="user_id" class="form-select" required><option value="">— Pilih GTK —</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('user_id') === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select><small class="text-muted">Petugas ini mendapat akses pendataan seluruh aset pada unit tersebut.</small></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimaryOfficer" {{ old('is_primary') ? 'checked' : '' }}><label class="form-check-label" for="isPrimaryOfficer">Jadikan petugas utama satuan kerja</label></div>
                        <div><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2" placeholder="Tugas khusus / keterangan…">{{ old('notes') }}</textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Penugasan</button></div>
                </form>
            </div>
        </div>

        <x-modal-confirm id="toggleOfficerModal" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Ubah Status Petugas?" submitLabel="Ya, Lanjutkan" submitIcon="ri-check-line" formId="toggleOfficerForm">
            <p class="text-center text-muted mb-0" id="toggleOfficerMessage">Status petugas akan diubah.</p>
        </x-modal-confirm>
        <form id="toggleOfficerForm" method="POST" class="d-none">@csrf</form>

        <x-modal-confirm id="primaryOfficerModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Jadikan Petugas Utama?" submitLabel="Ya, Jadikan Utama" submitIcon="ri-star-line" formId="primaryOfficerForm">
            <p class="text-center text-muted mb-0" id="primaryOfficerMessage">Petugas utama menjadi penanggung jawab pendataan unit.</p>
        </x-modal-confirm>
        <form id="primaryOfficerForm" method="POST" class="d-none">@csrf</form>
    @endif
@endsection

@push('script')
<script>
    document.getElementById('toggleOfficerModal')?.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; if (!button) return;
        document.getElementById('toggleOfficerForm').action = button.dataset.action;
        var active = button.dataset.active === '1';
        document.getElementById('toggleOfficerMessage').textContent = (active ? 'Nonaktifkan ' : 'Aktifkan kembali ') + button.dataset.name + '?';
    });
    document.getElementById('primaryOfficerModal')?.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; if (!button) return;
        document.getElementById('primaryOfficerForm').action = button.dataset.action;
        document.getElementById('primaryOfficerMessage').textContent = 'Jadikan ' + button.dataset.name + ' sebagai petugas utama?';
    });
</script>
@endpush
