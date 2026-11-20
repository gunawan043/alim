{{--
    Paste matriks dari Excel/Spreadsheet ke tabel nilai.

    Berlaku untuk tabel berkelas `js-tools-table` (Buku Nilai) dan tabel
    bertanda `data-paste-grid` (Leger — tab Mapel).

    Cara kerja:
    - Tempel satu sel  → diteruskan ke perilaku bawaan browser.
    - Tempel satu kolom/matriks → disebar mulai dari sel yang sedang fokus,
      berdasarkan posisi <td> di baris, bukan urutan input global. Dengan
      begitu tabel ber-header bertingkat atau berkolom span tetap benar.
    - Sel yang readOnly/disabled dilewati. Bila seluruh target terkunci,
      muncul pemberitahuan singkat.
--}}
<script>
(function () {
    function inputIn(cell) {
        if (!cell) return null;

        return cell.querySelector('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"])');
    }

    function bodyRows(table) {
        return Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    }

    function toast(message) {
        var el = document.createElement('div');
        el.textContent = message;
        el.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:2000;'
            + 'background:#dc3545;color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;'
            + 'box-shadow:0 6px 18px rgba(0,0,0,.2);max-width:90vw;text-align:center;';
        document.body.appendChild(el);
        window.setTimeout(function () { el.remove(); }, 3500);
    }

    function handlePaste(table, event) {
        var input = event.target;

        if (!input || input.tagName !== 'INPUT' || !table.contains(input)) return;

        var clipboard = event.clipboardData || window.clipboardData;
        var text = clipboard ? clipboard.getData('text') : '';

        if (!text) return;

        // Satu sel: biarkan browser menempel seperti biasa.
        if (text.indexOf('\t') === -1 && text.indexOf('\n') === -1) return;

        var row = input.closest('tr');
        var cell = input.closest('td');

        if (!row || !cell) return;

        var rows = bodyRows(table);
        var rowIndex = rows.indexOf(row);
        var colIndex = Array.prototype.indexOf.call(row.children, cell);

        if (rowIndex === -1 || colIndex === -1) return;

        event.preventDefault();

        var before = window.AlimGridUndo ? window.AlimGridUndo.snapshot(table) : null;

        var grid = text
            .replace(/\r/g, '')
            .replace(/\n+$/, '')
            .split('\n')
            .map(function (line) { return line.split('\t'); });

        var filled = 0;
        var blocked = 0;
        var firstBlocked = null;

        grid.forEach(function (values, dr) {
            var targetRow = rows[rowIndex + dr];

            if (!targetRow) return;

            values.forEach(function (raw, dc) {
                var targetCell = targetRow.children[colIndex + dc];

                if (!targetCell) return;

                var target = inputIn(targetCell);

                if (!target) return;

                if (target.readOnly || target.disabled) {
                    blocked++;
                    if (!firstBlocked) firstBlocked = target;

                    return;
                }

                var value = String(raw)
                    .replace(/\u00a0/g, ' ')
                    .replace(/\s+/g, '')
                    .replace(',', '.');

                target.value = value;
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('change', { bubbles: true }));
                filled++;
            });
        });

        if (filled > 0 && before && window.AlimGridUndo) {
            window.AlimGridUndo.commitSnapshot(table, before);
        }

        if (filled === 0 && blocked > 0) {
            toast('Kolom masih terkunci. Isi Nilai Murni terlebih dahulu.');

            if (firstBlocked) firstBlocked.focus();
        }
    }

    function init() {
        document
            .querySelectorAll('table.js-tools-table, table[data-paste-grid]')
            .forEach(function (table) {
                if (table.dataset.pasteGridReady === '1') return;

                table.dataset.pasteGridReady = '1';
                table.addEventListener('paste', function (event) {
                    handlePaste(table, event);
                });
            });
    }

    document.addEventListener('DOMContentLoaded', init);
    // Sebagian halaman merender tabel setelah DOM siap (mis. modal/tab).
    window.addEventListener('load', init);
})();
</script>

@include('partials.grid-undo')
