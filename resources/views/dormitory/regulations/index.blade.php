@extends('layouts.master')

@section('title', 'Peraturan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="ri-book-line me-2"></i>Peraturan Asrama</h4>
            </div>
        </div>
    </div>

    {{-- Actions Button --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <a href="{{ route('user.boarding-regulations.create') }}" class="btn btn-primary btn-lg float-end">
                <i class="ri-add-circle-fill me-1"></i>Buat Baru
            </a>

            {{-- Export Print Button --}}
            <a href="{{ route('user.boarding-regulations.export') }}" class="btn btn-outline-secondary btn-lg me-3">
                <i class="ri-download-excel-line me-1"></i>Ekspor PDF
            </a>

            <p class="text-muted mb-0 mt-3">Daftar peraturan asrama yang diterapkan di pesantren.</p>
        </div>
    </div>

    {{-- Table of Regulations --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">
                <i class="ri-list-ul me-2"></i>Daftar Peraturan
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="regulationTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;" class="text-center">No</th>
                            <th>Nama Peraturan</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($regulations as $regulation)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><strong>{{ $regulation->name }}</strong></td>
                            <td><span class="badge bg-info-subtle text-info">{{ $regulation->category->name ?? 'Unknown' }}</span></td>
                            <td>
                                @if($regulation->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('user.boarding-regulations.show', $regulation->id) }}" class="text-primary me-2">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.boarding-regulations.edit', $regulation->id) }}" class="text-warning">
                                    <i class="ri-edit-line"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h6 class="text-muted mb-1 mt-3">Belum Ada Peraturan</h6>
                                <p class="text-muted mb-0">Silakan buat peraturan asrama baru.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$regulations" />
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Table initialization
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('regulationTable');
        if (table) {
            // Add row numbers dynamically if needed
        }
    });
</script>
@endsection
