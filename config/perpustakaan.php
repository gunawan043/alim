<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pemetaan sekolah → unit perpustakaan
    |--------------------------------------------------------------------------
    |
    | Satu perpustakaan biasanya melayani lebih dari satu sekolah (mis.
    | Perpustakaan Putra melayani SD Putra, SMP Putra, MA Plus, dan Diniyah).
    | Tabel ini menentukan perpustakaan mana yang terbuka bagi guru yang
    | bertugas di sebuah sekolah.
    |
    | Kunci  : kode unit perpustakaan (library_units.code).
    | Nilai  : daftar pola nama sekolah. Dicocokkan tanpa membedakan huruf
    |          besar/kecil, cukup "mengandung" pola tersebut.
    |
    | Pola sengaja dipakai, bukan id sekolah, karena id berbeda di setiap
    | lingkungan (lokal, staging, produksi). Ubah daftar ini bila pembagian
    | perpustakaan berubah — tidak perlu mengubah kode.
    |
    */

    'units' => [
        'PERPUS-PUTRA' => [
            'SMP IT Putra',
            'SMP & SMA IT Putra',
            'MA Plus',
            'Diniyah',
        ],
        'PERPUS-PUTRI' => [
            'SMP IT Putri',
            'SMA IT Putri',
        ],
        'PERPUS-SD' => [
            'SD IT Putra',
            'SD IT Putri',
        ],
    ],

    /*
    | Satu sekolah hanya boleh menunjuk SATU perpustakaan. Karena itu sekolah
    | SD sengaja tidak dicantumkan di daftar Putra/Putri: SD punya
    | perpustakaannya sendiri. Bila sebuah nama sekolah cocok dengan lebih
    | dari satu pola, pola terpanjang (paling spesifik) yang dipakai.
    */


];
