# Ketersediaan Guru untuk Jadwal KBM

Fitur ini membatasi hari dan jam ketika seorang guru boleh ditempatkan pada
jadwal pelajaran. Generator jadwal otomatis akan melewati slot yang tidak
tersedia.

## Membuka pengaturan

**Jadwal KBM → Ketersediaan Guru**

Pilih guru, centang hari/jam yang tersedia, lalu simpan. Slot mengikuti master
**Jam Pelajaran** sekolah; jam istirahat tidak ditampilkan.

Perilaku penyimpanan:

- Semua slot dicentang → tidak ada pembatasan; baris pembatasan dihapus.
- Sebagian slot dicentang → hanya slot tersebut yang boleh dipakai.
- Tidak ada slot dicentang → guru tidak akan ditempatkan pada jadwal.
- Guru tanpa pengaturan → semua slot dianggap tersedia (kompatibel dengan
  jadwal lama).

## Efek pada generator

`JadwalGeneratorService` memeriksa ketersediaan guru sebelum menempatkan slot.
Generator tidak memakai grid cadangan jika sekolah belum memiliki master
Jam Pelajaran; jadwal tidak dibuat sampai slot diatur.
Jika slot tidak tersedia, generator mencari slot lain yang sesuai. Jika tidak
ada lagi, slot dilaporkan sebagai kekurangan JP, bukan dipaksakan.

Urutan pengecekan generator:

1. hari dan slot termasuk jam mengajar (bukan istirahat),
2. guru tersedia menurut pengaturan,
3. guru tidak sedang mengajar kelas lain,
4. rombel tidak sedang menerima mapel lain.

Edit jadwal manual juga menolak perpindahan guru ke hari/jam di luar
ketersediaan dan menampilkan peringatan konflik.

## Tampilan matriks

Halaman **Jadwal KBM** menampilkan matriks per hari dengan kolom:

`HARI | JAM KE | WAKTU | <semua rombel> | NO | KODE | NAMA GURU`

Setiap sel jadwal menampilkan **kode mapel** pada baris pertama dan **kode
guru** pada baris kedua. `JAM KE` dihitung sebagai urutan jam mengajar
(1..N) pada hari tersebut, mengikuti master Jam Pelajaran. Baris istirahat
digabung menjadi satu sel dan diberi garis pemisah antarhari. Contoh:

```text
MAT
B
```

Daftar guru di sisi kanan hanya berisi NO, KODE, dan NAMA GURU. Beban
mengajar tidak ditampilkan. Di bawahnya ada tabel **KODE MAPEL / NAMA MAPEL**
sebagai legenda kode yang dipakai pada sel jadwal.

Format yang sama dipakai oleh **Cetak Semua Rombel**, dengan kop sekolah,
TTD Kepala Satuan Pendidikan, dan stempel jika file media sekolah tersedia.

## Cetak dan edit manual

- **Cetak Semua Rombel** tersedia di halaman Jadwal KBM. PDF berisi jadwal
  setiap rombel, bukan hanya satu rombel.
- **Export Excel** menghasilkan file `.xlsx` matriks seluruh rombel, termasuk
  daftar guru dan kode mapel. Gunakan ini bila PDF terpotong.
- PDF memakai kop sekolah, TTD KSP, dan stempel dari media profil sekolah.
- **Edit Semua Rombel** menampilkan matriks seluruh kelas dalam satu halaman:
  - klik sel untuk menambah atau mengubah jadwal,
  - sel kuning = bentrok guru pada hari/jam yang sama,
  - sel merah = guru tidak tersedia pada hari/jam tersebut,
  - entri dapat dihapus dari modal edit.
- Editor per rombel tetap tersedia sebagai jalur lama.

Edit manual tetap memeriksa master slot. Bentrok guru/rombel dan guru yang
tidak tersedia tetap disimpan sebagai data, lalu ditandai kuning/merah pada
matriks. Guru otomatis dipilih dari penugasan mengajar ketika mapel dipilih
(berdasarkan rombel yang sedang diedit).

## Data

Tabel: `teacher_schedule_availabilities`

```json
{
  "1": [1, 2, 4],
  "2": [1, 2, 3]
}
```

Key adalah `day_of_week` (1=Senin ... 6=Sabtu), value adalah daftar
`slot_number` yang tersedia. Pembatasan disimpan per sekolah dan guru.
