@extends('waka.master')
@section('title') Surat Keluar @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('title') Surat Keluar @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Daftar Surat Keluar</h4>
                <p class="text-muted small mb-0">Total: {{ $suratKeluarList->total() }} surat</p>
            </div>
            <a href="{{ route('waka.surat-keluar.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> Tambah Surat Keluar
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover table-freeze align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>No. Surat</th>
                        <th>Tujuan</th>
                        <th>Perihal</th>
                        <th>Tgl. Surat</th>
                        <th>Tgl. Kirim</th>
                        <th>Sifat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratKeluarList as $i => $surat)
                        <tr>
                            <td>{{ $suratKeluarList->firstItem() + $i }}</td>
                            <td><strong>{{ $surat->nomor_surat }}</strong></td>
                            <td>{{ $surat->tujuan }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($surat->perihal, 50) }}</td>
                            <td>{{ $surat->tanggal_surat?->format('d/m/Y') }}</td>
                            <td>{{ $surat->tanggal_kirim?->format('d/m/Y') }}</td>
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
                                    <a href="{{ route('waka.surat-keluar.show', $surat->id) }}" class="btn btn-outline-info"><i class="ri-eye-line"></i></a>
                                    <a href="{{ route('waka.surat-keluar.edit', $surat->id) }}" class="btn btn-outline-warning"><i class="ri-edit-line"></i></a>
                                    <form action="{{ route('waka.surat-keluar.destroy', $surat->id) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-delete"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada surat keluar.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $suratKeluarList->links() }}</div>
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
                Swal.fire({ title: 'Hapus surat keluar?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus' })
                    .then((r) => { if (r.isConfirmed) form.submit(); });
            });
        });
    </script>
@endsection