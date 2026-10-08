<style>
    /* Konvensi UI modul Kurikulum — mengikuti pola gtk/index.blade.php */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        font-size: 13px;
        transition: all 0.2s;
        margin: 4px;
        cursor: pointer;
        color: inherit;
        text-decoration: none;
        background: #fff;
    }

    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }

    .stat-label { font-size: 11px; }

    .table-freeze th,
    .table-freeze td { vertical-align: middle; }
</style>
