@extends('waka.master')
@section('title') Penilaian Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi.index') }}">Supervisi</a> @endslot
        @slot('li_2') <a href="{{ route('waka.supervisi.show', $supervisi->id) }}">Detail</a> @endslot
        @slot('title') Penilaian Supervisi @endslot
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

    {{-- Identitas + pilih instrumen --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="ri-file-excel-2-line text-primary me-1"></i> Penilaian Supervisi Pembelajaran</h5>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('waka.supervisi.cetak', $supervisi->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="ri-printer-line me-1"></i>Cetak Instrumen
                </a>
                <a href="{{ route('waka.supervisi.show', $supervisi->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3">
                    <div class="text-muted">Nama Guru</div>
                    <div class="fw-semibold">{{ $supervisi->gtk?->user?->name ?? $supervisi->gtk_name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Mata Pelajaran</div>
                    <div class="fw-semibold">{{ $supervisi->mata_pelajaran ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Tahun Ajaran / Semester</div>
                    <div class="fw-semibold">
                        {{ $supervisi->academicYear?->name ?? '-' }}
                        ({{ ucfirst(['1' => 'Ganjil', '2' => 'Genap'][$supervisi->semester] ?? $supervisi->semester) }})
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Tanggal / Jenis</div>
                    <div class="fw-semibold">
                        {{ $supervisi->tanggal_supervisi?->format('d/m/Y') ?? '-' }}
                        &bull; {{ \Illuminate\Support\Str::headline($supervisi->jenis_supervisi) }}
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('waka.supervisi.penilaian', $supervisi->id) }}" class="row g-2 align-items-end mt-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted mb-1">Instrumen Penilaian</label>
                    <select name="instrumen_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Pilih Instrumen --</option>
                        @foreach($instrumenOptions as $opt)
                            <option value="{{ $opt->id }}" {{ $instrumen && $instrumen->id === $opt->id ? 'selected' : '' }}>
                                {{ $opt->nama }}{{ $opt->school_id ? '' : ' (bawaan)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if(! $instrumen)
        <x-empty-state icon="https://cdn.lordicon.com/msoeawqm.json"
            title="Pilih instrumen terlebih dahulu"
            message="Tentukan instrumen penilaian di atas untuk mulai mengisi skor."
        />
    @else
        <form method="POST" action="{{ route('waka.supervisi.penilaian.store', $supervisi->id) }}" id="penilaianForm">
            @csrf
            <input type="hidden" name="instrumen_id" value="{{ $instrumen->id }}">

            @foreach($instrumen->bagian as $bagian)
                <div class="card mb-3">
                    <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <span class="badge bg-primary-subtle text-primary me-1">{{ $bagian->kode ?: '-' }}</span>
                            {{ $bagian->nama }}
                        </h6>
                        <span class="badge bg-light text-muted" id="bagianTotal-{{ $bagian->id }}">Subtotal: 0</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px" class="text-center">No</th>
                                        <th>Aspek yang Dinilai</th>
                                        <th style="width:110px" class="text-center">Kondisi</th>
                                        <th style="width:150px" class="text-center">Skor (A–D)</th>
                                        <th style="width:220px">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bagian->items as $idx => $item)
                                        @php $p = $penilaian[$item->id] ?? null; @endphp
                                        <tr data-bagian="{{ $bagian->id }}">
                                            <td class="text-center">{{ $idx + 1 }}</td>
                                            <td>{{ $item->teks }}</td>
                                            <td class="text-center">
                                                @if($item->butuh_kondisi)
                                                    <select name="kondisi[{{ $item->id }}]" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        <option value="ya" {{ $p && $p->kondisi === true ? 'selected' : '' }}>Ya</option>
                                                        <option value="tidak" {{ $p && $p->kondisi === false ? 'selected' : '' }}>Tidak</option>
                                                    </select>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <select name="skor[{{ $item->id }}]" class="form-select form-select-sm skor-select"
                                                        data-bagian="{{ $bagian->id }}" data-bobot="{{ (float) $item->bobot }}">
                                                    <option value="">—</option>
                                                    <option value="4" {{ $p && (int) $p->skor === 4 ? 'selected' : '' }}>A (4)</option>
                                                    <option value="3" {{ $p && (int) $p->skor === 3 ? 'selected' : '' }}>B (3)</option>
                                                    <option value="2" {{ $p && (int) $p->skor === 2 ? 'selected' : '' }}>C (2)</option>
                                                    <option value="1" {{ $p && (int) $p->skor === 1 ? 'selected' : '' }}>D (1)</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="item_catatan[{{ $item->id }}]" class="form-control form-control-sm" maxlength="500" value="{{ $p->catatan ?? '' }}"></td>
                                        </tr>

                                        @foreach($item->subItems as $subIdx => $sub)
                                            @php $ps = $penilaian[$sub->id] ?? null; @endphp
                                            <tr data-bagian="{{ $bagian->id }}">
                                                <td class="text-center text-muted">{{ chr(97 + $subIdx) }}.</td>
                                                <td class="ps-4">{{ $sub->teks }}</td>
                                                <td class="text-center">
                                                    @if($sub->butuh_kondisi)
                                                        <select name="kondisi[{{ $sub->id }}]" class="form-select form-select-sm">
                                                            <option value="">—</option>
                                                            <option value="ya" {{ $ps && $ps->kondisi === true ? 'selected' : '' }}>Ya</option>
                                                            <option value="tidak" {{ $ps && $ps->kondisi === false ? 'selected' : '' }}>Tidak</option>
                                                        </select>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <select name="skor[{{ $sub->id }}]" class="form-select form-select-sm skor-select"
                                                            data-bagian="{{ $bagian->id }}" data-bobot="{{ (float) $sub->bobot }}">
                                                        <option value="">—</option>
                                                        <option value="4" {{ $ps && (int) $ps->skor === 4 ? 'selected' : '' }}>A (4)</option>
                                                        <option value="3" {{ $ps && (int) $ps->skor === 3 ? 'selected' : '' }}>B (3)</option>
                                                        <option value="2" {{ $ps && (int) $ps->skor === 2 ? 'selected' : '' }}>C (2)</option>
                                                        <option value="1" {{ $ps && (int) $ps->skor === 1 ? 'selected' : '' }}>D (1)</option>
                                                    </select>
                                                </td>
                                                <td><input type="text" name="item_catatan[{{ $sub->id }}]" class="form-control form-control-sm" maxlength="500" value="{{ $ps->catatan ?? '' }}"></td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Catatan Penilai</h6></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Catatan Temuan</label>
                                <textarea name="catatan_temuan" class="form-control" rows="3">{{ old('catatan_temuan', $supervisi->catatan_temuan) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rekomendasi</label>
                                <textarea name="rekomendasi" class="form-control" rows="3">{{ old('rekomendasi', $supervisi->rekomendasi) }}</textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Tindak Lanjut</label>
                                <textarea name="tindak_lanjut" class="form-control" rows="2">{{ old('tindak_lanjut', $supervisi->tindak_lanjut) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0">Ringkasan Nilai</h6></div>
                        <div class="card-body small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Jumlah Skor</span>
                                <span class="fw-semibold" id="sumSkor">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Skor Maksimum</span>
                                <span class="fw-semibold" id="sumMaks">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Nilai Akhir</span>
                                <span class="fw-bold fs-5" id="nilaiAkhir">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Ketercapaian</span>
                                <span class="badge bg-secondary-subtle text-secondary" id="ketercapaianBadge">—</span>
                            </div>

                            <div class="text-muted mb-2" style="font-size:.75rem;">
                                86–100 Amat baik &bull; 70–85 Baik &bull; 55–69 Cukup &bull; &lt;55 Kurang
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-save-line me-1"></i>Simpan Penilaian
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @push('scripts')
        <script>
            function hitungNilai() {
                var perBagian = {};
                var total = 0, maks = 0;

                document.querySelectorAll('.skor-select').forEach(function (sel) {
                    var bag = sel.dataset.bagian;
                    var bobot = parseFloat(sel.dataset.bobot || '0');
                    var val = parseInt(sel.value || '0', 10);

                    perBagian[bag] = perBagian[bag] || 0;

                    if (val > 0 && bobot > 0) {
                        total += val;
                        maks += bobot;
                        perBagian[bag] += val;
                    }
                });

                Object.keys(perBagian).forEach(function (bag) {
                    var el = document.getElementById('bagianTotal-' + bag);
                    if (el) el.textContent = 'Subtotal: ' + perBagian[bag];
                });

                var nilai = maks > 0 ? (total / maks * 100) : 0;
                var label = nilai >= 86 ? 'Amat baik' : (nilai >= 70 ? 'Baik' : (nilai >= 55 ? 'Cukup' : (maks > 0 ? 'Kurang' : '—')));
                var cls = nilai >= 86 ? 'bg-success-subtle text-success' : (nilai >= 70 ? 'bg-primary-subtle text-primary' : (nilai >= 55 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'));

                document.getElementById('sumSkor').textContent = total;
                document.getElementById('sumMaks').textContent = maks;
                document.getElementById('nilaiAkhir').textContent = maks > 0 ? nilai.toFixed(2) : '0';
                var badge = document.getElementById('ketercapaianBadge');
                badge.textContent = maks > 0 ? label : '—';
                badge.className = 'badge ' + (maks > 0 ? cls : 'bg-secondary-subtle text-secondary');
            }

            document.querySelectorAll('.skor-select').forEach(function (sel) {
                sel.addEventListener('change', hitungNilai);
            });

            document.addEventListener('DOMContentLoaded', hitungNilai);
        </script>
        @endpush
    @endif
@endsection
