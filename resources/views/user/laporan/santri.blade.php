@extends('layouts.master')

@section('title') Laporan Santri @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Data santri @endslot
    @endcomponent

    @php
        $statusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'graduate' => 'Lulus',
            'dropped' => 'Keluar',
            'transfer' => 'Pindah',
        ];
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Data santri</h1>
                <p class="lap-head-desc">Daftar santri per rombel beserta status dan jenis kelamin. Saringan yang aktif ikut terbawa ke berkas ekspor.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'santri'], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh CSV
                </a>
                <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'santri'], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Total santri', 'value' => $summary['total'], 'note' => 'seluruh status'],
            ['label' => 'Aktif', 'value' => $summary['aktif'], 'note' => 'terdaftar di rombel'],
            ['label' => 'Laki-laki', 'value' => $summary['laki'], 'note' => 'dari total'],
            ['label' => 'Perempuan', 'value' => $summary['perempuan'], 'note' => 'dari total'],
        ]])

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-q">Cari</label>
                        <input type="text" id="lap-q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama, NISN, atau NIS">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-rombel">Rombel</label>
                        <select id="lap-rombel" name="rombel" class="form-select">
                            <option value="">Semua rombel</option>
                            @foreach ($studyGroups as $group)
                                <option value="{{ $group->id }}" {{ request('rombel') === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-status">Status</label>
                        <select id="lap-status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $val => $label)
                                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-gender">Jenis kelamin</label>
                        <select id="lap-gender" name="gender" class="form-select">
                            <option value="">Semua</option>
                            <option value="L" {{ request('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ request('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar santri</h2>
                <span class="lap-meta">{{ number_format($students->total(), 0, ',', '.') }} baris</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>NISN / NIS</th>
                                <th>Nama</th>
                                <th style="width:64px">JK</th>
                                <th>Rombel</th>
                                @if (! $schoolId)
                                    <th>Sekolah</th>
                                @endif
                                <th style="width:110px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $i => $student)
                                @php
                                    $tone = match ($student->status) {
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'graduate' => 'info',
                                        'dropped', 'transfer' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="muted">{{ $students->firstItem() + $i }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $student->nisn ?: '—' }}</div>
                                        @if ($student->nis)
                                            <div class="lap-table-sub">{{ $student->nis }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->gender === 'L' ? 'L' : ($student->gender === 'P' ? 'P' : '—') }}</td>
                                    <td>{{ $student->currentClassHistory?->studyGroup?->name ?? '—' }}</td>
                                    @if (! $schoolId)
                                        <td class="muted">{{ $student->school?->name ?? '—' }}</td>
                                    @endif
                                    <td><span class="badge bg-{{ $tone }}-subtle">{{ $statusLabels[$student->status] ?? ucfirst($student->status) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $schoolId ? 6 : 7 }}">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Tidak ada santri yang cocok',
                                            'desc' => 'Ubah atau atur ulang saringan untuk melihat data lain.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($students->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $students])
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
