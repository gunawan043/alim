@extends('layouts.master')

@section('title') Laporan Pondok @endsection

@section('css')
    @include('user.laporan.partials._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') Laporan Pondok @endslot
    @endcomponent

    <div class="laporan-page">
        <div class="lap-head">
            <div>
                <h1 class="lap-head-title">Laporan pondok</h1>
                <p class="lap-head-desc">Rekap akademik, populasi santri, presensi, kepegawaian, dan mutu pendidikan dalam satu tempat.</p>
            </div>
        </div>

        @include('user.laporan.partials.kpi', ['items' => [
            ['label' => 'Santri aktif', 'value' => $stats['total_siswa'] ?? 0, 'note' => 'status aktif'],
            ['label' => 'GTK aktif', 'value' => $stats['total_gtk'] ?? 0, 'note' => 'akun kepegawaian'],
            ['label' => 'Rombel aktif', 'value' => $stats['total_rombel'] ?? 0, 'note' => 'tahun ajaran berjalan'],
            ['label' => 'Presensi GTK hari ini', 'value' => $stats['presensi_hari_ini'] ?? 0, 'note' => now()->locale('id')->translatedFormat('d F Y')],
            ['label' => 'Alumni', 'value' => $stats['total_alumni'] ?? 0, 'note' => 'seluruh angkatan'],
            ['label' => 'Alumni bekerja', 'value' => $stats['alumni_aktif'] ?? 0, 'note' => 'sedang atau sudah bekerja'],
        ]])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Jenis laporan</h2>
                <span class="lap-meta">11 laporan</span>
            </div>
            <div class="lap-links">
                @php
                    $links = [
                        ['route' => 'user.laporan.akademik', 'label' => 'Analisis akademik', 'desc' => 'Nilai murni STS/SAS per santri, kelas, dan mapel.'],
                        ['route' => 'user.laporan.akademik.ketuntasan', 'label' => 'Nilai & ketuntasan', 'desc' => 'Kelengkapan nilai dan ketuntasan per kelas.'],
                        ['route' => 'user.laporan.akademik.kehadiran', 'label' => 'Kehadiran santri', 'desc' => 'Persentase kehadiran dan santri berisiko.'],
                        ['route' => 'user.laporan.akademik.keterlaksanaan', 'label' => 'Keterlaksanaan', 'desc' => 'Rencana pembelajaran dibandingkan realisasi.'],
                        ['route' => 'user.laporan.akademik.administrasi', 'label' => 'Administrasi akademik', 'desc' => 'Kelengkapan perangkat, jurnal, dan input nilai.'],
                        ['route' => 'user.laporan.pendidikan.mutu', 'label' => 'Mutu pendidikan', 'desc' => 'Keterhubungan dokumen kurikulum sampai rapor.'],
                        ['route' => 'user.laporan.populasi.mutasi', 'label' => 'Mutasi & populasi', 'desc' => 'Mutasi, pertumbuhan, sebaran, dan keberlanjutan.'],
                        ['route' => 'user.laporan.presensi', 'label' => 'Presensi GTK', 'desc' => 'Hadir, terlambat, izin, sakit, dan alpa.'],
                        ['route' => 'user.laporan.santri', 'label' => 'Data santri', 'desc' => 'Daftar santri per rombel dan status.'],
                        ['route' => 'user.laporan.gtk', 'label' => 'Data GTK', 'desc' => 'Nama, jabatan, jenis GTK, dan status kepegawaian.'],
                        ['route' => 'user.laporan.asrama', 'label' => 'Asrama', 'desc' => 'Penghuni aktif, kapasitas, dan kamar.'],
                    ];
                @endphp
                @foreach ($links as $link)
                    <a href="{{ route($link['route'], ['userId' => $userId]) }}" class="lap-link">
                        <span class="lap-link-label">{{ $link['label'] }}<i class="ri-arrow-right-s-line"></i></span>
                        <span class="lap-link-desc">{{ $link['desc'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="lap-grid lap-grid-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Santri terbaru</h2>
                    <a href="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="lap-btn lap-btn-sm">Lihat semua</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentStudents->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada santri', 'desc' => 'Data santri akan muncul setelah pendaftaran dicatat.', 'height' => 180])
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach ($recentStudents as $student)
                                        <tr>
                                            <td>
                                                <div class="lap-table-name">{{ $student->name }}</div>
                                                <div class="lap-table-sub">{{ $student->nisn ?: $student->nis ?: '—' }}</div>
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $studentStatus = [
                                                        'active' => 'Aktif',
                                                        'inactive' => 'Nonaktif',
                                                        'graduate' => 'Lulus',
                                                        'dropped' => 'Keluar',
                                                        'transfer' => 'Pindah',
                                                    ][$student->status] ?? ucfirst((string) $student->status);
                                                @endphp
                                                <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}-subtle">{{ $studentStatus }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">GTK terbaru</h2>
                    <a href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="lap-btn lap-btn-sm">Lihat semua</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentGtk->isEmpty())
                        @include('user.laporan.partials.empty', ['title' => 'Belum ada GTK', 'desc' => 'Data kepegawaian akan muncul setelah akun GTK ditautkan.', 'height' => 180])
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach ($recentGtk as $person)
                                        <tr>
                                            <td>
                                                <div class="lap-table-name">{{ $person->name }}</div>
                                                <div class="lap-table-sub">{{ $person->employment?->jabatan ?: $person->email }}</div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $person->is_active ? 'success' : 'secondary' }}-subtle">{{ $person->is_active ? 'Aktif' : 'Nonaktif' }}</span>
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
    @include('user.laporan.partials._script')
@endpush
