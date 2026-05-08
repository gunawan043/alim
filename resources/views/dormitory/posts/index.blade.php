@extends('layouts.master')
@section('title') Informasi Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Informasi @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Informasi Asrama</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.posts.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Buat Posting
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Judul informasi..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-control">
                                <option value="">Semua Kategori</option>
                                <option value="pengumuman" {{ request('category') == 'pengumuman' ? 'selected' : '' }}>Pengumuman</option>
                                <option value="undangan"   {{ request('category') == 'undangan' ? 'selected' : '' }}>Undangan</option>
                                <option value="laporan"    {{ request('category') == 'laporan' ? 'selected' : '' }}>Laporan</option>
                                <option value="darurat"    {{ request('category') == 'darurat' ? 'selected' : '' }}>Darurat</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Visibilitas</th>
                                    <th>Tanggal</th>
                                    <th>Penulis</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($posts as $i => $post)
                                    <tr>
                                        <td class="text-center">{{ $posts->firstItem() + $i }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($post->is_pinned)
                                                    <i class="ri-pushpin-line text-warning" title="Disemat"></i>
                                                @endif
                                                <a href="{{ route('user.asrama.posts.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                                                   class="text-decoration-none fw-semibold">
                                                    {{ $post->title }}
                                                </a>
                                                @if($post->needs_response)
                                                    <span class="badge bg-warning-subtle text-warning" title="Butuh respons">
                                                        <i class="ri-question-answer-line"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-muted small text-truncate" style="max-width:300px;">
                                                {{ Str::limit(strip_tags($post->content), 80) }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($post->category === 'pengumuman')
                                                <span class="badge bg-primary-subtle text-primary">Pengumuman</span>
                                            @elseif($post->category === 'undangan')
                                                <span class="badge bg-info-subtle text-info">Undangan</span>
                                            @elseif($post->category === 'laporan')
                                                <span class="badge bg-success-subtle text-success">Laporan</span>
                                            @elseif($post->category === 'darurat')
                                                <span class="badge bg-danger-subtle text-danger">Darurat</span>
                                            @else
                                                <span class="badge bg-secondary-subtle">{{ $post->category }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($post->visibility === 'wali')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-parent-line me-1"></i>Wali Santri
                                                </span>
                                            @elseif($post->visibility === 'pengurus')
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ri-admin-line me-1"></i>Pengurus
                                                </span>
                                            @elseif($post->visibility === 'umum')
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-global-line me-1"></i>Umum
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small">{{ $post->created_at->format('d/m/Y') }}</span>
                                            <div class="text-muted small">{{ $post->created_at->format('H:i') }}</div>
                                        </td>
                                        <td>
                                            <span class="small">{{ $post->creator?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.posts.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Lihat">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.asrama.posts.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('user.asrama.posts.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Hapus">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="ri-broadcast-line fs-1 d-block mb-2 text-muted"></i>
                                            Belum ada informasi posted.
                                            <br>
                                            <a href="{{ route('user.asrama.posts.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-sm btn-success mt-2">
                                                <i class="ri-add-line me-1"></i> Buat Posting Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $posts->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Hapus informasi ini?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection