{{-- Tools tabel seragam Buku Nilai: pencarian nama, pengurutan kolom, gaya tabel Leger. --}}
<style>
    .js-tools-table { font-size: 12px; }
    /* Header beku (lapisan teratas, latar opak) */
    .js-tools-table thead th { position: sticky; top: 0; z-index: 30; background: var(--bs-tertiary-bg); font-weight: 600; }
    .js-tools-table tbody tr:hover td { background: rgba(var(--bs-primary-rgb), .05); }
    /* Gaya spreadsheet: tanpa border (border dari tabel); fokus hanya latar halus */
    .js-tools-table input { width: 100%; height: 28px; border: 0; border-radius: 0; background: transparent; text-align: center; font-size: 12px; color: var(--bs-body-color); }
    .js-tools-table input::placeholder { text-align: center; opacity: .45; }
    .js-tools-table input:focus { border: 0; outline: none; box-shadow: none; background: rgba(var(--bs-primary-rgb), .06); }
    .js-tools-table th[data-tt-dir] { cursor: pointer; }
    .js-tools-table th[data-tt-dir="asc"]::after { content: " ▲"; font-size: 9px; color: var(--bs-primary); }
    .js-tools-table th[data-tt-dir="desc"]::after { content: " ▼"; font-size: 9px; color: var(--bs-primary); }
</style>
<script>
(function () {
    function cellVal(cell) {
        if (!cell) return null;
        var inp = cell.querySelector('input, select');
        var raw = (inp ? inp.value : cell.textContent).trim();
        if (raw === '' || raw === '\u2014' || raw === '-' || raw === '---') return null;
        var n = parseFloat(raw.replace(',', '.'));
        return isNaN(n) ? raw.toLowerCase() : n;
    }

    function headerColumns(table) {
        var rows = table.querySelectorAll('thead tr');
        var occupied = [];
        var list = [];
        rows.forEach(function (tr, r) {
            var c = 0;
            tr.querySelectorAll('th, td').forEach(function (cell) {
                occupied[r] = occupied[r] || [];
                while (occupied[r][c]) c++;
                var cs = parseInt(cell.getAttribute('colspan') || '1', 10);
                var rs = parseInt(cell.getAttribute('rowspan') || '1', 10);
                list.push({ th: cell, colIndex: c });
                for (var rr = r; rr < r + rs; rr++) {
                    occupied[rr] = occupied[rr] || [];
                    for (var cc = c; cc < c + cs; cc++) occupied[rr][cc] = true;
                }
                c += cs;
            });
        });
        return list;
    }

    function applyTops(table) {
        var top = 0;
        table.querySelectorAll('thead tr').forEach(function (tr) {
            var h = tr.getBoundingClientRect().height;
            tr.querySelectorAll('th').forEach(function (th) { th.style.top = top + 'px'; });
            top += h;
        });
    }

    function rowsOf(table) {
        return Array.prototype.slice.call(table.querySelectorAll('tbody tr')).filter(function (r) {
            return r.querySelector('td');
        });
    }

    function initSearch(table) {
        var wrap = table.closest('.table-responsive') || table.parentNode;
        if (!wrap || !wrap.parentNode) return;
        if (wrap.previousElementSibling && wrap.previousElementSibling.classList.contains('tt-search')) return;

        var box = document.createElement('div');
        box.className = 'tt-search mb-2 d-flex justify-content-end px-3 pt-2';
        box.innerHTML = '<input type="text" class="form-control form-control-sm" style="max-width:240px;" placeholder="Cari nama santri...">';
        wrap.parentNode.insertBefore(box, wrap);

        var input = box.querySelector('input');
        input.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            rowsOf(table).forEach(function (row) {
                var nameCell = row.querySelector('[data-col="name"]') || row.children[2] || row.children[1];
                var name = (nameCell ? nameCell.textContent : '').toLowerCase();
                row.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    function initSort(table) {
        headerColumns(table).forEach(function (entry) {
            var th = entry.th;
            var idx = entry.colIndex;
            th.style.cursor = 'pointer';
            th.addEventListener('click', function () {
                var dir = th.getAttribute('data-tt-dir') === 'asc' ? 'desc' : 'asc';
                table.querySelectorAll('th[data-tt-dir]').forEach(function (h) { h.removeAttribute('data-tt-dir'); });
                th.setAttribute('data-tt-dir', dir);

                var tbody = table.querySelector('tbody');
                var rows = rowsOf(table);
                rows.sort(function (a, b) {
                    var va = cellVal(a.children[idx]);
                    var vb = cellVal(b.children[idx]);
                    if (va === null && vb === null) return 0;
                    if (va === null) return 1;
                    if (vb === null) return -1;
                    if (typeof va === 'number' && typeof vb === 'number') return dir === 'asc' ? va - vb : vb - va;
                    return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
                });
                rows.forEach(function (r) { tbody.appendChild(r); });
            });
        });
    }

    window.setW1Status = function (sid, status, btn) {
        var colors = { hadir: 'primary', terlambat: 'warning', sakit: 'warning', izin: 'success', alpa: 'danger' };

        var group = btn.closest('.status-group');
        if (group) {
            group.querySelectorAll('.status-btn').forEach(function (b) {
                var c = b.getAttribute('data-color') || colors[b.getAttribute('data-s')] || 'primary';
                b.classList.remove('on');
                b.classList.remove('btn-' + c);
                b.classList.add('btn-soft-' + c);
            });
        }

        var color = btn.getAttribute('data-color') || colors[status] || 'primary';
        btn.classList.remove('btn-soft-' + color);
        btn.classList.add('btn-' + color, 'on');

        var input = document.getElementById('w1-status-' + sid);
        if (input) input.value = status;

        var row = btn.closest('.presensi-row, tr');
        if (row) row.setAttribute('data-status', status);

        var wrap = document.getElementById('w1-note-wrap-' + sid);
        var notes = document.getElementById('w1-notes-' + sid);
        if (status === 'hadir') {
            if (wrap) wrap.classList.add('d-none');
            if (notes) notes.classList.add('d-none');
        } else {
            if (wrap) wrap.classList.remove('d-none');
            if (notes) { notes.classList.remove('d-none'); notes.focus(); }
        }

        // Simpan otomatis (bila halaman menyediakan hook)
        if (typeof window.__w1AutoSave === 'function') {
            window.__w1AutoSave(sid);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.js-tools-table').forEach(function (table) {
            initSearch(table);
            initSort(table);
            applyTops(table);
            window.addEventListener('resize', function () { applyTops(table); });
        });
    });
})();
</script>
