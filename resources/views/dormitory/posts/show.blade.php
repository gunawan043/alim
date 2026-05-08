@extends('layouts.master')
@section('title') Detail Informasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Informasi</a> @endslot
        @slot('title') {{ Str::limit($post->title, 30) }} @endslot
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
        {{-- Main Content --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if($post->is_pinned)
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="ri-pushpin-line me-1"></i>Disemat
                                </span>
                            @endif
                            @if($post->category === 'pengumuman')
                                <span class="badge bg-primary-subtle text-primary">Pengumuman</span>
                            @elseif($post->category === 'undangan')
                                <span class="badge bg-info-subtle text-info">Undangan</span>
                            @elseif($post->category === 'laporan')
                                <span class="badge bg-success-subtle text-success">Laporan</span>
                            @elseif($post->category === 'darurat')
                                <span class="badge bg-danger-subtle text-danger">Darurat</span>
                            @endif
                            @if($post->needs_response)
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="ri-question-answer-line me-1"></i>Butuh Respons
                                </span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.asrama.posts.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="ri-edit-line me-1"></i> Edit
                            </a>
                            <form method="POST"
                                  action="{{ route('user.asrama.posts.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
                                  class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="ri-delete-bin-line me-1"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3">{{ $post->title }}</h4>

                    {{-- Meta --}}
                    <div class="d-flex flex-wrap gap-3 text-muted small mb-4 pb-3 border-bottom">
                        <div>
                            <i class="ri-user-line me-1"></i>
                            {{ $post->creator?->name ?? '—' }}
                        </div>
                        <div>
                            <i class="ri-calendar-line me-1"></i>
                            {{ $post->created_at->format('d/m/Y') }}
                            <span class="text-muted">{{ $post->created_at->format('H:i') }}</span>
                        </div>
                        @if($post->created_at != $post->updated_at)
                        <div>
                            <i class="ri-edit-2-line me-1"></i>
                            Diedit: {{ $post->updated_at->format('d/m/Y H:i') }}
                        </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="post-content mb-4">
                        {!! nl2br(e($post->content)) !!}
                    </div>

                    {{-- Attachment --}}
                    @if($post->attachment_path)
                    <div class="alert alert-light d-flex align-items-center gap-3">
                        <i class="ri-attachment-2 fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">Lampiran</div>
                            <a href="{{ asset('storage/' . $post->attachment_path) }}"
                               target="_blank" class="text-decoration-none small">
                                <i class="ri-file-line me-1"></i>
                                {{ basename($post->attachment_path) }}
                            </a>
                        </div>
                    </div>
                    @endif

                    {{-- Visibility Info --}}
                    <div class="text-muted small mt-3 pt-3 border-top">
                        <i class="ri-eye-line me-1"></i>
                        Visibilitas:
                        @if($post->visibility === 'wali')
                            <span class="badge bg-warning-subtle text-warning">Wali Santri</span>
                        @elseif($post->visibility === 'pengurus')
                            <span class="badge bg-info-subtle text-info">Pengurus Asrama</span>
                        @elseif($post->visibility === 'umum')
                            <span class="badge bg-secondary-subtle text-secondary">Umum</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Responses Section (if needs_response) --}}
            @if($post->needs_response)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-question-answer-line me-2 text-primary"></i>
                        Respons Wali Santri
                        @if($post->responses && $post->responses->count() > 0)
                            <span class="badge bg-primary ms-2">{{ $post->responses->count() }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($post->responses && $post->responses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:50px;">No</th>
                                        <th>Nama Santri</th>
                                        <th>Wali</th>
                                        <th>Jenis Respons</th>
                                        <th>Pesan</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($post->responses as $idx => $resp)
                                        <tr>
                                            <td class="text-center">{{ $idx + 1 }}</td>
                                            <td>{{ $resp->pivot->student_id ? ($resp->name ?? '—') : '—' }}</td>
                                            <td>{{ $resp->pivot->parent_name ?? '—' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $resp->pivot->response_type === 'confirm' ? 'success' : ($resp->pivot->response_type === 'reject' ? 'danger' : 'info') }}-subtle">
                                                    {{ ucfirst($resp->pivot->response_type ?? '—') }}
                                                </span>
                                            </td>
                                            <td>{{ $resp->pivot->message ?? '—' }}</td>
                                            <td>
                                                @if($resp->pivot->created_at)
                                                    {{ \Carbon\Carbon::parse($resp->pivot->created_at)->format('d/m/Y H:i') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ri-question-answer-line fs-1 d-block mb-2 text-muted"></i>
                            Belum ada respons dari wali sanksi.
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-information-line me-2 text-primary"></i>Detail</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Asrama</div>
                        <div class="fw-semibold">{{ $dormitory->name }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Kategori</div>
                        <div class="fw-semibold">{{ $post->category_text }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Visibilitas</div>
                        <div class="fw-semibold">{{ $post->visibility_text }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Butuh Respons</div>
                        <div class="fw-semibold">
                            @if($post->needs_response)
                                <span class="badge bg-warning-subtle text-warning">Ya</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Disemat</div>
                        <div class="fw-semibold">
                            @if($post->is_pinned)
                                <span class="badge bg-warning-subtle text-warning">Ya</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="text-muted small">Penulis</div>
                        <div class="fw-semibold">{{ $post->creator?->name ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Dibuat</div>
                        <div class="fw-semibold">{{ $post->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($post->updated_at && $post->updated_at != $post->created_at)
                    <div class="mb-3">
                        <div class="text-muted small">Terakhir Diedit</div>
                        <div class="fw-semibold">{{ $post->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
               class="btn btn-light w-100 mt-3">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Hapus informasi ini? Tindakan ini tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection