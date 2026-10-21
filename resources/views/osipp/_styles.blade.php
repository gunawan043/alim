@once
    <style>
        .osipp-page .card { border: 1px solid var(--bs-border-color); }
        .osipp-page .stat-label { font-size: 11px; letter-spacing: .02em; }
        .osipp-page .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
        .osipp-page .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15, 23, 42, .08); }
        .osipp-page .osipp-nav .nav-link { color: var(--bs-secondary-color); border: 0; border-bottom: 2px solid transparent; border-radius: 0; padding: .7rem .9rem; font-size: .86rem; white-space: nowrap; }
        .osipp-page .osipp-nav .nav-link.active { color: var(--bs-primary); border-bottom-color: var(--bs-primary); background: transparent; }
        .osipp-page .osipp-nav .nav-link:hover { color: var(--bs-primary); }
        .osipp-page .table > :not(caption) > * > * { vertical-align: middle; }
        .osipp-page .table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); background: var(--bs-tertiary-bg); }
        .osipp-page .progress { height: .55rem; }
        .osipp-page .osipp-soft-row { background: var(--bs-tertiary-bg); border-radius: .6rem; padding: .7rem .8rem; }
        .osipp-page .osipp-period-select { min-width: 250px; }
        .osipp-page .osipp-empty { padding: 2.5rem 1rem; text-align: center; }
        .osipp-page .osipp-empty i { font-size: 2rem; color: var(--bs-secondary-color); }
        .osipp-page .osipp-avatar { width: 2rem; height: 2rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: var(--bs-primary-bg-subtle); color: var(--bs-primary); font-weight: 700; font-size: .72rem; }
        @media (max-width: 767.98px) {
            .osipp-page .osipp-period-select { min-width: 100%; width: 100%; }
        }
    </style>
@endonce
