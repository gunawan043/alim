@extends('layouts.master')
@section('title') Tambah Penilaian Kinerja @endsection
@push('css')
<style>
.step-indicator{display:flex;gap:.5rem;margin-bottom:2rem}
.step-item{flex:1;text-align:center;position:relative}
.step-item::after{content:'';position:absolute;top:18px;left:50%;width:100%;height:2px;background:#e2e8f0;z-index:0}
.step-item:last-child::after{display:none}
.step-circle{width:36px;height:36px;border-radius:50%;background:#e2e8f0;color:#64748b;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;position:relative;z-index:1;transition:all .3s}
.step-item.active .step-circle{background:#f97316;color:#fff}
.step-item.done .step-circle{background:#22c55e;color:#fff}
.step-label{font-size:.72rem;color:#64748b;font-weight:500}
.step-item.active .step-label{color:#f97316;font-weight:600}
.page-header-card{background:linear-gradient(135deg,#fff7ed 0%,#fffbf5 100%);border:1px solid #fed7aa;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1a0f00 0%,#1f1500 100%);border-color:#92400e}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f9731618;color:#ea580c;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-chart-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Tambah Penilaian Kinerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Form penilaian kinerja GTK</p>
        </div>
    </div>
    <a href="{{ route('user.ats.kinerja.index', $userId) }}" class="btn btn-light btn-sm no-print"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('user.ats.kinerja.store', $userId) }}" id="kinerjaForm">
                    @csrf
                    <div class="step-indicator mb-4">
                        <div class="step-item active"><div class="step-circle">1</div><div class="step-label">GTK & Periode</div></div>
                        <div class="step-item"><div class="step-circle">2</div><div class="step-label">Komponen & Skor</div></div>
                        <div class="step-item"><div class="step-circle">3</div><div class="step-label">Konfirmasi</div></div>
                    </div>

                    <div class="step-content" id="step1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GTK <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih GTK --</option>
                                    @foreach(\App\Models\User::where('is_active',true)->orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id')==$u->id?'selected':'' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                @error('user_id')<div class="invalid-feedback">{{ $$message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Periode Penilaian <span class="text-danger">*</span></label>
                                <select name="kinerja_periode_id" class="form-select @error('kinerja_periode_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Periode --</option>
                                    @foreach(\App\Models\KinerjaPeriode::orderBy('tanggal_mulai','desc')->get() as $p)
                                    <option value="{{ $p->id }}" {{ old('kinerja_periode_id')==$p->id?'selected':'' }}>{{ $p->nama }} ({{ $p->status }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Penilai</label>
                                <textarea name="catatan_penilai" class="form-control" rows="3" placeholder="Catatan keseluruhan penilaian...">{{ old('catatan_penilai') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" onclick="goStep(2)">Lanjut <i class="ri-arrow-right-line ms-1"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="step-content d-none" id="step2">
                        <h6 class="fw-semibold mb-3">Komponen Penilaian</h6>
                        @php $komponens = \App\Models\KinerjaKomponen::with('indikator')->where('is_active',true)->orderBy('urutan')->get(); @endphp
                        @forelse($komponens as $komponen)
                        <div class="card mb-2 border">
                            <div class="card-header bg-light py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">{{ $komponen->nama }}</span>
                                    <span class="badge bg-primary-subtle text-primary">{{ $komponen->bobot_persen }}%</span>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                @forelse($komponen->indikator as $indikator)
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-7">
                                        <span class="small">{{ $indikator->nama }}</span>
                                        @if($indikator->deskripsi)<span class="text-muted small d-block">{{ Str::limit($indikator->deskripsi,80) }}</span>@endif
                                    </div>
                                    <div class="col-md-3">
                                        <input type="range" class="form-range" min="0" max="100" value="{{ old('skor_'.$indikator->id,50) }}" name="skor[{{ $indikator->id }}]" id="skor_{{ $indikator->id }}" oninput="document.getElementById('skor_val_{{ $indikator->id }}').innerText=this.value">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="badge bg-dark" id="skor_val_{{ $indikator->id }}">{{ old('skor_'.$indikator->id,50) }}</span>/100
                                    </div>
                                </div>
                                @empty
                                <span class="text-muted small">Belum ada indikator untuk komponen ini.</span>
                                @endforelse
                            </div>
                        </div>
                        @empty
                        <div class="alert alert-warning mb-3"><i class="ri-alert-line me-1"></i> Belum ada komponen penilaian. <a href="{{ route('user.ats.kinerja.indikator', $userId) }}">Tambah di sini</a></div>
                        @endforelse
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-light" onclick="goStep(1)"><i class="ri-arrow-left-line me-1"></i>Kembali</button>
                            <button type="button" class="btn btn-primary" onclick="goStep(3)">Lanjut <i class="ri-arrow-right-line ms-1"></i></button>
                        </div>
                    </div>

                    <div class="step-content d-none" id="step3">
                        <div class="alert alert-info"><i class="ri-information-line me-1"></i> Periksa kembali data sebelum disimpan.</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GTK</label>
                                <p class="fw-semibold" id="confirm_gtk">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Periode</label>
                                <p class="fw-semibold" id="confirm_periode">-</p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-light" onclick="goStep(2)"><i class="ri-arrow-left-line me-1"></i>Kembali</button>
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Penilaian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Panduan</h5></div>
            <div class="card-body">
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Pilih GTK dan periode penilaian</li>
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Beri skor 0-100 per indikator</li>
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Sistem menghitung total & nilai huruf otomatis</li>
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> A ≥ 90, B = 80-89, C = 70-79, D < 70</li>
                    <li><i class="ri-checkbox-circle-line text-success me-1"></i> Penilaian dapat diedit selama berstatus Draft</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$('select').select2();
function goStep(n){
    $('.step-content').addClass('d-none');
    $('#step'+n).removeClass('d-none');
    $('.step-item').each(function(i){
        if(i+1<n){$(this).addClass('done').removeClass('active');$(this).find('.step-circle').text('✓')}
        else if(i+1===n){$(this).addClass('active').removeClass('done')}
        else{$(this).removeClass('active').removeClass('done')}
    });
    if(n===3){
        var gtk=$('select[name=user_id] option:selected').text();
        var periode=$('select[name=kinerja_periode_id] option:selected').text();
        $('#confirm_gtk').text(gtk);
        $('#confirm_periode').text(periode);
    }
}
</script>
@endpush
