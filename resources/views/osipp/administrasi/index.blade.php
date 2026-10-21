@extends('layouts.master')
@section('title', 'Administrasi OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Administrasi @endslot
        @endcomponent

        @include('osipp._header')

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Total Dokumen</p><h4 class="mb-0">{{ number_format($summary['total']) }}</h4></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Menunggu</p><h4 class="mb-0 text-info">{{ number_format($summary['submitted']) }}</h4></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Terverifikasi</p><h4 class="mb-0 text-success">{{ number_format($summary['verified']) }}</h4></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3"><p class="text-muted text-uppercase stat-label mb-1">Perlu Revisi</p><h4 class="mb-0 text-warning">{{ number_format($summary['revision']) }}</h4></div></div></div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-folder-shield-2-line me-1 text-primary"></i>Administrasi & Dokumentasi</h5>
                    <p class="text-muted small mb-0">Awasi kelengkapan proposal, surat, notulen, daftar hadir, dan LPJ. Tidak ada transaksi keuangan di sini.</p>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openDocumentModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Dokumen
                    </button>
                @endif
            </div>

            <div class="card-header border-top border-bottom bg-light py-2">
                <form method="GET" action="{{ route('osipp.administrasi.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari judul dokumen...">
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
                        <select name="document_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua jenis</option>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('document_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        <a href="{{ route('osipp.administrasi.index', ['userId' => $userId, 'period_id' => $period->id]) }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>Jenis</th>
                            <th>Referensi</th>
                            <th>Status</th>
                            <th>Catatan Verifikasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $document->title }}</div>
                                    <div class="text-muted small">{{ $document->created_at?->translatedFormat('d M Y') }}</div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $document->type_label }}</span></td>
                                <td class="small text-muted">
                                    {{ $document->program?->name ?? '—' }}
                                    @if($document->activity) · {{ $document->activity->title }} @endif
                                </td>
                                <td><span class="badge bg-{{ $document->status_badge }}">{{ $document->status_label }}</span></td>
                                <td class="small text-muted">{{ $document->verification_notes ? \Illuminate\Support\Str::limit($document->verification_notes, 60) : '—' }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        @if($document->file_path || $document->external_url)
                                            <a href="{{ route('osipp.administrasi.download', ['userId' => $userId, 'document' => $document->id]) }}" class="btn btn-sm btn-outline-secondary" title="Unduh / buka"><i class="ri-download-2-line"></i></a>
                                        @endif
                                        @if($canManage && $document->isEditable())
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openDocumentModal('{{ $document->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            <form method="POST" action="{{ route('osipp.administrasi.submit', ['userId' => $userId, 'document' => $document->id]) }}" onsubmit="return confirm('Ajukan dokumen ini untuk verifikasi?');">
                                                @csrf
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-info" title="Ajukan verifikasi"><i class="ri-send-plane-line"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('osipp.administrasi.destroy', ['userId' => $userId, 'document' => $document->id]) }}" onsubmit="return confirm('Hapus dokumen ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        @endif
                                        @if($canVerify && $document->status === \App\Models\Osipp\OsippDocument::STATUS_SUBMITTED)
                                            <button class="btn btn-sm btn-success" type="button" title="Verifikasi" onclick="openDocumentVerificationModal('{{ route('osipp.administrasi.verify', ['userId' => $userId, 'document' => $document->id]) }}', 'Verifikasi Dokumen', false)"><i class="ri-check-line"></i></button>
                                            <button class="btn btn-sm btn-warning" type="button" title="Minta revisi" onclick="openDocumentVerificationModal('{{ route('osipp.administrasi.revision', ['userId' => $userId, 'document' => $document->id]) }}', 'Minta Revisi Dokumen', true)"><i class="ri-edit-2-line"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" title="Tolak" onclick="openDocumentVerificationModal('{{ route('osipp.administrasi.reject', ['userId' => $userId, 'document' => $document->id]) }}', 'Tolak Dokumen', true)"><i class="ri-close-line"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-folder-shield-2-line fs-3 d-block mb-2"></i>Belum ada dokumen administrasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())
                <div class="card-footer">{{ $documents->links() }}</div>
            @endif
        </div>

        @if($canManage)
            <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.administrasi.store', ['userId' => $userId]) }}" id="documentForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="documentMethod">
                            <input type="hidden" name="period_id" value="{{ $period->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="documentModalLabel">Tambah Dokumen Administrasi</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="documentTitle" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                        <select name="document_type" id="documentType" class="form-select" required>
                                            @foreach($typeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Program Kerja</label>
                                        <select name="program_id" id="documentProgram" class="form-select">
                                            <option value="">— Tidak terkait program —</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kegiatan</label>
                                        <select name="activity_id" id="documentActivity" class="form-select">
                                            <option value="">— Tidak terkait kegiatan —</option>
                                            @foreach($activities as $activity)
                                                <option value="{{ $activity->id }}">{{ $activity->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unggah Berkas</label>
                                        <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <div class="form-text">PDF, DOC/DOCX, JPG/PNG. Maksimal 10 MB.</div>
                                        <div class="text-success small mt-1" id="documentExistingFile" style="display:none;"><i class="ri-attachment-2 me-1"></i>Berkas sudah tersimpan.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Atau Tautan Dokumen</label>
                                        <input type="url" name="external_url" id="documentExternalUrl" class="form-control" placeholder="https://drive.google.com/...">
                                        <div class="form-text">Gunakan tautan bila dokumen disimpan di luar ALIM.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="notes" id="documentNotes" class="form-control" rows="3"></textarea>
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

        <div class="modal fade" id="documentVerificationModal" tabindex="-1" aria-labelledby="documentVerificationLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="" id="documentVerificationForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="documentVerificationLabel">Verifikasi Dokumen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Catatan Verifikasi <span class="text-danger" id="documentVerificationRequired" style="display:none;">*</span></label>
                            <textarea name="verification_notes" id="documentVerificationNotes" class="form-control" rows="4" placeholder="Tuliskan catatan verifikasi, alasan revisi, atau penolakan."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="documentVerificationSubmit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    const osippDocuments = @json($documentsJson);
    const osippDocumentStoreUrl = @json(route('osipp.administrasi.store', ['userId' => $userId]));

    function openDocumentModal(id) {
        const form = document.getElementById('documentForm');
        if (!form) return;
        const data = id ? osippDocuments.find(function (item) { return item.id === id; }) : null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('documentModal'));

        form.action = data ? data.update_url : osippDocumentStoreUrl;
        document.getElementById('documentMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('documentModalLabel').textContent = data ? 'Edit Dokumen Administrasi' : 'Tambah Dokumen Administrasi';
        document.getElementById('documentTitle').value = data ? (data.title || '') : '';
        document.getElementById('documentType').value = data ? (data.document_type || 'other') : 'other';
        document.getElementById('documentProgram').value = data ? (data.program_id || '') : '';
        document.getElementById('documentActivity').value = data ? (data.activity_id || '') : '';
        document.getElementById('documentExternalUrl').value = data ? (data.external_url || '') : '';
        document.getElementById('documentNotes').value = data ? (data.notes || '') : '';
        document.getElementById('documentExistingFile').style.display = data && data.has_file ? 'block' : 'none';
        form.querySelector('input[type="file"]').value = '';
        modal.show();
    }

    function openDocumentVerificationModal(url, title, notesRequired) {
        const form = document.getElementById('documentVerificationForm');
        form.action = url;
        document.getElementById('documentVerificationLabel').textContent = title;
        document.getElementById('documentVerificationNotes').value = '';
        document.getElementById('documentVerificationNotes').required = notesRequired;
        document.getElementById('documentVerificationRequired').style.display = notesRequired ? 'inline' : 'none';
        document.getElementById('documentVerificationSubmit').textContent = title.includes('Tolak') ? 'Tolak' : (title.includes('Revisi') ? 'Kembalikan' : 'Simpan');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('documentVerificationModal')).show();
    }
</script>
@endsection
