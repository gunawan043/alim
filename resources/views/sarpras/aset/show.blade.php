@extends('layouts.master')
@section('title') Detail Aset — {{ $aset->asset_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('user.sarpras.gedung.index', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('user.sarpras.aset.index', ['userId' => $userId]) }}">Aset</a> @endslot
        @slot('title') {{ $aset->asset_name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="mb-0">Detail Aset</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sarpras.aset.edit', ['userId' => $userId, 'id' => $aset->id]) }}" class="btn btn-sm btn-warning">
                                <i class="ri-pencil-line me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted fw-medium" style="width:200px">Nama Aset</td>
                                    <td>{{ $aset->asset_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kode Aset</td>
                                    <td><code>{{ $aset->asset_code ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kategori</td>
                                    <td>{{ $aset->category?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Ruang</td>
                                    <td>
                                        @if($aset->room)
                                            <a href="{{ route('user.sarpras.ruang.show', ['userId' => $userId, 'id' => $aset->room_id]) }}">
                                                {{ $aset->room->room_name }}
                                            </a>
                                            @if($aset->room->building)
                                                <span class="text-muted"> — {{ $aset->room->building->building_name }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Satuan Pendidikan</td>
                                    <td>{{ $aset->room?->school?->name ?? ($aset->school?->name ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Merk / Model</td>
                                    <td>{{ $aset->brand ? "{$aset->brand} {$aset->model}" : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Nomor Seri</td>
                                    <td><code>{{ $aset->serial_number ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Warna</td>
                                    <td>{{ $aset->color ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Spesifikasi</td>
                                    <td>{{ $aset->specification ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Kondisi</td>
                                    <td>
                                        @php
                                            $kondisiColor = [
                                                'baik' => 'success',
                                                'rusak_ringan' => 'warning',
                                                'rusak_sedang' => 'warning',
                                                'rusak_berat' => 'danger',
                                                'hilang' => 'secondary',
                                                'dihapus' => 'secondary',
                                            ][$aset->condition] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $kondisiColor }}-subtle text-{{ $kondisiColor }}">
                                            {{ ucfirst(str_replace('_', ' ', $aset->condition)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Status</td>
                                    <td>
                                        @php $statusColor = ['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary'][$aset->status] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                            {{ ucfirst(str_replace('_', ' ', $aset->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Dapat Dipinjam</td>
                                    <td>
                                        @if($aset->is_bookable)
                                            <span class="badge bg-info-subtle text-info">Ya</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Harga Perolehan</td>
                                    <td>
                                        @if($aset->acquisition_price)
                                            Rp {{ number_format($aset->acquisition_price, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Tahun Perolehan</td>
                                    <td>{{ $aset->acquisition_year ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Sumber Perolehan</td>
                                    <td>{{ $aset->acquisition_source ? ucfirst(str_replace('_', ' ', $aset->acquisition_source)) : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Sumber Dana</td>
                                    <td>{{ $aset->funding_source ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Nilai Saat Ini</td>
                                    <td>
                                        @if($aset->current_value)
                                            Rp {{ number_format($aset->current_value, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @if($aset->last_audit_date)
                                <tr>
                                    <td class="text-muted fw-medium">Audit Terakhir</td>
                                    <td>{{ $aset->last_audit_date?->format('d M Y') ?? '-' }} — oleh {{ $aset->lastAuditBy?->name ?? '-' }}</td>
                                </tr>
                                @endif
                                @if($aset->notes)
                                <tr>
                                    <td class="text-muted fw-medium">Catatan</td>
                                    <td>{{ $aset->notes }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted fw-medium">Status</td>
                                    <td>
                                        @if($aset->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium">Dibuat</td>
                                    <td>{{ $aset->created_at?->format('d M Y H:i') ?? '-' }} — {{ $aset->creator?->name ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
