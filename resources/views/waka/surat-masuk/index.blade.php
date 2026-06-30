@extends('waka.master')
@section('title') Surat Masuk @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .table-container { position: relative; width: 100%; overflow-x: auto; }
        .table-freeze th, .table-freeze td { white-space: normal; vertical-align: middle; padding: 10px 14px; }
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('title') Surat Masuk @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Daftar Surat Masuk</h4>
                <p class="text-muted small mb-0">Total: {{ $suratMasukList->total() }} surat</p>
            </div>
            <a href="{{ route('waka.surat-masuk.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> Tambah Surat Masuk
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table table-hover table-freeze align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>No. Surat</th>
                            <th>Pengirim</th>
                            <th>Perihal</th>
                            <th>Tgl. Surat</th>
                            <th>Tgl. Terima</th>
                            <th>Sifat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suratMasukList as $i => $surat)
                            <tr>
                                <td>{{ $suratMasukList->firstItem() + $i }}</td>
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->pengirim }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($surat->perihal, 50) }}</td>
                                <td>{{ $surat->tanggal_surat?->format('d/m/Y') }}</td>
                                <td>{{ $surat->tanggal_terima?->format('d/m/Y') }}</td>
                                <td>
                                    @if($surat->sifat === 'rahasia')
                                        <span class="badge badge-soft-warning">Rahasia</span>
                                    @elseif($surat->sifat === 'penting')
                                        <span class="badge bg-danger">Penting</span>
                                    @else
                                        <span class="badge badge-soft-success">Biasa</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('waka.surat-masuk.show', $surat->id) }}" class="btn btn-outline-info">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('waka.surat-masuk.edit', $surat->id) }}" class="btn btn-outline-warning">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <form action="{{ route('waka.surat-masuk.destroy', $surat->id) }}" method="POST" class="d-inline form-delete">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada surat masuk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $suratMasukList->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                Swal.fire({
                    title: 'Hapus surat masuk ini?',
                    text: 'Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                }).then((result) => { if (result.isConfirmed) form.submit(); });
            });
        });
    </script>
@endsection