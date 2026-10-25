@extends('layouts.master')
@section('title', 'AI Assistant — '.($curriculumLabels['perangkat_short'] ?? 'RPM').' & Soal')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') AI Assistant @endslot
        @slot('title') AI Assistant — RPM &amp; Soal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @unless($aiConfigured)
        <div class="alert alert-warning" role="alert">
            <i class="ri-robot-2-line me-1"></i>
            <strong>Provider AI belum dikonfigurasi.</strong>
            @if(canPermission('super-admin-only'))
                Buka <a href="{{ route('user.sa.ai-providers.index', ['userId' => $userId]) }}" class="alert-link">Pengaturan AI</a>
                untuk menambahkan API key (berbayar, free tier, atau lokal seperti Ollama).
            @else
                Hubungi Super Admin untuk mengisi API key di Pengaturan AI.
            @endif
        </div>
    @endunless

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-magic-line text-primary me-1"></i>Buat Draft dengan AI</h5>
                    <p class="text-muted mb-0 small">Profil: <strong>{{ $curriculumProfile?->name ?? 'Default' }}</strong> · Hasil berupa draft — wajib ditinjau sebelum dipakai pada {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}/Bank Soal resmi.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.kurikulum.ai.generate', ['userId' => $userId]) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Jenis Draft <span class="text-danger">*</span></label>
                                <select name="type" id="aiType" class="form-select" onchange="toggleAiFields()">
                                    <option value="soal" {{ old('type', request('type', 'soal')) === 'soal' ? 'selected' : '' }}>Soal</option>
                                    <option value="rpm" {{ old('type', request('type')) === 'rpm' ? 'selected' : '' }}>{{ $curriculumLabels['perangkat_label'] ?? 'RPM / Modul Ajar' }}</option>
                                    <option value="projek" {{ old('type', request('type')) === 'projek' ? 'selected' : '' }}>Projek {{ $curriculumLabels['kokurikuler_label'] ?? 'P5 / P5RA' }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">— Pilih Mapel —</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id', request('subject_id')) === $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}{{ $subject->name_ar ? ' — '.$subject->name_ar : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">— Semua / Sesuai Mapel —</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}" {{ old('grade_level_id', request('grade_level_id')) === $grade->id ? 'selected' : '' }}>
                                            {{ $grade->name }}{{ $grade->fase ? ' (Fase '.$grade->fase.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) old('academic_year_id', request('academic_year_id', $activeAy?->id)) === (string) $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ old('semester', request('semester', $activeAy?->semester)) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester', request('semester', $activeAy?->semester)) === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Materi / TP / CP (konteks)</label>
                                <textarea name="topik" class="form-control" rows="3" maxlength="2000"
                                          placeholder="Tempel TP/CP atau uraikan materi yang ingin dibuat...">{{ old('topik', request('topik')) }}</textarea>
                            </div>

                            <div class="col-md-4 ai-soal-fields">
                                <label class="form-label">Jumlah Soal</label>
                                <input type="number" name="jumlah_soal" class="form-control" min="1" max="50" value="{{ old('jumlah_soal', 5) }}">
                            </div>
                            <div class="col-md-4 ai-soal-fields">
                                <label class="form-label">Bentuk Soal</label>
                                <select name="bentuk_soal" class="form-select">
                                    <option value="campuran" {{ old('bentuk_soal', 'campuran') === 'campuran' ? 'selected' : '' }}>Campuran</option>
                                    <option value="pg" {{ old('bentuk_soal') === 'pg' ? 'selected' : '' }}>Pilihan Ganda</option>
                                    <option value="essay" {{ old('bentuk_soal') === 'essay' ? 'selected' : '' }}>Essay</option>
                                </select>
                            </div>
                            <div class="col-md-4 ai-soal-fields">
                                <label class="form-label">Level Kognitif</label>
                                <select name="level_kognitif" class="form-select">
                                    <option value="campuran" {{ old('level_kognitif', 'campuran') === 'campuran' ? 'selected' : '' }}>Campuran</option>
                                    @foreach(['C1','C2','C3','C4','C5','C6'] as $level)
                                        <option value="{{ $level }}" {{ old('level_kognitif') === $level ? 'selected' : '' }}>{{ $level }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 ai-rpm-fields ai-project-fields d-none">
                                <label class="form-label">Jumlah Pertemuan / Aktivitas</label>
                                <input type="number" name="jumlah_pertemuan" class="form-control" min="1" max="12" value="{{ old('jumlah_pertemuan', 2) }}">
                            </div>
                            <div class="col-md-4 ai-project-fields d-none">
                                <label class="form-label">Jenis Projek</label>
                                <select name="project_type" class="form-select">
                                    <option value="p5ra" @selected($curriculumProfile?->isKemenag())>P5RA</option>
                                    <option value="p5" @selected(! $curriculumProfile?->isKemenag())>P5</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Bahasa</label>
                                <select name="bahasa" class="form-select">
                                    <option value="id" {{ old('bahasa', 'id') === 'id' ? 'selected' : '' }}>Indonesia</option>
                                    <option value="ar" {{ old('bahasa') === 'ar' ? 'selected' : '' }}>Arab</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Instruksi Tambahan</label>
                                <textarea name="instruksi" class="form-control" rows="2" maxlength="2000"
                                          placeholder="Mis. konteks pesantren, hindari pilihan yang terlalu mirip...">{{ old('instruksi') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100" {{ $aiConfigured ? '' : 'disabled' }}>
                                    <i class="ri-magic-line me-1"></i>Generate Draft
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-0"><i class="ri-history-line text-success me-1"></i>Riwayat Draft AI</h5>
                        <p class="text-muted mb-0 small">50 draft terbaru pada satuan pendidikan ini.</p>
                    </div>
                    <span class="badge bg-info-subtle text-info">Provider: {{ $aiConfigured ? 'aktif' : 'belum diatur' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Judul</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Status</th>
                                <th>Dibuat</th>
                                <th class="text-end" style="width:150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $record)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id]) }}" class="fw-medium link-primary">
                                            {{ $record->title }}
                                        </a>
                                        <div class="text-muted small">
                                            {{ $record->subject?->name ?? '—' }}
                                            @if($record->gradeLevel) · {{ $record->gradeLevel->name }} @endif
                                            @if($record->creator) · {{ $record->creator->name }} @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $record->type === 'soal' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">
                                            {{ $record->type === 'rpm' ? ($curriculumLabels['perangkat_short'] ?? 'RPM') : 'SOAL' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($record->isApproved())
                                            <span class="badge bg-success-subtle text-success">Disetujui</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $record->created_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Lihat"><i class="ri-eye-line"></i></a>
                                            @if($isTeam && ! $record->isApproved())
                                                <form method="POST" action="{{ route('user.kurikulum.ai.approve', ['userId' => $userId, 'id' => $record->id]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success" title="Setujui"><i class="ri-check-line"></i></button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-soft-danger delete-ai" title="Hapus"
                                                    data-action="{{ route('user.kurikulum.ai.destroy', ['userId' => $userId, 'id' => $record->id]) }}"
                                                    data-name="{{ $record->title }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">
                                    <i class="ri-magic-line fs-3 d-block mb-2"></i>
                                    Belum ada draft AI. Buat draft pertama di form sebelah.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <form id="deleteAiForm" method="POST" class="d-none" action="">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteAiModal"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primary-color="#f7b84b" secondary-color="#f06548"
        title="Hapus Draft AI?"
        message="Draft <strong id=&quot;deleteAiName&quot;>ini</strong> akan dihapus permanen."
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger"
        form-id="deleteAiForm" />
@endsection

@section('script')
    <script>
        function toggleAiFields() {
            const type = document.getElementById('aiType').value;
            document.querySelectorAll('.ai-soal-fields').forEach(el => el.classList.toggle('d-none', type !== 'soal'));
            document.querySelectorAll('.ai-rpm-fields').forEach(function (el) {
                const alsoProject = el.classList.contains('ai-project-fields');
                el.classList.toggle('d-none', !(type === 'rpm' || (alsoProject && type === 'projek')));
            });
            document.querySelectorAll('.ai-project-fields:not(.ai-rpm-fields)').forEach(el => el.classList.toggle('d-none', type !== 'projek'));
        }

        document.addEventListener('DOMContentLoaded', toggleAiFields);

        document.querySelectorAll('.delete-ai').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteAiForm').action = this.dataset.action;
                document.getElementById('deleteAiName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteAiModal')).show();
            });
        });
    </script>
@endsection
