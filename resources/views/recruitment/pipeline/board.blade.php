@extends('layouts.master')
@section('title') Pipeline Board — {{ $job->judul }} @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
<style>
    .pipeline-board { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 16px; align-items: flex-start; }
    .pipeline-col {
        min-width: 280px;
        max-width: 280px;
        background: var(--bs-gray-100);
        border-radius: 12px;
        padding: 12px;
        flex-shrink: 0;
    }
    .col-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 4px 12px; border-bottom: 2px solid var(--bs-border-color);
        margin-bottom: 10px;
    }
    .col-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .col-count { font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; }
    .stage-cards { min-height: 60px; }
    .ats-card {
        background: white; border-radius: 10px; padding: 12px;
        margin-bottom: 8px; cursor: grab; border: 1px solid var(--bs-border-color);
        transition: box-shadow 0.2s, transform 0.15s; position: relative; overflow: hidden;
    }
    .ats-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0;
        height: 3px; background: var(--stage-color, #667eea);
    }
    .ats-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); transform: translateY(-2px); }
    .ats-card.dragging { opacity: 0.4; cursor: grabbing; }
    .ats-card .card-avatar {
        width: 34px; height: 34px; border-radius: 50%; object-fit: cover;
        border: 2px solid var(--bs-border-color);
    }
    .ats-card .score-badge {
        font-size: 0.7rem; font-weight: 700; padding: 2px 7px;
        border-radius: 20px; background: var(--bs-light-bg-subtle);
    }
    .ats-card .app-no { font-size: 0.68rem; color: var(--bs-secondary-color); }
    .ats-card .stage-tag {
        font-size: 0.65rem; padding: 1px 6px; border-radius: 4px;
    }
    .ats-card .action-row { display: flex; gap: 4px; margin-top: 8px; }
    .ats-card .action-row .btn { font-size: 0.68rem; padding: 2px 8px; }
    .sortable-ghost { opacity: 0.3; background: var(--bs-primary-bg-subtle) !important; }
    .empty-stage {
        text-align: center; padding: 24px 12px; color: var(--bs-secondary-color);
        font-size: 0.78rem; border: 2px dashed var(--bs-border-color); border-radius: 8px;
    }
    .drop-zone-active { background: var(--bs-primary-bg-subtle) !important; border-color: var(--bs-primary) !important; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('li_2') {{ $job->judul }} @endslot
    @slot('title') Pipeline Board @endslot
@endcomponent

{{-- Header --}}
<div class="row mb-3">
    <div class="col-md-6">
        <h5 class="mb-0">{{ $job->judul }}</h5>
        <span class="badge bg-secondary mt-1">{{ $job->kode_lowongan }}</span>
        <span class="badge bg-{{ $job->status === 'aktif' ? 'success' : 'secondary' }} mt-1">{{ ucfirst($job->status) }}</span>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}"
           class="btn btn-secondary btn-sm">
            <i class="ri-arrow-left-line"></i> Detail Lowongan
        </a>
        <a href="{{ route('user.ats.pipeline.index', ['userId' => $userId, 'jobId' => $job->id]) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="ri-list-check"></i> List View
        </a>
    </div>
</div>

