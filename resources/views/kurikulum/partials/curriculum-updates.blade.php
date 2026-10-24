@php
    $curriculumUpdates = $curriculumUpdates ?? (array) config('curriculum_updates.items', []);
    $curriculumUpdatesAt = $curriculumUpdatesAt ?? (string) config('curriculum_updates.updated_at', '');
@endphp

@if(! empty($curriculumUpdates))
    <div class="card border-info-subtle mb-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-newspaper-line me-1 text-info"></i>Info Pembaruan Kurikulum</h5>
                <p class="text-muted mb-0 small">Ringkasan dari publikasi resmi Kemendikdasmen dan Kemenag untuk seluruh GTK.</p>
            </div>
            <div class="text-md-end">
                <span class="badge bg-info-subtle text-info">AI + sumber resmi</span>
                <div class="small text-muted mt-1">Diperbarui {{ $curriculumUpdatesAt ? \Carbon\Carbon::parse($curriculumUpdatesAt)->format('d/m/Y') : '-' }}</div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($curriculumUpdates as $update)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <span class="badge bg-primary-subtle text-primary">{{ $update['tag'] ?? 'Kurikulum' }}</span>
                                <small class="text-muted">{{ $update['date'] ?? '' }}</small>
                            </div>
                            <div class="fw-semibold mt-2">{{ $update['title'] ?? '' }}</div>
                            <p class="small text-muted mb-2 mt-1">{{ $update['summary'] ?? '' }}</p>
                            @if(! empty($update['url']))
                                <a href="{{ $update['url'] }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-underline">
                                    Baca sumber: {{ $update['source'] ?? 'tautan resmi' }} <i class="ri-external-link-line ms-1"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="alert alert-light border mt-3 mb-0 small">
                <i class="ri-information-line me-1 text-info"></i>
                Ringkasan ini dibantu AI dan dapat berubah bila regulasi diperbarui. Selalu cek tautan sumber resmi sebelum mengambil keputusan kurikulum.
            </div>
        </div>
    </div>
@endif
