@menuallowed('tugas-kepala-satuan-pendidikan')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KEPALA SEKOLAH / KEPALA SATUAN PENDIDIKAN (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Sebagian akun lama menyimpan jabatan kepala sebagai tugas tambahan
     ("Kepala Sekolah") tanpa role "Satuan Pendidikan". Sidebar Satuan
     Pendidikan tetap dirender agar seluruh menu kepemimpinan tersedia.
     Bila role Satuan Pendidikan sudah ada, dispatcher tidak meng-include
     file ini (mencegah menu ganda).
     ═══════════════════════════════════════════════════════════════════════════ --}}

@include('layouts.sidebar.satuan-pendidikan.sidebar')
@endmenuallowed
