@extends('layouts.master')
@section('title', $curriculumLabels['perangkat_label'] ?? 'Perangkat Pembelajaran')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $sections = \App\Models\PerangkatPembelajaran::SECTIONS_RPM;
        $legacyFilled = $perangkat->legacyFilledSections();
        $atp = $perangkat->atp;
        $tps = $atp?->items?->map(fn ($i) => $i->tujuanPembelajaran)->filter() ?? collect();
        $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
        $disabled = $canEdit ? '' : 'disabled';
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}">{{ $curriculumLabels['perangkat_label'] ?? 'Perangkat' }}</a> @endslot
        @slot('title') {{ $perangkat->judul }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $perangkat->judul }}</h4>
            <p class="text-muted mb-0 small">
                {{ $perangkat->subject?->name }}
                · {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}
                · {{ $perangkat->academicYear?->name }}
                · Semester {{ ucfirst($perangkat->semester) }}
                · Guru: {{ $perangkat->teacher?->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary p-2">
                {{ \App\Models\PerangkatPembelajaran::TIPE_OPTIONS[$perangkat->tipe] ?? $perangkat->tipe }}
            </span>
            <span class="badge {{ ($perangkat->source ?? 'manual') === 'ai' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' }} p-2">
                {{ ($perangkat->source ?? 'manual') === 'ai' ? 'Sumber: AI — tinjau' : 'Sumber: Manual' }}
            </span>
            @if($curriculumProfile ?? null)
                <span class="badge bg-success-subtle text-success p-2">{{ $curriculumProfile->name }}</span>
            @endif
            <span class="badge {{ $perangkat->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} p-2">
                {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$perangkat->status] ?? $perangkat->status }}
            </span>
            <a href="{{ route('user.kurikulum.cetak.rpm', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
            </a>
            @if($atp)
                <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $atp->id]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-link align-bottom me-1"></i> ATP ({{ $atp->total_jp }} JP)
                </a>
            @endif
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($perangkat->isLocked())
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-lock-line me-1"></i>
            RPM ini sudah <strong>final dan terkunci</strong>. Revisi dilakukan dengan membuat <strong>versi baru</strong> —
            dokumen ini tetap tersimpan sebagai riwayat.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(! $canEdit)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri-team-line me-1"></i>
            Ini <strong>RPM bersama serumpun</strong> (mapel &amp; jenjang/fase sama). Anda dapat melihat dan mencetaknya;
            perubahan hanya dapat dilakukan penyusun atau tim kurikulum.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tahap 2: alur dokumen, approval rumpun, versi & penguncian --}}
    @php
        $wf = $perangkat->workflow_status ?? 'draft';
        $wfBadge = [
            'draft' => 'bg-secondary-subtle text-secondary',
            'review' => 'bg-warning-subtle text-warning',
            'revisi' => 'bg-danger-subtle text-danger',
            'approved' => 'bg-success-subtle text-success',
        ][$wf] ?? 'bg-secondary-subtle text-secondary';
    @endphp
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <span class="badge {{ $wfBadge }} p-2">
                    <i class="ri-git-branch-line me-1"></i>{{ \App\Models\PerangkatPembelajaran::WORKFLOW_OPTIONS[$wf] ?? $wf }}
                </span>
                <span class="badge bg-dark-subtle text-dark p-2 ms-1">v{{ $perangkat->version ?? 1 }}</span>
                @if($perangkat->isLocked())
                    <span class="badge bg-success-subtle text-success p-2 ms-1"><i class="ri-lock-line me-1"></i>Terkunci (final)</span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($myReviewAssignment)
                    <form method="POST" action="{{ route('user.review-soal.decide', ['userId' => $userId, 'assignmentId' => $myReviewAssignment->id]) }}" class="d-flex gap-2">
                        @csrf
                        <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">
                            <i class="ri-check-line me-1"></i>Setujui
                        </button>
                        <button type="submit" name="status" value="revision" class="btn btn-sm btn-outline-danger">
                            <i class="ri-arrow-go-back-line me-1"></i>Minta Perbaikan
                        </button>
                    </form>
                @endif
                @if($canSubmitReview)
                    @if(! ($curriculumProfile ?? null)?->isKemenag() || empty($kbcReviewMissing))
                        <form method="POST" action="{{ route('user.kurikulum.perangkat.submit-review', ['userId' => $userId, 'id' => $perangkat->id]) }}">
                            @csrf
                            <button class="btn btn-sm btn-primary"><i class="ri-send-plane-line me-1"></i>Ajukan Approval Rumpun</button>
                        </form>
                    @else
                        <button class="btn btn-sm btn-secondary" disabled title="Lengkapi checklist KBC wajib review terlebih dahulu."><i class="ri-lock-line me-1"></i>Ajukan Approval Rumpun</button>
                    @endif
                @endif
                @if($canCreateVersion)
                    <form method="POST" action="{{ route('user.kurikulum.perangkat.new-version', ['userId' => $userId, 'id' => $perangkat->id]) }}"
                          onsubmit="return confirm('Buat versi baru dari RPM ini? Dokumen lama tetap tersimpan sebagai riwayat.');">
                        @csrf
                        <button class="btn btn-sm btn-soft-warning"><i class="ri-git-branch-line me-1"></i>Buat Versi Baru</button>
                    </form>
                @endif
            </div>
        </div>
        @if(($workflowProgress['total'] ?? 0) > 0)
            <div class="card-footer bg-light-subtle small text-muted">
                Approval anggota rumpun: <strong>{{ $workflowProgress['approved'] }}/{{ $workflowProgress['total'] }}</strong> menyetujui
                · {{ $workflowProgress['pending'] }} menunggu · {{ $workflowProgress['revision'] }} minta perbaikan
            </div>
        @endif
    </div>

    @if(($curriculumProfile ?? null)?->isKemenag())
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-heart-3-line text-danger me-1"></i>Checklist Integrasi KBC</h5>
                    <p class="text-muted mb-0 small">Bagian wajib saat review: tema, materi insersi, pilar, dan indikator. Saat final: seluruh checklist.</p>
                </div>
                <span class="badge {{ $kbcPercentage >= 100 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} p-2">{{ $kbcPercentage }}% lengkap</span>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height:8px"><div class="progress-bar {{ $kbcPercentage >= 100 ? 'bg-success' : 'bg-warning' }}" style="width:{{ $kbcPercentage }}%"></div></div>
                <div class="row g-2">
                    @foreach($kbcChecklist as $key => $item)
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 small">
                                @if($item['filled'])
                                    <i class="ri-checkbox-circle-fill text-success"></i>
                                @else
                                    <i class="ri-checkbox-blank-circle-line text-muted"></i>
                                @endif
                                <span class="{{ $item['filled'] ? 'text-muted text-decoration-line-through' : 'fw-medium' }}">{{ $item['label'] }}</span>
                                @if(! $item['filled'] && in_array($key, $kbcReviewMissing, true))
                                    <span class="badge bg-warning-subtle text-warning">wajib review</span>
                                @elseif(! $item['filled'])
                                    <span class="badge bg-secondary-subtle text-secondary">wajib final</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($kbcMissing !== [])
                    <div class="alert alert-warning py-2 mt-3 mb-0 small"><i class="ri-error-warning-line me-1"></i>Belum dapat ditetapkan final sampai seluruh bagian KBC terisi.</div>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('user.kurikulum.perangkat.update', ['userId' => $userId, 'id' => $perangkat->id]) }}">
        @csrf @method('PUT')

        {{-- ── Informasi Umum ─────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i> Informasi Umum</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" value="{{ $perangkat->judul }}" required maxlength="255" {{ $disabled }}>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Kelas</label>
                        <select name="study_group_id" class="form-select" {{ $disabled }}>
                            <option value="">— Semua Kelas —</option>
                            @foreach($studyGroups as $group)
                                <option value="{{ $group->id }}" {{ $perangkat->study_group_id === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Struktur {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}</label>
                        <select name="tipe" class="form-select" {{ $disabled }}>
                            @foreach(\App\Models\PerangkatPembelajaran::TIPE_OPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ $perangkat->tipe === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" {{ $disabled }}>
                            <option value="draft" {{ $perangkat->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="final" {{ $perangkat->status === 'final' ? 'selected' : '' }}>Final</option>
                        </select>
                    </div>
                    <div class="col-lg-9">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="catatan" class="form-control" value="{{ $perangkat->catatan }}" maxlength="2000" {{ $disabled }}>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CP & TP otomatis dari ATP ──────────────────────────── --}}
        @if($cps->isNotEmpty() || $tps->isNotEmpty())
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-link text-primary me-1"></i> CP &amp; TP (otomatis dari ATP)</h5>
                    <span class="badge bg-secondary-subtle text-secondary">Tidak perlu diisi ulang</span>
                </div>
                <div class="card-body">
                    @if($cps->isNotEmpty())
                        <h6 class="text-muted small text-uppercase mb-2">Capaian Pembelajaran</h6>
                        @foreach($cps as $cp)
                            <p class="small mb-1">
                                <span class="badge bg-info-subtle text-info">Fase {{ $cp->fase }}</span>
                                @if($cp->elemen) <span class="badge bg-light text-dark">{{ $cp->elemen }}</span> @endif
                                {{ $cp->deskripsi }}
                            </p>
                        @endforeach
                    @endif
                    @if($tps->isNotEmpty())
                        <h6 class="text-muted small text-uppercase mb-2 mt-3">Tujuan Pembelajaran</h6>
                        <ol class="small mb-0 ps-3">
                            @foreach($tps as $tp)
                                <li class="mb-1"><strong>{{ $tp->kode_tp }}</strong> — {{ $tp->deskripsi }}</li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Desain Pembelajaran / Pengalaman / Asesmen per tipe ── --}}
        @foreach($perangkat->groups() as $group)
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="{{ $group['icon'] }} text-primary me-1"></i> {{ $group['label'] }}</h5>
                    <p class="text-muted mb-0 small mt-1">{{ $group['desc'] }}</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($group['keys'] as $key)
                            <div class="col-md-6">
                                <label class="form-label">{{ $sections[$key] ?? $key }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        {{-- ── Bagian fondasi lama (bila masih terisi) ────────────── --}}
        @if(! empty($legacyFilled))
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line text-secondary me-1"></i> Fondasi Pembelajaran Mendalam</h5>
                    <p class="text-muted mb-0 small mt-1">Bagian dari struktur sebelumnya yang masih terisi — tetap dipertahankan.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($legacyFilled as $key => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000" {{ $disabled }}>{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($canEdit)
            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-success">
                    <i class="ri-save-line align-bottom me-1"></i> Simpan {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}
                </button>
                <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light">Batal</a>
            </div>
        @endif
    </form>

    @if($canEdit)
        <form method="POST" action="{{ route('user.kurikulum.perangkat.destroy', ['userId' => $userId, 'id' => $perangkat->id]) }}"
              onsubmit="return confirm('Hapus {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }} ini?');">
            @csrf @method('DELETE')
            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line align-bottom me-1"></i> Hapus {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}</button>
        </form>
    @endif

    {{-- Riwayat versi (revisi = versi baru; dokumen lama tetap tersimpan) --}}
    @if(($versions ?? collect())->count() > 1)
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-history-line text-primary me-1"></i>Riwayat Versi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Versi</th>
                                <th>Judul</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Dibuat</th>
                                <th class="text-end" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr class="{{ $version->id === $perangkat->id ? 'table-primary' : '' }}">
                                    <td class="fw-semibold">v{{ $version->version }}</td>
                                    <td class="small">{{ $version->judul }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ \App\Models\PerangkatPembelajaran::WORKFLOW_OPTIONS[$version->workflow_status] ?? $version->workflow_status }}
                                        </span>
                                        @if($version->is_current)
                                            <span class="badge bg-primary-subtle text-primary">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center small text-muted">{{ $version->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $version->id]) }}"
                                           class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
