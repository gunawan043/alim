@extends('layouts.master')
@section('title') Edit Informasi — {{ $post->title ?? 'Post' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Informasi</a> @endslot
        @slot('title') Edit @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.posts.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'postUuid' => $post->id]) }}"
          enctype="multipart/form-data"
          id="postForm">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Left: Main Content --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-broadcast-line me-2 text-primary"></i>Form Informasi
                        </h5>
                    </div>
                    <div class="card-body">

                        {{-- Title --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Judul <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="Contoh: Jadwal Kegiatan Bulan Ramadhan"
                                   value="{{ old('title', $post->title) }}" required maxlength="255">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Content --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Isi Informasi <span class="text-danger">*</span>
                            </label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror"
                                      rows="10" placeholder="Tulis informasi selengkap mungkin..."
                                      required>{{ old('content', $post->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Attachment --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lampiran (Opsional)</label>
                            @if($post->attachment_path)
                                <div class="alert alert-secondary py-2 mb-2 d-flex align-items-center gap-2">
                                    <i class="ri-file-paper-2-line text-primary"></i>
                                    <span class="small">Lampiran saat ini:
                                        <a href="{{ asset('storage/' . $post->attachment_path) }}"
                                           target="_blank" class="text-decoration-underline">
                                            {{ basename($post->attachment_path) }}
                                        </a>
                                    </span>
                                </div>
                                <div class="form-text text-danger mb-2">Unggah file baru untuk mengganti lampiran yang ada. Kosongkan jika tidak ingin mengubah.</div>
                            @endif
                            <input type="file" name="attachment" id="attachment"
                                   class="form-control @error('attachment') is-invalid @enderror"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            <div class="form-text">Format: PDF, DOC, JPG, PNG, ZIP. Maksimal 5 MB.</div>
                            @error('attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Right: Metadata --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-settings-2-line me-2 text-primary"></i>Pengaturan</h5>
                    </div>
                    <div class="card-body">

                        {{-- Category --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Kategori <span class="text-danger">*</span>
                            </label>
                            <select name="category" id="category"
                                    class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="pengumuman" {{ old('category', $post->category) == 'pengumuman' ? 'selected' : '' }}>Pengumuman</option>
                                <option value="undangan"   {{ old('category', $post->category) == 'undangan'   ? 'selected' : '' }}>Undangan</option>
                                <option value="laporan"    {{ old('category', $post->category) == 'laporan'    ? 'selected' : '' }}>Laporan</option>
                                <option value="darurat"    {{ old('category', $post->category) == 'darurat'    ? 'selected' : '' }}>Darurat</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Visibility --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Visibilitas <span class="text-danger">*</span>
                            </label>
                            <select name="visibility" id="visibility"
                                    class="form-select @error('visibility') is-invalid @enderror" required>
                                <option value="">-- Pilih Visibilitas --</option>
                                <option value="wali"      {{ old('visibility', $post->visibility) == 'wali'      ? 'selected' : '' }}>Wali Santri</option>
                                <option value="pengurus"  {{ old('visibility', $post->visibility) == 'pengurus'  ? 'selected' : '' }}>Pengurus Asrama</option>
                                <option value="umum"      {{ old('visibility', $post->visibility) == 'umum'      ? 'selected' : '' }}>Umum</option>
                            </select>
                            @error('visibility')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        {{-- Needs Response --}}
                        <div class="form-check mb-3">
                            <input type="checkbox" name="needs_response" id="needs_response"
                                   class="form-check-input" value="1"
                                   {{ old('needs_response', $post->needs_response) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="needs_response">
                                Butuh Respons dari Wali
                            </label>
                            <div class="form-text text-muted small">
                                Jika dicentang, wali bisa mengirim respons/balasan.
                            </div>
                        </div>

                        {{-- Is Pinned --}}
                        <div class="form-check mb-4">
                            <input type="checkbox" name="is_pinned" id="is_pinned"
                                   class="form-check-input" value="1"
                                   {{ old('is_pinned', $post->is_pinned) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_pinned">
                                Sematkan di Atas
                            </label>
                            <div class="form-text text-muted small">
                                Jika dicentang, informasi akan selalu muncul di paling atas.
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Post Info --}}
                <div class="card mt-3">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-information-line me-2 text-primary"></i>Info Informasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label text-muted small">ID</label>
                            <div><code class="small">{{ $post->id }}</code></div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small">Dibuat</label>
                            <div class="small">{{ $post->created_at->format('d M Y, H:i') }}</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted small">Terakhir Update</label>
                            <div class="small">{{ $post->updated_at->format('d M Y, H:i') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="submitBtn">
                        <i class="ri-save-line me-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
    document.getElementById('postForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    });
</script>
@endsection
