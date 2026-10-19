@extends('layouts.master')
@section('title', 'Peta Alur Tasmi’')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') <a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}">Tasmi’</a> @endslot @slot('title') Peta Alur Tasmi’ @endslot @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-route-line text-primary me-1"></i>Alur Kegiatan Tasmi’</h5><p class="text-muted mb-0 small">Dari kesiapan santri sampai syahadah dicetak.</p></div>
            <a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}" class="btn btn-primary"><i class="ri-award-line me-1"></i>Buka Sesi Tasmi’</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach([
                    ['1', 'Santri Menyatakan Siap', 'Santri menyampaikan kesiapan kepada guru pengampu. Guru mencatat kesiapan dan berkoordinasi dengan koordinator.', 'Guru Pengampu', 'primary', 'ri-user-smile-line'],
                    ['2', 'Daftarkan Peserta', 'Guru halaqah atau koordinator mendaftarkan santri ke sesi: juz, materi, mustami, waktu, dan tempat.', 'Guru / Koordinator / Departemen', 'info', 'ri-user-add-line'],
                    ['3', 'Jadwalkan Sesi', 'Koordinator menentukan tanggal, waktu, tempat, dan status sesi menjadi terjadwal.', 'Koordinator / Departemen', 'warning', 'ri-calendar-todo-line'],
                    ['4', 'Guru Menilai', 'Mustami/guru pengampu menyimak hafalan tanpa mushaf, lalu mengisi nilai Tahfizh, Tajwid, dan Fashohah.', 'Mustami / Guru', 'success', 'ri-edit-2-line'],
                    ['5', 'Verifikasi Koordinator', 'Koordinator memeriksa nilai. Setelah diverifikasi, nilai terkunci dan tidak dapat diubah.', 'Koordinator', 'danger', 'ri-check-double-line'],
                    ['6', 'Syahadah & Cetak', 'Koordinator menerbitkan syahadah dari nilai terverifikasi. TU Departemen mencetak syahadah.', 'Koordinator → TU', 'dark', 'ri-certificate-line'],
                ] as [$no, $title, $description, $role, $color, $icon])
                    <div class="col-lg-4 col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-{{ $color }} fs-6">{{ $no }}</span>
                                <i class="{{ $icon }} fs-4 text-{{ $color }}"></i>
                                <span class="fw-semibold">{{ $title }}</span>
                            </div>
                            <p class="text-muted small mb-2">{{ $description }}</p>
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $role }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="alert alert-primary-subtle border mt-3 mb-0 small"><i class="ri-information-line me-1"></i>Urutan wajib: peserta → jadwal → nilai guru → verifikasi koordinator → syahadah. Syahadah tidak dapat diterbitkan sebelum nilai diverifikasi.</div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-shield-user-line text-primary me-1"></i>Hak Akses per Peran</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Peran</th><th>Tambah Peserta</th><th>Jadwal</th><th>Nilai</th><th>Verifikasi</th><th>Syahadah</th></tr></thead><tbody>
                    <tr><td>Guru Tahfidz / Mustami</td><td class="text-center text-success">✓</td><td class="text-center">—</td><td class="text-center text-success">✓</td><td class="text-center">—</td><td class="text-center">—</td></tr>
                    <tr><td>Koordinator Tahfidz Sekolah</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                    <tr><td>Departemen Tahfidz</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center">—</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                    <tr><td>TU Departemen Tahfidz</td><td class="text-center">—</td><td class="text-center">—</td><td class="text-center">—</td><td class="text-center">—</td><td class="text-center text-success">Cetak</td></tr>
                    <tr><td>Santri / Wali</td><td colspan="5" class="text-muted small">Menyampaikan kesiapan kepada guru; pengajuan mandiri via portal belum tersedia.</td></tr>
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-flow-chart text-primary me-1"></i>Status Data</h6></div>
                <div class="card-body">
                    <div class="mb-3"><div class="fw-semibold">Peserta</div><div class="small text-muted">Terdaftar → Sedang Ujian → Selesai / Tidak Hadir</div><div class="small">Verifikasi: <span class="badge bg-warning-subtle text-warning">Pending</span> → <span class="badge bg-success-subtle text-success">Verified</span></div></div>
                    <div class="mb-3"><div class="fw-semibold">Sesi</div><div class="small text-muted">Draft → Terjadwal → Berlangsung → Selesai</div></div>
                    <div><div class="fw-semibold">Syahadah</div><div class="small text-muted">Belum → Terbit → Dicetak</div><div class="small text-danger">Hanya bisa terbit jika peserta sudah <strong>verified</strong>.</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i>Data yang Harus Ada</h6></div>
        <div class="card-body"><div class="row g-3">
            <div class="col-md-4"><div class="fw-semibold mb-1">Peserta</div><div class="small text-muted">Santri, juz/daftar juz, materi, mustami, waktu, tempat.</div></div>
            <div class="col-md-4"><div class="fw-semibold mb-1">Nilai</div><div class="small text-muted">Tahfizh, Tajwid, Fashohah, catatan penguji, predikat.</div></div>
            <div class="col-md-4"><div class="fw-semibold mb-1">Verifikasi</div><div class="small text-muted">Verifikator, waktu verifikasi, status, catatan.</div></div>
            <div class="col-md-4"><div class="fw-semibold mb-1">Syahadah</div><div class="small text-muted">Nomor, jenis, jumlah juz, nilai, predikat, nama Arab.</div></div>
            <div class="col-md-4"><div class="fw-semibold mb-1">Cetak</div><div class="small text-muted">Lembaga, kode lembaga, bulan/tahun cetak, penandatangan.</div></div>
            <div class="col-md-4"><div class="fw-semibold mb-1">Audit</div><div class="small text-muted">Instrumen nilai, audit syahadah, dan konsistensi rapor.</div></div>
        </div></div>
    </div>
@endsection
