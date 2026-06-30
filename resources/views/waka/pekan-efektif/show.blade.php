@extends('waka.master')
@section('title') Detail Pekan Efektif @endsection

@section('css')
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pekan Efektif @endslot
        @slot('li_2') <a href="{{ route('waka.pekan-efektif.index') }}">Daftar</a> @endslot
        @slot('title') Pekan {{ $pekan->minggu_ke }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Pekan</h5>
                    <div>
                        <a href="{{ route('waka.pekan-efektif.edit', $pekan->id) }}" class="btn btn-warning btn-sm"><i class="ri-edit-line"></i> Edit</a>
                        <a href="{{ route('waka.pekan-efektif.index') }}" class="btn btn-secondary btn-sm"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:200px">Tahun Ajaran</th><td>{{ $pekan->academicYear?->name ?? '-' }}</td></tr>
                        <tr><th>Semester</th><td>{{ $pekan->semester == 1 ? 'Ganjil' : 'Genap' }}</td></tr>
                        <tr><th>Minggu Ke</th><td>{{ $pekan->minggu_ke }}</td></tr>
                        <tr><th>Periode</th><td>{{ $pekan->tanggal_mulai?->format('d F Y') }} s/d {{ $pekan->tanggal_selesai?->format('d F Y') }}</td></tr>
                        <tr><th>Jenis</th>
                            <td>
                                @php $map = ['efektif'=>'success','libur'=>'danger','ujian'=>'warning','kegiatan_sekolah'=>'info','lainnya'=>'secondary']; @endphp
                                <span class="badge bg-{{ $map[$pekan->jenis] ?? 'secondary' }}">{{ \Illuminate\Support\Str::headline(str_replace('_',' ',$pekan->jenis)) }}</span>
                            </td>
                        </tr>
                        @if($pekan->keterangan)
                            <tr><th>Keterangan</th><td>{{ $pekan->keterangan }}</td></tr>
                        @endif
                        <tr><th>Dibuat</th><td>{{ $pekan->created_at?->format('d/m/Y H:i') }}</td></tr>
                        <tr><th>Diperbarui</th><td>{{ $pekan->updated_at?->format('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection