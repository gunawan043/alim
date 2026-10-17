@extends('layouts.master')
@section('title') Daftar Mata Pelajaran — {{ $studyGroup->full_name }}     {{-- Modal konfirmasi ubah status mapel rombel — template lord-icon/zoomIn --}}
    <x-modal-confirm id="toggleSubjectModal"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Ubah Status Mapel Rombel?"
        message="Status mapel pada rombel ini akan diperbarui. Struktur akademik (buku nilai, jadwal, leger) mengikuti perubahan ini."
        submit-id="toggleSubjectConfirm"
        submit-label="Ya, Lanjutkan"
        submit-icon="ri-check-line"
        submit-class="btn-primary" />

@endsection

@section('content')
@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
@endphp
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a>
    @endslot
    @slot('li_3') {{ $studyGroup->full_name }} @endslot
    @slot('title') Daftar Mata Pelajaran @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
           class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Rombel
        </a>
        <a href="{{ route('user.study-groups.subjects.create', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
           class="btn btn-primary btn-sm float-end">
            <i class="ri-add-line me-1"></i> Assign Mapel
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Mata Pelajaran di Rombel</h5>
            </div>
            <div class="card-body">
                @if($assignments->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="ri-book-close-line fs-1 d-block mb-2"></i>
                        Belum ada mata pelajaran di-assign ke rombel ini.
                        <div class="mt-3">
                            <a href="{{ route('user.study-groups.subjects.create', ['userId' => $userId, 'id' => $studyGroup->id]) }}"
                               class="btn btn-sm btn-primary">
                                <i class="ri-add-line me-1"></i> Assign Mata Pelajaran
                            </a>
                        </div>
                    </div>
                @else
                    <div class="table-container">
                        <table class="table table-striped table-hover table-freeze align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kategori</th>
                                    <th>Guru Pengampu</th>
                                    <th class="text-center">JP/minggu</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $a)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('user.study-groups.subjects.show', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $a->id]) }}"
                                               class="text-decoration-none fw-semibold">
                                                {{ $a->subject?->code ?? '?' }} — {{ $a->subject?->name ?? 'Deleted' }}
                                            </a>
                                        </td>
                                        <td>{{ ucfirst($a->subject?->category ?? '–') }}</td>
                                        <td>
                                            @if($a->teacher)
                                                <span class="text-primary">{{ $a->teacher->name }}</span>
                                            @else
                                                <span class="text-muted">Belum ada</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($a->weekly_hours, 1) }}</td>
                                        <td>
                                            @if($a->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="white-space:nowrap;">
                                            @if($a->is_active)
                                                <button type="button" class="btn btn-ghost btn-sm"
                                                        onclick="deactivate('{{ $a->id }}')">
                                                    <i class="ri-pause-line"></i>
                                                </button>
                                                <form id="del-form-{{ $a->id }}"
                                                      action="{{ route('user.study-groups.subjects.destroy', ['userId' => $userId, 'id' => $studyGroup->id, 'assignmentId' => $a->id]) }}"
                                                      method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-ghost btn-sm text-success"
                                                        onclick="reactivate('{{ $a->id }}')">
                                                    <i class="ri-play-line"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
var pendingToggle = null;

function deactivate(id) { askToggle(id, false); }
function reactivate(id) { askToggle(id, true); }

function askToggle(id, active) {
    pendingToggle = { id: id, active: active };
    var modalEl = document.getElementById('toggleSubjectModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function runToggle() {
    if (!pendingToggle) return;
    var payload = pendingToggle;
    pendingToggle = null;

    var modalEl = document.getElementById('toggleSubjectModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getInstance(modalEl)?.hide();
    }

    fetch(`{{ route('user.study-groups.show', ['userId' => $userId, 'id' => 'x']) }}/subjects/${payload.id}/update`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ is_active: payload.active })
    }).then(r => r.json()).then(d => {
        location.reload();
    }).catch(e => location.reload());
}

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('toggleSubjectConfirm');
    if (btn) btn.addEventListener('click', runToggle);
});
</script>
@endpush
