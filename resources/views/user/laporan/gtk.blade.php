@extends('layouts.master')

@section('title') Laporan GTK @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Data GTK @endslot
    @endcomponent

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Data GTK</h1>
                <p class="lap-head-desc">Nama, jabatan, jenis GTK, dan status kepegawaian. Saringan yang aktif ikut terbawa ke berkas ekspor.</p>
            </div>
            <div class="lap-head-actions">
                <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'gtk'], request()->query())) }}" class="lap-btn">
                    <i class="ri-download-2-line"></i>Unduh CSV
                </a>
                <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'gtk'], request()->query())) }}" target="_blank" class="lap-btn">
                    <i class="ri-printer-line"></i>Cetak PDF
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="lap-filters">
                    <div class="lap-field">
                        <label class="form-label" for="lap-q">Cari</label>
                        <input type="text" id="lap-q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama atau email">
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-jenis">Jenis GTK</label>
                        <select id="lap-jenis" name="jenis" class="form-select">
                            <option value="">Semua jenis</option>
                            @foreach ($jenisOptions as $jenis)
                                <option value="{{ $jenis }}" {{ request('jenis') === $jenis ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $jenis)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lap-field">
                        <label class="form-label" for="lap-status">Status</label>
                        <select id="lap-status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="lap-actions">
                        <button type="submit" class="lap-btn lap-btn-primary">Terapkan</button>
                        <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="lap-btn lap-btn-icon" title="Atur ulang" aria-label="Atur ulang saringan">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>


        @include('user.laporan.partials.scope')

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Total GTK', 'value' => $summary['total'], 'note' => 'seluruh status akun'],
            ['label' => 'Akun aktif', 'value' => $summary['aktif'], 'note' => 'dapat masuk aplikasi'],
            ['label' => 'Akun nonaktif', 'value' => $summary['nonaktif'], 'note' => 'akses ditutup'],
        ]])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="ri-team-line"></i>Daftar GTK</h2>
                <span class="lap-meta">{{ number_format($gtk->total(), 0, ',', '.') }} baris</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:56px">No</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Jenis GTK</th>
                                <th>Status kepegawaian</th>
                                <th style="width:110px">Status akun</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gtk as $i => $person)
                                <tr>
                                    <td class="muted">{{ $gtk->firstItem() + $i }}</td>
                                    <td>
                                        <div class="lap-table-name">{{ $person->name }}</div>
                                        <div class="lap-table-sub">{{ $person->email }}</div>
                                    </td>
                                    <td>{{ $person->employment?->jabatan ?: '—' }}</td>
                                    <td>{{ $person->employment?->jenis_gtk ? ucwords(str_replace('_', ' ', $person->employment->jenis_gtk)) : '—' }}</td>
                                    <td>{{ $person->employment?->status_kepegawaian ? ucwords(str_replace('_', ' ', $person->employment->status_kepegawaian)) : '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $person->is_active ? 'success' : 'secondary' }}-subtle">{{ $person->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        @include('user.laporan.partials.empty', [
                                            'title' => 'Tidak ada GTK yang cocok',
                                            'desc' => 'Ubah atau atur ulang saringan untuk melihat data lain.',
                                        ])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($gtk->hasPages())
                <div class="card-footer">
                    @include('shared._pagination', ['paginator' => $gtk])
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('user.laporan.partials._script')
@endpush
