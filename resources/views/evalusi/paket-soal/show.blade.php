@extends('layouts.master')
@section('title') {{ $paket->judul }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Paket Soal @endslot
        @slot('li_2') Detail @endslot
        @slot('title') {{ $paket->judul }} @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
            <div>
                <strong>{{ $paket->judul }}</strong>
                <span class="badge bg-{{ $paket->is_published ? 'success' : 'warning' }} ms-2">
                    {{ $paket->is_published ? 'Published' : 'Draft' }}
                </span>
                <span class="badge bg-info ms-1">{{ $paket->kode_paket }}</span>
            </div>
            <div>
                @if(!$paket->is_published && $paket->jumlah_soal_aktual > 0)
                <form action="{{ route('user.paket-soal.publish', $paket->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-success" onclick="return confirm('Publish paket ini?')">
                        <i class="ri-check-line"></i> Publish
                    </button>
                </form>
                @endif

                @if($paket->is_published)
                <form action="{{ route('user.paket-soal.unpublish', $paket->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-warning">Unpublish</button>
                </form>
                @endif

                <a href="{{ route('user.paket-soal.reroll', $paket->id) }}" class="btn btn-sm btn-outline-info">
                    <i class="ri-refresh-line"></i> Re-roll Soal
                </a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-2">Mapel</dt><dd class="col-sm-10">{{ $paket->kisiKisi->subject->name ?? '-' }}</dd>
                <dt class="col-sm-2">Fase</dt><dd class="col-sm-10">{{ $paket->kisiKisi->gradeLevel->nama ?? '-' }}</dd>
                <dt class="col-sm-2">Jumlah Soal</dt><dd class="col-sm-10"><strong>{{ $paket->jumlah_soal_aktual }} butir</strong></dd>
                <dt class="col-sm-2">Total Bobot</dt><dd class="col-sm-10">{{ number_format($paket->total_bobot_aktual, 2) }}</dd>
                <dt class="col-sm-2">Waktu</dt><dd class="col-sm-10">{{ $paket->waktu_pengerjaan_menit }} menit</dd>
                <dt class="col-sm-2">KKM</dt><dd class="col-sm-10">{{ $paket->kkm ?? '-' }}</dd>
                <dt class="col-sm-2">Acak Soal/Opsi</dt>
                <dd class="col-sm-10">
                    <span class="badge bg-{{ $paket->is_acak_urutan_soal ? 'success':'secondary' }}">Soal</span>
                    <span class="badge bg-{{ $paket->is_acak_opsi ? 'success':'secondary' }}">Opsi</span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Soal dalam Paket ({{ $paket->items->count() }})</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Pertanyaan</th>
                        <th>Tipe</th>
                        <th>TP</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paket->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ Str::limit($item->soal->pertanyaan ?? '-', 100) }}</td>
                        <td>{{ strtoupper($item->soal->tipe_soal ?? '-') }}</td>
                        <td>{{ Str::limit($item->soal->tujuanPembelajaran->deskripsi ?? $item->soal->tp_id ?? '-', 40) }}</td>
                        <td>{{ number_format($item->bobot_override ?? $item->soal->bobot_default ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection