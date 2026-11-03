@extends('layouts.master')

@section('title', 'SOP & Konfigurasi Keamanan')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('title') SOP & Konfigurasi @endslot
    @endcomponent

    @php
        $tabs = [
            'pos' => ['label' => 'Pos Jaga', 'icon' => 'ri-map-pin-line'],
            'shift' => ['label' => 'Shift', 'icon' => 'ri-time-line'],
            'zona' => ['label' => 'Zona Area', 'icon' => 'ri-layout-masonry-line'],
            'jenis' => ['label' => 'Jenis Insiden', 'icon' => 'ri-list-settings-line'],
            'kontak' => ['label' => 'Kontak Darurat', 'icon' => 'ri-phone-line'],
            'sop' => ['label' => 'SOP Pengamanan', 'icon' => 'ri-file-shield-2-line'],
        ];
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        @foreach($tabs as $key => $meta)
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                                   href="{{ route('user.keamanan.config.index', ['userId' => $userId, 'tab' => $key]) }}">
                                    <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- ══ POS JAGA ══ --}}
                @if($tab === 'pos')
                    <div class="card-header py-2 bg-light border-bottom d-flex align-items-center">
                        <span class="text-muted small flex-grow-1">Pos jaga menjadi titik penempatan regu dan asal pencatatan tamu.</span>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#postModal">
                            <i class="ri-add-line me-1"></i> Tambah Pos
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Kode</th><th>Nama Pos</th><th>Zona</th><th>Lokasi</th><th>Telepon</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($posts as $post)
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $post->code }}</span></td>
                                            <td class="fw-semibold">{{ $post->name }}</td>
                                            <td>{{ $post->zone->name ?? '—' }}</td>
                                            <td>{{ $post->location ?: '—' }}</td>
                                            <td>{{ $post->phone ?: '—' }}</td>
                                            <td>
                                                @if($post->is_active)
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('user.keamanan.config.posts.destroy', ['userId' => $userId, 'post' => $post->id]) }}"
                                                      data-confirm="Hapus pos {{ $post->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="7" title="Belum ada pos jaga" message="Tambahkan pos gerbang atau pos dalam." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="ri-map-pin-add-line me-2 text-primary"></i> Tambah Pos Jaga</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.keamanan.config.posts.store', ['userId' => $userId]) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                                <input type="text" name="code" class="form-control" placeholder="POS-01" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" placeholder="Pos Gerbang Utama" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Zona</label>
                                                <select name="zone_id" class="form-select">
                                                    <option value="">— Tanpa zona —</option>
                                                    @foreach($zones as $zone)
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Telepon</label>
                                                <input type="text" name="phone" class="form-control">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Lokasi</label>
                                                <input type="text" name="location" class="form-control">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Catatan</label>
                                                <textarea name="notes" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ══ SHIFT ══ --}}
                @if($tab === 'shift')
                    <div class="card-header py-2 bg-light border-bottom d-flex align-items-center">
                        <span class="text-muted small flex-grow-1">Shift dipakai pada jadwal patroli dan regu.</span>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#shiftModal">
                            <i class="ri-add-line me-1"></i> Tambah Shift
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Kode</th><th>Nama</th><th>Jam</th><th>Catatan</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($shifts as $shift)
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $shift->code }}</span></td>
                                            <td class="fw-semibold">{{ $shift->name }}</td>
                                            <td>{{ $shift->start_time }} — {{ $shift->end_time }}</td>
                                            <td>{{ $shift->notes ?: '—' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('user.keamanan.config.shifts.destroy', ['userId' => $userId, 'shift' => $shift->id]) }}"
                                                      data-confirm="Hapus shift {{ $shift->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="5" title="Belum ada shift" message="Tambahkan pembagian jam jaga." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="ri-time-line me-2 text-primary"></i> Tambah Shift</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.keamanan.config.shifts.store', ['userId' => $userId]) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                                <input type="text" name="code" class="form-control" placeholder="S1" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" placeholder="Pagi" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                                <input type="time" name="start_time" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                                <input type="time" name="end_time" class="form-control" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Catatan</label>
                                                <textarea name="notes" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ══ ZONA ══ --}}
                @if($tab === 'zona')
                    <div class="card-header py-2 bg-light border-bottom d-flex align-items-center">
                        <span class="text-muted small flex-grow-1">Zona membantu menandai area berisiko pada laporan insiden.</span>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#zoneModal">
                            <i class="ri-add-line me-1"></i> Tambah Zona
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Kode</th><th>Nama Zona</th><th>Tingkat Risiko</th><th>Jumlah Pos</th><th>Keterangan</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($zones as $zone)
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $zone->code }}</span></td>
                                            <td class="fw-semibold">{{ $zone->name }}</td>
                                            <td>
                                                <span class="badge bg-{{ $zone->risk_level === 'tinggi' ? 'danger' : ($zone->risk_level === 'sedang' ? 'warning' : 'success') }}-subtle text-{{ $zone->risk_level === 'tinggi' ? 'danger' : ($zone->risk_level === 'sedang' ? 'warning' : 'success') }}">
                                                    {{ $zone->riskLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $zone->posts_count }}</td>
                                            <td>{{ $zone->description ?: '—' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('user.keamanan.config.zones.destroy', ['userId' => $userId, 'zone' => $zone->id]) }}"
                                                      data-confirm="Hapus zona {{ $zone->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="6" title="Belum ada zona" message="Tambahkan zona area pesantren." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="zoneModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="ri-layout-masonry-line me-2 text-primary"></i> Tambah Zona</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.keamanan.config.zones.store', ['userId' => $userId]) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                                <input type="text" name="code" class="form-control" placeholder="Z-01" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" placeholder="Area Asrama Putra" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Tingkat Risiko <span class="text-danger">*</span></label>
                                                <select name="risk_level" class="form-select" required>
                                                    @foreach(\App\Models\Security\SecurityZone::RISK_OPTIONS as $value => $label)
                                                        <option value="{{ $value }}" {{ $value === 'sedang' ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Keterangan</label>
                                                <textarea name="description" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ══ JENIS INSIDEN ══ --}}
                @if($tab === 'jenis')
                    <div class="card-header py-2 bg-light border-bottom d-flex align-items-center">
                        <span class="text-muted small flex-grow-1">Jenis insiden dipakai saat melaporkan kejadian.</span>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#typeModal">
                            <i class="ri-add-line me-1"></i> Tambah Jenis
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Kode</th><th>Nama</th><th>Tingkat Default</th><th>Laporan</th><th>Keterangan</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($types as $type)
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $type->code }}</span></td>
                                            <td class="fw-semibold">{{ $type->name }}</td>
                                            <td>{{ \App\Models\Security\SecurityIncident::SEVERITY_OPTIONS[$type->default_severity] ?? $type->default_severity }}</td>
                                            <td><span class="badge bg-info-subtle text-info">{{ $type->incidents_count }}</span></td>
                                            <td>{{ $type->description ?: '—' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('user.keamanan.config.incident-types.destroy', ['userId' => $userId, 'type' => $type->id]) }}"
                                                      data-confirm="Hapus jenis insiden {{ $type->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="6" title="Belum ada jenis insiden" message="Tambahkan jenis agar laporan lebih terstruktur." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="ri-list-settings-line me-2 text-primary"></i> Tambah Jenis Insiden</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.keamanan.config.incident-types.store', ['userId' => $userId]) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                                <input type="text" name="code" class="form-control" placeholder="INS-01" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" placeholder="Orang Asing Masuk" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Tingkat Default <span class="text-danger">*</span></label>
                                                <select name="default_severity" class="form-select" required>
                                                    @foreach(\App\Models\Security\SecurityIncident::SEVERITY_OPTIONS as $value => $label)
                                                        <option value="{{ $value }}" {{ $value === 'sedang' ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Keterangan</label>
                                                <textarea name="description" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ══ KONTAK DARURAT ══ --}}
                @if($tab === 'kontak')
                    <div class="card-header py-2 bg-light border-bottom d-flex align-items-center">
                        <span class="text-muted small flex-grow-1">Nomor yang dihubungi saat keadaan darurat.</span>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#contactModal">
                            <i class="ri-add-line me-1"></i> Tambah Kontak
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Nama</th><th>Jabatan</th><th>Telepon</th><th>Kategori</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($contacts as $contact)
                                        <tr>
                                            <td class="fw-semibold">{{ $contact->name }}</td>
                                            <td>{{ $contact->role ?: '—' }}</td>
                                            <td>
                                                <a href="tel:{{ $contact->phone }}" class="text-decoration-none">
                                                    <i class="ri-phone-line me-1"></i>{{ $contact->phone }}
                                                </a>
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $contact->categoryLabel() }}</span></td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('user.keamanan.config.contacts.destroy', ['userId' => $userId, 'contact' => $contact->id]) }}"
                                                      data-confirm="Hapus kontak {{ $contact->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="5" title="Belum ada kontak darurat" message="Tambahkan nomor penting: pimpinan, polisi, damkar, rumah sakit." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="ri-phone-line me-2 text-primary"></i> Tambah Kontak Darurat</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.keamanan.config.contacts.store', ['userId' => $userId]) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Jabatan / Instansi</label>
                                                <input type="text" name="role" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Telepon <span class="text-danger">*</span></label>
                                                <input type="text" name="phone" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                                <select name="category" class="form-select" required>
                                                    @foreach(\App\Models\Security\SecurityContact::CATEGORY_OPTIONS as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Urutan Tampil</label>
                                                <input type="number" name="sort_order" class="form-control" min="0" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ══ SOP ══ --}}
                @if($tab === 'sop')
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-2"><i class="ri-door-open-line me-1 text-primary"></i> Prosedur Pos Gerbang</h6>
                                    <ol class="small text-muted mb-0 ps-3">
                                        <li>Setiap tamu wajib menyerahkan identitas dan menyebutkan keperluan.</li>
                                        <li>Catat tamu pada menu <strong>Log Tamu</strong>, lalu cetak kartu tamu.</li>
                                        <li>Kartu tamu wajib dikembalikan saat tamu keluar; catat waktu keluar.</li>
                                        <li>Kendaraan yang masuk dicatat pada menu <strong>Log Kendaraan</strong>.</li>
                                        <li>Tamu tanpa keperluan jelas atau mencurigakan ditolak dan dilaporkan.</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-2"><i class="ri-shield-check-line me-1 text-success"></i> Prosedur Verifikasi Izin Santri</h6>
                                    <ol class="small text-muted mb-0 ps-3">
                                        <li>Pindai QR pada kartu izin santri melalui menu <strong>Scan QR Izin</strong>.</li>
                                        <li>Pastikan nama santri, penjemput, dan masa berlaku izin sesuai.</li>
                                        <li>Bila QR tidak terbaca, gunakan <strong>Verifikasi Manual</strong> dengan kode izin.</li>
                                        <li>Izin yang belum disetujui atau penjemput belum diverifikasi tidak boleh dilepas.</li>
                                        <li>Catat waktu keluar dan kembali; santri terlambat masuk daftar pantauan.</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-2"><i class="ri-map-pin-line me-1 text-warning"></i> Prosedur Patroli</h6>
                                    <ol class="small text-muted mb-0 ps-3">
                                        <li>Buka jadwal patroli hari ini, tekan <strong>Mulai</strong>.</li>
                                        <li>Pindai QR setiap checkpoint sesuai urutan rute.</li>
                                        <li>Tandai kondisi <strong>Aman</strong> atau <strong>Ada Temuan</strong> beserta catatan.</li>
                                        <li>Temuan yang berpotensi bahaya wajib dibuatkan laporan insiden.</li>
                                        <li>Patroli selesai otomatis setelah semua checkpoint dipindai.</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-2"><i class="ri-alert-line me-1 text-danger"></i> Prosedur Penanganan Insiden</h6>
                                    <ol class="small text-muted mb-0 ps-3">
                                        <li>Amankan lokasi dan pastikan tidak ada korban tambahan.</li>
                                        <li>Catat insiden pada menu <strong>Laporan Insiden</strong> dalam hari yang sama.</li>
                                        <li>Hubungi kontak darurat sesuai kategori kejadian.</li>
                                        <li>Setiap tindakan dicatat pada riwayat tindak lanjut.</li>
                                        <li>Insiden ditutup oleh Kepala/Koordinator dengan uraian penyelesaian.</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="ri-information-line me-1"></i>
                                    Aturan tata tertib dan kebijakan izin santri yang berlaku dapat dilihat di
                                    <a href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}" class="alert-link">Kebijakan Izin Santri</a>
                                    dan
                                    <a href="{{ route('user.boarding-regulations.index', ['userId' => $userId]) }}" class="alert-link">Peraturan Asrama</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
