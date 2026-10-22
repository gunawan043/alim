@extends('layouts.master')

@section('title', 'Detail Pelanggaran Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}">Pelanggaran</a> @endslot
        @slot('title') Detail Pelanggaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-start">
                    <div><h5 class="card-title mb-1">{{ $violation->violation_type }}</h5><div class="lang-muted">{{ $violation->violation_date?->format('d/m/Y') }} · {{ $violation->place ?: 'Tempat tidak dicatat' }}</div></div>
                    <span class="badge lang-badge bg-warning-subtle text-warning">{{ $violation->points }} poin</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="lang-muted">Santri</div><div class="fw-semibold">{{ $violation->student?->name ?? '-' }}</div></div>
                        <div class="col-md-3"><div class="lang-muted">Kelas</div><div>{{ $violation->class_name ?: '-' }}</div></div>
                        <div class="col-md-3"><div class="lang-muted">Asrama</div><div>{{ $violation->dormitory?->name ?? '-' }}</div></div>
                        <div class="col-md-3"><div class="lang-muted">Status</div><div>{{ $violation->status_label }}</div></div>
                        <div class="col-12"><div class="lang-muted">Deskripsi</div><div>{{ $violation->description ?: '-' }}</div></div>
                        <div class="col-12"><div class="lang-muted">Catatan</div><div>{{ $violation->notes ?: '-' }}</div></div>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <a href="{{ route('user.bahasa.violations.print-pledge', ['userId' => $userId, 'violation' => $violation->id]) }}" target="_blank" class="btn btn-sm btn-light"><i class="ri-file-paper-2-line me-1"></i>Berita Acara</a>
                    <a href="{{ route('user.bahasa.violations.print-coaching', ['userId' => $userId, 'violation' => $violation->id]) }}" target="_blank" class="btn btn-sm btn-light"><i class="ri-printer-line me-1"></i>Cetak Pembinaan</a>
                    @if($canManage)
                        <a href="{{ route('user.bahasa.violations.edit', ['userId' => $userId, 'violation' => $violation->id]) }}" class="btn btn-sm btn-soft-warning"><i class="ri-pencil-line me-1"></i>Edit</a>
                        <form method="POST" action="{{ route('user.bahasa.violations.destroy', ['userId' => $userId, 'violation' => $violation->id]) }}" onsubmit="return confirm('Hapus pelanggaran ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line me-1"></i>Hapus</button></form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Riwayat Pembinaan</h5></div>
                <div class="card-body">
                    @forelse($violation->coachings as $coaching)
                        <div class="border-start border-3 border-success ps-3 mb-3">
                            <div class="fw-semibold">{{ $coaching->category ?: 'Pembinaan' }} · {{ $coaching->coaching_date?->format('d/m/Y') ?? '-' }}</div>
                            <div class="lang-muted">Pembina: {{ $coaching->coach?->name ?? '-' }}</div>
                            <div class="mt-1">{{ $coaching->action ?: '-' }}</div>
                            @if($coaching->notes)<div class="lang-muted mt-1">{{ $coaching->notes }}</div>@endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada pembinaan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if($canRecord)
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Tambah Pembinaan</h5></div>
                <form method="POST" action="{{ route('user.bahasa.violations.coachings.store', ['userId' => $userId, 'violation' => $violation->id]) }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-2"><label class="form-label small mb-1">Kategori</label><input name="category" class="form-control" placeholder="Pembinaan / peringatan"></div>
                        <div class="mb-2"><label class="form-label small mb-1">Tindakan</label><textarea name="action" rows="2" class="form-control" placeholder="Bentuk pembinaan"></textarea></div>
                        <div class="mb-2"><label class="form-label small mb-1">Tanggal</label><input type="date" name="coaching_date" value="{{ now()->toDateString() }}" class="form-control"></div>
                        <div class="mb-2"><label class="form-label small mb-1">Catatan Perkembangan</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                        <div class="mb-2"><label class="form-label small mb-1">Tindak Lanjut</label><textarea name="follow_up" rows="2" class="form-control"></textarea></div>
                        <div class="form-check"><input type="hidden" name="pledge_signed" value="0"><input type="checkbox" class="form-check-input" name="pledge_signed" value="1" id="pledgeSigned"><label class="form-check-label" for="pledgeSigned">Berita acara sudah ditandatangani</label></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-success w-100"><i class="ri-check-line me-1"></i>Simpan Pembinaan</button></div>
                </form>
            </div>
        </div>
        @endif
    </div>
@endsection
