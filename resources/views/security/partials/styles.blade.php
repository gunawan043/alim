{{-- Gaya bersama modul Satuan Keamanan — mengikuti pola gtk/index.blade.php --}}
@push('css')
<style>
    .table-container { position: relative; width: 100%; overflow-x: auto; }
    .table-freeze { table-layout: auto; min-width: max-content; margin-bottom: 0; width: 100%; }
    .table-freeze th, .table-freeze td {
        white-space: normal; overflow: visible; vertical-align: middle;
        padding: 12px 16px; word-break: break-word;
    }
    .table-freeze th:first-child, .table-freeze td:first-child {
        position: sticky; left: 0; z-index: 100; min-width: 150px; max-width: 220px;
        box-shadow: 2px 0 5px rgba(0,0,0,.1); background: var(--bs-body-bg);
    }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
    .stat-card { transition: all .3s ease; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .filter-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        border: 1px solid #e2e8f0; border-radius: 30px; font-size: 13px;
        transition: all .2s; margin: 4px; cursor: pointer; background: transparent;
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .timeline-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .metric-bar { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
    .metric-bar > span { display: block; height: 100%; border-radius: 4px; }
    [data-bs-theme="dark"] .table-freeze th:first-child,
    [data-bs-theme="dark"] .table-freeze td:first-child { background: var(--bs-secondary-bg); }
</style>
@endpush
