<style>
    .table-container { position: relative; width: 100%; overflow-x: auto; }
    .table-freeze { table-layout: auto; min-width: max-content; margin-bottom: 0; width: 100%; }
    .table-freeze th, .table-freeze td { white-space: normal; overflow: visible; text-overflow: clip; vertical-align: middle; padding: 10px 12px; word-break: break-word; }
    .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
    .card-animate { transition: all .3s ease; }
    .card-animate:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 13px; transition: all .2s; margin: 4px; cursor: pointer; }
    .filter-badge:hover, .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .lab-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; }
    .lab-form-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; padding: 16px; }
    .lab-table td, .lab-table th { vertical-align: middle; }
    .lab-muted { color: #64748b; }
    @media (max-width: 767.98px) { .table-freeze th, .table-freeze td { padding: 8px; font-size: 12px; } }
</style>
