@extends('layouts.master')

@section('title', 'Buku Nilai Tahfidz')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Penilaian @endslot
        @slot('title') Buku Nilai Tahfidz @endslot
    @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-end gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-book-3-line text-primary me-1"></i>Buku Nilai Tahfidz</h5>
                <p class="text-muted mb-0 small">
                    Rekap nilai mentah per jenis setoran. Nilai akhir tidak dihitung otomatis sampai aturan resmi pondok ditetapkan.
                </p>
            </div>
            <div class="d-flex gap-2">
                @if($group)
                    <a href="{{ route('user.tahfidz.gradebook.print', ['userId' => $userId, 'group_id' => $group->id, 'semester' => $semester]) }}"
                       class="btn btn-soft-danger">
                        <i class="ri-printer-line me-1"></i>Cetak / PDF
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('user.tahfidz.gradebook.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Halaqah</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()">
                        @foreach($groups as $availableGroup)
                            <option value="{{ $availableGroup->id }}" @selected((string) $groupId === (string) $availableGroup->id)>
                                {{ $availableGroup->name }}{{ $availableGroup->studyGroup ? ' — '.$availableGroup->studyGroup->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @if(! $group)
        <div class="alert alert-info mt-3"><i class="ri-information-line me-1"></i>Belum ada halaqah yang dapat Anda akses.</div>
    @else
        <div class="row tahfidz-stat mt-1">
            @foreach([
                ['Santri', $stats['students'], 'ri-user-heart-line', 'primary'],
                ['Setoran', $stats['setoran'], 'ri-file-list-3-line', 'info'],
                ['Rata Tikrar', $stats['tikrar_avg'] !== null ? number_format((float) $stats['tikrar_avg'], 2) : '—', 'ri-refresh-line', 'success'],
                ["Rata Tasmi'", $stats['tasmi_avg'] !== null ? number_format((float) $stats['tasmi_avg'], 2) : '—', 'ri-award-line', 'warning'],
            ] as [$label, $value, $icon, $color])
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate"><div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                            <div><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3></div>
                        </div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card mt-1">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0">{{ $group->name }} · {{ ucfirst($semester) }} · {{ $activeYear?->name ?? 'Tahun aktif' }}</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 tahfidz-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45px" class="text-center">No</th>
                            <th>Santri</th>
                            <th class="text-center">Ziyadah</th>
                            <th class="text-center">Tikrar</th>
                            <th class="text-center">Murajaah</th>
                            <th class="text-center">Robet</th>
                            <th class="text-center">Nilai Tikrar</th>
                            <th class="text-center">Lahn Khofi</th>
                            <th class="text-center">Lahn Jali</th>
                            <th class="text-center">Nilai Lahn</th>
                            <th class="text-center">Rata Setoran</th>
                            <th class="text-center">Tasmi'</th>
                            <th class="text-center">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $index => $row)
                            <tr>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('user.tahfidz.santri.show', ['userId' => $userId, 'studentId' => $row['student']?->id]) }}" class="fw-medium text-decoration-none">
                                        {{ $row['student']?->name ?? '—' }}
                                    </a>
                                    <div class="text-muted small">{{ $row['student']?->nis }}</div>
                                </td>
                                <td class="text-center">{{ $row['ziyadah'] }}</td>
                                <td class="text-center">{{ $row['tikrar'] }}</td>
                                <td class="text-center">{{ $row['murajaah'] }}</td>
                                <td class="text-center">{{ $row['robet'] }}</td>
                                <td class="text-center">{{ $row['tikrar_avg'] !== null ? number_format((float) $row['tikrar_avg'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['lahn_khofi'] }}</td>
                                <td class="text-center">{{ $row['lahn_jali'] }}</td>
                                <td class="text-center">{{ $row['lahn_avg'] !== null ? number_format((float) $row['lahn_avg'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['setoran_avg'] !== null ? number_format((float) $row['setoran_avg'], 2) : '—' }}</td>
                                <td class="text-center">
                                    {{ $row['tasmi_avg'] !== null ? number_format((float) $row['tasmi_avg'], 2) : '—' }}
                                    @if($row['tasmi_count'] > 0)<div class="text-muted small">{{ $row['tasmi_count'] }} sesi</div>@endif
                                </td>
                                <td class="text-center text-muted">—</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted py-5">Belum ada santri aktif pada halaqah ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
