@extends('layouts.master')

@section('title', 'Daftar Insiden')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Insiden Keamanan @endslot
        @slot('title') Daftar Insiden @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Terbuka', 'value' => number_format($stats['open']), 'icon' => 'ri-alert-line', 'color' => 'danger'],
            ['label' => 'Baru', 'value' => number_format($stats['new']), 'icon' => 'ri-spam-2-line', 'color' => 'warning'],
            ['label' => 'Investigasi', 'value' => number_format($stats['investigating']), 'icon' => 'ri-search-eye-line', 'color' => 'violet'],
            ['label' => 'Selesai Bulan Ini', 'value' => number_format($stats['closed_month']), 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Insiden Keamanan</h5>
                            <p class="text-muted mb-0 small">Setiap laporan ditindaklanjuti dan ditutup dengan uraian penyelesaian.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2" action="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}">
                                    <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                                           placeholder="Cari judul, kode, lokasi..." style="width: 240px;">
                                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                </form>
                                <a href="{{ route('user.keamanan.incidents.create', ['userId' => $userId]) }}" class="btn btn-warning">
                                    <i class="ri-add-line me-1"></i> Lapor Insiden
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                        <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}"
                           class="filter-badge {{ ! request('status') ? 'active' : '' }}">Semua</a>
                        @foreach(\App\Models\Security\SecurityIncident::STATUS_OPTIONS as $value => $label)
                            <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId, 'status' => $value]) }}"
                               class="filter-badge {{ request('status') === $value ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th>Insiden</th>
                                    <th>Kode</th>
                                    <th>Jenis</th>
                                    <th>Lokasi</th>
                                    <th>Waktu</th>
                                    <th>Tingkat</th>
                                    <th>Status</th>
                                    <th>Penanggung Jawab</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($incidents as $incident)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $incident->title }}</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($incident->description, 60) }}</div>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $incident->code }}</span></td>
                                        <td>{{ $incident->type->name ?? '—' }}</td>
                                        <td>{{ $incident->location ?: '—' }}</td>
                                        <td>{{ optional($incident->occurred_at)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $incident->severityColor() }}-subtle text-{{ $incident->severityColor() }}">
                                                {{ $incident->severityLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $incident->statusColor() }}-subtle text-{{ $incident->statusColor() }}">
                                                {{ $incident->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>{{ $incident->assignee->name ?? '—' }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.keamanan.incidents.show', ['userId' => $userId, 'incident' => $incident->id]) }}">
                                                            <i class="ri-eye-fill text-info me-2"></i> Lihat Detail
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="9" title="Belum ada insiden"
                                        message="Insiden keamanan yang dilaporkan akan muncul di sini.">
                                        <a href="{{ route('user.keamanan.incidents.create', ['userId' => $userId]) }}" class="btn btn-primary mt-3">
                                            <i class="ri-add-line me-1"></i> Lapor Insiden
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($incidents->hasPages())
                        @include('shared._pagination', ['paginator' => $incidents])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
