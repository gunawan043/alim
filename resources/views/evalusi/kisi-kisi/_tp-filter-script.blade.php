{{-- Filter dropdown TP berdasarkan Mapel & Tingkat terpilih (konteks otomatis kisi-kisi).
     Dipakai di create.blade.php & edit.blade.php.
     Prasyarat di halaman: #subject-select, #grade-select, dan select TP dengan atribut data-tp-select
     yang setiap opsinya memiliki data-subject-id/data-grade-level-id. --}}
<script>
(function () {
    function filterTpOptions(root) {
        root = root || document;
        var subjectEl = document.getElementById('subject-select');
        var gradeEl = document.getElementById('grade-select');
        var subjectId = subjectEl ? subjectEl.value : '';
        var gradeId = gradeEl ? gradeEl.value : '';

        root.querySelectorAll('select[data-tp-select]').forEach(function (sel) {
            var keepSelected = false;

            sel.querySelectorAll('option[data-subject-id]').forEach(function (opt) {
                var okSubject = !subjectId || opt.dataset.subjectId === subjectId;
                var okGrade = !gradeId || !opt.dataset.gradeLevelId || opt.dataset.gradeLevelId === gradeId;
                var ok = okSubject && okGrade;
                opt.hidden = !ok;
                if (ok && opt.selected && opt.value) {
                    keepSelected = true;
                }
            });

            // Pilihan lama yang tidak lagi relevan → kosongkan agar tidak salah simpan.
            if (sel.value && !keepSelected) {
                sel.value = '';
            }
        });
    }

    window.__filterTpOptions = filterTpOptions;

    document.addEventListener('DOMContentLoaded', function () {
        filterTpOptions();

        ['subject-select', 'grade-select'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () { filterTpOptions(); });
            }
        });
    });
})();
</script>
