@extends('layouts.master')
@section('title', 'Anggota & Jabatan OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Anggota & Jabatan @endslot
        @endcomponent

        @include('osipp._header')

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-group-line me-1 text-primary"></i>Kepengurusan {{ $period->name }}</h5>
                    <p class="text-muted small mb-0">{{ $members->total() }} anggota terdaftar pada periode ini.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openMemberModal(null)">
                        <i class="ri-user-add-line me-1"></i>Tambah Anggota
                    </button>
                @endif
            </div>

            <div class="card-header border-top border-bottom bg-light py-2">
                <form method="GET" action="{{ route('osipp.anggota.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama, NIS, atau NISN...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua status</option>
                            @foreach(\App\Models\Osipp\OsippMember::STATUS_OPTIONS as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        <a href="{{ route('osipp.anggota.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Santri</th>
                            <th>Jabatan</th>
                            <th>Divisi</th>
                            <th>Peran</th>
                            <th>Masa Tugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="osipp-avatar">{{ strtoupper(substr($member->student?->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $member->student?->name ?? 'Santri tidak ditemukan' }}</div>
                                            <div class="text-muted small">{{ $member->student?->nis ?: $member->student?->nisn ?: '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $member->position }}</span></td>
                                <td>{{ $member->division ?: '—' }}</td>
                                <td>{{ $member->role_label }}</td>
                                <td class="small text-muted">
                                    {{ $member->start_date?->translatedFormat('d M Y') ?? '—' }}
                                    @if($member->end_date) s.d. {{ $member->end_date->translatedFormat('d M Y') }} @endif
                                </td>
                                <td><span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">{{ $member->status_label }}</span></td>
                                <td class="text-end">
                                    @if($canManage)
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openMemberModal('{{ $member->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.anggota.destroy', ['userId' => $userId, 'member' => $member->id]) }}" onsubmit="return confirm('Hapus anggota ini dari kepengurusan?');">
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
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="ri-group-line fs-3 d-block mb-2"></i>
                                    Belum ada anggota kepengurusan yang didata.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="card-footer">{{ $members->links() }}</div>
            @endif
        </div>

        @if($canManage)
            <div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.anggota.store', ['userId' => $userId]) }}" id="memberForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="memberMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="memberModalLabel">Tambah Anggota OSIPP</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Santri <span class="text-danger">*</span></label>
                                        <select name="student_id" id="memberStudent" class="form-select" required></select>
                                        <div class="form-text">Cari berdasarkan nama, NIS, atau NISN.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="memberStatus" class="form-select" required>
                                            @foreach(\App\Models\Osipp\OsippMember::STATUS_OPTIONS as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                        <input type="text" name="position" id="memberPosition" class="form-control" required list="positionSuggestions" placeholder="Ketua, Sekretaris, Koordinator...">
                                        <datalist id="positionSuggestions">
                                            @foreach($positions as $position)
                                                <option value="{{ $position }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Kode Jabatan</label>
                                        <input type="text" name="position_code" id="memberPositionCode" class="form-control" placeholder="ketua">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Peran <span class="text-danger">*</span></label>
                                        <select name="member_role" id="memberRole" class="form-select" required>
                                            @foreach(\App\Models\Osipp\OsippMember::ROLE_OPTIONS as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Divisi / Bidang</label>
                                        <input type="text" name="division" id="memberDivision" class="form-control" placeholder="Keagamaan, Pendidikan, Humas...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Mulai Menjabat</label>
                                        <input type="date" name="start_date" id="memberStart" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Akhir Menjabat</label>
                                        <input type="date" name="end_date" id="memberEnd" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Urutan</label>
                                        <input type="number" name="sort_order" id="memberSort" class="form-control" min="0" value="0">
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="notes" id="memberNotes" class="form-control" placeholder="Catatan singkat jabatan">
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
    const osippMembers = @json($membersJson);
    const osippMemberStoreUrl = @json(route('osipp.anggota.store', ['userId' => $userId]));
    const osippStudentSearchUrl = @json(route('osipp.siswa.search', ['userId' => $userId]));

    jQuery(function () {
        const studentSelect = jQuery('#memberStudent');
        if (!studentSelect.length || !studentSelect.select2) return;

        studentSelect.select2({
            dropdownParent: jQuery('#memberModal'),
            width: '100%',
            placeholder: 'Cari santri...',
            minimumInputLength: 1,
            ajax: {
                url: osippStudentSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) { return { results: data.results || [] }; }
            }
        });
    });

    function openMemberModal(id) {
        const form = document.getElementById('memberForm');
        if (!form) return;
        const data = id ? osippMembers.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('memberModal'));

        form.action = data ? data.update_url : osippMemberStoreUrl;
        document.getElementById('memberMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('memberModalLabel').textContent = data ? 'Edit Anggota OSIPP' : 'Tambah Anggota OSIPP';
        document.getElementById('memberStatus').value = data ? (data.status || 'active') : 'active';
        document.getElementById('memberPosition').value = data ? (data.position || '') : '';
        document.getElementById('memberPositionCode').value = data ? (data.position_code || '') : '';
        document.getElementById('memberDivision').value = data ? (data.division || '') : '';
        document.getElementById('memberRole').value = data ? (data.member_role || 'pengurus') : 'pengurus';
        document.getElementById('memberStart').value = data ? (data.start_date || '') : '';
        document.getElementById('memberEnd').value = data ? (data.end_date || '') : '';
        document.getElementById('memberSort').value = data ? (data.sort_order || 0) : 0;
        document.getElementById('memberNotes').value = data ? (data.notes || '') : '';

        const select = jQuery('#memberStudent');
        select.val(null).trigger('change');
        if (data && data.student_id) {
            const option = new Option(data.student_name || 'Santri terpilih', data.student_id, true, true);
            select.append(option).trigger('change');
        }

        modal.show();
    }
</script>
@endsection
