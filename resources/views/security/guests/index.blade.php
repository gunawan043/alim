@extends('layouts.master')

@section('title', 'Buku Tamu')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Pos Gerbang @endslot
        @slot('title') Buku Tamu @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Tamu di Dalam', 'value' => number_format($stats['inside']), 'icon' => 'ri-user-follow-line', 'color' => 'primary'],
            ['label' => 'Masuk Hari Ini', 'value' => number_format($stats['today']), 'icon' => 'ri-login-box-line', 'color' => 'success'],
            ['label' => 'Keluar Hari Ini', 'value' => number_format($stats['out_today']), 'icon' => 'ri-logout-box-line', 'color' => 'warning'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Tamu</h5>
                            <p class="text-muted mb-0 small">Kunjungan tamu yang tercatat di pos gerbang.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2"
                                      action="{{ route('user.keamanan.guests.index', ['userId' => $userId]) }}">
                                    <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                                           placeholder="Cari nama, kode, atau instansi..." style="width: 240px;">
                                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#guestModal">
                                    <i class="ri-add-line me-1"></i> Registrasi Tamu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                        <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId]) }}"
                           class="filter-badge {{ ! request('status') ? 'active' : '' }}">Semua</a>
                        <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId, 'status' => 'di_dalam']) }}"
                           class="filter-badge {{ request('status') === 'di_dalam' ? 'active' : '' }}">Di Dalam</a>
                        <a href="{{ route('user.keamanan.guests.index', ['userId' => $userId, 'status' => 'keluar']) }}"
                           class="filter-badge {{ request('status') === 'keluar' ? 'active' : '' }}">Sudah Keluar</a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($guests->isEmpty())
                        <x-empty-state icon="ri-user-search-line" title="Belum ada tamu tercatat"
                                       message="Registrasi tamu pertama lewat tombol Registrasi Tamu." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 150px;">Kode</th>
                                        <th>Nama Tamu</th>
                                        <th>Instansi / Asal</th>
                                        <th>Keperluan</th>
                                        <th>Bertemu</th>
                                        <th>Pos</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th class="text-center" style="width: 90px;">Status</th>
                                        <th class="text-end" style="width: 130px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guests as $guest)
                                        <tr>
                                            <td class="fw-semibold">{{ $guest->code }}</td>
                                            <td>
                                                {{ $guest->visitor_name }}
                                                @if($guest->visitor_count > 1)
                                                    <span class="badge bg-info-subtle text-info ms-1">{{ $guest->visitor_count }} orang</span>
                                                @endif
                                                @if($guest->phone)
                                                    <div class="text-muted small">{{ $guest->phone }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $guest->institution ?: '-' }}
                                                @if($guest->origin)
                                                    <div class="text-muted small">{{ $guest->origin }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $guest->purpose }}</td>
                                            <td>{{ $guest->person_to_meet ?: '-' }}</td>
                                            <td>{{ $guest->post?->name ?? '-' }}</td>
                                            <td>{{ $guest->check_in_at?->format('d/m H:i') ?? '-' }}</td>
                                            <td>{{ $guest->check_out_at?->format('d/m H:i') ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($guest->isInside())
                                                    <span class="badge bg-success-subtle text-success">Di Dalam</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Keluar</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-soft-secondary" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('user.keamanan.guests.show', ['userId' => $userId, 'guest' => $guest->id]) }}">
                                                                <i class="ri-eye-line me-1"></i> Detail
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('user.keamanan.guests.card', ['userId' => $userId, 'guest' => $guest->id]) }}"
                                                               target="_blank">
                                                                <i class="ri-printer-line me-1"></i> Cetak Kartu
                                                            </a>
                                                        </li>
                                                        @if($guest->isInside())
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST"
                                                                      action="{{ route('user.keamanan.guests.checkout', ['userId' => $userId, 'guest' => $guest->id]) }}"
                                                                      data-confirm="Catat tamu ini sudah keluar?">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item text-warning">
                                                                        <i class="ri-logout-box-line me-1"></i> Catat Keluar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if($guests->hasPages())
                    <div class="card-footer border-top-dashed">
                        {{ $guests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal registrasi tamu --}}
    <div class="modal fade" id="guestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.keamanan.guests.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-user-add-line me-1"></i> Registrasi Tamu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                                <input type="text" name="visitor_name" class="form-control" required
                                       value="{{ old('visitor_name') }}" placeholder="Nama lengkap tamu">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Identitas</label>
                                <select name="id_type" class="form-select">
                                    @foreach(\App\Models\Security\SecurityGuestLog::ID_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nomor Identitas</label>
                                <input type="text" name="id_number" class="form-control" value="{{ old('id_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. HP</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Asal</label>
                                <input type="text" name="origin" class="form-control" value="{{ old('origin') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Instansi</label>
                                <input type="text" name="institution" class="form-control" value="{{ old('institution') }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                                <input type="text" name="purpose" class="form-control" required
                                       value="{{ old('purpose') }}" placeholder="Mis. mengantar barang, wali santri">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Orang</label>
                                <input type="number" name="visitor_count" class="form-control" min="1" max="100"
                                       value="{{ old('visitor_count', 1) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bertemu</label>
                                <input type="text" name="person_to_meet" class="form-control" value="{{ old('person_to_meet') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hubungan</label>
                                <input type="text" name="relationship" class="form-control" value="{{ old('relationship') }}"
                                       placeholder="Mis. wali, rekan kerja">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pos</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Otomatis —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" @selected(old('post_id') === $post->id)>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asrama Tujuan</label>
                                <select name="dormitory_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($dormitories as $dormitory)
                                        <option value="{{ $dormitory->id }}" @selected(old('dormitory_id') === $dormitory->id)>{{ $dormitory->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Santri yang Dikunjungi</label>
                                <select name="student_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') === $student->id)>
                                            {{ $student->name }} ({{ $student->nisn ?: '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nopol Kendaraan</label>
                                <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Kartu Tamu</label>
                                <input type="text" name="card_number" class="form-control" value="{{ old('card_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Menu "Registrasi Tamu" di sidebar membuka halaman ini dengan ?register=1
    if (new URLSearchParams(window.location.search).get('register') === '1') {
        var modal = document.getElementById('guestModal');
        if (modal) {
            new bootstrap.Modal(modal).show();
        }
    }
});
</script>
@endpush
