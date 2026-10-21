@extends('layouts.master')
@section('title', 'Qism & Bidang OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Qism & Bidang @endslot
        @endcomponent

        @include('osipp._header')

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Qism Aktif</p><h4 class="mb-0">{{ $qisms->where('is_active', true)->count() }}</h4></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Anggota Qism</p><h4 class="mb-0">{{ $qisms->sum(fn ($qism) => $qism->members->count()) }}</h4></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Pengurus Inti</p><h4 class="mb-0">{{ $stats['core_members'] ?? 0 }}</h4></div></div>
            </div>
        </div>

        <div class="alert alert-light border d-flex align-items-start gap-2">
            <i class="ri-information-line fs-5 text-primary"></i>
            <div class="small text-muted">
                <strong>Struktur OSIPP:</strong> pengurus inti dapat memiliki lebih dari satu anggota.
                Setiap Qism dipimpin satu Ketua Qism dan dibantu anggota. Anggota Qism harus terdaftar sebagai anggota OSIPP pada periode ini.
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-node-tree me-1 text-primary"></i>Daftar Qism</h5>
                    <p class="text-muted small mb-0">Kelola bidang kerja, ketua Qism, dan anggota tiap bidang.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openQismModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Qism
                    </button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Qism / Bidang</th>
                            <th>Ketua Qism</th>
                            <th>Anggota</th>
                            <th class="text-center">Jumlah</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($qisms as $qism)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $qism->name }}</div>
                                    @if($qism->description)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($qism->description, 90) }}</div>
                                    @endif
                                    @if($qism->code)<span class="badge bg-secondary-subtle text-secondary mt-1">{{ $qism->code }}</span>@endif
                                </td>
                                <td>{{ $qism->leader?->student?->name ?? 'Belum ditentukan' }}</td>
                                <td class="small">
                                    {{ $qism->members->map(fn ($member) => $member->student?->name)->filter()->join(', ') ?: '—' }}
                                </td>
                                <td class="text-center">{{ $qism->members->count() }}</td>
                                <td><span class="badge bg-{{ $qism->is_active ? 'success' : 'secondary' }}">{{ $qism->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end">
                                    @if($canManage)
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openQismModal('{{ $qism->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.qism.destroy', ['userId' => $userId, 'qism' => $qism->id]) }}" onsubmit="return confirm('Hapus Qism ini? Anggota akan dilepas dari Qism.');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-node-tree fs-3 d-block mb-2"></i>Belum ada Qism. Tambahkan bidang kerja pertama.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($canManage)
            <div class="modal fade" id="qismModal" tabindex="-1" aria-labelledby="qismModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.qism.store', ['userId' => $userId]) }}" id="qismForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="qismMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="qismModalLabel">Tambah Qism OSIPP</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label">Nama Qism / Bidang <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="qismName" class="form-control" required placeholder="Qism Keagamaan, Qism Pendidikan...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="code" id="qismCode" class="form-control" placeholder="KEAGAMAAN">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Urutan</label>
                                        <input type="number" name="sort_order" id="qismSort" class="form-control" min="0" value="0">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">Ketua Qism</label>
                                        <select name="leader_member_id" id="qismLeader" class="form-select">
                                            <option value="">— Belum ditentukan —</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}">{{ $member->student?->name ?? 'Santri' }} · {{ $member->position }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Satu Qism memiliki satu ketua; anggota lain dapat dipilih di bawah.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="is_active" id="qismActive" class="form-select">
                                            <option value="1">Aktif</option>
                                            <option value="0">Nonaktif</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Anggota Qism</label>
                                        <div class="osipp-soft-row" style="max-height: 220px; overflow-y: auto;">
                                            @forelse($members as $member)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input qism-member-check" type="checkbox" name="member_ids[]" value="{{ $member->id }}" id="qismMember{{ $member->id }}">
                                                    <label class="form-check-label small" for="qismMember{{ $member->id }}">
                                                        {{ $member->student?->name ?? 'Santri' }}
                                                        <span class="text-muted">· {{ $member->position }}</span>
                                                    </label>
                                                </div>
                                            @empty
                                                <span class="text-muted small">Belum ada anggota yang dapat dimasukkan ke Qism. Tambahkan anggota OSIPP terlebih dahulu.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi / Tugas Bidang</label>
                                        <textarea name="description" id="qismDescription" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
<script>
    const osippQisms = @json($qismsJson);
    const osippQismStoreUrl = @json(route('osipp.qism.store', ['userId' => $userId]));

    function openQismModal(id) {
        const form = document.getElementById('qismForm');
        if (!form) return;
        const data = id ? osippQisms.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('qismModal'));

        form.action = data ? data.update_url : osippQismStoreUrl;
        document.getElementById('qismMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('qismModalLabel').textContent = data ? 'Edit Qism OSIPP' : 'Tambah Qism OSIPP';
        document.getElementById('qismName').value = data ? (data.name || '') : '';
        document.getElementById('qismCode').value = data ? (data.code || '') : '';
        document.getElementById('qismSort').value = data ? (data.sort_order || 0) : 0;
        document.getElementById('qismLeader').value = data ? (data.leader_member_id || '') : '';
        document.getElementById('qismActive').value = data ? (data.is_active ? '1' : '0') : '1';
        document.getElementById('qismDescription').value = data ? (data.description || '') : '';
        document.querySelectorAll('.qism-member-check').forEach(function (checkbox) {
            checkbox.checked = !!data && Array.isArray(data.member_ids) && data.member_ids.indexOf(checkbox.value) !== -1;
        });
        modal.show();
    }
</script>
@endsection