{{-- Kanban Board --}}
<div class="pipeline-board" id="pipelineBoard">
    @forelse($boardData as $stageId => $data)
    <div class="pipeline-col"
         data-stage-id="{{ $data['stage']->id }}"
         style="--stage-color: {{ $data['stage']->warna ?? '#667eea' }}">
        <div class="col-header">
            <div>
                <div class="col-title" style="color: {{ $data['stage']->warna ?? '#667eea' }}">
                    <i class="{{ $data['stage']->icon ?? 'ri-checkbox-circle-line' }} me-1"></i>
                    {{ $data['stage']->nama_tahapan }}
                </div>
            </div>
            <div>
                <span class="badge bg-{{ $data['stage']->warna ?? 'primary' }}-subtle text-{{ $data['stage']->warna ?? 'primary' }} col-count">
                    {{ $data['applications']->count() }}
                </span>
            </div>
        </div>

        <div class="stage-cards" data-stage-id="{{ $data['stage']->id }}">
            @forelse($data['applications'] as $application)
            <div class="ats-card" data-application-id="{{ $application->id }}" data-stage-color="{{ $data['stage']->warna ?? '#667eea' }}">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <img src="{{ $application->recruitmentProfile->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($application->recruitmentProfile->user->name ?? 'U') . '&background=667eea&color=fff&size=34' }}"
                         class="card-avatar" alt="{{ $application->recruitmentProfile->user->name ?? 'U' }}">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate" style="font-size:0.82rem">
                            <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]) }}"
                               class="text-body text-decoration-none">
                                {{ $application->recruitmentProfile->user->name ?? 'N/A' }}
                            </a>
                        </div>
                        <span class="app-no">#{{ $application->no_lamaran }}</span>
                    </div>
                    @if($application->nilai_akhir)
                    <span class="score-badge">
                        <i class="ri-star-s-line text-warning me-1"></i>{{ number_format($application->nilai_akhir, 1) }}
                    </span>
                    @endif
                </div>

                {{-- Tags --}}
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span class="stage-tag bg-{{ $data['stage']->warna ?? 'primary' }}-subtle text-{{ $data['stage']->warna ?? 'primary' }}">
                        {{ $data['stage']->nama_tahapan }}
                    </span>
                    <span class="stage-tag bg-light text-muted">
                        <i class="ri-time-line me-1"></i>{{ $application->tanggal_melamar->diffForHumans() }}
                    </span>
                </div>

                {{-- Action Buttons --}}
                <div class="action-row">
                    <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]) }}"
                       class="btn btn-light btn-sm flex-grow-1">
                        <i class="ri-eye-line me-1"></i> Detail
                    </a>
                    @if(!$loop->last)
                    <button class="btn btn-success btn-sm"
                            onclick="moveNext('{{ $application->id }}', '{{ $data['stage']->id }}', this)"
                            title="Pindahkan ke tahap berikutnya">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="empty-stage">
                <i class="ri-inbox-2-line fs-2 d-block mb-1"></i>
                Belum ada pelamar
            </div>
            @endforelse
        </div>

        {{-- Add Stage Info --}}
        @if($data['stage']->durasi_hari)
        <div class="text-center mt-2">
            <small class="text-muted">Target: {{ $data['stage']->durasi_hari }} hari</small>
        </div>
        @endif
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <i class="ri-list-check fs-1 text-muted"></i>
        <p class="text-muted mt-2">Pipeline belum dibuat untuk lowongan ini.</p>
        <a href="{{ route('user.ats.pipeline.index', ['userId' => $userId, 'jobId' => $job->id]) }}"
           class="btn btn-primary btn-sm">
            <i class="ri-settings-line me-1"></i> Buat Pipeline
        </a>
    </div>
    @endforelse
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── SortableJS Drag & Drop ──────────────────────────────────
    var el = document.getElementById('pipelineBoard');
    if (el && typeof Sortable !== 'undefined') {
        Sortable.create(el, {
            animation: 200,
            ghostClass: 'sortable-ghost',
            draggable: '.ats-card',
            handle: '.ats-card',
            group: 'pipeline-cards',
            onEnd: function(evt) {
                var appId  = evt.item.dataset.applicationId;
                var stageId = evt.to.closest('.pipeline-col').dataset.stageId;

                // Skip if dropped in same column
                if (evt.from === evt.to) return;

                fetch('/' + '{{ $userId }}/ats/applications/' + appId + '/move-to-stage', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ stage_id: stageId })
                })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: d.message || 'Gagal memindahkan' });
                        location.reload();
                    }
                })
                .catch(() => { Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan' }); location.reload(); });
            }
        });
    }

    // ── Move to Next Stage ───────────────────────────────────
    window.moveNext = function(appId, fromStageId, btn) {
        var $btn = $(btn);
        $btn.prop('disabled', true).html('<i class="ri-loader-2-line fa-spin"></i>');

        fetch('/' + '{{ $userId }}/ats/applications/' + appId + '/move-next', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: d.error || 'Gagal memindahkan' });
                $btn.prop('disabled', false).html('<i class="ri-arrow-right-s-line"></i>');
            }
        })
        .catch(() => { Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan' }); location.reload(); });
    };
});
</script>
@endsection