@extends('layouts.master')
@section('title') Notifikasi Dokumen ISO @endsection

@section('css') @endsection

@php $userId = $userId ?? auth()->id(); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('title') Notifikasi Dokumen ISO @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-notification-3-line text-primary me-1"></i>
                        Pengaturan Notifikasi — {{ $user->name }}
                    </h5>
                    <p class="text-muted small mt-1">
                        Anda akan menerima email setiap kali ada perubahan dokumen ISO di divisi yang Anda subscribe.
                    </p>
                </div>
                <div class="card-body">

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <i class="ri-check-line me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Divisi / Satuan Kerja</th>
                                <th>Kode</th>
                                <th class="text-center" style="width:100px">Status</th>
                                <th class="text-center" style="width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($divisiList as $i => $d)
                            <tr>
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $d['nama'] }}</td>
                                <td><code class="small">{{ $d['kode'] }}</code></td>
                                <td class="text-center">
                                    @if($d['subscribed'])
                                        <span class="badge bg-success-subtle text-success" style="font-size:0.7rem">
                                            <i class="ri-check-line me-1"></i> Subscribe
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem">
                                            <i class="ri-close-line me-1"></i> Tidak
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($d['subscribed'])
                                        <form action="{{ route('dokumen-iso.unsubscribe', [$userId, $d['id']]) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-danger btn-sm"
                                                    title="Unsubscribe">
                                                <i class="ri-notification-off-line"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('dokumen-iso.subscribe', [$userId, $d['id']]) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-success btn-sm"
                                                    title="Subscribe">
                                                <i class="ri-notification-3-line"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Info panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="ri-information-line me-1"></i> Tentang Notifikasi</h6>
                    <ul class="ps-3 text-muted small">
                        <li class="mb-2">
                            <strong>Super Admin</strong> otomatis tersubscribe ke semua divisi.
                        </li>
                        <li class="mb-2">
                            User biasa tersubscribe sesuai <strong>WorkUnit / Divisi</strong> unit kerjanya.
                        </li>
                        <li class="mb-2">
                            Perubahan (tambah, edit, hapus) dokumen ISO di divisi Anda akan memicu email.
                        </li>
                        <li>
                            Email berisi <strong>kode dokumen, nama, revisi, tanggal berlaku</strong>,
                            dan tombol untuk melihat daftar lengkap.
                        </li>
                    </ul>
                    <hr>
                    <a href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}"
                       class="btn btn-soft-secondary btn-sm w-100">
                        <i class="ri-arrow-left-line me-1"></i> Kembali ke Dokumen ISO
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script') @endsection