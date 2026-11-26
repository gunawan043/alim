@extends('layouts.master')
@section('title', 'Buku Nilai Tahfidz')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $selectedGroupId = $group?->id;
        $selectedSubjectId = $book?->subject_id;
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Penilaian @endslot @slot('title') Buku Nilai Tahfidz @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show"><strong>Periksa kembali nilai.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>Buku Nilai Tahfidz</h5>
                <p class="text-muted mb-0 small">
                    Dinilai per halaqah dengan kolom Ziyadah, Rabt, STS, dan SAS.
                    NR Final = jumlah (nilai × bobot) dibagi total bobot komponen yang sudah terisi.
                </p>
            </div>
            <span class="badge bg-light text-body">
                {{ $academicYear?->name ?? 'Tahun ajaran belum aktif' }} · {{ ucfirst($semester) }}
            </span>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Halaqah</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()" @disabled($groups->isEmpty())>
                        @forelse($groups as $g)
                            <option value="{{ $g->id }}" @selected($selectedGroupId === $g->id)>{{ $g->name }} — {{ $g->studyGroup?->name ?? $g->school?->name }}</option>
                        @empty
                            <option>Belum ada halaqah</option>
                        @endforelse
                    </select>
                </div>
                @if($books->count() > 1)
                    <div class="col-md-3">
                        <label class="form-label small">Mapel</label>
                        <select name="subject_id" class="form-select" onchange="this.form.submit()">
                            @foreach($books as $b)
                                <option value="{{ $b->subject_id }}" @selected($selectedSubjectId === $b->subject_id)>{{ $b->subject?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                </div>
            </form>
        </div>

        @if(! $academicYear)
            <div class="card-body text-center text-muted py-5">
                <i class="ri-calendar-line fs-2 d-block mb-2"></i>
                Belum ada tahun ajaran. Aktifkan tahun ajaran terlebih dahulu.
            </div>
        @elseif(! $group)
            <div class="card-body text-center text-muted py-5">
                <i class="ri-team-line fs-2 d-block mb-2"></i>
                Anda belum terdaftar sebagai pengajar halaqah mana pun pada tahun ajaran ini.
            </div>
        @elseif($books->isEmpty())
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="ri-alert-line me-1"></i>
                    Belum ada mapel berformat tahfidz di sekolah ini, sehingga buku nilai belum bisa dibuat.
                    Tandai mapel tahfidz dulu di
                    <a href="{{ route('user.tahfidz.format.index', ['userId' => $userId]) }}">Format Penilaian Mapel</a>.
                </div>
            </div>
        @elseif($rows->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                Belum ada santri aktif di halaqah ini pada {{ $academicYear->name }}.
            </div>
        @else
            <form method="POST" action="{{ route('user.tahfidz.penilaian.store', ['userId' => $userId]) }}">
                @csrf
                <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}">
                <input type="hidden" name="tahfidz_gradebook_id" value="{{ $book->id }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                {{-- Panel bobot: sama seperti "Pengaturan NR Final" pada buku nilai mapel lain --}}
                <div class="card-body border-bottom">
                    <div class="border rounded p-2" style="background:#f8f9fa;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-semibold small">Bobot NR Final:</span>
                            @foreach(['ziyadah' => 'Ziyadah', 'rabt' => 'Rabt', 'sts' => 'STS', 'sas' => 'SAS'] as $key => $label)
                                <div class="input-group input-group-sm" style="width:132px;">
                                    <span class="input-group-text small">{{ $label }} %</span>
                                    <input type="number" name="weights[{{ $key }}]" id="w-{{ $key }}"
                                           class="form-control weight-input" min="0" max="100" step="0.5"
                                           value="{{ old('weights.'.$key, $book->{'weight_'.$key}) }}">
                                </div>
                            @endforeach
                            <div id="weight-sum" class="badge bg-secondary small">Total: 100%</div>
                            <span class="text-muted small">Total bobot harus 100%.</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tahfidz-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:44px" class="text-center">#</th>
                                <th>Santri</th>
                                <th class="text-center" style="width:110px">Rombel</th>
                                <th class="text-center" style="width:110px">Ziyadah</th>
                                <th class="text-center" style="width:110px">Rabt</th>
                                <th class="text-center" style="width:110px">STS</th>
                                <th class="text-center" style="width:110px">SAS</th>
                                <th class="text-center" style="width:100px">NR Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                @php
                                    $member = $row['member'];
                                    $score = $row['score'];
                                    $sid = $member['student_id'];
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $member['name'] }}</div>
                                        <div class="text-muted small">{{ $member['nis'] }}</div>
                                    </td>
                                    <td class="text-center small text-muted">{{ $member['study_group_name'] ?? '—' }}</td>
                                    @foreach(['ziyadah', 'rabt', 'sts', 'sas'] as $column)
                                        <td class="text-center">
                                            <input type="number" step="0.01" min="0" max="100"
                                                   class="form-control form-control-sm text-center score-input"
                                                   name="scores[{{ $sid }}][{{ $column }}]"
                                                   value="{{ old("scores.{$sid}.{$column}", $score?->{$column}) }}">
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold">{{ $score?->nr_final !== null ? number_format((float) $score->nr_final, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="text-muted small">
                        <i class="ri-information-line me-1"></i>
                        Kosongkan kolom yang belum dinilai. Santri yang keluar dari halaqah tidak lagi tampil di sini.
                    </span>
                    <button class="btn btn-success" type="submit"><i class="ri-save-line me-1"></i>Simpan Nilai &amp; Bobot</button>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const inputs = document.querySelectorAll('.weight-input');
        const sumEl = document.getElementById('weight-sum');
        if (! inputs.length || ! sumEl) return;

        function render() {
            let total = 0;
            inputs.forEach(el => { total += parseFloat(el.value) || 0; });
            const rounded = Math.round(total * 10) / 10;
            sumEl.textContent = 'Total: ' + rounded + '%';
            sumEl.className = 'badge small ' + (Math.abs(rounded - 100) < 0.01 ? 'bg-success' : 'bg-danger');
        }

        inputs.forEach(el => el.addEventListener('input', render));
        render();
    })();
</script>
@endpush
