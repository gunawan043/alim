@extends('layouts.master')

@section('title', 'Notifikasi Wali — Pelanggaran Bahasa')

@section('content')
    @php
        $userId = $userId ?? request()->route('userId') ?? auth()->id();

        $totalStudents = $grouped->count();
        $withContact = $grouped->where('has_contact', true)->count();
        $maxPoints = (int) ($grouped->max('points') ?? 0);
    @endphp

    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Pelanggaran @endslot
        @slot('title') Notifikasi Wali Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="lang-hero mb-3">
        <h3 class="mb-1">Notifikasi Wali Santri</h3>
        <p>
            Rangkuman pelanggaran bahasa per santri pada rentang tanggal terpilih,
            lalu kirimkan pemberitahuan ke wali lewat kanal notifikasi yang sama dengan modul lain.
        </p>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Santri Melanggar', $totalStudents, 'Santri dengan catatan pada rentang ini'],
            ['Punya Kontak Wali', $withContact, 'Bisa menerima notifikasi'],
            ['Total Pelanggaran', $totalViolations, 'Seluruh catatan pada rentang ini'],
            ['Poin Tertinggi', $maxPoints, 'Akumulasi poin satu santri'],
        ] as [$label, $value, $hint])
            <div class="col-xl-3 col-md-6">
                <div class="lang-stat">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ number_format($value) }}</div>
                    <div class="hint">{{ $hint }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('user.bahasa.guardian-notifications.index', ['userId' => $userId]) }}"
                class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Dari tanggal</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Sampai tanggal</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Cari santri</label>
                    <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm"
                        placeholder="Nama atau NIS…">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="ri-filter-3-line me-1"></i>Terapkan
                    </button>
                    <a href="{{ route('user.bahasa.guardian-notifications.index', ['userId' => $userId]) }}"
                        class="btn btn-sm btn-light" title="Reset filter">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('user.bahasa.guardian-notifications.store', ['userId' => $userId]) }}" id="notifyForm">
        @csrf
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-notification-3-line text-success me-1"></i>Santri dengan Pelanggaran</h5>
                    <p class="text-muted mb-0 small">
                        {{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }}
                        –
                        {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#notifyModal" id="openNotifyModal">
                    <i class="ri-send-plane-line me-1"></i>Kirim Notifikasi
                    (<span id="selectedCount">0</span>)
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 44px">
                                <input type="checkbox" class="form-check-input" id="checkAllStudents" title="Pilih semua">
                            </th>
                            <th>Santri</th>
                            <th class="text-center" style="width: 110px">Pelanggaran</th>
                            <th class="text-center" style="width: 90px">Poin</th>
                            <th>Jenis</th>
                            <th class="text-center" style="width: 130px">Terakhir</th>
                            <th class="text-center" style="width: 120px">Kontak Wali</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grouped as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input student-check"
                                        name="student_ids[]" value="{{ $item['student']->id }}"
                                        aria-label="Pilih {{ $item['student']->name }}">
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $item['student']->name }}</div>
                                    <div class="lang-muted">
                                        NIS {{ $item['student']->nis ?: '—' }}
                                        @if($item['student']->guardian_name)
                                            · Wali: {{ $item['student']->guardian_name }}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge lang-badge bg-danger-subtle text-danger">{{ $item['total'] }}×</span>
                                </td>
                                <td class="text-center fw-semibold">{{ $item['points'] }}</td>
                                <td>
                                    @forelse($item['types'] as $type)
                                        <span class="badge lang-badge bg-light text-dark me-1 mb-1">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                                <td class="text-center small">
                                    {{ $item['last'] ? \Carbon\Carbon::parse($item['last'])->translatedFormat('d M Y') : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($item['has_contact'])
                                        <span class="badge lang-badge bg-success-subtle text-success">Tersedia</span>
                                    @else
                                        <span class="badge lang-badge bg-secondary-subtle text-secondary">Tidak ada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" title="Tidak ada pelanggaran"
                                message="Tidak ditemukan catatan pelanggaran bahasa pada rentang tanggal ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light-subtle">
                <p class="text-muted mb-0 small">
                    <i class="ri-information-line me-1"></i>
                    Santri tanpa nomor kontak wali tetap bisa dipilih, namun notifikasi hanya terkirim ke wali yang memiliki akun.
                </p>
            </div>
        </div>

        <div class="modal fade" id="notifyModal" tabindex="-1" aria-labelledby="notifyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notifyModalLabel">
                            <i class="ri-send-plane-line me-1"></i>Kirim Notifikasi ke Wali
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Notifikasi akan dikirim untuk <strong id="modalSelectedCount">0</strong> santri terpilih.
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="notifyTitle">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="notifyTitle" class="form-control" maxlength="120"
                                value="{{ old('title', 'Pemberitahuan Pelanggaran Bahasa') }}" required>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="notifyMessage">Pesan <span class="text-danger">*</span></label>
                            <textarea name="message" id="notifyMessage" class="form-control" rows="4" maxlength="500" required>{{ old('message', 'Ananda tercatat melakukan pelanggaran aturan berbahasa. Mohon bimbingan dan kerja sama Bapak/Ibu di rumah.') }}</textarea>
                            <div class="form-text">Maksimal 500 karakter.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="notifySubmit">
                            <i class="ri-send-plane-line me-1"></i>Kirim
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const master = document.getElementById('checkAllStudents');
            const counter = document.getElementById('selectedCount');
            const modalCounter = document.getElementById('modalSelectedCount');
            const submitBtn = document.getElementById('notifySubmit');
            const openBtn = document.getElementById('openNotifyModal');
            const form = document.getElementById('notifyForm');
            const boxes = () => Array.from(document.querySelectorAll('.student-check'));

            function refresh() {
                const all = boxes();
                const selected = all.filter(box => box.checked).length;

                if (counter) counter.textContent = selected;
                if (modalCounter) modalCounter.textContent = selected;
                if (submitBtn) submitBtn.disabled = selected === 0;

                if (master) {
                    master.checked = selected > 0 && selected === all.length;
                    master.indeterminate = selected > 0 && selected < all.length;
                }
            }

            if (master) {
                master.addEventListener('change', function () {
                    boxes().forEach(box => { box.checked = master.checked; });
                    refresh();
                });
            }

            boxes().forEach(box => box.addEventListener('change', refresh));

            if (openBtn) {
                openBtn.addEventListener('click', function (event) {
                    if (boxes().filter(box => box.checked).length === 0) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                });
            }

            if (form) form.addEventListener('submit', function (event) {
                if (boxes().filter(box => box.checked).length === 0) {
                    event.preventDefault();
                }
            });

            refresh();
        });
    </script>
@endpush
