@if(! empty($curriculumFlow))
    <div class="card border-primary-subtle mb-3" id="alur-kurikulum">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-flow-chart me-1 text-primary"></i>Alur Lengkap Penyusunan Kurikulum</h5>
                <p class="text-muted mb-0 small">Klik setiap langkah untuk membuka halaman terkait. Ikuti urutan dari profil sampai perangkat pembelajaran.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary">10 langkah</span>
        </div>
        <div class="card-body">
            <div class="curriculum-flow-grid">
                @foreach($curriculumFlow as $step)
                    <a href="{{ $step['url'] }}" class="curriculum-flow-card {{ $step['final'] ?? false ? 'border-success' : '' }}"
                       title="Buka {{ $step['title'] }}">
                        <div class="curriculum-flow-top">
                            <span class="curriculum-flow-number">{{ $loop->iteration }}</span>
                            <i class="{{ $step['icon'] ?? 'ri-file-line' }} curriculum-flow-icon"></i>
                        </div>
                        <div class="curriculum-flow-title">{{ $step['title'] }}</div>
                        <div class="curriculum-flow-desc">{{ $step['description'] }}</div>
                        <div class="curriculum-flow-open">
                            Buka halaman <i class="ri-arrow-right-line ms-1"></i>
                        </div>
                        @if(! $loop->last && $loop->iteration % 5 !== 0)
                            <i class="ri-arrow-right-line curriculum-flow-arrow"></i>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="alert alert-light border mt-3 mb-0 small">
                <i class="ri-information-line me-1 text-primary"></i>
                Perangkat Pembelajaran tidak dibuat dari nol. Dokumen mengambil konteks dari Profil Kurikulum, CP, TP, ATP, PROTA, PROSEM, JP efektif, dan kelas yang sama.
            </div>
        </div>
    </div>
@endif
