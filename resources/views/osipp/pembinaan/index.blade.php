@extends('layouts.master')
@section('title', 'Pembinaan OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Pembinaan @endslot
        @endcomponent

        @include('osipp._header')

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-user-heart-line me-1 text-primary"></i>Catatan Pembinaan</h5>
                    <p class="text-muted small mb-0">Dokumentasikan pembinaan pengurus OSIPP, peserta, tindak lanjut, dan hasilnya.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openCoachingModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Pembinaan
                    </button>
                @endif
            </div>

            <div class="card-header border-top border-bottom bg-light py-2">
                <form method="GET" action="{{ route('osipp.pembinaan.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari topik, ringkasan, atau tindak lanjut...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua status</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        <a href="{{ route('osipp.pembinaan.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal / Topik</th>
                            <th>Jenis</th>
                            <th>Peserta</th>
                            <th>Pembina</th>
                            <th>Status</th>
                            <th>Tindak Lanjut</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coachings as $coaching)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $coaching->topic }}</div>
                                    <div class="text-muted small">{{ $coaching->coaching_date?->translatedFormat('d M Y') }}{{ $coaching->location ? ' · '.$coaching->location : '' }}</div>
                                </td>
                                <td><span class="badge bg-info-subtle text-info">{{ $coaching->type_label }}</span></td>
                                <td class="small">
                                    @if($coaching->coaching_type === \App\Models\Osipp\OsippCoaching::TYPE_INDIVIDUAL)
                                        {{ $coaching->member?->student?->name ?? '—' }}
                                    @else
                                        {{ $coaching->attendances->map(fn ($a) => $a->member?->student?->name)->filter()->join(', ') ?: '—' }}
                                    @endif
                                </td>
                                <td>{{ $coaching->mentor?->name ?? '—' }}</td>
                                <td><span class="badge bg-{{ $coaching->status_badge }}">{{ $coaching->status_label }}</span></td>
                                <td class="small text-muted">{{ $coaching->follow_up ? \Illuminate\Support\Str::limit($coaching->follow_up, 70) : '—' }}</td>
                                <td class="text-end">
                                    @if($canManage)
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openCoachingModal('{{ $coaching->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.pembinaan.destroy', ['userId' => $userId, 'coaching' => $coaching->id]) }}" onsubmit="return confirm('Hapus catatan pembinaan ini?');">
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
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-user-heart-line fs-3 d-block mb-2"></i>Belum ada catatan pembinaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($coachings->hasPages())
                <div class="card-footer">{{ $coachings->links() }}</div>
            @endif
        </div>

        @if($canManage)
            <div class="modal fade" id="coachingModal" tabindex="-1" aria-labelledby="coachingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.pembinaan.store', ['userId' => $userId]) }}" id="coachingForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="coachingMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="coachingModalLabel">Tambah Pembinaan OSIPP</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                        <select name="coaching_type" id="coachingType" class="form-select" required>
                                            @foreach($typeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" name="coaching_date" id="coachingDate" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="coachingStatus" class="form-select" required>
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Topik <span class="text-danger">*</span></label>
                                        <input type="text" name="topic" id="coachingTopic" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="location" id="coachingLocation" class="form-control">
                                    </div>
                                    <div class="col-md-6" id="coachingIndividualWrap">
                                        <label class="form-label">Anggota (individu)</label>
                                        <select name="member_id" id="coachingMember" class="form-select">
                                            <option value="">— Pilih anggota —</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}">{{ $member->student?->name ?? 'Santri' }} · {{ $member->position }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Peserta Pembinaan</label>
                                        <div class="osipp-soft-row" style="max-height: 190px; overflow-y: auto;">
                                            @forelse($members as $member)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input coaching-member-check" type="checkbox" name="member_ids[]" value="{{ $member->id }}" id="coachingMember{{ $member->id }}">
                                                    <label class="form-check-label small" for="coachingMember{{ $member->id }}">{{ $member->student?->name ?? 'Santri' }} <span class="text-muted">· {{ $member->position }}</span></label>
                                                </div>
                                            @empty
                                                <span class="text-muted small">Belum ada anggota pada periode ini.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Ringkasan Pembinaan</label>
                                        <textarea name="summary" id="coachingSummary" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Tindak Lanjut</label>
                                        <textarea name="follow_up" id="coachingFollowUp" class="form-control" rows="2"></textarea>
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
    const osippCoachings = @json($coachingsJson);
    const osippCoachingStoreUrl = @json(route('osipp.pembinaan.store', ['userId' => $userId]));

    function openCoachingModal(id) {
        const form = document.getElementById('coachingForm');
        if (!form) return;
        const data = id ? osippCoachings.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('coachingModal'));

        form.action = data ? data.update_url : osippCoachingStoreUrl;
        document.getElementById('coachingMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('coachingModalLabel').textContent = data ? 'Edit Pembinaan OSIPP' : 'Tambah Pembinaan OSIPP';
        document.getElementById('coachingType').value = data ? (data.coaching_type || 'kelompok') : 'kelompok';
        document.getElementById('coachingDate').value = data ? (data.coaching_date || '') : '';
        document.getElementById('coachingStatus').value = data ? (data.status || 'completed') : 'completed';
        document.getElementById('coachingTopic').value = data ? (data.topic || '') : '';
        document.getElementById('coachingLocation').value = data ? (data.location || '') : '';
        document.getElementById('coachingMember').value = data ? (data.member_id || '') : '';
        document.getElementById('coachingSummary').value = data ? (data.summary || '') : '';
        document.getElementById('coachingFollowUp').value = data ? (data.follow_up || '') : '';
        document.querySelectorAll('.coaching-member-check').forEach(function (checkbox) {
            checkbox.checked = !!data && Array.isArray(data.member_ids) && data.member_ids.indexOf(checkbox.value) !== -1;
        });
        toggleCoachingType();
        modal.show();
    }

    function toggleCoachingType() {
        const type = document.getElementById('coachingType');
        const wrap = document.getElementById('coachingIndividualWrap');
        if (type && wrap) wrap.style.display = type.value === 'individu' ? 'block' : 'none';
    }

    document.getElementById('coachingType')?.addEventListener('change', toggleCoachingType);
</script>
@endsection
