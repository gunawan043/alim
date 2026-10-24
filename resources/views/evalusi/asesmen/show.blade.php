@extends('layouts.master')
@section('title') {{ $kisi->judul }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('title') {{ $kisi->judul }} @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="mb-2">{{ $kisi->judul }}</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary-subtle text-primary"><i class="ri-book-2-line me-1"></i>{{ $kisi->subject?->name ?? '-' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-layout-grid-line me-1"></i>{{ $kisi->gradeLevel?->name ?? 'Semua tingkat' }}</span>
                        <span class="badge bg-info-subtle text-info"><i class="ri-calendar-line me-1"></i>{{ ucfirst($kisi->semester) }} · {{ $kisi->academicYear?->name ?? '-' }}</span>
                        <span class="badge bg-dark-subtle text-dark">{{ str_replace('_', ' ', ucfirst($kisi->jenis_ujian)) }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user.kisi-kisi-soal.cetak', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" target="_blank" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Cetak Kisi</a>
                    <a href="{{ route('user.asesmen.cetak', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Pusat Cetak</a>
                    <a href="{{ route($state['route'], $state['params']) }}" class="btn btn-{{ $state['color'] }} btn-sm">
                        {{ $state['label'] }} <i class="ri-arrow-right-line ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @foreach($steps as $step)
                    <a href="#step-{{ $step['key'] }}" class="badge text-decoration-none {{ $step['done'] ? 'bg-success-subtle text-success' : ($state['step'] === $step['key'] ? 'bg-primary text-white' : 'bg-light text-muted border') }}">
                        @if($step['done'])<i class="ri-check-line me-1"></i>@else{{ $loop->iteration }}. @endif{{ $step['label'] }}
                    </a>
                    @if(! $loop->last)<i class="ri-arrow-right-s-line text-muted"></i>@endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3" id="step-rencana">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-file-list-3-line me-1 text-primary"></i>1. Rencana / Kisi-kisi</span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('user.kisi-kisi-soal.edit', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-pencil-line"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><small class="text-muted d-block">Target soal</small><strong>{{ $targetSoal }} butir</strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Soal approved</small><strong>{{ $approvedSoal }} butir</strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Item kisi-kisi</small><strong>{{ $kisi->items->count() }} item</strong></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>TP</th><th>Materi</th><th>Level</th><th>Jumlah</th></tr></thead>
                            <tbody>
                                @foreach($kisi->items as $item)
                                    <tr>
                                        <td>{{ Str::limit($item->tujuanPembelajaran?->deskripsi ?? $item->tujuanPembelajaran?->nama ?? '-', 70) }}</td>
                                        <td>{{ $item->materi ?: '-' }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->level_kognitif }}</span></td>
                                        <td>{{ $item->jumlah_soal }} butir</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3" id="step-soal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-archive-2-line me-1 text-primary"></i>2. Soal & Bank Soal</span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('user.bank-soal.create', ['userId' => $userId, 'subject_id' => $kisi->subject_id, 'academic_year_id' => $kisi->academic_year_id, 'semester' => $kisi->semester, 'kisi_id' => $kisi->id]) }}" class="btn btn-sm btn-soft-success"><i class="ri-add-line me-1"></i>Bank Baru</a>
                        <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Semua Bank</a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($banks as $bank)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2">
                            <div>
                                <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="fw-medium link-primary">{{ $bank->nama }}</a>
                                <div class="small text-muted">{{ $bank->approved_soal_count }} soal approved · {{ \App\Models\Soal::BAHASA_OPTIONS[$bank->bahasa] ?? 'Latin' }}</div>
                            </div>
                            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-sm btn-soft-primary">Input Soal</a>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada bank soal untuk mapel ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3" id="step-paket">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-stack-line me-1 text-primary"></i>3. Paket Soal</span>
                    <a href="{{ route('user.paket-soal.create', ['userId' => $userId, 'kisiKisiId' => $kisi->id]) }}" class="btn btn-sm btn-soft-success"><i class="ri-add-line me-1"></i>Buat Paket</a>
                </div>
                <div class="card-body">
                    @forelse($pakets as $paket)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2">
                            <div>
                                <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="fw-medium link-primary">{{ $paket->judul }}</a>
                                <div class="small text-muted">{{ $paket->kode_paket }} · {{ $paket->items_count }} butir</div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\PaketSoal::WORKFLOW_OPTIONS[$paket->workflow_status] ?? $paket->workflow_status }}</span>
                        </div>
                    @empty
                        <div class="text-muted">Paket belum dibuat.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3" id="step-ljk">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-scan-2-line me-1 text-primary"></i>5. LJK & Nilai</span>
                    @if($latestPaket?->is_published)
                        <a href="{{ route('user.ljk.create', ['userId' => $userId, 'paket_soal_id' => $latestPaket->id]) }}" class="btn btn-sm btn-soft-success"><i class="ri-add-line me-1"></i>Terbitkan LJK</a>
                    @else
                        <span class="small text-muted">Publish paket terlebih dahulu</span>
                    @endif
                </div>
                <div class="card-body">
                    @forelse($ljkExams as $exam)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2">
                            <div>
                                <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="fw-medium link-primary">{{ $exam->judul }}</a>
                                <div class="small text-muted">{{ $exam->studyGroup?->name ?? '-' }} · {{ $exam->code }}</div>
                            </div>
                            <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-sm btn-soft-primary">Hasil & Nilai</a>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada LJK untuk paket ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="ri-flashlight-line me-1 text-warning"></i>Langkah Berikutnya</div>
                <div class="card-body">
                    <p class="text-muted small">Status saat ini</p>
                    <span class="badge bg-{{ $state['color'] }}-subtle text-{{ $state['color'] }} mb-3">{{ $state['label'] }}</span>
                    <div class="d-grid">
                        <a href="{{ route($state['route'], $state['params']) }}" class="btn btn-{{ $state['color'] }}">{{ $state['label'] }} <i class="ri-arrow-right-line ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="card mb-3" id="step-review">
                <div class="card-header"><i class="ri-shield-check-line me-1 text-primary"></i>4. Review & Finalisasi</div>
                <div class="card-body">
                    @if($latestPaket)
                        <p class="small text-muted mb-2">Terakhir: <strong>{{ $latestPaket->judul }}</strong></p>
                        <div class="d-grid gap-2">
                            <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $latestPaket->id]) }}" class="btn btn-soft-primary btn-sm">Buka Paket</a>
                            <a href="{{ route('user.paket-soal.validasi-soal', ['userId' => $userId, 'paketUuid' => $latestPaket->id]) }}" class="btn btn-soft-secondary btn-sm">Validasi Paket</a>
                        </div>
                    @else
                        <span class="text-muted small">Belum ada paket untuk direview.</span>
                    @endif
                </div>
            </div>

            <div class="card" id="step-cetak">
                <div class="card-header"><i class="ri-printer-line me-1 text-primary"></i>Cetak</div>
                <div class="card-body">
                    @if($latestPaket)
                        <div class="d-grid gap-2">
                            <a href="{{ route('user.paket-soal.naskah.cetak', ['userId' => $userId, 'paketUuid' => $latestPaket->id]) }}" class="btn btn-soft-primary btn-sm" target="_blank"><i class="ri-file-text-line me-1"></i>Naskah Soal</a>
                            <a href="{{ route('user.paket-soal.kartu-soal', ['userId' => $userId, 'paketUuid' => $latestPaket->id]) }}" class="btn btn-soft-secondary btn-sm" target="_blank"><i class="ri-file-list-3-line me-1"></i>Kartu Soal</a>
                            @if($ljkExams->isNotEmpty())
                                <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $ljkExams->first()->id]) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-scan-2-line me-1"></i>LJK</a>
                            @endif
                        </div>
                    @else
                        <span class="text-muted small">Cetak tersedia setelah paket dibuat.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
