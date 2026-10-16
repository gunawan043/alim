@extends('layouts.master')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
@section('title') {{ $kisi->judul }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kisi-kisi @endslot
        @slot('li_2') Detail @endslot
        @slot('title') {{ $kisi->judul }} @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
            <div>
                <strong>{{ $kisi->judul }}</strong>
                <span class="badge bg-info ms-2">{{ str_replace('_', ' ', $kisi->jenis_ujian) }}</span>
                <span class="badge bg-secondary">{{ ucfirst($kisi->semester) }}</span>
            </div>
            <div>
                <a href="{{ route('user.paket-soal.create', ['userId' => $userId, 'kisiKisiId' => $kisi->id]) }}" class="btn btn-sm btn-success">
                    <i class="ri-file-list-3-line"></i> Buat Paket Soal
                </a>
                <a href="{{ route('user.kisi-kisi-soal.edit', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn btn-sm btn-warning">
                    <i class="ri-pencil-line"></i> Edit
                </a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-2">Mapel</dt><dd class="col-sm-10">{{ $kisi->subject->name ?? '-' }}</dd>
                <dt class="col-sm-2">Fase</dt><dd class="col-sm-10">{{ $kisi->gradeLevel->nama ?? '-' }}</dd>
                <dt class="col-sm-2">Tingkat</dt><dd class="col-sm-10">{{ strtoupper($kisi->tingkat_sekolah) }}</dd>
                <dt class="col-sm-2">Peminatan</dt><dd class="col-sm-10">{{ strtoupper($kisi->peminatan ?? '-') }}</dd>
                <dt class="col-sm-2">Target Soal</dt><dd class="col-sm-10">{{ $kisi->total_soal_target }} butir</dd>
                @if($kisi->deskripsi)
                <dt class="col-sm-2">Deskripsi</dt><dd class="col-sm-10">{{ $kisi->deskripsi }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Item Kisi-kisi ({{ $kisi->items->count() }})</div>
        <div class="card-body">
            @php
                $bentukSoalLabels = ['pg' => 'Pilihan Ganda', 'mcc' => 'PG Kompleks', 'bs' => 'Benar/Salah', 'jodoh' => 'Menjodohkan', 'isian' => 'Isian', 'uraian' => 'Uraian'];
                $levelLabels = [
                    'C1_mengingat' => 'C1 — Mengingat',
                    'C2_memahami' => 'C2 — Memahami',
                    'C3_menerapkan' => 'C3 — Menerapkan',
                    'C4_menganalisis' => 'C4 — Menganalisis',
                    'C5_mengevaluasi' => 'C5 — Mengevaluasi',
                    'C6_mencita' => 'C6 — Mencipta',
                    'C6_mencipta' => 'C6 — Mencipta',
                ];
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px" class="text-center">No</th>
                            <th>Indikator</th>
                            <th>Materi</th>
                            <th style="width:130px">Bentuk Soal</th>
                            <th style="width:100px" class="text-center">Nomor Soal</th>
                            <th style="width:140px">Level Kognitif</th>
                            <th style="width:80px" class="text-center">Jumlah</th>
                            <th style="width:100px" class="text-center">Bobot/Soal</th>
                            <th style="width:110px" class="text-center">Total Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kisi->items as $i => $item)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>
                                    @if($item->tujuanPembelajaran?->kode_tp)
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $item->tujuanPembelajaran->kode_tp }}</span>
                                    @endif
                                    {{ $item->tujuanPembelajaran->deskripsi ?? $item->tujuanPembelajaran->nama ?? '-' }}
                                </td>
                                <td class="small">{{ $item->materi ?: '—' }}</td>
                                <td class="small">{{ $item->bentuk_soal ? ($bentukSoalLabels[$item->bentuk_soal] ?? $item->bentuk_soal) : '—' }}</td>
                                <td class="text-center small">{{ $item->nomor_soal ?: '—' }}</td>
                                <td class="small">{{ $levelLabels[$item->level_kognitif] ?? $item->level_kognitif }}</td>
                                <td class="text-center">{{ $item->jumlah_soal }}</td>
                                <td class="text-center">{{ number_format($item->bobot_per_soal, 2) }}</td>
                                <td class="text-center fw-semibold">{{ number_format($item->jumlah_soal * $item->bobot_per_soal, 2) }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="9" title="Belum ada item kisi-kisi" message="Tambahkan item TP + level kognitif terlebih dahulu." />
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Total</th>
                            <th class="text-center">{{ $kisi->items->sum('jumlah_soal') }}</th>
                            <th class="text-center">-</th>
                            <th class="text-center">{{ number_format($kisi->items->sum(fn($i) => $i->jumlah_soal * $i->bobot_per_soal), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection