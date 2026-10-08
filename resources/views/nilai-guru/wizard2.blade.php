@extends('layouts.master')
@section('title', 'Jurnal Pembelajaran')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $adminBook = $book['adminBook'];
        $weekMap = $plan['weeks']->keyBy('minggu_ke');
        $pekanJson = $plan['weeks']->map(fn ($w) => [
            'minggu_ke' => $w->minggu_ke,
            'mulai' => $w->tanggal_mulai?->toDateString(),
            'selesai' => $w->tanggal_selesai?->toDateString(),
            'jenis' => $w->jenis,
        ])->values();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Buku Admin Guru @endslot
        @slot('li_3') Jurnal Pembelajaran @endslot
        @slot('title') Jurnal Pembelajaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Buku + Ringkasan Realisasi --}}
    <div class="card border-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-md-auto">
                    <p class="mb-n1 btn btn-primary btn-sm" style="font-size: 10px;"><i class="ri-book-2-line me-1"></i>{{ $adminBook->subject->name ?? '-' }}</p>
                    <p class="mb-n1 btn btn-secondary btn-sm"><i class="ri-team-line me-1"></i>{{ $adminBook->studyGroup->name }}</p>
                    <p class="mb-n1 btn btn-dark btn-sm"><i class="ri-calendar-line me-1"></i>{{ ucfirst($adminBook->semester) }}</p>
                    <p class="mb-n1 btn btn-warning btn-sm"><i class="ri-government-line me-1"></i>{{ $adminBook->academicYear->name ?? '-' }}</p>
                </div>
                <div class="col-md d-flex justify-content-md-end align-items-center gap-2">
                    <a href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId, 'academic_year_id' => $adminBook->academic_year_id, 'semester' => $adminBook->semester]) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-line-chart-line me-1"></i> Realisasi
                    </a>
                    <select class="form-select form-select-sm" style="width:auto;" onchange="location.href=this.value">
                        @foreach($books as $b)
                            <option value="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $b->id]) }}" {{ $b->id == $adminBook->id ? 'selected' : '' }}>
                                {{ $b->subject->name ?? '-' }} | {{ $b->studyGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 fs-13">Realisasi TP</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px">
                            <div class="progress-bar {{ $progress >= 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ min(100, $progress) }}%"></div>
                        </div>
                        <span class="small fw-semibold">{{ $realizedCount }}/{{ $plannedTpCount }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 fs-13">Jurnal Pertemuan</p>
                    <h4 class="fs-18 fw-semibold mb-0 text-info">{{ $journals->count() }} <span class="fs-13 text-muted">pertemuan</span></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 fs-13">Asesmen Formatif</p>
                    <h4 class="fs-18 fw-semibold mb-0 {{ $formatifCount > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $formatifCount > 0 ? $formatifCount.' siswa' : 'Belum ada' }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 fs-13">Nilai Sumatif</p>
                    <h4 class="fs-18 fw-semibold mb-0 {{ $sumatifCount > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $sumatifCount > 0 ? $sumatifCount.' siswa' : 'Belum ada' }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed py-2">
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('user.schools.guru-mapel.w1', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-outline-secondary">Presensi Siswa</a>
                        <a href="{{ route('user.schools.guru-mapel.w2', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-primary">Jurnal Pembelajaran</a>
                        <a href="{{ route('user.schools.guru-mapel.w3', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-outline-secondary">Nilai Sumatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w4', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-outline-secondary">Asesmen Formatif</a>
                        <a href="{{ route('user.schools.guru-mapel.w5', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-outline-secondary">Penghargaan Akademik</a>
                        <a href="{{ route('user.schools.guru-mapel.w6', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}" class="btn btn-outline-secondary">Catatan Guru</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('user.schools.guru-mapel.w2.store', ['userId' => $userId, 'adminBookId' => $adminBook->id]) }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Minggu ke-</label>
                                <input type="number" name="meeting_number" class="form-control"
                                       value="{{ old('meeting_number', ($journals->max('meeting_number') ?? 0) + 1) }}" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tanggal Pertemuan</label>
                                <input type="date" name="meeting_date" id="meeting_date" class="form-control"
                                       value="{{ old('meeting_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jam Masuk</label>
                                <input type="time" name="time_in" class="form-control" value="{{ old('time_in') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jam Pulang</label>
                                <input type="time" name="time_out" class="form-control" value="{{ old('time_out') }}">
                            </div>
                        </div>

                        {{-- Rencana: PROSEM → TP → RPM (dari data yang sama, tanpa input ulang) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Rencana Pekan (PROSEM)</label>
                                <select name="prosem_item_id" id="prosem_item" class="form-select" onchange="applyProsem()">
                                    <option value="">— Sesuai rencana PROSEM —</option>
                                    @foreach($plan['prosemItems'] as $item)
                                        <option value="{{ $item->id }}"
                                            data-tp="{{ $item->tujuan_pembelajaran_id }}"
                                            data-minggu="{{ $item->mulai_minggu_ke }}-{{ $item->selesai_minggu_ke }}"
                                            {{ old('prosem_item_id') == $item->id ? 'selected' : '' }}>
                                            Pekan {{ $item->mulai_minggu_ke }}{{ $item->selesai_minggu_ke > $item->mulai_minggu_ke ? '–'.$item->selesai_minggu_ke : '' }}
                                            · {{ $item->tujuanPembelajaran?->kode_tp }} {{ \Illuminate\Support\Str::limit($item->tujuanPembelajaran?->deskripsi, 60) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($plan['prosemItems']->isEmpty())
                                    <small class="text-muted">PROSEM belum tersusun — TP dapat dipilih langsung dari ATP.</small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">TP yang Diajarkan (realisasi)</label>
                                <select name="tujuan_pembelajaran_id" id="tp_select" class="form-select">
                                    <option value="">— Pilih TP —</option>
                                    @foreach($plan['tps'] as $tp)
                                        <option value="{{ $tp->id }}" {{ old('tujuan_pembelajaran_id') == $tp->id ? 'selected' : '' }}>
                                            {{ $tp->kode_tp }} — {{ \Illuminate\Support\Str::limit($tp->deskripsi, 70) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">RPM Digunakan</label>
                                <select name="perangkat_pembelajaran_id" class="form-select">
                                    <option value="">— Tanpa RPM —</option>
                                    @foreach($plan['rpmOptions'] as $rpm)
                                        <option value="{{ $rpm->id }}" {{ old('perangkat_pembelajaran_id') == $rpm->id ? 'selected' : '' }}>
                                            {{ \Illuminate\Support\Str::limit($rpm->judul, 55) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Materi Pembelajaran</label>
                            <textarea name="material" id="material" class="form-control" rows="4"
                                      placeholder="Tuliskan materi yang dibahas, metode mengajar, dan media yang digunakan...">{{ old('material') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="teacher_signature" name="teacher_signature"
                                       value="Terverifikasi" {{ old('teacher_signature') ? 'checked' : '' }}>
                                <label class="form-check-label" for="teacher_signature">
                                    Tanda tangan guru — saya sudah mengisi jurnal ini
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Jurnal
                            </button>
                        </div>
                    </form>

                    @if($journals->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="text-muted fw-semibold mb-3"><i class="ri-history-line me-1"></i> Riwayat Jurnal &amp; Realisasi TP</h6>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th style="width:70px">Minggu</th>
                                        <th style="width:90px">Tanggal</th>
                                        <th style="width:120px">Jam</th>
                                        <th>TP / Materi</th>
                                        <th style="width:170px">Rencana &amp; RPM</th>
                                        <th style="width:100px">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journals as $i => $j)
                                        <tr>
                                            <td class="text-center fw-bold text-muted">{{ $i + 1 }}</td>
                                            <td><strong>{{ $j->meeting_number }}</strong></td>
                                            <td>{{ $j->meeting_date->format('d/m/Y') }}</td>
                                            <td>{{ substr($j->time_in ?? '', 0, 5) ?: '-' }} - {{ substr($j->time_out ?? '', 0, 5) ?: '-' }}</td>
                                            <td>
                                                @if($j->tujuanPembelajaran)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $j->tujuanPembelajaran->kode_tp }}</span>
                                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($j->tujuanPembelajaran->deskripsi, 70) }}</div>
                                                @endif
                                                <div class="small">{{ $j->material ? \Illuminate\Support\Str::limit($j->material, 90) : '—' }}</div>
                                            </td>
                                            <td class="small text-muted">
                                                @if($j->prosemItem)
                                                    <div>Pekan {{ $j->prosemItem->mulai_minggu_ke }}{{ $j->prosemItem->selesai_minggu_ke > $j->prosemItem->mulai_minggu_ke ? '–'.$j->prosemItem->selesai_minggu_ke : '' }}</div>
                                                @endif
                                                @if($j->perangkat)
                                                    <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $j->perangkat->id]) }}" class="link-primary">
                                                        <i class="ri-booklet-line me-1"></i>{{ \Illuminate\Support\Str::limit($j->perangkat->judul, 40) }}
                                                    </a>
                                                @elseif(! $j->prosemItem)
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($j->teacher_signature)
                                                    <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Terverifikasi</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ri-edit-line me-1"></i>Draft</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ri-help-line me-1 text-secondary"></i> Petunjuk Pengisian</h6></div>
                <div class="card-body" style="font-size:.75rem;">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Pilih <strong>Rencana Pekan (PROSEM)</strong> — TP terisi otomatis; pilih <strong>RPM</strong> yang digunakan.</li>
                        <li class="mb-2">Tanggal dalam rentang pekan rencana akan dicocokkan otomatis (minggu libur tidak dihitung).</li>
                        <li class="mb-2">Isi <strong>Minggu ke-</strong>, <strong>Jam</strong>, dan <strong>materi</strong>; centang tanda tangan bila jurnal sudah final.</li>
                        <li>TP yang tercatat pada jurnal menjadi <strong>realisasi ATP</strong>; nilai formatif &amp; sumatif diisi melalui tombol asesmen di atas.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var PEKAN = @json($pekanJson);

        function applyProsem() {
            var select = document.getElementById('prosem_item');
            var opt = select.options[select.selectedIndex];
            if (! opt || ! opt.value) return;
            var tpId = opt.dataset.tp;
            if (tpId) {
                document.getElementById('tp_select').value = tpId;
            }
        }

        function suggestWeekByDate() {
            var dateEl = document.getElementById('meeting_date');
            if (! dateEl || ! dateEl.value) return;

            var match = PEKAN.find(function (p) { return dateEl.value >= p.mulai && dateEl.value <= p.selesai; });
            if (! match) return;

            var select = document.getElementById('prosem_item');
            if (select.value) return; // jangan menimpa pilihan guru

            var opt = Array.from(select.options).find(function (o) {
                if (! o.dataset.minggu) return false;
                var parts = o.dataset.minggu.split('-').map(Number);
                return match.minggu_ke >= parts[0] && match.minggu_ke <= parts[1];
            });

            if (opt) {
                opt.selected = true;
                applyProsem();
            }
        }

        document.getElementById('meeting_date')?.addEventListener('change', suggestWeekByDate);
        document.addEventListener('DOMContentLoaded', suggestWeekByDate);
    </script>
@endsection
