@extends('layouts.master')
@section('title', 'Pengaturan AI')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Pengaturan AI @endslot
        @slot('title') Pengaturan Provider AI @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info py-3 small" role="alert">
        <i class="ri-information-line me-1"></i>
        <strong>Multi-platform:</strong> pilih provider gratis/free tier, berbayar, atau lokal. API key disimpan <strong>terenkripsi</strong>.
        Model dari platform yang sama dapat <strong>berbagi satu API key</strong> (key grup) — cukup isi key di salah satu baris.
        Baris hasil seeder masih <strong>nonaktif</strong>; isi key lalu aktifkan model yang dipilih dan jadikan default.
        Provider default dipakai generator nama Arab, AI Assistant RPM/soal, dan chat AI.
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-robot-2-line text-primary me-1"></i>Provider AI</h5>
                <p class="text-muted mb-0 small">Satu provider default aktif. Endpoint harus mendukung <code>/chat/completions</code> (OpenAI-compatible).</p>
            </div>
            <button type="button" class="btn btn-success" onclick="openProviderForm()">
                <i class="ri-add-line me-1"></i>Tambah Provider
            </button>
        </div>

        <div class="card-body border-bottom py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="search" id="aiProviderSearch" class="form-control"
                           placeholder="Cari nama, model, atau platform…">
                </div>
                <div class="col-md-3">
                    <select id="aiProviderTier" class="form-select">
                        <option value="">Semua tier</option>
                        <option value="free">Gratis / free tier</option>
                        <option value="local">Lokal</option>
                        <option value="paid">Berbayar</option>
                    </select>
                </div>
                <div class="col-md-4 text-md-end text-muted small">
                    <span id="aiProviderCount">{{ $providers->count() }} model</span>
                    · Baru dari seeder masih nonaktif
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama / Platform</th>
                        <th>Model</th>
                        <th>Endpoint</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Tes Terakhir</th>
                        <th class="text-end" style="width:220px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                        @php
                            $tier = $provider->extra['tier'] ?? 'paid';
                            $tierLabel = match ($tier) {
                                'free' => 'Gratis',
                                'local' => 'Lokal',
                                'paid' => 'Berbayar',
                                default => ucfirst($tier),
                            };
                            $tierClass = match ($tier) {
                                'free' => 'bg-success-subtle text-success',
                                'local' => 'bg-info-subtle text-info',
                                default => 'bg-warning-subtle text-warning',
                            };
                            $needsKey = $presets[$provider->provider]['needs_key'] ?? true;
                            $hasOwnKey = filled($provider->api_key);
                            $hasGroupKey = $provider->keyGroup() && ($keyGroups[$provider->keyGroup()] ?? false);
                            $hasKey = $hasOwnKey || $hasGroupKey;
                        @endphp
                        <tr data-name="{{ strtolower($provider->name.' '.$provider->model.' '.($presets[$provider->provider]['label'] ?? $provider->provider)) }}"
                            data-tier="{{ $tier }}"
                            data-platform="{{ $provider->provider }}">
                            <td>
                                <div class="fw-medium">
                                    {{ $provider->name }}
                                    <span class="badge {{ $tierClass }} ms-1">{{ $tierLabel }}</span>
                                </div>
                                <div class="text-muted small">{{ $presets[$provider->provider]['label'] ?? $provider->provider }}</div>
                                @if(! empty($provider->extra['notes']))
                                    <div class="text-muted" style="font-size:11px;">{{ $provider->extra['notes'] }}</div>
                                @endif
                            </td>
                            <td>
                                <code>{{ $provider->model }}</code>
                                @if(! empty($provider->extra['pricing']))
                                    <div class="text-muted" style="font-size:11px;">{{ $provider->extra['pricing'] }}</div>
                                @endif
                            </td>
                            <td class="small text-muted text-break" style="max-width:320px">{{ $provider->endpoint }}</td>
                            <td class="text-center">
                                @if($provider->is_default)
                                    <span class="badge bg-primary-subtle text-primary">Default</span>
                                @endif
                                @if($provider->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                                @if($needsKey)
                                    @if($hasOwnKey)
                                        <span class="badge bg-success-subtle text-success">Key sendiri</span>
                                    @elseif($hasGroupKey)
                                        <span class="badge bg-info-subtle text-info">Key grup {{ $provider->keyGroup() }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Tanpa kunci</span>
                                    @endif
                                @else
                                    <span class="badge bg-info-subtle text-info">Lokal — tanpa key</span>
                                @endif
                            </td>
                            <td class="text-center small">
                                @if($provider->last_tested_at)
                                    <span class="badge {{ $provider->last_test_status === 'ok' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        {{ $provider->last_test_status === 'ok' ? 'OK' : 'Gagal' }}
                                    </span>
                                    <div class="text-muted" style="font-size:10px;">{{ $provider->last_tested_at->format('d M H:i') }}</div>
                                    @if($provider->last_test_message)
                                        <div class="text-muted text-truncate" style="max-width:180px;font-size:10px;" title="{{ $provider->last_test_message }}">{{ $provider->last_test_message }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <form method="POST" action="{{ route('user.sa.ai-providers.test', ['userId' => $userId, 'id' => $provider->id]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-info" title="Tes koneksi"><i class="ri-plug-line"></i></button>
                                    </form>
                                    @if(! $provider->is_default)
                                        <form method="POST" action="{{ route('user.sa.ai-providers.default', ['userId' => $userId, 'id' => $provider->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" title="Jadikan default"><i class="ri-star-line"></i></button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-soft-warning" title="Edit"
                                            data-provider="{{ json_encode($provider->only(['id', 'name', 'provider', 'endpoint', 'model', 'is_default', 'is_active'])) }}"
                                            data-has-key="{{ $provider->hasApiKey() ? '1' : '0' }}"
                                            onclick="openProviderForm(JSON.parse(this.dataset.provider), this.dataset.hasKey === '1')">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-provider" title="Hapus"
                                            data-action="{{ route('user.sa.ai-providers.destroy', ['userId' => $userId, 'id' => $provider->id]) }}"
                                            data-name="{{ $provider->name }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            <i class="ri-robot-2-line fs-3 d-block mb-2"></i>
                            Belum ada provider AI. Tambahkan provider berbayar atau lokal (Ollama/LM Studio).
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal form provider --}}
    <div class="modal fade" id="providerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="providerForm" action="{{ route('user.sa.ai-providers.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="providerMethod" value="POST">
                    <div class="modal-header bg-primary-subtle">
                        <h5 class="modal-title" id="providerModalTitle"><i class="ri-robot-2-line me-1"></i>Tambah Provider AI</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Provider <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="providerName" class="form-control" maxlength="100" required placeholder="Mis. OpenAI Utama">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Platform <span class="text-danger">*</span></label>
                                <select name="provider" id="providerPreset" class="form-select" onchange="applyPreset()">
                                    @foreach($presets as $key => $preset)
                                        <option value="{{ $key }}">{{ $preset['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="providerNote"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Endpoint Chat Completions <span class="text-danger">*</span></label>
                                <input type="url" name="endpoint" id="providerEndpoint" class="form-control" maxlength="500" required placeholder="https://.../v1/chat/completions">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <input type="text" name="model" id="providerModel" class="form-control" maxlength="150" required placeholder="gpt-4o-mini">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">API Key <span class="text-muted small">(opsional untuk lokal)</span></label>
                                <input type="password" name="api_key" id="providerKey" class="form-control" maxlength="2000" autocomplete="new-password" placeholder="sk-...">
                                <div class="form-text" id="providerKeyHint">Biarkan kosong untuk endpoint lokal (Ollama/LM Studio).</div>
                                <div class="form-check mt-2 d-none" id="clearKeyWrap">
                                    <input class="form-check-input" type="checkbox" name="clear_api_key" value="1" id="clearProviderKey">
                                    <label class="form-check-label small" for="clearProviderKey">Hapus API key tersimpan</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="providerActive" checked>
                                    <label class="form-check-label" for="providerActive">Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="providerDefault">
                                    <label class="form-check-label" for="providerDefault">Jadikan default</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <form id="deleteProviderForm" method="POST" class="d-none" action="">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteProviderModal"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primary-color="#f7b84b" secondary-color="#f06548"
        title="Hapus Provider AI?"
        message="Provider <strong id=&quot;deleteProviderName&quot;>ini</strong> akan dihapus. Bila provider default dihapus, provider aktif lain akan dijadikan default."
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger"
        form-id="deleteProviderForm" />
@endsection

@section('script')
    <script>
        const AI_PRESETS = @json($presets);
        const PROVIDER_STORE_URL = @json(route('user.sa.ai-providers.store', ['userId' => $userId]));
        const PROVIDER_UPDATE_URL = @json(route('user.sa.ai-providers.update', ['userId' => $userId, 'id' => '__ID__']));

        function applyPreset() {
            const key = document.getElementById('providerPreset').value;
            const preset = AI_PRESETS[key] || {};
            if (preset.endpoint) document.getElementById('providerEndpoint').value = preset.endpoint;
            if (preset.model) document.getElementById('providerModel').value = preset.model;
            document.getElementById('providerNote').textContent = preset.note || '';
            document.getElementById('providerKeyHint').textContent = preset.needs_key
                ? 'Diperlukan API key untuk platform ini.'
                : 'Endpoint lokal tidak memerlukan API key (boleh kosong).';
        }

        function openProviderForm(data, hasKey) {
            const isEdit = !!data;
            document.getElementById('providerModalTitle').innerHTML = isEdit
                ? '<i class="ri-robot-2-line me-1"></i>Edit Provider AI'
                : '<i class="ri-robot-2-line me-1"></i>Tambah Provider AI';
            document.getElementById('providerForm').action = isEdit
                ? PROVIDER_UPDATE_URL.replace('__ID__', data.id)
                : PROVIDER_STORE_URL;
            document.getElementById('providerMethod').value = isEdit ? 'PUT' : 'POST';

            document.getElementById('providerName').value = isEdit ? (data.name || '') : '';
            document.getElementById('providerPreset').value = isEdit ? (data.provider || 'custom') : 'openai';
            document.getElementById('providerEndpoint').value = isEdit ? (data.endpoint || '') : '';
            document.getElementById('providerModel').value = isEdit ? (data.model || '') : '';
            document.getElementById('providerKey').value = '';
            document.getElementById('providerKey').placeholder = isEdit && hasKey
                ? '•••••• (biarkan kosong untuk tetap)'
                : 'sk-...';
            document.getElementById('providerActive').checked = isEdit ? !!data.is_active : true;
            document.getElementById('providerDefault').checked = isEdit ? !!data.is_default : false;
            document.getElementById('clearKeyWrap').classList.toggle('d-none', !(isEdit && hasKey));
            document.getElementById('clearProviderKey').checked = false;

            if (! isEdit) applyPreset();
            else document.getElementById('providerNote').textContent = (AI_PRESETS[data.provider] || {}).note || '';

            new bootstrap.Modal(document.getElementById('providerModal')).show();
        }

        document.querySelectorAll('.delete-provider').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = document.getElementById('deleteProviderForm');
                form.action = this.dataset.action;
                document.getElementById('deleteProviderName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteProviderModal')).show();
            });
        });

        // Filter katalog provider (client-side, ringan).
        (function () {
            const search = document.getElementById('aiProviderSearch');
            const tier = document.getElementById('aiProviderTier');
            const count = document.getElementById('aiProviderCount');
            if (! search || ! tier || ! count) return;

            const rows = Array.from(document.querySelectorAll('tbody tr[data-name]'));

            function applyFilter() {
                const q = (search.value || '').trim().toLowerCase();
                const selected = tier.value;
                let visible = 0;

                rows.forEach(function (row) {
                    const matchesText = ! q || (row.dataset.name || '').includes(q);
                    const matchesTier = ! selected || row.dataset.tier === selected;
                    const show = matchesText && matchesTier;
                    row.classList.toggle('d-none', ! show);
                    if (show) visible++;
                });

                count.textContent = visible + ' model';
            }

            search.addEventListener('input', applyFilter);
            tier.addEventListener('change', applyFilter);
        })();
    </script>
@endsection
