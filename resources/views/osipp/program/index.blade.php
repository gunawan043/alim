@extends('layouts.master')
@section('title', 'Program Kerja OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Program Kerja @endslot
        @endcomponent

        @include('osipp._header')

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-list-check-2 me-1 text-primary"></i>Program Kerja</h5>
                    <p class="text-muted small mb-0">Pantau target, penanggung jawab, dan progres program kepengurusan.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openProgramModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Program
                    </button>
                @endif
            </div>

            <div class="card-header border-top border-bottom bg-light py-2">
                <form method="GET" action="{{ route('osipp.program.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari program atau kategori...">
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
                        <a href="{{ route('osipp.program.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Penanggung Jawab</th>
                            <th>Pelaksanaan</th>
                            <th class="text-center">Kegiatan</th>
                            <th style="min-width: 160px;">Progres</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $program->name }}</div>
                                    <div class="text-muted small">{{ $program->category ?: 'Tanpa kategori' }}</div>
                                </td>
                                <td>{{ $program->responsible?->student?->name ?? '—' }}</td>
                                <td class="small text-muted">
                                    {{ $program->start_date?->translatedFormat('d M Y') ?? '—' }}
                                    @if($program->end_date) s.d. {{ $program->end_date->translatedFormat('d M Y') }} @endif
                                </td>
                                <td class="text-center">{{ $program->activities_count }}</td>
                                <td>
                                    @if($canManage)
                                        <form method="POST" action="{{ route('osipp.program.progress', ['userId' => $userId, 'program' => $program->id]) }}" class="d-flex align-items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                                            <input type="number" name="progress" class="form-control form-control-sm" value="{{ $program->progress }}" min="0" max="100" style="width: 66px;">
                                            <span class="text-muted small">%</span>
                                            <button class="btn btn-sm btn-outline-primary" type="submit" title="Simpan progres"><i class="ri-check-line"></i></button>
                                        </form>
                                        <div class="progress mt-1"><div class="progress-bar bg-{{ $program->status_badge }}" style="width: {{ $program->progress }}%"></div></div>
                                    @else
                                        <div class="progress"><div class="progress-bar bg-{{ $program->status_badge }}" style="width: {{ $program->progress }}%"></div></div>
                                        <div class="text-muted small mt-1">{{ $program->progress }}%</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $program->status_badge }}">{{ $program->status_label }}</span></td>
                                <td class="text-end">
                                    @if($canManage)
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openProgramModal('{{ $program->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.program.destroy', ['userId' => $userId, 'program' => $program->id]) }}" onsubmit="return confirm('Hapus program kerja ini?');">
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
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-list-check-2 fs-3 d-block mb-2"></i>Belum ada program kerja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($programs->hasPages())
                <div class="card-footer">{{ $programs->links() }}</div>
            @endif
        </div>

        @if($canManage)
            <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.program.store', ['userId' => $userId]) }}" id="programForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="programMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="programModalLabel">Tambah Program Kerja</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Program <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="programName" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="programStatus" class="form-select" required>
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kategori</label>
                                        <input type="text" name="category" id="programCategory" class="form-control" placeholder="Pendidikan, Sosial, Keagamaan...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Penanggung Jawab</label>
                                        <select name="responsible_member_id" id="programResponsible" class="form-select">
                                            <option value="">— Pilih anggota —</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}">{{ $member->student?->name ?? 'Santri' }} · {{ $member->position }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Mulai</label>
                                        <input type="date" name="start_date" id="programStart" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Selesai</label>
                                        <input type="date" name="end_date" id="programEnd" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Progres (%)</label>
                                        <input type="number" name="progress" id="programProgress" class="form-control" min="0" max="100" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Target Output</label>
                                        <input type="text" name="target_output" id="programTarget" class="form-control" placeholder="Output yang diharapkan">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="description" id="programDescription" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="notes" id="programNotes" class="form-control" rows="2"></textarea>
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
    const osippPrograms = @json($programsJson);
    const osippProgramStoreUrl = @json(route('osipp.program.store', ['userId' => $userId]));

    function openProgramModal(id) {
        const form = document.getElementById('programForm');
        if (!form) return;
        const data = id ? osippPrograms.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programModal'));

        form.action = data ? data.update_url : osippProgramStoreUrl;
        document.getElementById('programMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('programModalLabel').textContent = data ? 'Edit Program Kerja' : 'Tambah Program Kerja';
        document.getElementById('programName').value = data ? (data.name || '') : '';
        document.getElementById('programStatus').value = data ? (data.status || 'planned') : 'planned';
        document.getElementById('programCategory').value = data ? (data.category || '') : '';
        document.getElementById('programResponsible').value = data ? (data.responsible_member_id || '') : '';
        document.getElementById('programStart').value = data ? (data.start_date || '') : '';
        document.getElementById('programEnd').value = data ? (data.end_date || '') : '';
        document.getElementById('programProgress').value = data ? (data.progress || 0) : 0;
        document.getElementById('programTarget').value = data ? (data.target_output || '') : '';
        document.getElementById('programDescription').value = data ? (data.description || '') : '';
        document.getElementById('programNotes').value = data ? (data.notes || '') : '';
        modal.show();
    }
</script>
@endsection
