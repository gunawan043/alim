@extends('layouts.master')
@section('title', 'Kegiatan & Verifikasi OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Kegiatan & Verifikasi @endslot
        @endcomponent

        @include('osipp._header')

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Total Kegiatan</p><h4 class="mb-0">{{ number_format($summary['total']) }}</h4></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Menunggu</p><h4 class="mb-0 text-info">{{ number_format($summary['submitted']) }}</h4></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Terverifikasi</p><h4 class="mb-0 text-success">{{ number_format($summary['verified']) }}</h4></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Selesai</p><h4 class="mb-0">{{ number_format($summary['completed']) }}</h4></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-flag-line me-1 text-primary"></i>Kegiatan OSIPP</h5>
                    <p class="text-muted small mb-0">Catat kegiatan, ajukan verifikasi, lalu tindak lanjuti hasilnya.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openActivityModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Kegiatan
                    </button>
                @endif
            </div>

            <div class="card-header border-top border-bottom bg-light py-2">
                <form method="GET" action="{{ route('osipp.kegiatan.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari kegiatan, lokasi...">
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
                    <div class="col-md-3">
                        <select name="program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" @selected(request('program_id') === $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        <a href="{{ route('osipp.kegiatan.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Pelaksanaan</th>
                            <th>Penanggung Jawab</th>
                            <th>Status</th>
                            <th>Catatan Verifikasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $activity->title }}</div>
                                    <div class="text-muted small">{{ $activity->program?->name ?? 'Tanpa program' }}{{ $activity->location ? ' · '.$activity->location : '' }}</div>
                                </td>
                                <td class="small">
                                    <div>{{ $activity->activity_date?->translatedFormat('d M Y') }}</div>
                                    <div class="text-muted">
                                        {{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : '—' }}
                                        @if($activity->end_time) s.d. {{ substr((string) $activity->end_time, 0, 5) }} @endif
                                    </div>
                                </td>
                                <td>{{ $activity->responsible?->student?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status_label }}</span>
                                    @if($activity->verified_at)
                                        <div class="text-muted" style="font-size:10px;">{{ $activity->verified_at->translatedFormat('d M Y H:i') }}</div>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $activity->verification_notes ? \Illuminate\Support\Str::limit($activity->verification_notes, 70) : '—' }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        @if($canManage && $activity->isEditable())
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openActivityModal('{{ $activity->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.kegiatan.submit', ['userId' => $userId, 'activity' => $activity->id]) }}" onsubmit="return confirm('Ajukan kegiatan ini untuk verifikasi?');">
                                                @csrf
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-info" title="Ajukan verifikasi"><i class="ri-send-plane-line"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('osipp.kegiatan.destroy', ['userId' => $userId, 'activity' => $activity->id]) }}" onsubmit="return confirm('Hapus kegiatan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        @endif
                                        @if($canVerify && $activity->status === \App\Models\Osipp\OsippActivity::STATUS_SUBMITTED)
                                            <button class="btn btn-sm btn-success" type="button" title="Verifikasi" onclick="openVerificationModal('{{ route('osipp.kegiatan.verify', ['userId' => $userId, 'activity' => $activity->id]) }}', 'Verifikasi Kegiatan', false)"><i class="ri-check-line"></i></button>
                                            <button class="btn btn-sm btn-warning" type="button" title="Minta revisi" onclick="openVerificationModal('{{ route('osipp.kegiatan.revision', ['userId' => $userId, 'activity' => $activity->id]) }}', 'Minta Revisi Kegiatan', true)"><i class="ri-edit-2-line"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" title="Tolak" onclick="openVerificationModal('{{ route('osipp.kegiatan.reject', ['userId' => $userId, 'activity' => $activity->id]) }}', 'Tolak Kegiatan', true)"><i class="ri-close-line"></i></button>
                                        @endif
                                        @if($canVerify && $activity->status === \App\Models\Osipp\OsippActivity::STATUS_VERIFIED)
                                            <button class="btn btn-sm btn-success" type="button" title="Tandai selesai" onclick="openVerificationModal('{{ route('osipp.kegiatan.complete', ['userId' => $userId, 'activity' => $activity->id]) }}', 'Tandai Kegiatan Selesai', false)"><i class="ri-flag-2-line"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-flag-line fs-3 d-block mb-2"></i>Belum ada kegiatan OSIPP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($activities->hasPages())
                <div class="card-footer">{{ $activities->links() }}</div>
            @endif
        </div>

        @if($canManage)
            <div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.kegiatan.store', ['userId' => $userId]) }}" id="activityForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="activityMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="activityModalLabel">Tambah Kegiatan OSIPP</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="activityTitle" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Program Kerja</label>
                                        <select name="program_id" id="activityProgram" class="form-select">
                                            <option value="">— Tanpa program —</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" name="activity_date" id="activityDate" class="form-control" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Mulai</label>
                                        <input type="time" name="start_time" id="activityStart" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Selesai</label>
                                        <input type="time" name="end_time" id="activityEnd" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kategori</label>
                                        <input type="text" name="category" id="activityCategory" class="form-control" placeholder="Keagamaan, Sosial...">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="location" id="activityLocation" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Penanggung Jawab</label>
                                        <select name="responsible_member_id" id="activityResponsible" class="form-select">
                                            <option value="">— Pilih anggota —</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}">{{ $member->student?->name ?? 'Santri' }} · {{ $member->position }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Jumlah Peserta</label>
                                        <input type="number" name="participant_count" id="activityParticipants" class="form-control" min="0" value="0">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Tautan Dokumentasi</label>
                                        <input type="url" name="documentation_url" id="activityDocumentation" class="form-control" placeholder="https://...">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="description" id="activityDescription" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan sebagai Draft</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="" id="verificationForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="verificationModalLabel">Verifikasi Kegiatan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Catatan Verifikasi <span class="text-danger" id="verificationRequired" style="display:none;">*</span></label>
                            <textarea name="verification_notes" id="verificationNotes" class="form-control" rows="4" placeholder="Tuliskan catatan verifikasi, alasan revisi, atau penolakan."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="verificationSubmit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    const osippActivities = @json($activitiesJson);
    const osippActivityStoreUrl = @json(route('osipp.kegiatan.store', ['userId' => $userId]));

    function openActivityModal(id) {
        const form = document.getElementById('activityForm');
        if (!form) return;
        const data = id ? osippActivities.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('activityModal'));

        form.action = data ? data.update_url : osippActivityStoreUrl;
        document.getElementById('activityMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('activityModalLabel').textContent = data ? 'Edit Kegiatan OSIPP' : 'Tambah Kegiatan OSIPP';
        document.getElementById('activityTitle').value = data ? (data.title || '') : '';
        document.getElementById('activityProgram').value = data ? (data.program_id || '') : '';
        document.getElementById('activityDate').value = data ? (data.activity_date || '') : '';
        document.getElementById('activityStart').value = data ? (data.start_time || '') : '';
        document.getElementById('activityEnd').value = data ? (data.end_time || '') : '';
        document.getElementById('activityCategory').value = data ? (data.category || '') : '';
        document.getElementById('activityLocation').value = data ? (data.location || '') : '';
        document.getElementById('activityResponsible').value = data ? (data.responsible_member_id || '') : '';
        document.getElementById('activityParticipants').value = data ? (data.participant_count || 0) : 0;
        document.getElementById('activityDocumentation').value = data ? (data.documentation_url || '') : '';
        document.getElementById('activityDescription').value = data ? (data.description || '') : '';
        modal.show();
    }

    function openVerificationModal(url, title, notesRequired) {
        const form = document.getElementById('verificationForm');
        form.action = url;
        document.getElementById('verificationModalLabel').textContent = title;
        document.getElementById('verificationNotes').value = '';
        document.getElementById('verificationNotes').required = notesRequired;
        document.getElementById('verificationRequired').style.display = notesRequired ? 'inline' : 'none';
        document.getElementById('verificationSubmit').textContent = title.includes('Tolak') ? 'Tolak' : (title.includes('Revisi') ? 'Kembalikan' : 'Simpan');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('verificationModal')).show();
    }
</script>
@endsection
