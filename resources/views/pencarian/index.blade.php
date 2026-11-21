@extends('layouts.master')
@section('title', 'Pencarian')

@section('css')
<style>
    .cat-page {
        --cat-paper: #f4f0e6;
        --cat-surface: #fffdf8;
        --cat-line: #e1dacb;
        --cat-ink: #202a2a;
        --cat-muted: #68706d;
        --cat-soft: #8b918c;
        --cat-accent: #1f5c52;
        --cat-accent-soft: #e8efeb;
        --cat-warn: #8a5a1f;
        color: var(--cat-ink);
        padding-bottom: 40px;
    }
    .cat-page * { box-sizing: border-box; }
    .cat-page-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
    }
    .cat-kicker {
        font-size: 11px;
        line-height: 1.4;
        color: var(--cat-soft);
        margin-bottom: 6px;
    }
    .cat-title {
        font-size: 26px;
        line-height: 1.2;
        font-weight: 500;
        letter-spacing: -0.015em;
        color: var(--cat-ink);
        margin: 0;
    }
    .cat-sub {
        max-width: 620px;
        margin: 8px 0 0;
        font-size: 13px;
        line-height: 1.6;
        color: var(--cat-muted);
    }
    .cat-head-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .cat-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 34px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid transparent;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
    }
    .cat-btn-quiet {
        background: var(--cat-surface);
        border-color: var(--cat-line);
        color: var(--cat-ink);
    }
    .cat-btn-quiet:hover {
        background: #faf7f0;
        border-color: #cbc3b5;
        color: var(--cat-ink);
    }
    .cat-btn-primary {
        background: var(--cat-accent);
        color: #fff;
    }
    .cat-btn-primary:hover { background: #174940; color: #fff; }
    .cat-btn:focus-visible,
    .cat-source:focus-visible,
    .cat-tab:focus-visible,
    .cat-suggestion:focus-visible,
    .cat-action:focus-visible {
        outline: 2px solid var(--cat-accent);
        outline-offset: 2px;
    }
    .cat-search-panel {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid var(--cat-line);
        border-radius: 14px;
        background: var(--cat-paper);
    }
    .cat-search-label {
        display: block;
        margin-bottom: 8px;
        font-size: 12px;
        color: var(--cat-muted);
    }
    .cat-search-box {
        display: flex;
        align-items: stretch;
        min-height: 50px;
        overflow: hidden;
        border: 1px solid var(--cat-line);
        border-radius: 10px;
        background: var(--cat-surface);
    }
    .cat-search-box:focus-within {
        border-color: var(--cat-accent);
        box-shadow: 0 0 0 3px rgba(31, 92, 82, 0.1);
    }
    .cat-search-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        flex-shrink: 0;
        color: var(--cat-soft);
        font-size: 16px;
    }
    .cat-search-box input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--cat-ink);
        font-size: 15px;
        padding: 0 12px 0 0;
    }
    .cat-search-box input::placeholder { color: #9a9f99; }
    .cat-search-box button {
        border: 0;
        background: var(--cat-accent);
        color: #fff;
        padding: 0 22px;
        font-size: 12px;
        font-weight: 500;
    }
    .cat-search-box button:hover { background: #174940; }
    .cat-hint {
        margin-top: 8px;
        font-size: 11px;
        line-height: 1.5;
        color: var(--cat-soft);
    }
    .cat-layout {
        display: grid;
        grid-template-columns: 232px minmax(0, 1fr);
        gap: 32px;
        align-items: start;
        margin-top: 24px;
    }
    .cat-rail { position: sticky; top: 16px; }
    .cat-rail-block + .cat-rail-block {
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--cat-line);
    }
    .cat-rail-title {
        margin: 0 0 8px;
        font-size: 11px;
        color: var(--cat-soft);
    }
    .cat-source {
        display: flex;
        gap: 10px;
        padding: 10px 12px;
        border-left: 2px solid transparent;
        border-radius: 0 8px 8px 0;
        color: var(--cat-ink);
        text-decoration: none;
    }
    .cat-source + .cat-source { margin-top: 2px; }
    .cat-source:hover { background: rgba(32, 42, 42, 0.035); }
    .cat-source.active {
        border-left-color: var(--cat-accent);
        background: var(--cat-accent-soft);
    }
    .cat-source > i {
        width: 16px;
        margin-top: 1px;
        flex-shrink: 0;
        font-size: 16px;
        color: var(--cat-soft);
        text-align: center;
    }
    .cat-source.active > i { color: var(--cat-accent); }
    .cat-source-label {
        display: block;
        font-size: 12.5px;
        font-weight: 500;
        line-height: 1.35;
    }
    .cat-source-desc {
        display: block;
        margin-top: 2px;
        font-size: 11px;
        line-height: 1.45;
        color: var(--cat-muted);
    }
    .cat-scope {
        font-size: 12px;
        line-height: 1.65;
        color: var(--cat-muted);
    }
    .cat-scope strong { color: var(--cat-ink); font-weight: 500; }
    .cat-results { min-width: 0; }
    .cat-results-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--cat-line);
    }
    .cat-result-label {
        margin-bottom: 3px;
        font-size: 11px;
        color: var(--cat-soft);
    }
    .cat-result-count {
        font-size: 13px;
        color: var(--cat-muted);
        font-variant-numeric: tabular-nums;
    }
    .cat-result-count strong { color: var(--cat-ink); font-weight: 500; }
    .cat-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }
    .cat-tab {
        padding: 8px 10px 10px;
        margin-bottom: -13px;
        border-bottom: 2px solid transparent;
        color: var(--cat-muted);
        font-size: 12px;
        text-decoration: none;
        white-space: nowrap;
    }
    .cat-tab:hover { color: var(--cat-ink); }
    .cat-tab.active {
        border-bottom-color: var(--cat-accent);
        color: var(--cat-accent);
        font-weight: 500;
    }
    .cat-group { margin-top: 28px; }
    .cat-group-head {
        display: flex;
        align-items: baseline;
        gap: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--cat-line);
    }
    .cat-group-index {
        font-size: 11px;
        color: var(--cat-soft);
        font-variant-numeric: tabular-nums;
    }
    .cat-group-title {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
        color: var(--cat-ink);
    }
    .cat-group-count {
        margin-left: auto;
        font-size: 11px;
        color: var(--cat-soft);
        font-variant-numeric: tabular-nums;
    }
    .cat-item {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 16px 0;
        border-bottom: 1px solid var(--cat-line);
    }
    .cat-item:last-child { border-bottom: 0; }
    .cat-item-main { min-width: 0; }
    .cat-item-title {
        display: inline;
        color: var(--cat-ink);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.45;
        text-decoration: none;
    }
    .cat-item-title:hover {
        color: var(--cat-accent);
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .cat-item-excerpt {
        max-width: 760px;
        margin: 4px 0 0;
        color: var(--cat-muted);
        font-size: 12.5px;
        line-height: 1.55;
    }
    .cat-item-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0;
        margin-top: 8px;
        color: var(--cat-soft);
        font-size: 11.5px;
        font-variant-numeric: tabular-nums;
    }
    .cat-item-meta .sep { margin: 0 7px; color: #c5bdae; }
    .cat-actions {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        flex-shrink: 0;
    }
    .cat-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 8px;
        color: var(--cat-warn);
        font-size: 11px;
        white-space: nowrap;
    }
    .cat-status::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #ad772b;
    }
    .cat-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 1px solid var(--cat-line);
        border-radius: 8px;
        background: var(--cat-surface);
        color: var(--cat-muted);
        font-size: 15px;
    }
    .cat-action:hover {
        border-color: #c5bdae;
        background: #faf7f0;
        color: var(--cat-ink);
    }
    .cat-empty,
    .cat-panel {
        margin-top: 24px;
        border: 1px solid var(--cat-line);
        border-radius: 12px;
        background: var(--cat-surface);
    }
    .cat-empty {
        display: grid;
        grid-template-columns: 168px minmax(0, 1fr);
        gap: 32px;
        align-items: center;
        padding: 32px;
        text-align: left;
    }
    .cat-empty-plain {
        display: block;
        padding: 28px;
    }
    .cat-empty-drawer {
        display: flex;
        flex-direction: column;
        margin: 0;
    }
    .cat-empty-drawer span {
        height: 40px;
        padding-left: 16px;
        border: 1px solid var(--cat-line);
        border-bottom: 0;
        background: var(--cat-paper);
        color: var(--cat-soft);
        font-size: 10px;
        line-height: 40px;
    }
    .cat-empty-drawer span:first-child { border-radius: 8px 8px 0 0; }
    .cat-empty-drawer span:last-child {
        border-bottom: 1px solid var(--cat-line);
        border-radius: 0 0 8px 8px;
    }
    .cat-empty-title {
        margin: 0;
        color: var(--cat-ink);
        font-size: 15px;
        font-weight: 500;
    }
    .cat-empty-text {
        max-width: none;
        margin: 6px 0 0;
        color: var(--cat-muted);
        font-size: 12.5px;
        line-height: 1.6;
    }
    .cat-suggestions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 8px 16px;
        margin-top: 18px;
    }
    .cat-suggestion {
        padding: 3px 0;
        border-bottom: 1px solid var(--cat-line);
        color: var(--cat-muted);
        font-size: 12px;
        text-decoration: none;
    }
    .cat-suggestion:hover { border-bottom-color: var(--cat-accent); color: var(--cat-accent); }
    .cat-panel { padding: 22px 24px; }
    .cat-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--cat-line);
    }
    .cat-panel-title {
        margin: 0;
        color: var(--cat-ink);
        font-size: 15px;
        font-weight: 500;
    }
    .cat-note { color: var(--cat-soft); font-size: 11px; }
    .cat-ai-answer {
        white-space: pre-wrap;
        color: var(--cat-ink);
        font-size: 13.5px;
        line-height: 1.75;
    }
    .cat-notice {
        padding: 12px 14px;
        border-left: 2px solid #ad772b;
        border-radius: 0 8px 8px 0;
        background: #faf4e8;
        color: #694717;
        font-size: 12.5px;
        line-height: 1.6;
    }
    .cat-web-title {
        color: var(--cat-ink);
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
    }
    .cat-web-title:hover { color: var(--cat-accent); text-decoration: underline; text-underline-offset: 3px; }
    .cat-web-domain { color: var(--cat-soft); font-size: 11px; }
    @media (max-width: 991.98px) {
        .cat-page-head { flex-direction: column; gap: 16px; }
        .cat-layout { grid-template-columns: 1fr; gap: 24px; }
        .cat-rail { position: static; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .cat-rail-block + .cat-rail-block { margin-top: 0; padding-top: 0; border-top: 0; }
        .cat-results-head { align-items: flex-start; flex-direction: column; gap: 12px; }
        .cat-tab { margin-bottom: -1px; }
    }
    @media (max-width: 575.98px) {
        .cat-page-head { gap: 12px; }
        .cat-title { font-size: 22px; }
        .cat-search-panel { padding: 16px; }
        .cat-search-box { min-height: 46px; }
        .cat-search-box button { padding: 0 16px; }
        .cat-rail { grid-template-columns: 1fr; gap: 20px; }
        .cat-empty { grid-template-columns: 1fr; gap: 20px; padding: 24px; }
        .cat-empty-drawer { flex-direction: row; }
        .cat-empty-drawer span {
            flex: 1;
            height: 34px;
            padding-left: 0;
            line-height: 34px;
            text-align: center;
        }
        .cat-item { flex-direction: column; gap: 10px; }
        .cat-actions { align-items: center; }
        .cat-status { margin-top: 0; }
        .cat-panel { padding: 18px; }
    }
</style>
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('title') Pencarian @endslot
    @endcomponent

    <div class="cat-page">
        <div class="cat-page-head">
            <div>
                <div class="cat-kicker">Katalog sekolah</div>
                <h1 class="cat-title">Cari soal, RPM, dan bahan ajar</h1>
                <p class="cat-sub">Satu tempat untuk menelusuri soal, perangkat pembelajaran, bank soal, dan paket asesmen di lingkungan sekolah Anda.</p>
            </div>
            <div class="cat-head-actions">
                <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="cat-btn cat-btn-quiet">Soal Saya</a>
                <a href="{{ route('user.soal-saya.create', ['userId' => $userId]) }}" class="cat-btn cat-btn-primary">
                    <i class="ri-add-line"></i>Buat soal
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('user.pencarian.index', ['userId' => $userId]) }}" class="cat-search-panel" role="search">
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <label class="cat-search-label" for="cat-q">Kata kunci</label>
            <div class="cat-search-box">
                <span class="cat-search-icon"><i class="ri-search-line"></i></span>
                <input id="cat-q" type="search" name="q" value="{{ $query }}" placeholder="Mapel, topik, atau judul" autofocus>
                <button type="submit">Cari</button>
            </div>
            <div class="cat-hint">Gunakan kata kunci singkat. Pilih sumber di bawah untuk mengubah cakupan pencarian.</div>
        </form>

        <div class="cat-layout">
            <aside class="cat-rail">
                <div class="cat-rail-block">
                    <p class="cat-rail-title">Sumber</p>
                    <a class="cat-source {{ $source === 'db' ? 'active' : '' }}"
                       href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'db', 'type' => $type]) }}">
                        <i class="ri-archive-line"></i>
                        <span>
                            <span class="cat-source-label">Database sekolah</span>
                            <span class="cat-source-desc">Soal, RPM, bank, paket</span>
                        </span>
                    </a>
                    <a class="cat-source {{ $source === 'ai' ? 'active' : '' }}"
                       href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'ai', 'type' => $type]) }}">
                        <i class="ri-lightbulb-line"></i>
                        <span>
                            <span class="cat-source-label">Rekomendasi AI</span>
                            <span class="cat-source-desc">Saran materi dan kata kunci</span>
                        </span>
                    </a>
                    <a class="cat-source {{ $source === 'internet' ? 'active' : '' }}"
                       href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'internet', 'type' => $type]) }}">
                        <i class="ri-global-line"></i>
                        <span>
                            <span class="cat-source-label">Internet</span>
                            <span class="cat-source-desc">Referensi dari web</span>
                        </span>
                    </a>
                </div>
                <div class="cat-rail-block">
                    <p class="cat-rail-title">Ruang lingkup</p>
                    <div class="cat-scope">
                        <strong>{{ $school->name }}</strong><br>
                        Data yang dapat Anda akses di satuan pendidikan ini.
                    </div>
                </div>
            </aside>

            <main class="cat-results">
                @if($query === '')
                    <div class="cat-empty">
                        <div class="cat-empty-drawer" aria-hidden="true">
                            <span>Soal</span><span>RPM</span><span>Referensi</span>
                        </div>
                        <div class="cat-empty-copy">
                            <h2 class="cat-empty-title">Belum ada kata kunci</h2>
                            <p class="cat-empty-text">Mulai dengan topik, judul, atau nama mapel. Anda dapat mencari di database sekolah, meminta saran AI, atau menelusuri internet.</p>
                            <div class="cat-suggestions">
                                <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'bilangan bulat', 'source' => 'db']) }}">bilangan bulat</a>
                                <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'teks eksposisi', 'source' => 'db']) }}">teks eksposisi</a>
                                <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'tahfidz', 'source' => 'db']) }}">tahfidz</a>
                                <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'asesmen formatif', 'source' => 'ai']) }}">rekomendasi AI</a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="cat-results-head">
                        <div>
                            <div class="cat-result-label">
                                @if($source === 'db') Database sekolah
                                @elseif($source === 'ai') Rekomendasi AI
                                @else Hasil internet
                                @endif
                            </div>
                            <div class="cat-result-count">
                                @if($source === 'db')
                                    <strong>{{ $total }}</strong> hasil untuk &ldquo;{{ $query }}&rdquo;
                                @elseif($source === 'ai')
                                    Saran untuk &ldquo;{{ $query }}&rdquo;
                                @else
                                    {{ count($webResults) }} tautan untuk &ldquo;{{ $query }}&rdquo;
                                @endif
                            </div>
                        </div>
                        @if($source === 'db')
                            <nav class="cat-tabs" aria-label="Jenis data">
                                @foreach(['all' => 'Semua', 'soal' => 'Soal', 'rpm' => 'RPM', 'bank' => 'Bank soal', 'paket' => 'Paket soal'] as $value => $label)
                                    <a class="cat-tab {{ $type === $value ? 'active' : '' }}"
                                       href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'db', 'type' => $value]) }}"
                                       @if($type === $value) aria-current="page" @endif>{{ $label }}</a>
                                @endforeach
                            </nav>
                        @else
                            <span class="cat-note">{{ $source === 'ai' ? 'Periksa kembali' : 'Tautan eksternal' }}</span>
                        @endif
                    </div>

                    @if($source === 'db')
                        @if(in_array($type, ['all', 'soal'], true) && $soalResults->isNotEmpty())
                            <section class="cat-group" aria-labelledby="cat-group-soal">
                                <div class="cat-group-head">
                                    <span class="cat-group-index">01</span>
                                    <h2 class="cat-group-title" id="cat-group-soal">Soal</h2>
                                    <span class="cat-group-count">{{ $soalResults->count() }} entri</span>
                                </div>
                                @foreach($soalResults as $soal)
                                    @php
                                        $jenis = \App\Models\Soal::JENIS_ASESMEN_OPTIONS[$soal->jenis_asesmen] ?? $soal->jenis_asesmen;
                                        $tipe = ucfirst(strtolower(str_replace('_', ' ', $soal->tipe_soal ?? '')));
                                        $excerpt = \Illuminate\Support\Str::limit(strip_tags((string) ($soal->materi ?: $soal->pembahasan)), 180);
                                    @endphp
                                    <article class="cat-item">
                                        <div class="cat-item-main">
                                            <a class="cat-item-title" href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $soal->bank_soal_id, 'id' => $soal->id]) }}">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 170) }}
                                            </a>
                                            @if($excerpt !== '')
                                                <p class="cat-item-excerpt">{{ $excerpt }}</p>
                                            @endif
                                            <div class="cat-item-meta">
                                                <span>{{ $soal->bankSoal?->subject?->name ?? '-' }}</span>
                                                @if($soal->bankSoal?->gradeLevel?->name)
                                                    <span class="sep">&middot;</span><span>{{ $soal->bankSoal->gradeLevel->name }}</span>
                                                @endif
                                                @if($jenis)
                                                    <span class="sep">&middot;</span><span>{{ $jenis }}</span>
                                                @endif
                                                @if($tipe !== '')
                                                    <span class="sep">&middot;</span><span>{{ $tipe }}</span>
                                                @endif
                                                @if($soal->creator?->name)
                                                    <span class="sep">&middot;</span><span>{{ $soal->creator->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="cat-actions">
                                            @if($soal->requiresValidation() && ! $soal->isValidated())
                                                <span class="cat-status">Perlu validasi</span>
                                            @endif
                                            <form method="POST" action="{{ route('user.soal-saya.clone', ['userId' => $userId, 'id' => $soal->id]) }}">
                                                @csrf
                                                <button type="submit" class="cat-action" title="Klon ke Soal Saya" aria-label="Klon ke Soal Saya"><i class="ri-file-copy-line"></i></button>
                                            </form>
                                            <a class="cat-action" href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $soal->bank_soal_id, 'id' => $soal->id]) }}" title="Buka soal" aria-label="Buka soal"><i class="ri-arrow-right-line"></i></a>
                                        </div>
                                    </article>
                                @endforeach
                            </section>
                        @endif

                        @if(in_array($type, ['all', 'rpm'], true) && $rpmResults->isNotEmpty())
                            <section class="cat-group" aria-labelledby="cat-group-rpm">
                                <div class="cat-group-head">
                                    <span class="cat-group-index">02</span>
                                    <h2 class="cat-group-title" id="cat-group-rpm">RPM / Perangkat</h2>
                                    <span class="cat-group-count">{{ $rpmResults->count() }} entri</span>
                                </div>
                                @foreach($rpmResults as $rpm)
                                    @php $rpmStatus = \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$rpm->status] ?? $rpm->status; @endphp
                                    <article class="cat-item">
                                        <div class="cat-item-main">
                                            <a class="cat-item-title" href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $rpm->id]) }}">{{ $rpm->judul }}</a>
                                            @if(filled($rpm->catatan))
                                                <p class="cat-item-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) $rpm->catatan), 180) }}</p>
                                            @endif
                                            <div class="cat-item-meta">
                                                <span>{{ $rpm->subject?->name ?? '-' }}</span>
                                                @if($rpm->gradeLevel?->name)<span class="sep">&middot;</span><span>{{ $rpm->gradeLevel->name }}</span>@endif
                                                @if($rpm->teacher?->name)<span class="sep">&middot;</span><span>{{ $rpm->teacher->name }}</span>@endif
                                                @if($rpmStatus)<span class="sep">&middot;</span><span>{{ $rpmStatus }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="cat-actions">
                                            <a class="cat-action" href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $rpm->id]) }}" title="Buka RPM" aria-label="Buka RPM"><i class="ri-arrow-right-line"></i></a>
                                        </div>
                                    </article>
                                @endforeach
                            </section>
                        @endif

                        @if(in_array($type, ['all', 'bank'], true) && $bankResults->isNotEmpty())
                            <section class="cat-group" aria-labelledby="cat-group-bank">
                                <div class="cat-group-head">
                                    <span class="cat-group-index">03</span>
                                    <h2 class="cat-group-title" id="cat-group-bank">Bank soal</h2>
                                    <span class="cat-group-count">{{ $bankResults->count() }} entri</span>
                                </div>
                                @foreach($bankResults as $bank)
                                    <article class="cat-item">
                                        <div class="cat-item-main">
                                            <a class="cat-item-title" href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">{{ $bank->nama }}</a>
                                            @if(filled($bank->deskripsi))
                                                <p class="cat-item-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) $bank->deskripsi), 180) }}</p>
                                            @endif
                                            <div class="cat-item-meta">
                                                <span>{{ $bank->subject?->name ?? '-' }}</span>
                                                @if($bank->gradeLevel?->name)<span class="sep">&middot;</span><span>{{ $bank->gradeLevel->name }}</span>@endif
                                                @if($bank->owner?->name)<span class="sep">&middot;</span><span>{{ $bank->owner->name }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="cat-actions">
                                            <a class="cat-action" href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" title="Buka bank soal" aria-label="Buka bank soal"><i class="ri-arrow-right-line"></i></a>
                                        </div>
                                    </article>
                                @endforeach
                            </section>
                        @endif

                        @if(in_array($type, ['all', 'paket'], true) && $paketResults->isNotEmpty())
                            <section class="cat-group" aria-labelledby="cat-group-paket">
                                <div class="cat-group-head">
                                    <span class="cat-group-index">04</span>
                                    <h2 class="cat-group-title" id="cat-group-paket">Paket soal</h2>
                                    <span class="cat-group-count">{{ $paketResults->count() }} entri</span>
                                </div>
                                @foreach($paketResults as $paket)
                                    <article class="cat-item">
                                        <div class="cat-item-main">
                                            <a class="cat-item-title" href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">{{ $paket->judul }}</a>
                                            <div class="cat-item-meta">
                                                <span>{{ $paket->kode_paket }}</span>
                                                @if($paket->kisiKisi?->subject?->name)<span class="sep">&middot;</span><span>{{ $paket->kisiKisi->subject->name }}</span>@endif
                                                @if($paket->kisiKisi?->gradeLevel?->name)<span class="sep">&middot;</span><span>{{ $paket->kisiKisi->gradeLevel->name }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="cat-actions">
                                            <a class="cat-action" href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" title="Buka paket soal" aria-label="Buka paket soal"><i class="ri-arrow-right-line"></i></a>
                                        </div>
                                    </article>
                                @endforeach
                            </section>
                        @endif

                        @if($total === 0)
                            <div class="cat-empty cat-empty-plain">
                                <h2 class="cat-empty-title">Tidak ada hasil di database</h2>
                                <p class="cat-empty-text">Belum ada data yang cocok untuk &ldquo;{{ $query }}&rdquo; di {{ $school->name }}. Coba kata kunci lain atau telusuri sumber lain.</p>
                                <div class="cat-suggestions">
                                    <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'ai']) }}">Coba rekomendasi AI</a>
                                    <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'internet']) }}">Cari di internet</a>
                                </div>
                            </div>
                        @endif
                    @elseif($source === 'ai')
                        <section class="cat-panel" aria-label="Rekomendasi AI">
                            @if($aiError)
                                <div class="cat-notice">{{ $aiError }}</div>
                            @else
                                <div class="cat-ai-answer">{{ $aiAnswer ?: 'AI belum memberikan jawaban.' }}</div>
                                <p class="cat-hint">Rekomendasi AI bersifat saran. Verifikasi ke sumber asli sebelum digunakan.</p>
                                <div class="cat-suggestions" style="justify-content:flex-start; margin-top:20px;">
                                    <a class="cat-suggestion" href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'db']) }}">Telusuri &ldquo;{{ $query }}&rdquo; di database sekolah</a>
                                </div>
                            @endif
                        </section>
                    @else
                        <section class="cat-panel" aria-label="Hasil internet">
                            @if($webError)
                                <div class="cat-notice">{{ $webError }}</div>
                            @elseif(empty($webResults))
                                <p class="cat-empty-text" style="margin:0;">Tidak ada tautan internet yang ditemukan untuk kata kunci ini.</p>
                            @else
                                @foreach($webResults as $result)
                                    @php
                                        $url = $result['url'] ?? '';
                                        $host = $url !== '' ? parse_url($url, PHP_URL_HOST) : '';
                                    @endphp
                                    <article class="cat-item">
                                        <div class="cat-item-main">
                                            <a class="cat-web-title" href="{{ $url ?: '#' }}" target="_blank" rel="noopener noreferrer">{{ $result['title'] ?? 'Sumber' }}</a>
                                            @if(filled($result['snippet'] ?? null))
                                                <p class="cat-item-excerpt">{{ $result['snippet'] }}</p>
                                            @endif
                                            @if($host)
                                                <div class="cat-web-domain">{{ $host }}</div>
                                            @endif
                                        </div>
                                        <div class="cat-actions">
                                            <a class="cat-action" href="{{ $url ?: '#' }}" target="_blank" rel="noopener noreferrer" title="Buka tautan" aria-label="Buka tautan"><i class="ri-external-link-line"></i></a>
                                        </div>
                                    </article>
                                @endforeach
                            @endif
                        </section>
                    @endif
                @endif
            </main>
        </div>
    </div>
@endsection
