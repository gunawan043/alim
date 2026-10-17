@extends('waka.master')

@section('title') Template Surat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
        @slot('title') Template Surat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(! $schoolId)
        <div class="alert alert-warning">
            <i class="ri-error-warning-line me-1"></i>Satuan pendidikan belum terdeteksi. Template tidak dapat disimpan.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-add-line me-2 text-primary"></i>Tambah Template</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('waka.surat-template.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Template <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required placeholder="Contoh: Undangan Rapat">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Perihal Default</label>
                            <input type="text" name="perihal_template" class="form-control" value="{{ old('perihal_template') }}" placeholder="Perihal surat">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Surat <span class="text-danger">*</span></label>
                            <textarea name="isi_template" class="form-control" rows="7" required placeholder="Assalamu'alaikum…&#10;&#10;Dengan hormat, …">{{ old('isi_template') }}</textarea>
                            <div class="form-text">Gunakan teks biasa. Nomor, tanggal, dan tujuan tetap diisi pada form surat.</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="tplActive" checked>
                            <label class="form-check-label" for="tplActive">Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" @disabled(! $schoolId)>
                            <i class="ri-save-line me-1"></i>Simpan Template
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-file-text-line me-2 text-primary"></i>Daftar Template</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">No</th>
                                    <th>Nama</th>
                                    <th>Perihal</th>
                                    <th>Status</th>
                                    <th class="text-end" style="width:160px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $i => $template)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $template->nama }}</div>
                                            @if(! $template->school_id)
                                                <span class="badge bg-secondary-subtle text-secondary">Global (baca-saja)</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $template->perihal_template ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}-subtle text-{{ $template->is_active ? 'success' : 'secondary' }}">
                                                {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($template->school_id)
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#editTpl{{ $template->id }}">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteTemplateModal"
                                                        data-action="{{ route('waka.surat-template.destroy', $template->id) }}"
                                                        data-name="{{ $template->nama }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($template->school_id)
                                        <tr class="collapse" id="editTpl{{ $template->id }}">
                                            <td colspan="5" class="bg-light">
                                                <form action="{{ route('waka.surat-template.update', $template->id) }}" method="POST" class="row g-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Nama Template</label>
                                                        <input type="text" name="nama" class="form-control form-control-sm" value="{{ $template->nama }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Perihal</label>
                                                        <input type="text" name="perihal_template" class="form-control form-control-sm" value="{{ $template->perihal_template }}">
                                                    </div>
                                                    <div class="col-md-4 d-flex align-items-end gap-2">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $template->id }}" {{ $template->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label small" for="active{{ $template->id }}">Aktif</label>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary ms-auto"><i class="ri-save-line me-1"></i>Simpan</button>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small">Isi Surat</label>
                                                        <textarea name="isi_template" class="form-control form-control-sm" rows="5" required>{{ $template->isi_template }}</textarea>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <x-empty-state colspan="5" title="Belum ada template"
                                        message="Tambahkan template surat agar pembuatan surat keluar lebih cepat." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteTemplateForm" method="POST" class="d-none" action="">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteTemplateModal" class="zoomIn"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primaryColor="#f7b84b" secondaryColor="#f06548"
        title="Yakin ingin menghapus?"
        submitLabel="Ya, Hapus!"
        submitIcon="ri-delete-bin-line"
        submitClass="btn-danger"
        formId="deleteTemplateForm">
        <p class="text-center text-muted mb-0" id="deleteTemplateMessage">Template akan dihapus permanen.</p>
    </x-modal-confirm>
@endsection

@section('script')
<script>
    document.getElementById('deleteTemplateModal')?.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        document.getElementById('deleteTemplateForm').action = button.getAttribute('data-action') || '';
        document.getElementById('deleteTemplateMessage').textContent = 'Template "' + (button.getAttribute('data-name') || '') + '" akan dihapus.';
    });
</script>
@endsection
