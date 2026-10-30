@extends('layouts.master')
@section('title') {{ $pageTitle ?? 'Broadcast' }} @endsection

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $broadcastRoutes = $broadcastRoutes ?? [
        'store' => 'user.broadcasts.store',
        'show' => 'user.broadcasts.show',
    ];
    $broadcastRouteParams = $broadcastRouteParams ?? ['userId' => $userId];
    $broadcastRouteId = $broadcastRouteId ?? 'id';
    $lockedTarget = $lockedTarget ?? null;
    $lockedRecipient = $lockedRecipient ?? null;
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') {{ $broadcastBreadcrumb ?? 'Komunikasi' }} @endslot
        @slot('title') {{ $broadcastPageTitle ?? 'Broadcast' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan:
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- ============================================================
             FORM BROADCAST
        ============================================================ --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-megaphone-line me-1 text-primary"></i>Buat Broadcast</h5>
                    <p class="text-muted mb-0 small">Kirim pengumuman ke target terpilih.</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route($broadcastRoutes['store'], $broadcastRouteParams) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-sm" required value="{{ old('title') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Isi Broadcast <span class="text-danger">*</span></label>
                            <textarea name="content" rows="4" class="form-control form-control-sm" required>{{ old('content') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-12 text-muted mb-1">Banner (wajib 4:5, 1080×1350)</label>
                            <input type="file" name="banner" id="broadcastBanner" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" onchange="previewBroadcastBanner(this)">
                            <small class="text-muted">Server menormalisasi ke 1080×1350. Satu gambar untuk pop-up broadcast.</small>
                            <div class="mt-2">
                                <img id="broadcastBannerPreview" alt="Pratinjau banner" class="rounded border" style="display:none;width:90px;height:112px;object-fit:cover;">
                            </div>
                        </div>
                        <div class="row g-2">
                            @if($lockedTarget)
                                <input type="hidden" name="target_type" value="{{ $lockedTarget['type'] }}">
                                <input type="hidden" name="target_id" value="{{ $lockedTarget['id'] }}">
                                <div class="col-12">
                                    <label class="form-label fs-12 text-muted mb-1">Target</label>
                                    <div class="form-control form-control-sm bg-light">{{ $lockedTarget['label'] }}</div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">Target</label>
                                    <select name="target_type" id="targetType" class="form-select form-select-sm" required>
                                        @if($isGlobal)<option value="global" @selected(old('target_type') === 'global')>Semua Sekolah</option>@endif
                                        <option value="school" @selected(old('target_type') === 'school')>Sekolah</option>
                                        <option value="dormitory" @selected(old('target_type') === 'dormitory')>Asrama</option>
                                        <option value="class" @selected(old('target_type') === 'class')>Kelas</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">Pilih Target</label>
                                    <select name="target_id" id="targetSelect" data-selected="{{ old('target_id') }}" class="form-select form-select-sm">
                                        <option value="">— Pilih —</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}" data-target="school">{{ $school->name }}</option>
                                        @endforeach
                                        @foreach($dormitories as $dormitory)
                                            <option value="{{ $dormitory->id }}" data-target="dormitory">Asrama: {{ $dormitory->name }}</option>
                                        @endforeach
                                        @foreach($studyGroups as $studyGroup)
                                            <option value="{{ $studyGroup->id }}" data-target="class">Kelas: {{ $studyGroup->gradeLevel?->name }} {{ $studyGroup->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" id="targetHelp"></small>
                                </div>
                            @endif
                            @if($lockedRecipient)
                                <input type="hidden" name="recipient_group" value="{{ $lockedRecipient }}">
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">Penerima</label>
                                    <div class="form-control form-control-sm bg-light">Wali Santri penghuni asrama</div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">Penerima</label>
                                    <select name="recipient_group" class="form-select form-select-sm" required>
                                        <option value="wali" @selected(old('recipient_group') === 'wali')>Wali Santri</option>
                                        <option value="gtk" @selected(old('recipient_group') === 'gtk')>GTK</option>
                                        <option value="both" @selected(old('recipient_group') === 'both')>Wali &amp; GTK</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Tingkat</label>
                                <select name="severity" class="form-select form-select-sm" required>
                                    <option value="info">Info</option>
                                    <option value="warning">Peringatan</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Darurat</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-12 text-muted mb-1">Kanal</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach(['inapp' => 'In-app', 'push' => 'Push', 'wa' => 'WhatsApp'] as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $value }}"
                                                   id="channel-{{ $value }}" {{ $value === 'inapp' || in_array($value, old('channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="channel-{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Kedaluwarsa</label>
                                <input type="datetime-local" name="expires_at" class="form-control form-control-sm" value="{{ old('expires_at') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Tanggal Mulai Kegiatan</label>
                                <input type="date" name="activity_start_date" class="form-control form-control-sm" value="{{ old('activity_start_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 text-muted mb-1">Tanggal Selesai Kegiatan</label>
                                <input type="date" name="activity_end_date" class="form-control form-control-sm" value="{{ old('activity_end_date') }}">
                                <small class="text-muted">Opsional; otomatis masuk Kalender Pendidikan sebagai Agenda Kegiatan (tidak memengaruhi Pekan Efektif).</small>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ack_required" value="1" id="ackRequired" {{ old('ack_required') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="ackRequired">Wajib konfirmasi (ack)</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                            <i class="ri-send-plane-line me-1"></i> Kirim Broadcast
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================
             RIWAYAT BROADCAST
        ============================================================ --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-history-line me-1 text-primary"></i>Riwayat Broadcast</h5>
                            <p class="text-muted mb-0 small">
                                <span class="badge bg-primary-subtle text-primary">{{ $broadcasts->total() }} broadcast</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $broadcasts->count() }}</strong> dari
                            <strong>{{ $broadcasts->total() }}</strong> broadcast
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Target</th>
                                    <th>Penerima</th>
                                    <th>Tanggal</th>
                                    <th class="text-center" style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($broadcasts as $broadcast)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($broadcast->banner_url)
                                                    <img src="{{ $broadcast->banner_url }}" alt="Banner" class="rounded border" style="width:34px;height:43px;object-fit:cover;">
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $broadcast->title }}</div>
                                                    <div class="small text-muted">{{ $broadcast->severity }} · {{ implode(', ', $broadcast->channels ?? []) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small">
                                            @if($broadcast->target_type === 'global')
                                                Semua Sekolah
                                            @elseif($broadcast->target_type === 'school')
                                                {{ $broadcast->school?->name }}
                                            @elseif($broadcast->target_type === 'dormitory')
                                                Asrama {{ $broadcast->dormitory?->name }}
                                            @else
                                                Kelas {{ $broadcast->studyGroup?->name }}
                                            @endif
                                        </td>
                                        <td class="small text-capitalize">{{ $broadcast->recipient_group }}</td>
                                        <td class="small text-muted">{{ $broadcast->published_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route($broadcastRoutes['show'], $broadcastRouteParams + [$broadcastRouteId => $broadcast->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if(! empty($broadcastRoutes['destroy']))
                                                    <form method="POST"
                                                          action="{{ route($broadcastRoutes['destroy'], $broadcastRouteParams + [$broadcastRouteId => $broadcast->id]) }}"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Hapus broadcast ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <lord-icon
                                                src="https://cdn.lordicon.com/msoeawqm.json"
                                                trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a"
                                                style="width:75px;height:75px">
                                            </lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum ada broadcast</h6>
                                            <p class="text-muted mb-0 small">Belum ada broadcast yang dikirim.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($broadcasts->hasPages())
                        @include('shared._pagination', ['paginator' => $broadcasts->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
function previewBroadcastBanner(input) {
    const preview = document.getElementById('broadcastBannerPreview');
    const file = input.files && input.files[0];
    if (!file) {
        preview.style.display = 'none';
        preview.removeAttribute('src');
        return;
    }
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('targetType');
    const target = document.getElementById('targetSelect');
    const help = document.getElementById('targetHelp');
    if (!type || !target) return;

    const initial = target.dataset.selected || '';
    const options = Array.from(target.options);

    function filterTarget(keep) {
        const selectedType = type.value;
        target.innerHTML = '';

        options.forEach(function (option) {
            const isPlaceholder = !option.value;
            const matches = isPlaceholder || option.getAttribute('data-target') === selectedType;

            if (matches) {
                option.disabled = false;
                option.hidden = false;
                target.appendChild(option);
            } else {
                option.disabled = true;
                option.hidden = true;
            }
        });

        if (selectedType === 'global') {
            target.value = '';
            target.disabled = true;
            target.required = false;
            help.textContent = 'Broadcast akan dikirim ke seluruh sekolah.';
        } else {
            target.disabled = false;
            target.required = true;
            const canKeep = keep && options.some(function (option) {
                return option.value === keep && option.getAttribute('data-target') === selectedType;
            });
            target.value = canKeep ? keep : '';
            help.textContent = selectedType === 'school'
                ? 'Pilih sekolah target.'
                : (selectedType === 'dormitory' ? 'Pilih asrama target.' : 'Pilih kelas target.');
        }
    }

    filterTarget(initial);
    type.addEventListener('change', function () { filterTarget(''); });
});
</script>
@endsection