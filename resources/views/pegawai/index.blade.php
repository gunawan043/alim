{{-- Secure Access: Data Pegawai --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>Data Pegawai | ALIM Alim</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.1.0/remixicon.min.css">
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width:900px">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:48px;height:48px;background:#16a34a18;color:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="ri-group-line fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark mb-1">Data Pegawai</h4>
                <p class="mb-0 text-muted" style="font-size:.85rem">Sistem Informasi Akademik & Informasi Manajemen</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light border-bottom-dashed d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-group-line text-primary me-1"></i> Daftar Pegawai ({{ $data->count() }})</h5>
                <input type="text" id="search" class="form-control form-control-sm" placeholder="Cari nama..." style="width:200px">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-pegawai">
                    <thead>
                        <tr>
                            <th class="bg-light" style="width:48px">No</th>
                            <th class="bg-light">Nama</th>
                            <th class="bg-light">Email</th>
                            <th class="bg-light">GTK</th>
                            <th class="bg-light text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                            {{ strtoupper(substr($item->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="fw-medium">{{ $item->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="small">{{ $item->email ?? '-' }}</td>
                                <td class="small">
                                    @if($item->gtkProfile)
                                        {{ $item->gtkProfile->nama ?? '-' }}
                                        @if($item->gtkProfile->jabatan)
                                            <br><span class="badge bg-light text-muted">{{ $item->gtkProfile->jabatan }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Tidak ada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->is_active ?? false)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    Belum ada data pegawai
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-4 text-muted small">
            ALIM Alim - Academic Learning & Information Management
        </div>
    </div>

    <script>
    document.getElementById('search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#table-pegawai tbody tr').forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
