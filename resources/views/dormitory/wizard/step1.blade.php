@extends('layouts.master')
@section('title') Wizard Izin Kepulangan — Langkah 1 @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Izin Kepulangan</a> @endslot
        @slot('title') Pilih Santri @endslot
    @endcomponent

    {{-- Wizard Progress --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center position-relative">
                <div class="text-center flex-fill" style="z-index:2">
                    <div class="avatar-sm mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div>
                    <div class="mt-1 small fw-semibold text-primary">1. Pilih Santri</div>
                </div>
                <div class="progress flex-fill position-absolute" style="height:2px;top:18px;left:0;right:0">
                    <div class="progress-bar bg-primary" style="width:25%"></div>
                </div>
                <div class="text-center flex-fill" style="z-index:2">
                    <div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">2</div>
                    <div class="mt-1 small text-muted">2. Detail Izin</div>
                </div>
                <div class="text-center flex-fill" style="z-index:2">
                    <div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">3</div>
                    <div class="mt-1 small text-muted">3. Waktu & Penjemput</div>
                </div>
                <div class="text-center flex-fill" style="z-index:2">
                    <div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">4</div>
                    <div class="mt-1 small text-muted">4. Konfirmasi</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-user-search-line me-2 text-primary"></i>Pilih Santri yang Akan Izin</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="search-santri" class="form-control" placeholder="Cari nama / NISN..." autofocus>
            </div>

            <form method="GET" action="{{ route('user.asrama.permit-wizard.step2', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" id="selectForm">
                <input type="hidden" name="student_id" id="student_id" required>
                <div class="row g-2" id="santri-list">
                    @forelse($residents as $r)
                        @php $s = $r->student; @endphp
                        <div class="col-md-6 col-lg-4 santri-item" data-name="{{ strtolower($s->name ?? '') }}" data-nisn="{{ $s->nisn ?? '' }}">
                            <div class="card border h-100 mb-0">
                                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $s->name ?? '(Tanpa Nama)' }}</div>
                                        <div class="small text-muted">
                                            {{ $s->nisn ?? '-' }}
                                            @if($r->room) · {{ $r->room->name }} @endif
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary pick-btn"
                                            data-id="{{ $r->student_id }}" data-name="{{ $s->name }}">
                                        <i class="ri-arrow-right-line"></i> Pilih
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0"><i class="ri-alert-line me-2"></i>Belum ada penghuni aktif di asrama ini.</div>
                        </div>
                    @endforelse
                </div>
            </form>
        </div>
    </div>

    @push('script')
        <script>
            const searchInput = document.getElementById('search-santri');
            searchInput?.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('.santri-item').forEach(el => {
                    const match = el.dataset.name.includes(q) || el.dataset.nisn.includes(q);
                    el.style.display = match ? '' : 'none';
                });
            });

            document.querySelectorAll('.pick-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('student_id').value = this.dataset.id;
                    document.getElementById('selectForm').submit();
                });
            });
        </script>
    @endpush
@endsection
