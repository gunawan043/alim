# Import SK & Matriks Pembagian Tugas

Fitur ini mengimpor **Surat Keputusan (SK) Pembagian Tugas** beserta **matriks penugasan** dari satu workbook Excel/CSV.

## Akses

- Menu: **SK & Keputusan → Import** (`/{userId}/institution-decrees/import`).
- Tombol **Import** tersedia di halaman daftar SK.
- Template dapat diunduh dari halaman import (sudah terisi tahun ajaran, penandatangan, dan contoh baris).

## Struktur workbook

Sheet yang dibaca (nama tidak peka huruf besar/kecil):

| Sheet | Wajib | Isi |
|---|---|---|
| `SK` | Ya | Satu baris = satu SK |
| `MATRIKS` | Tidak | Satu baris = guru + mapel + rombel + JP |
| `TUGAS` | Tidak | Satu baris = tugas tambahan guru |

Sheet bantu: `PETUNJUK`, `REF_GTK`, `REF_MAPEL`, `REF_ROMBEL` (tidak dibaca saat import).

### Kolom `SK`

`Nomor SK`, `Jenis SK`, `Judul`, `Deskripsi`, `Tahun Ajaran`, `Tanggal Ditetapkan`, `Tanggal Efektif`, `Tanggal Berakhir`, `Penandatangan NUPY`, `Penandatangan Nama`, `Jabatan Penandatangan`, `Status`.

- `Nomor SK`, `Judul`, `Tanggal Ditetapkan` wajib.
- `Tahun Ajaran` kosong → memakai pilihan di form import atau tahun ajaran aktif.
- `Status`: `draft`, `active`, `archived` (menerima `aktif`/`arsip`).
- Tanggal menerima `YYYY-MM-DD`, `DD/MM/YYYY`, atau serial tanggal Excel.

### Kolom `MATRIKS`

`Nomor SK`, `NUPY`, `Nama Guru`, `Kode Mapel`, `Mapel`, `Kelas`, `Rombel`, `JP`, `Koordinator`.

- `Nomor SK`, guru, mapel, rombel, dan `JP > 0` wajib.
- Guru dicocokkan lewat `NUPY` lebih dulu, lalu nama persis.
- Mapel dicocokkan lewat `Kode Mapel`, lalu nama persis.
- Rombel dicocokkan lewat nama; isi `Kelas` bila nama rombel sama antar tingkat.
- Aturan satu mapel satu guru per rombel tetap berlaku; pengecualian Tahfidz/Hadits.

### Kolom `TUGAS`

`Nomor SK`, `NUPY`, `Nama Guru`, `Nama Tugas`, `Kode Tugas`, `Rombel`, `JP`.

- `Kode Tugas` menghubungkan ke master `additional_task_types` (mis. `wali-kelas`).
- Bila kode tidak ada, tugas disimpan sebagai tugas kustom.

## Perilaku

- Satu file boleh berisi banyak SK (dikelompokkan berdasarkan `Nomor SK`).
- Baris yang gagal dilaporkan per baris; baris valid tetap diproses.
- Nomor SK yang sudah ada ditolak, kecuali opsi **Perbarui SK yang sudah ada** dicentang.
- Saat memperbarui, matriks/tugas yang tidak ada lagi di file akan dihapus untuk SK tersebut.
- Setiap penugasan aktif otomatis menyiapkan **Buku Admin** guru.
- Setelah import, isi matriks dibekukan sebagai **snapshot** (`institution_decrees.matrix_snapshot`).
- Guru, mapel, rombel, tahun ajaran, dan penandatangan harus sudah ada di master.

## Catatan teknis

- Reader: `App\Imports\InstitutionDecreeImport` (PhpSpreadsheet, multi-sheet).
- Template: `App\Exports\InstitutionDecreeTemplateExport`.
- Penyimpanan matriks terpusat di `App\Services\InstitutionDecreeMatrixService` (dipakai import, halaman Buat SK, Edit SK, dan Edit Matriks).
- Snapshot dipakai halaman detail & cetak; halaman detail dapat menampilkan data live dengan `?live=1`.
