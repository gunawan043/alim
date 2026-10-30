@extends('layouts.master')

@section('title')
    Rekonsiliasi Data Santri
@endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Akademik
        @endslot
        @slot('li_2')
            Data Santri
        @endslot
        @slot('title')
            Rekonsiliasi Data Santri
        @endslot
    @endcomponent

    <div class="alert {{ $totalIssues > 0 ? 'alert-warning' : 'alert-success' }} py-2 px-3 small">
        @if ($totalIssues > 0)
            Ditemukan <strong>{{ number_format($totalIssues, 0, ',', '.') }}</strong> data yang perlu diperiksa.
            Setiap bagian menampilkan maksimal 15 contoh; gunakan filter di daftar santri untuk melihat semuanya.
        @else
            Tidak ada data yang tidak konsisten. Semua santri punya jalur masuk dan status rombel yang sesuai.
        @endif
    </div>

    <div class="row g-3">
        @foreach ($issues as $issue)
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="fw-semibold">{{ $issue['title'] }}</span>
                            <span class="badge bg-{{ $issue['severity'] }}-subtle text-{{ $issue['severity'] }} ms-2">
                                {{ number_format($issue['count'], 0, ',', '.') }} data
                            </span>
                        </div>
                        @if ($issue['count'] > 0)
                            <a href="{{ route('user.students.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-secondary">
                                <i class="ri-list-check me-1"></i>Buka daftar santri
                            </a>
                        @endif
                    </div>
                    <div class="card-body py-2">
                        <p class="text-muted small mb-2">{{ $issue['description'] }}</p>

                        @if ($issue['count'] === 0)
                            <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Aman</span>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th style="width:120px">NISN</th>
                                            <th style="width:140px">Status</th>
                                            <th style="width:160px">Jalur Masuk</th>
                                            <th style="width:170px">Keluar</th>
                                            <th style="width:100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($issue['rows'] as $row)
                                            <tr>
                                                <td>
                                                    {{ $row->name }}
                                                    @if ($row->trashed())
                                                        <span class="badge bg-secondary-subtle text-secondary ms-1">Arsip</span>
                                                    @endif
                                                </td>
                                                <td><code>{{ $row->nisn ?: '-' }}</code></td>
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $row->status_text }}</span></td>
                                                <td class="small">{{ $row->entry_source_text }}</td>
                                                <td class="small">
                                                    @if ($row->exit_reason || $row->exit_date)
                                                        {{ $row->exit_reason_text }}
                                                        @if ($row->exit_date)
                                                            · {{ $row->exit_date->format('d/m/Y') }}
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($row->trashed())
                                                        <a href="{{ route('user.students.index', ['userId' => $userId, 'arsip' => 1]) }}" class="btn btn-sm btn-soft-secondary">Arsip</a>
                                                    @else
                                                        <a href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $row->id]) }}" class="btn btn-sm btn-soft-primary">Perbaiki</a>
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
        @endforeach
    </div>

    @php
        $duplicateGroups = [
            ['title' => 'NIK duplikat', 'field' => 'NIK', 'rows' => $duplicateNik],
            ['title' => 'NISN duplikat', 'field' => 'NISN', 'rows' => $duplicateNisn],
        ];
    @endphp

    <div class="row g-3 mt-1">
        @foreach ($duplicateGroups as $block)
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light py-2">
                        <span class="fw-semibold">{{ $block['title'] }}</span>
                        <span class="badge bg-info-subtle text-info ms-2">{{ count($block['rows']) }} kelompok</span>
                    </div>
                    <div class="card-body py-2">
                        <p class="text-muted small mb-2">
                            Satu {{ $block['field'] }} dipakai lebih dari satu santri. Pastikan bukan data ganda.
                        </p>
                        @forelse ($block['rows'] as $dup)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <code>{{ $dup['value'] }}</code>
                                    <span class="badge bg-warning-subtle text-warning">{{ $dup['total'] }} santri</span>
                                </div>
                                <div class="small text-muted mt-1">{{ implode(' · ', $dup['students']) }}</div>
                            </div>
                        @empty
                            <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Tidak ada duplikat</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
