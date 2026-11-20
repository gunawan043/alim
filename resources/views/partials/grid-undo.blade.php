{{--
    Undo/Redo untuk tabel nilai (Buku Nilai wizard 1–6 dan tab Mapel Leger).

    - Setiap perubahan sel (diketik atau ditempel) dicatat sebagai satu langkah.
    - Ctrl/Cmd+Z = undo, Ctrl/Cmd+Shift+Z atau Ctrl+Y = redo.
    - Tombol Undo/Redo tampil di atas tabel dan otomatis nonaktif saat kosong.
--}}
<script>
(function () {
    'use strict';

    var LIMIT = 50;
    var stores = new WeakMap();
    var lastTable = null;

    function inputsOf(table) {
        return Array.prototype.slice.call(
            table.querySelectorAll('tbody input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"])')
        );
    }

    function snapshot(table) {
        return inputsOf(table).map(function (inp) { return inp.value; });
    }

    function same(a, b) {
        if (!a || !b || a.length !== b.length) return false;

        for (var i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) return false;
        }

        return true;
    }

    function store(table) {
        var s = stores.get(table);

        if (!s) {
            s = { undo: [], redo: [], baseline: null, timer: null, suspend: 0, buttons: null };
            stores.set(table, s);
        }

        return s;
    }

    function refreshButtons(table) {
        var s = store(table);

        if (!s.buttons) return;

        s.buttons.undo.disabled = s.undo.length === 0;
        s.buttons.redo.disabled = s.redo.length === 0;
    }

    function push(table, snap) {
        var s = store(table);

        s.undo.push(snap);

        if (s.undo.length > LIMIT) s.undo.shift();

        s.redo.length = 0;
        refreshButtons(table);
    }

    function apply(table, values) {
        var s = store(table);

        s.suspend++;

        inputsOf(table).forEach(function (inp, idx) {
            if (idx >= values.length || inp.value === values[idx]) return;

            inp.value = values[idx];
            inp.dispatchEvent(new Event('input', { bubbles: true }));
            inp.dispatchEvent(new Event('change', { bubbles: true }));
        });

        s.suspend--;
    }

    function undo(table) {
        var s = store(table);

        if (!s.undo.length) return false;

        s.redo.push(snapshot(table));
        apply(table, s.undo.pop());
        s.baseline = snapshot(table);
        refreshButtons(table);

        return true;
    }

    function redo(table) {
        var s = store(table);

        if (!s.redo.length) return false;

        s.undo.push(snapshot(table));
        apply(table, s.redo.pop());
        s.baseline = snapshot(table);
        refreshButtons(table);

        return true;
    }

    function commit(table) {
        var s = store(table);

        if (s.suspend > 0 || !s.baseline) return;

        var now = snapshot(table);

        if (same(now, s.baseline)) {
            s.baseline = now;

            return;
        }

        s.undo.push(s.baseline);

        if (s.undo.length > LIMIT) s.undo.shift();

        s.redo.length = 0;
        s.baseline = now;
        refreshButtons(table);
    }

    function buildButtons(table) {
        var wrap = table.closest('.table-responsive') || table.parentNode;

        if (!wrap || !wrap.parentNode) return;
        if (wrap.previousElementSibling && wrap.previousElementSibling.classList.contains('grid-undo-bar')) return;

        var bar = document.createElement('div');
        bar.className = 'grid-undo-bar d-flex justify-content-end gap-2 px-3 pb-2';

        function btn(label, title) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-secondary';
            b.innerHTML = label;
            b.title = title;

            return b;
        }

        var undoBtn = btn('<i class="ri-arrow-go-back-line me-1"></i>Undo', 'Batalkan perubahan terakhir (Ctrl+Z)');
        var redoBtn = btn('<i class="ri-arrow-go-forward-line me-1"></i>Redo', 'Ulangi perubahan (Ctrl+Shift+Z)');

        undoBtn.addEventListener('click', function () { undo(table); });
        redoBtn.addEventListener('click', function () { redo(table); });

        bar.appendChild(undoBtn);
        bar.appendChild(redoBtn);
        wrap.parentNode.insertBefore(bar, wrap);

        store(table).buttons = { undo: undoBtn, redo: redoBtn };
        refreshButtons(table);
    }

    function initTable(table) {
        if (table.dataset.gridUndoReady === '1') return;

        // Hanya tabel nilai (punya sel angka). Tabel presensi/jurnal tidak perlu.
        if (!table.querySelector('tbody input[type="number"]')) return;

        table.dataset.gridUndoReady = '1';

        var s = store(table);

        buildButtons(table);

        table.addEventListener('focusin', function (e) {
            if (!e.target || e.target.tagName !== 'INPUT') return;

            lastTable = table;
            s.baseline = snapshot(table);
        });

        table.addEventListener('input', function (e) {
            if (!e.target || e.target.tagName !== 'INPUT') return;

            window.clearTimeout(s.timer);
            s.timer = window.setTimeout(function () { commit(table); }, 800);
        });

        table.addEventListener('focusout', function (e) {
            if (!e.target || e.target.tagName !== 'INPUT') return;

            window.clearTimeout(s.timer);
            commit(table);
        });
    }

    function init() {
        document.querySelectorAll('table.js-tools-table, table[data-paste-grid]').forEach(initTable);
    }

    function activeTable() {
        var el = document.activeElement;
        var table = el && el.closest ? el.closest('table.js-tools-table, table[data-paste-grid]') : null;

        return table || lastTable;
    }

    document.addEventListener('keydown', function (e) {
        var key = (e.key || '').toLowerCase();

        if (!(e.ctrlKey || e.metaKey)) return;

        var isUndo = key === 'z' && !e.shiftKey;
        var isRedo = key === 'y' || (key === 'z' && e.shiftKey);

        if (!isUndo && !isRedo) return;

        var table = activeTable();

        if (!table) return;

        e.preventDefault();

        if (isUndo) undo(table);
        else redo(table);
    });

    document.addEventListener('DOMContentLoaded', init);
    window.addEventListener('load', init);

    // Dipakai handler paste agar satu kali tempel = satu langkah undo.
    window.AlimGridUndo = {
        snapshot: snapshot,
        commitSnapshot: function (table, snap) {
            var s = store(table);
            var now = snapshot(table);

            if (!snap || same(now, snap)) {
                s.baseline = now;

                return;
            }

            push(table, snap);
            s.baseline = now;
        },
        undo: undo,
        redo: redo,
    };
})();
</script>
