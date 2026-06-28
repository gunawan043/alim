@extends('layouts.master')
@section('title') Paket Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Paket Soal @endslot
        @slot('title') Daftar Paket Soal @endslot
    @endcomponent

    <div class="mb-3">
        <form class="d-flex gap-2">
            <select name="jenis_ujian" class="form-control form-control-sm" style="max-width:200px">
                <option value="">Semua Jenis</option>
                @foreach(['sts','sas','ulangan_harian','try_out','latihan'] as $j)
                    <option value="{{ $j }}" {{ request('jenis_ujian') === $j ? 'selected':'' }}>{{ ucfirst(str_replace('_', ' ', $j)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kode</th>
                                    <th>Jenis</th>
                                    <th>Soal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pakets as $pkt)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.paket-soal.show', $pkt->id) }}">{{ $pkt->judul }}</a>
                                        <br><small class="text-muted">{{ $pkt->kisiKisi->subject->name ?? '-' }} · {{ $pkt->kisiKisi->jenis_ujian ?? '-' }}</small>
                                    </td>
                                    <td>{{ $pkt->kode_paket }}</td>
                                    <td><span class="badge bg-info">{{ str_replace('_', ' ', $pkt->kisiKisi->jenis_ujian ?? '') }}</span></td>
                                    <td>{{ $pkt->jumlah_soal_aktual }}</td>
                                    <td>
                                        @if($pkt->is_published)
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$pkt->is_published && $pkt->jumlah_soal_aktual > 0)
                                        <form action="{{ route('user.paket-soal.publish', $pkt->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" onclick="return confirm('Publish paket ini?')">
                                                <i class="ri-check-line"></i> Publish
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ route('user.paket-soal.reroll', $pkt->id) }}" class="btn btn-sm btn-outline-danger">
                                            <i class="ri-refresh-line"></i>
                                        </a>
                                        <form action="{{ route('user.paket-soal.destroy', $pkt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">Belum ada paket soal.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $pakets->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection