@extends('layouts.master')
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
                <a href="{{ route('user.paket-soal.create', $kisi->id) }}" class="btn btn-sm btn-success">
                    <i class="ri-file-list-3-line"></i> Buat Paket Soal
                </a>
                <a href="{{ route('user.kisi-kisi.edit', $kisi->id) }}" class="btn btn-sm btn-warning">
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
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tujuan Pembelajaran</th>
                        <th>Level Kognitif</th>
                        <th>Jumlah</th>
                        <th>Bobot/Soal</th>
                        <th>Total Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kisi->items as $i => $item)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $item->tujuanPembelajaran->deskripsi ?? $item->tujuanPembelajaran->nama ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $item->level_kognitif }}</span></td>
                        <td>{{ $item->jumlah_soal }}</td>
                        <td>{{ number_format($item->bobot_per_soal, 2) }}</td>
                        <td>{{ number_format($item->jumlah_soal * $item->bobot_per_soal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>{{ $kisi->items->sum('jumlah_soal') }}</th>
                        <th>-</th>
                        <th>{{ number_format($kisi->items->sum(fn($i) => $i->jumlah_soal * $i->bobot_per_soal), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection