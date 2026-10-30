<style>
    /* Penyesuaian lintas halaman untuk mode gelap. */
    [data-bs-theme="dark"] .bg-white {
        background-color: var(--bs-body-bg) !important;
    }

    [data-bs-theme="dark"] .bg-light {
        background-color: var(--bs-tertiary-bg) !important;
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .text-dark {
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .border-light {
        border-color: var(--bs-border-color) !important;
    }

    [data-bs-theme="dark"] .table-light {
        --bs-table-bg: var(--bs-tertiary-bg);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .table > :not(caption) > * > * {
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .modal-content {
        background-color: var(--bs-modal-bg);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .modal-header,
    [data-bs-theme="dark"] .modal-footer {
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .form-control::placeholder {
        color: var(--bs-secondary-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-selection--single,
    [data-bs-theme="dark"] .select2-container--default .select2-selection--multiple {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-dropdown {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: var(--bs-primary);
        color: #fff;
    }

    [data-bs-theme="dark"] .swal2-popup {
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .swal2-title,
    [data-bs-theme="dark"] .swal2-html-container {
        color: var(--bs-body-color);
    }

    /* ── Komponen umum daftar/kartu ───────────────────────────────── */
    [data-bs-theme="dark"] .card { background-color: var(--bs-body-bg); border-color: var(--bs-border-color); }
    [data-bs-theme="dark"] .card-header,
    [data-bs-theme="dark"] .card-footer { background-color: transparent; border-color: var(--bs-border-color); }

    [data-bs-theme="dark"] .table {
        --bs-table-color: var(--bs-body-color);
        --bs-table-bg: transparent;
        --bs-table-border-color: var(--bs-border-color);
        --bs-table-striped-bg: rgba(255, 255, 255, .03);
        --bs-table-hover-bg: rgba(255, 255, 255, .06);
    }

    [data-bs-theme="dark"] .list-group-item { background-color: var(--bs-body-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }

    [data-bs-theme="dark"] .dropdown-menu { background-color: var(--bs-body-bg); border-color: var(--bs-border-color); }
    [data-bs-theme="dark"] .dropdown-item { color: var(--bs-body-color); }
    [data-bs-theme="dark"] .dropdown-item:hover,
    [data-bs-theme="dark"] .dropdown-item:focus { background-color: var(--bs-tertiary-bg); color: var(--bs-body-color); }
    [data-bs-theme="dark"] .dropdown-divider { border-color: var(--bs-border-color); }
    [data-bs-theme="dark"] .dropdown-header { color: var(--bs-secondary-color); }

    [data-bs-theme="dark"] .btn-light { background-color: var(--bs-tertiary-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }
    [data-bs-theme="dark"] .btn-light:hover { background-color: var(--bs-secondary-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }
    [data-bs-theme="dark"] .btn-outline-dark { color: var(--bs-body-color); border-color: var(--bs-border-color); }
    [data-bs-theme="dark"] .btn-outline-dark:hover { background-color: var(--bs-secondary-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }
    [data-bs-theme="dark"] .btn-white { background-color: var(--bs-tertiary-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }

    [data-bs-theme="dark"] .nav-tabs { border-color: var(--bs-border-color); }
    [data-bs-theme="dark"] .nav-tabs .nav-link { color: var(--bs-secondary-color); }
    [data-bs-theme="dark"] .nav-tabs .nav-link.active { background-color: var(--bs-body-bg); border-color: var(--bs-border-color) var(--bs-border-color) var(--bs-body-bg); color: var(--bs-body-color); }

    [data-bs-theme="dark"] .alert-light { background-color: var(--bs-tertiary-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }

    [data-bs-theme="dark"] .page-link { background-color: var(--bs-body-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }
    [data-bs-theme="dark"] .page-item.disabled .page-link { background-color: var(--bs-tertiary-bg); border-color: var(--bs-border-color); color: var(--bs-secondary-color); }

    [data-bs-theme="dark"] .bg-light-subtle { background-color: var(--bs-tertiary-bg) !important; }
    [data-bs-theme="dark"] .text-bg-light { background-color: var(--bs-tertiary-bg) !important; color: var(--bs-body-color) !important; }
    [data-bs-theme="dark"] hr { border-color: var(--bs-border-color); opacity: 1; }

    /* Baris tabel berwarna — build Velzon memakai variabel --vz-table-* dengan
       warna literal, sehingga mode gelap perlu menimpanya. */
    [data-bs-theme="dark"] .table-primary,
    [data-bs-theme="dark"] .table-secondary,
    [data-bs-theme="dark"] .table-success,
    [data-bs-theme="dark"] .table-info,
    [data-bs-theme="dark"] .table-warning,
    [data-bs-theme="dark"] .table-danger,
    [data-bs-theme="dark"] .table-light {
        --vz-table-color: var(--vz-body-color);
        --vz-table-border-color: var(--vz-border-color);
        --vz-table-striped-color: var(--vz-body-color);
        --vz-table-active-color: var(--vz-body-color);
        --vz-table-hover-color: var(--vz-body-color);
    }
    [data-bs-theme="dark"] .table-primary { --vz-table-bg: rgba(13, 110, 253, .16); --vz-table-striped-bg: rgba(13, 110, 253, .10); --vz-table-active-bg: rgba(13, 110, 253, .24); --vz-table-hover-bg: rgba(13, 110, 253, .24); }
    [data-bs-theme="dark"] .table-secondary { --vz-table-bg: rgba(148, 163, 184, .16); --vz-table-striped-bg: rgba(148, 163, 184, .10); --vz-table-active-bg: rgba(148, 163, 184, .24); --vz-table-hover-bg: rgba(148, 163, 184, .24); }
    [data-bs-theme="dark"] .table-success { --vz-table-bg: rgba(25, 135, 84, .16); --vz-table-striped-bg: rgba(25, 135, 84, .10); --vz-table-active-bg: rgba(25, 135, 84, .24); --vz-table-hover-bg: rgba(25, 135, 84, .24); }
    [data-bs-theme="dark"] .table-info { --vz-table-bg: rgba(13, 202, 240, .16); --vz-table-striped-bg: rgba(13, 202, 240, .10); --vz-table-active-bg: rgba(13, 202, 240, .24); --vz-table-hover-bg: rgba(13, 202, 240, .24); }
    [data-bs-theme="dark"] .table-warning { --vz-table-bg: rgba(255, 193, 7, .16); --vz-table-striped-bg: rgba(255, 193, 7, .10); --vz-table-active-bg: rgba(255, 193, 7, .24); --vz-table-hover-bg: rgba(255, 193, 7, .24); }
    [data-bs-theme="dark"] .table-danger { --vz-table-bg: rgba(220, 53, 69, .16); --vz-table-striped-bg: rgba(220, 53, 69, .10); --vz-table-active-bg: rgba(220, 53, 69, .24); --vz-table-hover-bg: rgba(220, 53, 69, .24); }
    [data-bs-theme="dark"] .table-light { --vz-table-bg: var(--bs-tertiary-bg); --vz-table-striped-bg: var(--bs-secondary-bg); --vz-table-active-bg: var(--bs-secondary-bg); --vz-table-hover-bg: var(--bs-secondary-bg); }

</style>
