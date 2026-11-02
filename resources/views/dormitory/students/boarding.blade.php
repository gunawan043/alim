@extends('layouts.master')
@section('title', 'Santri Boarding — '.($dormitory->name ?? 'Asrama'))
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kamar</a> @endslot
        @slot('li_2') <a href="{{ route('user.asrama.rooms.placement', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Penempatan</a> @endslot
        @slot('title') Santri Boarding — {{ $dormitory->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
        <i class="ri-shield-keyhole-line fs-18 mt-1"></i>
        <div>
            <strong>Halaman data lintas asrama.</strong>
            Halaman ini khusus administrasi status boarding dan sengaja dipisahkan dari halaman kamar.
            Data selalu <strong>terkunci satu gender</strong>
            ({{ $gender === 'L' ? 'putra' : 'putri' }}) — data lawan jenis tidak pernah ditampilkan.
        </div>
    </div>

    @php
        $genderLabel = fn (string $code) => $code === 'L' ? 'Putra' : 'Putri';
        $genderIcon = fn (string $code) => $code === 'L' ? 'ri-men-line' : 'ri-women-line';
    @endphp

    {{-- ── Statistik ─────────────────────────────────────────────── --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-3">
        @foreach([
            ['label' => 'Total Santri', 'value' => $counts['total'], 'icon' => 'ri-team-line', 'tone' => 'primary'],
            ['label' => 'Boarding', 'value' => $counts['boarding'], 'icon' => 'ri-home-heart-line', 'tone' => 'success'],
            ['label' => 'Non-Boarding', 'value' => $counts['non_boarding'], 'icon' => 'ri-home-line', 'tone' => 'secondary'],
            ['label' => 'Belum Ditentukan', 'value' => $counts['belum'], 'icon' => 'ri-question-line', 'tone' => 'warning'],
            ['label' => 'Sudah Berkamar', 'value' => $counts['berkamar'], 'icon' => 'ri-hotel-bed-line', 'tone' => 'info'],
        ] as $stat)
            <div class="col">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs flex-shrink-0">
                                <span class="avatar-title bg-{{ $stat['tone'] }}-subtle rounded-circle">
                                    <i class="{{ $stat['icon'] }} text-{{ $stat['tone'] }}"></i>
                                </span>
                            </span>
                            <div>
                                <p class="text-muted mb-0 fs-12">{{ $stat['label'] }}</p>
                                <h5 class="mb-0">{{ number_format($stat['value']) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-md">
                    <h5 class="card-title mb-0"><i class="ri-user-settings-line me-1 text-primary"></i>Data Santri Boarding</h5>
                    <p class="text-muted mb-0 small">{{ $dormitory->school?->name }} · {{ $genderLabel($gender) }}</p>
                </div>
                <div class="col-md-auto">
                    @if(count($allowedGenders) > 1)
                        <div class="btn-group btn-group-sm" role="group" aria-label="Pilih gender">
                            @foreach($allowedGenders as $code)
                                <a href="{{ route('user.asrama.boarding.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'gender' => $code] + request()->except('page')) }}"
                                   class="btn btn-outline-{{ $code === 'L' ? 'primary' : 'danger' }} {{ $gender === $code ? 'active' : '' }}">
                                    <i class="{{ $genderIcon($code) }} me-1"></i>{{ $genderLabel($code) }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="badge bg-{{ $gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $gender === 'L' ? 'primary' : 'danger' }} fs-12">
                            <i class="{{ $genderIcon($gender) }} me-1"></i>{{ $genderLabel($gender) }} saja
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3" role="search">
                <input type="hidden" name="gender" value="{{ $gender }}">
                <div class="col-md-5">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                           placeholder="Cari nama / NIS / NISN…" aria-label="Cari santri">
                </div>
                <div class="col-md-4">
                    <select name="boarding_status" class="form-select form-select-sm" aria-label="Filter status boarding">
                        <option value="">— Semua status —</option>
                        <option value="boarding" @selected(request('boarding_status') === 'boarding')>Boarding</option>
                        <option value="non_boarding" @selected(request('boarding_status') === 'non_boarding')>Non-Boarding</option>
                        <option value="belum" @selected(request('boarding_status') === 'belum')>Belum ditentukan</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="ri-search-line me-1"></i>Cari</button>
                    <a href="{{ route('user.asrama.boarding.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'gender' => $gender]) }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="text-muted small" id="boarding-selected-count">0 dipilih</span>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="boarding-set-non" disabled>
                        <i class="ri-home-line me-1"></i>Tandai Non-Boarding
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="boarding-set-on" disabled>
                        <i class="ri-home-heart-line me-1"></i>Tandai Boarding
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;">
                                <input type="checkbox" class="form-check-input" id="boarding-check-all" aria-label="Pilih semua">
                            </th>
                            <th>Nama Santri</th>
                            <th style="width:16%;">Kelas</th>
                            <th style="width:8%;">Gender</th>
                            <th style="width:16%;">Status Boarding</th>
                            <th style="width:24%;">Asrama / Kamar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            @php $resident = $residents->get($student->id); @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input boarding-row-check"
                                           value="{{ $student->id }}" aria-label="Pilih {{ $student->name }}">
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $student->name }}</span>
                                    <br><small class="text-muted">NIS {{ $student->nis ?: '-' }} · NISN {{ $student->nisn ?: '-' }}</small>
                                </td>
                                <td>{{ $student->class_name }}</td>
                                <td><span class="badge bg-{{ $student->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $student->gender === 'L' ? 'primary' : 'danger' }}">{{ $student->gender === 'L' ? 'Putra' : 'Putri' }}</span></td>
                                <td>
                                    @if($student->boarding_status === 'boarding')
                                        <span class="badge bg-success-subtle text-success">Boarding</span>
                                    @elseif($student->boarding_status === 'non_boarding')
                                        <span class="badge bg-secondary-subtle text-secondary">Non-Boarding</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($resident)
                                        <span class="badge bg-info-subtle text-info">{{ $resident->dormitory?->name ?? '—' }}</span>
                                        <small class="text-muted d-block">{{ $resident->room?->name ?? $resident->room?->code ?? '—' }}</small>
                                    @else
                                        <span class="text-muted small">Belum berkamar</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada santri pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $students->links() }}</div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const updateUrl = "{{ route('user.asrama.api.boarding-status.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
    const csrf = "{{ csrf_token() }}";

    const checkAll = document.getElementById('boarding-check-all');
    const rowChecks = Array.from(document.querySelectorAll('.boarding-row-check'));
    const countEl = document.getElementById('boarding-selected-count');
    const btnOn = document.getElementById('boarding-set-on');
    const btnNon = document.getElementById('boarding-set-non');

    function selected() {
        return rowChecks.filter(c => c.checked).map(c => c.value);
    }

    function refresh() {
        const n = selected().length;
        countEl.textContent = n + ' dipilih';
        btnOn.disabled = n === 0;
        btnNon.disabled = n === 0;
    }

    checkAll?.addEventListener('change', function () {
        rowChecks.forEach(c => { c.checked = checkAll.checked; });
        refresh();
    });

    rowChecks.forEach(c => c.addEventListener('change', refresh));

    function setStatus(status) {
        const ids = selected();
        if (!ids.length) return;

        btnOn.disabled = true;
        btnNon.disabled = true;

        fetch(updateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ student_ids: ids, status: status })
        })
            .then(r => r.json())
            .then(function (data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' });
                    refresh();
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan jaringan.' });
                refresh();
            });
    }

    btnOn?.addEventListener('click', () => setStatus('boarding'));
    btnNon?.addEventListener('click', () => setStatus('non_boarding'));

    refresh();
});
</script>
@endpush
