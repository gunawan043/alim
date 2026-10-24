@extends('layouts.master')
@section('title') Pusat Cetak Asesmen @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('title') Pusat Cetak @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">Pusat Cetak Asesmen</h5>
                <p class="text-muted mb-0">Naskah, kartu soal, validasi, dan LJK dalam satu tempat.</p>
            </div>
            <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paket</th>
                            <th>Mapel</th>
                            <th>Status</th>
                            <th class="text-center">Soal</th>
                            <th class="text-center">Print Job</th>
                            <th class="text-end">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pakets as $paket)
                            <tr>
                                <td>
                                    <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="fw-semibold link-primary">{{ $paket->judul }}</a>
                                    <div class="small text-muted">{{ $paket->kode_paket }} · {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }}</div>
                                </td>
                                <td>{{ $paket->kisiKisi?->subject?->name ?? '-' }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ \App\Models\PaketSoal::WORKFLOW_OPTIONS[$paket->workflow_status] ?? $paket->workflow_status }}</span></td>
                                <td class="text-center">{{ $paket->items_count }}</td>
                                <td class="text-center">{{ $paket->print_jobs_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('user.paket-soal.naskah.cetak', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" target="_blank" class="btn btn-sm btn-soft-primary" title="Naskah"><i class="ri-file-text-line"></i> Naskah</a>
                                        <a href="{{ route('user.paket-soal.kartu-soal', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" target="_blank" class="btn btn-sm btn-soft-secondary" title="Kartu Soal"><i class="ri-file-list-3-line"></i> Kartu</a>
                                        <a href="{{ route('user.paket-soal.validasi-soal', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" target="_blank" class="btn btn-sm btn-soft-secondary" title="Validasi"><i class="ri-shield-check-line"></i> Validasi</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada paket final yang siap dicetak.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $pakets->links() }}</div>
        </div>
    </div>
@endsection
