@extends('layouts.master')

@section('title') Laporan Asrama @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Asrama @endslot
    @endcomponent

    @php
        $terisi = (int) $summary['penghuni'];
        $kapasitas = (int) $summary['kapasitas'];
        $hunian = $kapasitas > 0 ? round($terisi / $kapasitas * 100, 1) : null;
    @endphp

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Asrama</h1>
                <p class="lap-head-desc">Jumlah penghuni aktif, kapasitas, dan kamar per asrama.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'asrama'], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.asrama', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-q">Cari asrama</label>
                        <input type="text" id="lap-q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama asrama">
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.asrama', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>


        @include('user.laporan.partials.scope')

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Asrama', 'value' => $summary['total'], 'note' => 'unit terdaftar'],
            ['label' => 'Kapasitas', 'value' => $summary['kapasitas'], 'note' => 'tempat tidur'],
            ['label' => 'Penghuni aktif', 'value' => $summary['penghuni'], 'note' => $hunian !== null ? number_format($hunian, 1, ',', '.').'% terisi' : 'belum ada kapasitas'],
            ['label' => 'Sisa kapasitas', 'value' => $summary['kosong'], 'note' => 'tempat tersedia', 'tone' => $summary['kosong'] > 0 ? 'ok' : 'warn'],
        ]])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-hotel-line"></i>Daftar asrama</h2>
                <span class="lap-meta">{{ number_format($dormitories->total(), 0, ',', '.') }} asrama</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>Nama asrama</th>
                                <th class="text-end">Kamar</th>
                                <th class="text-end">Penghuni</th>
                                <th class="text-end">Kapasitas</th>
                                <th class="text-end" style="width:170px">Keterisian</th>
                                <th style="width:120px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dormitories as $i => $dorm)
                                @php
                                    $sisa = max(0, (int) $dorm->capacity - (int) $dorm->active_residents_count);
                                    $penuh = $dorm->capacity > 0 && $dorm->active_residents_count >= $dorm->capacity;
                                    $rate = $dorm->capacity > 0 ? min(100, (int) $dorm->active_residents_count / (int) $dorm->capacity * 100) : null;
                                @endphp
                                <tr>
                                    <td class="muted">{{ $dormitories->firstItem() + $i }}</td>
                                    <td class="lap-table-name">{{ $dorm->name }}</td>
                                    <td class="text-end">{{ number_format($dorm->rooms_count, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($dorm->active_residents_count, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($dorm->capacity, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if ($rate === null)
                                            <span class="muted">—</span>
                                        @else
                                            <span class="lap-mini">
                                                <span class="lap-mini-track">
                                                    <span class="lap-mini-fill {{ $penuh ? 'is-full' : '' }}" data-lap-bar="{{ $rate / 100 }}"></span>
                                                </span>
                                                <span>{{ number_format($rate, 0, ',', '.') }}%</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (! $dorm->is_active)
                                            <span class="badge">Nonaktif</span>
                                        @elseif ($penuh)
                                            <span class="badge bg-warning-subtle">Penuh</span>
                                        @else
                                            <span class="badge bg-success-subtle">{{ number_format($sisa, 0, ',', '.') }} tersisa</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Tidak ada asrama yang cocok',
                                            'desc' => 'Ubah atau atur ulang pencarian.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($dormitories->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $dormitories])
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
