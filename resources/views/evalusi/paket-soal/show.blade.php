@extends('layouts.master')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
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
                @php $wfBadge = ['draft' => 'warning', 'review' => 'info', 'revisi' => 'danger', 'approved' => 'success', 'published' => 'success'][$paket->workflow_status] ?? 'secondary'; @endphp
                <span class="badge bg-{{ $wfBadge }} ms-2">
                    {{ \App\Models\PaketSoal::WORKFLOW_OPTIONS[$paket->workflow_status] ?? $paket->workflow_status }}
                </span>
                @if($paket->hasSnapshot())
                    <span class="badge bg-dark-subtle text-dark ms-1"><i class="ri-lock-line me-1"></i>Naskah final</span>
                @endif
                <span class="badge bg-info ms-1">{{ $paket->kode_paket }}</span>
            </div>
            <div>
                @if(!$paket->is_published && $paket->jumlah_soal_aktual > 0)
                <form action="{{ route('user.paket-soal.publish', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-success" onclick="return confirm('Publish paket ini?')">
                        <i class="ri-check-line"></i> Publish
                    </button>
                </form>
                @endif

                @if($paket->is_published)
                <form action="{{ route('user.paket-soal.unpublish', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-warning">Unpublish</button>
                </form>
                @endif

                <a href="{{ route('user.paket-soal.reroll', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="btn btn-sm btn-outline-info">
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
                <dt class="col-sm-2">KKTP</dt><dd class="col-sm-10">{{ $paket->kkm ?? '-' }}</dd>
                <dt class="col-sm-2">Acak Soal/Opsi</dt>
                <dd class="col-sm-10">
                    <span class="badge bg-{{ $paket->is_acak_urutan_soal ? 'success':'secondary' }}">Soal</span>
                    <span class="badge bg-{{ $paket->is_acak_opsi ? 'success':'secondary' }}">Opsi</span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-list-check-3 text-primary me-1"></i>Komposisi Naskah</h5>
            @if(!empty($komposisi))
                <span class="badge bg-{{ $validasiNaskah['ok'] ? 'success' : 'warning' }}-subtle text-{{ $validasiNaskah['ok'] ? 'success' : 'warning' }}">
                    {{ $validasiNaskah['ok'] ? 'Komposisi terpenuhi' : implode('; ', $validasiNaskah['errors']) }}
                </span>
            @endif
        </div>
        <div class="card-body">
            @if($bisaKelolaNaskah)
                <form method="POST" action="{{ route('user.paket-soal.komposisi.simpan', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="mb-3">
                    @csrf @method('PUT')
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead class="table-light"><tr><th>Jenis Butir</th><th style="width:130px">Jumlah</th><th style="width:130px">Bobot</th></tr></thead>
                            <tbody>
                                @for($i = 0; $i < max(3, count($komposisi)); $i++)
                                    @php $row = $komposisi[$i] ?? []; @endphp
                                    <tr>
                                        <td>
                                            <select name="komposisi[{{ $i }}][jenis]" class="form-select form-select-sm">
                                                <option value="">— pilih jenis —</option>
                                                @foreach(\App\Services\Evaluasi\PaketNaskahService::JENIS_LABEL as $jenis => $label)
                                                    <option value="{{ $jenis }}" {{ ($row['jenis'] ?? '') === $jenis ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="komposisi[{{ $i }}][jumlah]" min="1" max="100" class="form-control form-control-sm" value="{{ $row['jumlah'] ?? '' }}"></td>
                                        <td><input type="number" step="0.01" name="komposisi[{{ $i }}][bobot]" min="0" max="100" class="form-control form-control-sm" value="{{ $row['bobot'] ?? '' }}"></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-sm btn-primary"><i class="ri-save-line me-1"></i>Simpan Komposisi</button>
                </form>
                <form method="POST" action="{{ route('user.paket-soal.komposisi.generate', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                      onsubmit="return confirm('Isi ulang butir dari komposisi? Butir paket saat ini akan diganti.');">
                    @csrf
                    <button class="btn btn-sm btn-soft-success"><i class="ri-magic-line me-1"></i>Generate Butir dari Komposisi</button>
                    <span class="small text-muted ms-2">Mendukung pilihan ganda, kompleks, benar/salah, menjodohkan, isian, dan uraian/esai.</span>
                </form>
            @elseif(!empty($komposisi))
                <ul class="list-unstyled mb-0 small">
                    @foreach($komposisi as $row)
                        <li class="d-flex justify-content-between border-bottom py-1">
                            <span>{{ \App\Services\Evaluasi\PaketNaskahService::JENIS_LABEL[$row['jenis']] ?? $row['jenis'] }}</span>
                            <span class="text-muted">{{ $row['jumlah'] }} butir · bobot {{ number_format((float) $row['bobot'], 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted small mb-0">Komposisi naskah belum disusun.</p>
            @endif
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
                        <th>Jenis</th>
                        <th>TP</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paket->items->sortBy('urutan')->values() as $idx => $item)
                    @php $konten = $item->kontenFinal(); @endphp
                    <tr>
                        <td>{{ $konten['nomor'] ?? $idx + 1 }}</td>
                        <td>{{ Str::limit(strip_tags($konten['pertanyaan'] ?? '-'), 100) }}</td>
                        <td>{{ \App\Services\Evaluasi\PaketNaskahService::JENIS_LABEL[$konten['jenis'] ?? ''] ?? strtoupper($konten['jenis'] ?? '-') }}</td>
                        <td>{{ $konten['tp_id'] ? Str::limit($konten['tp_id'], 40) : '-' }}</td>
                        <td>{{ number_format((float) ($konten['bobot'] ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection