@extends('layouts.master')
@section('title', 'Ambil Soal')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">Paket Soal</a>
        @endslot
        @slot('li_2')
            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">{{ $bank->nama }}</a>
        @endslot
        @slot('title') Ambil Soal @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="alert alert-light border d-flex gap-2 align-items-start">
        <i class="ri-information-line fs-5 mt-1 text-primary"></i>
        <div class="small mb-0">
            <strong>Ambil soal dari paket lain.</strong> Soal yang dipilih <em>disalin</em> ke
            <strong>{{ $bank->nama }}</strong>; paket asal tidak berubah. Soal hasil salinan masuk sebagai draft
            dan tetap menjalani validasi seperti soal baru.
        </div>
    </div>

    <form method="GET" action="{{ route('user.bank-soal.ambil-soal', ['userId' => $userId, 'id' => $bank->id]) }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Cari teks soal</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Kata kunci pertanyaan...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Dari paket</label>
                    <select name="sumber_id" class="form-select">
                        <option value="">Semua paket</option>
                        @foreach($sumber as $s)
                            <option value="{{ $s->id }}" {{ request('sumber_id') === $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}{{ $s->subject ? ' — '.$s->subject->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Mapel</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ request('subject_id') === $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Cari</button>
                    <a href="{{ route('user.bank-soal.ambil-soal', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('user.bank-soal.ambil-soal.store', ['userId' => $userId, 'id' => $bank->id]) }}" id="ambilForm">
        @csrf

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Soal yang tersedia ({{ $soals->total() }})</h5>
                <div class="d-flex gap-2 align-items-center">
                    <span class="small text-muted"><span id="terpilih">0</span> soal dipilih</span>
                    <button type="button" class="btn btn-light btn-sm" id="pilihSemua">Pilih semua di halaman ini</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"></th>
                            <th>Pertanyaan</th>
                            <th style="width:200px">Paket asal</th>
                            <th style="width:110px">Jenis</th>
                            <th style="width:80px">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($soals as $s)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input pilih-soal" name="soal_ids[]" value="{{ $s->id }}">
                                </td>
                                <td>
                                    <div class="small">{{ \Illuminate\Support\Str::limit(strip_tags($s->pertanyaan), 160) }}</div>
                                    <div class="mt-1 small text-muted">
                                        @foreach($s->options->sortBy('urutan') as $o)
                                            <span class="me-2">
                                                <strong>{{ $o->label }}.</strong> {{ \Illuminate\Support\Str::limit($o->teks_opsi, 30) }}
                                                @if($o->is_correct)<i class="ri-check-line text-success" title="Kunci"></i>@endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="small">{{ $s->bankSoal?->nama ?? '—' }}</td>
                                <td class="small text-uppercase">{{ $s->tipe_soal }}</td>
                                <td class="small">{{ $s->options->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Tidak ada soal yang cocok. Coba ubah kata kunci atau filter paket.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>{{ $soals->links() }}</div>
                <div class="d-flex gap-2">
                    <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary" id="btnAmbil" disabled>
                        <i class="ri-download-2-line me-1"></i>Ambil ke paket ini
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ambilForm');
    const btn = document.getElementById('btnAmbil');
    const jml = document.getElementById('terpilih');
    const pilihSemua = document.getElementById('pilihSemua');

    function hitung() {
        const n = form.querySelectorAll('.pilih-soal:checked').length;
        jml.textContent = n;
        btn.disabled = n === 0;
    }

    form.addEventListener('change', function (e) {
        if (e.target.classList.contains('pilih-soal')) hitung();
    });

    pilihSemua.addEventListener('click', function () {
        const boxes = form.querySelectorAll('.pilih-soal');
        const semua = Array.from(boxes).every(b => b.checked);
        boxes.forEach(b => { b.checked = !semua; });
        pilihSemua.textContent = semua ? 'Pilih semua di halaman ini' : 'Batal pilih';
        hitung();
    });

    form.addEventListener('submit', function (e) {
        if (btn.disabled) {
            e.preventDefault();
        } else if (!confirm('Salin soal terpilih ke paket ini?')) {
            e.preventDefault();
        }
    });

    hitung();
});
</script>
@endsection
