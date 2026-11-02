# Alur Unit Rumah Tangga (URT) — dari Satuan Kerja sampai Eksekusi

Dokumen ini memetakan sembilan prosedur URT ke fitur yang ada di ALIM, termasuk
titik serah-terima antar unit dan tombol yang dipakai di layar.

Halaman utama: **Sarpras → Alur & Penugasan → Alur Kerja URT**
(`sarpras.workflow.index`), dan **Penugasan & Area Kerja**
(`sarpras.workflow.assignment`).

## Alur penambahan (semua lewat modal, tanpa pindah halaman)

Halaman **Penugasan & Area Kerja** memakai pola satu halaman + modal (seperti
`gtk/index.blade.php`). Urutannya:

| # | Langkah | Tombol | Modal | Endpoint |
|---|---|---|---|---|
| 1 | Pastikan satuan kerja | Data Unit | — | `user.work-units.index` |
| 2 | Tunjuk petugas unit | Tambah Petugas | `#modalOfficer` | `sarpras.petugas.store` |
| 3 | Tambah area kerja | Tambah Area | `#modalArea` | `sarpras.kebersihan.area.store` |
| 4 | Tambah uraian pekerjaan | Tambah Uraian | `#modalTask` | `sarpras.kebersihan.task.store` |
| 5 | Tugaskan petugas ke area | Tugaskan | `#modalAssignment` | `sarpras.kebersihan.assignment.store` |
| 6 | Isi checklist harian | Isi Checklist | — | `sarpras.kebersihan.report.create` |

Pada tabel area, tombol per baris langsung membuka modal uraian/penugasan dengan
area terpilih. Tab terakhir dipertahankan setelah submit agar pengguna tidak
kehilangan konteks.

Menu tersedia di: sidebar **Unit Rumah Tangga** (Alur & Penugasan + Kebersihan &
Lingkungan + Pertamanan), sidebar **Sarpras**, **Koordinator Sarpras**,
**Satuan Pendidikan → modul Sarpras**, dan **Super Admin → Infrastruktur**.

## Rantai penugasan (tulang punggung semua prosedur)

```
Satuan Kerja  →  Petugas  →  Area Kerja  →  Uraian Pekerjaan  →  Eksekusi  →  Verifikasi
(work_units)     (work_unit_   (cleaning_     (cleaning_tasks /   (checklist /   (laporan /
                  sarpras_      areas)         checklist_items)   work order)    approval)
                  officers)
```

Aturan yang dipakai:

1. **Satuan kerja** adalah pemilik aset dan ruang (`assets.work_unit_id`, `asset_rooms.work_unit_id`).
2. **Petugas** ditunjuk lewat `work_unit_sarpras_officers` (satu unit boleh punya beberapa petugas,
   satu petugas ditandai *utama*).
3. **Area kerja** memakai master gedung/ruang bila ada; area non-ruang (taman, gorong-gorong,
   jalan) tetap didaftarkan sebagai area.
4. **Uraian pekerjaan** melekat pada area: judul, standar, metode, frekuensi.
5. **Eksekusi** hanya mungkin bila area punya petugas *dan* uraian. Halaman Penugasan & Area Kerja
   menandai area yang belum lengkap.
6. **Verifikasi** menutup lingkaran: laporan/checklist diverifikasi koordinator, temuan kerusakan
   masuk antrean perbaikan.

## Prosedur 1 — Pendataan Aset

| # | Tahap | Pelaku | Layar |
|---|---|---|---|
| 1 | Catat aset baru | Satuan kerja | `sarpras.user.aset.create` |
| 2 | Import massal (opsional) | Unit/URT | `sarpras.aset.import` |
| 3 | Verifikasi URT | Kepala/Koordinator URT | `sarpras.aset.index?verification_status=pending` |
| 4 | QR aset | URT | `sarpras.qr.index` |
| 5 | Stock opname | URT + unit | `sarpras.stock-opname.index` |
| 6 | Buku induk | URT | `sarpras.laporan.buku-induk` |

Status: aset buatan unit = `verification_status = pending`; aset buatan URT langsung `verified`.
Tidak ada aset yang bisa masuk QR/opname sebelum diverifikasi.

## Prosedur 2 — Pemeliharaan Sarpras

Jadwal → persetujuan PIC unit → persetujuan Kepala URT → work order → eksekusi teknisi →
riwayat otomatis.

| # | Tahap | Pelaku | Layar |
|---|---|---|---|
| 1 | Susun jadwal | Koordinator URT | `sarpras.pemeliharaan.schedule.create` |
| 2 | PIC approval | PIC unit | `sarpras.pic.index` |
| 3 | Kepala approval | Kepala URT | `sarpras.kepala.index` |
| 4 | Work order | Koordinator URT | `sarpras.pemeliharaan.schedule.index` |
| 5 | Eksekusi + checklist | Teknisi | `sarpras.teknisi.dashboard` / `sarpras.checklist.index` |
| 6 | Riwayat & biaya | otomatis | `sarpras.pemeliharaan.log.index` |

Saat work order diselesaikan, sistem menulis `asset_maintenance_logs`, memperbarui kondisi aset,
dan menghitung tanggal perawatan berikutnya dari frekuensi jadwal.

## Prosedur 3 — Perbaikan Sarpras

| # | Tahap | Pelaku | Layar |
|---|---|---|---|
| 1 | Lapor kerusakan | siapa pun | `sarpras.user.kerusakan.create` |
| 2 | Verifikasi laporan | Koordinator URT | `sarpras.repair.index` |
| 3 | Repair vs replace | Koordinator/Kepala | `sarpras.rvr.index` |
| 4 | Work order | Koordinator URT | `sarpras.repair.index` |
| 5 | Eksekusi | Teknisi | `sarpras.teknisi.dashboard` |
| 6 | Riwayat perbaikan | URT | `sarpras.pemeliharaan.log.index?log_type=korektif` |

## Prosedur 4 — Peminjaman Barang

Ajukan → setujui → serah terima → kembali → laporan.
Layar: `sarpras.peminjaman.create`, `sarpras.peminjaman.index`,
`sarpras.laporan.buku-peminjaman`.

Status aset berubah `dipinjam` saat serah terima dan kembali `tersedia` saat diterima.
Keterlambatan ditandai otomatis dari `expected_return_date`.

## Prosedur 5 — Penghapusan Aset

| # | Tahap | Pelaku | Layar |
|---|---|---|---|
| 1 | Usulkan penghapusan | unit pemilik / URT | kartu aset → **Usulkan Penghapusan** (`sarpras.disposal.propose`) |
| 2 | Telaah | Koordinator URT | `sarpras.disposal.pending` |
| 3 | Setujui/tolak | Kepala URT | `sarpras.disposal.pending` |
| 4 | Eksekusi (jual/scrap/hibah) | URT | `sarpras.disposal.pending` |
| 5 | Buku induk | URT | `sarpras.laporan.buku-induk` |

Perbaikan alur: sebelumnya antrean hanya menampilkan aset yang **sudah** berstatus `dihapus`,
sehingga tidak ada langkah "mengusulkan". Sekarang usulan menulis `disposal_date` +
`disposal_method` + alasan tanpa mengubah kondisi aset; persetujuan barulah mengubah kondisi
menjadi `dihapus` dan menonaktifkan aset. Aset yang sudah dihapus otomatis keluar dari antrean.

## Prosedur 6 — Pengadaan Sarana Prasarana

Usulan unit → permintaan pengadaan → persetujuan → PO → penerimaan → konversi menjadi aset.

Layar: `sarpras.user.pengadaan.index` (diarahkan ke SIPLah untuk pengadaan mandiri),
`sarpras.pengadaan.create`, `sarpras.pengadaan.index`, `sarpras.po.index`.
Hasil konversi masuk ke Prosedur 1.

## Prosedur 7 — Penanganan Listrik Padam

Lapor → tugaskan → tindakan → tutup → riwayat.
Layar: `sarpras.electricity.outages.index`, `sarpras.electricity.index`.

Status `open` = masih padam; `resolved` = menyala kembali. Setiap tindakan tersimpan di
`electricity_outage_actions` dengan pelaku dan waktu.

## Prosedur 8 — Pemeliharaan Kebersihan

| # | Tahap | Pelaku | Layar |
|---|---|---|---|
| 1 | Tetapkan area | Koordinator URT | `sarpras.kebersihan.areas` |
| 2 | Uraian pekerjaan | Koordinator URT | detail area (`sarpras.kebersihan.area.show`) |
| 3 | Tugaskan petugas + backup | Koordinator URT | detail area |
| 4 | Checklist harian | Petugas | `sarpras.kebersihan.report.create` |
| 5 | Verifikasi | Koordinator URT | `sarpras.kebersihan.reports` |
| 6 | Program tahunan | Koordinator URT | `sarpras.kebersihan.programs` |

## Prosedur 9 — Pemeliharaan Taman

Sama dengan kebersihan, dengan `area_type = taman`, ditambah agenda harian pertamanan
(pemeliharaan, pembibitan, permintaan darurat) di `sarpras.pertamanan.agenda.index`.

Layar: area taman (`sarpras.kebersihan.areas?area_type=taman`),
checklist taman (`sarpras.kebersihan.report.create?area_type=taman`),
program taman (`sarpras.kebersihan.programs?area_type=taman`).

## Titik rawan yang kini ditandai

Halaman **Penugasan & Area Kerja** menghitung dan menampilkan:

- **Satuan kerja belum punya petugas** → pendataan dan pelaporan tidak punya pemilik.
- **Area kerja belum punya petugas** → checklist tidak bisa diisi.
- **Area kerja belum punya uraian** → hasil kerja tidak terukur.

Setiap temuan punya tombol langsung ke layar perbaikannya, sehingga tidak ada tahap yang
"menggantung" tanpa tindak lanjut.

## Sisa pekerjaan yang disarankan

1. **Area kerja untuk teknisi** belum memakai tabel area; penugasan teknisi masih lewat
   `work_orders.assignee_id`. Bila ingin pembagian area teknisi (mis. gedung A/B), perlu
   penambahan jenis area dan relasi petugas-teknisi.
2. **Agenda pertamanan** belum terhubung ke area; saat ini hanya per petugas per hari.
3. **Notifikasi otomatis** untuk usulan penghapusan dan area tanpa petugas belum dibuat
   (penugasan work order dan area kebersihan sudah terkirim ke WhatsApp).

## Notifikasi WhatsApp saat penugasan

Alur pengiriman:

1. Aksi pengguna (tugaskan teknisi / petugas area) menulis data penugasan.
2. `SarprasNotificationService::dispatch()` mengirim `SendSarprasNotificationJob`.
3. Job memanggil `deliver()` per penerima.
4. `deliver()` **wajib** lewat `NotificationUniversalService::send()` agar
   `handleDeliveryChannels()` berjalan (in-app, email, WhatsApp, push).

> Sebelumnya `deliver()` membuat baris `NotificationUniversal` secara langsung sehingga
> kanal WhatsApp tidak pernah dipanggil. Sekarang semua event Sarpras ikut terkirim ke WA.

Event yang tercakup (bisa diatur per event di **Pengaturan Sistem → Notifikasi →
Matriks Channel Notifikasi**, grup *Sarpras & Unit Rumah Tangga*):

| Event | Pemicu |
|---|---|
| `WorkOrderAssigned` | Teknisi ditugaskan pada work order perbaikan/pemeliharaan |
| `RepairStatusChanged` | Status permintaan perbaikan berubah |
| `RepairRequestSubmitted` | Permintaan perbaikan diajukan |
| `CleaningAssigned` | Petugas/backup ditugaskan ke area kebersihan |
| `MaintenanceDue` / `MaintenanceOverdue` | Jadwal pemeliharaan mendekati / melewati tempo |
| `LoanOverdue` | Peminjaman terlambat dikembalikan |
| `WarrantyExpired`, `AssetMoved`, `StockOpname*` | Event aset lain |

Nomor tujuan diambil berurutan: `gtk_contacts.no_whatsapp` → `users.no_hp` →
`gtk_profiles.no_whatsapp` → `recruitment_profiles.no_whatsapp`. Bila petugas belum punya
nomor, penugasan tetap tersimpan dan halaman memberi tahu nama petugas yang perlu dilengkapi.

Pesan WA otomatis ditambah tautan halaman terkait (absolut dari `APP_URL`) agar petugas
bisa langsung membuka work order / area kerja dari ponsel.

Konfigurasi gateway (sekali saja): **Super Admin → Pengaturan Sistem → Notifikasi**:

1. Aktifkan **Notifikasi WhatsApp** dan **Status Gateway**.
2. Pilih provider: `fonnte`, `wablas`, `meta` (Cloud API resmi), `generic`, atau `log`.
3. Isi **API Token** (dan *Phone Number ID* bila memakai Meta).
4. Isi **Nomor Admin ALIM** untuk mode *Terpusat*, atau pilih mode *Keduanya*.
5. Klik **Cek Status Device** lalu **Uji Kirim ke Nomor Pusat**.

## Agenda mendatang & prioritas di dashboard

`App\Services\Sarpras\UrtAgendaService` menyusun dua daftar dari data yang sudah ada
(tanpa tabel baru):

- **Agenda 30 hari** — jadwal pemeliharaan, work order terjadwal, agenda pertamanan,
  peminjaman jatuh tempo, dan perkiraan barang datang dari pengadaan. Maksimal 15 baris,
  diurutkan per tanggal.
- **Prioritas** — hanya baris yang bernilai > 0: pemeliharaan terlambat, kerusakan menunggu
  tindakan, listrik padam aktif, aset belum diverifikasi, peminjaman menunggu persetujuan,
  pengadaan menunggu persetujuan, usulan penghapusan.

Dipakai di dua tempat:

| Layar | Berkas |
|---|---|
| Dashboard Sarpras (`sarpras.dashboard.index`) | kartu di bawah peta alur kerja |
| Dashboard Unit Rumah Tangga | widget `agenda-prioritas-urt` (semua jabatan) |

Setiap baris agenda dan prioritas adalah tautan langsung ke layar penanganannya, sehingga
dashboard berfungsi sebagai daftar kerja harian, bukan sekadar angka.


## Laporan Penugasan Sarpras

Kebersihan punya `cleaning_reports`; penugasan teknis (perbaikan & pemeliharaan)
memakai halaman terpisah: **Sarpras → Laporan Penugasan**
(`sarpras.assignment-report.index`).

Alur:

1. Teknisi menyelesaikan work order (`sarpras.teknisi.complete`).
2. Sistem langsung mengarahkan ke formulir laporan dengan pekerjaan terpilih
   (`?work_order_id=`), isian sudah terisi otomatis dari lingkup pekerjaan.
3. Petugas melengkapi laporan singkat → status *Menunggu verifikasi*.
4. Koordinator URT / Admin Sarpras memverifikasi → status *Terverifikasi*.

Isi laporan (sengaja singkat):

| Kolom | Wajib | Keterangan |
|---|---|---|
| Apa yang dikerjakan | ya | Satu paragraf, sudah terisi otomatis dari work order |
| Kendala | tidak | Ada tombol pilihan cepat (sparepart, alat, akses, cuaca, vendor) |
| Tindak lanjut | tidak | Mis. pantau 1 minggu, ajukan penggantian unit |
| Durasi / kondisi setelah / suku cadang / biaya | tidak | Diisi bila relevan |

Halaman ini sekaligus menjadi penanda agenda:

- Tab **Belum dilaporkan** = work order berstatus selesai yang belum punya laporan
  (agenda yang belum ditutup secara administratif).
- Tab **Laporan** = riwayat laporan beserta status verifikasinya.
- Angka ini juga muncul di peta alur kerja (langkah *Isi laporan pekerjaan*) dan
  di daftar prioritas dashboard.

Satu work order hanya boleh punya satu laporan; duplikasi ditolak dengan pesan jelas.


---

## QR Code Aset & Timeline Kegiatan

### 1. QR dibuat otomatis

Setiap aset mendapat QR saat dibuat (`assets.qr_generated_at` terisi otomatis lewat
`Asset::saving`), tidak ada lagi langkah "Generate QR" manual. Tombol *Generate*
yang lama tetap ada hanya untuk membersihkan data lama.

Isi QR adalah URL stabil tanpa `userId`:

- per unit: `/sarpras/scan/{asset_code}`
- kelompok: `/sarpras/scan/group/{qr_group_key}`

URL tanpa `userId` membuat label yang sudah dicetak tetap valid walau dipindai
pegawai lain (diarahkan ke route ber-`userId` oleh redirect legacy).

### 2. Dua mode QR

| Mode | Dipakai untuk | Perilaku |
|---|---|---|
| `individual` (default) | elektronik, aset besar, aset bernilai tinggi | satu label per unit, riwayat terpisah |
| `group` | barang banyak & sejenis (kursi, meja, piring) | satu label per kelompok ruang + kategori |

Kunci kelompok dibuat dari `room_id + asset_category_id + work_unit_id`
(`Asset::makeQrGroupKey`), sehingga aset sejenis di ruang yang sama otomatis
berbagi satu label. Mode diubah dari halaman detail aset (kartu **QR Code Aset**)
oleh Kepala/Koordinator URT atau petugas Sarpras.

Saat QR kelompok dipindai, pengguna melihat daftar seluruh unit dalam kelompok
beserta kondisi masing-masing (`sarpras.qr.group`), lalu bisa masuk ke detail
atau timeline unit tertentu.

### 3. Akses cepat scan

- Sidebar URT: menu **Scan QR Aset** (badge *Cepat*), tampil untuk struktural
dan petugas operasional.
- Dashboard URT: tile pertama pada **Aksi Cepat URT**.
- Dashboard Sarpras: tombol **Scan QR Aset** di header peta alur.

### 4. Timeline kegiatan ala Excel

Halaman: **Aset → Detail → Timeline** (`sarpras.aset.timeline`).

Grid-nya disusun seperti spreadsheet:

- Klik sel lalu ketik; `Enter` turun satu baris, `Tab` ke kolom kanan.
- `Ctrl/Cmd + V` menempel blok dari Excel/Google Sheets (pemetaan kolom otomatis).
- Tombol **Tambah Baris**, hapus baris (baris lama ditandai hapus, bukan langsung hilang).
- Satu tombol **Simpan Timeline** menyimpan semua baris sekaligus.
- **Ekspor CSV** (UTF-8 + BOM) agar bisa dibuka langsung di Excel.

Kolom: Tanggal*, Jenis* (perawatan rutin/perbaikan/darurat), Uraian pekerjaan,
Pelaksana, Biaya, Kondisi sebelum, Kondisi sesudah, Jadwal berikutnya, Catatan.

Data disimpan di `maintenance_histories`, sehingga otomatis muncul di Asset Passport
dan perhitungan biaya siklus hidup aset. Di bawah grid ditampilkan **Riwayat
Perbaikan** (tiket `repair_requests`) yang berasal dari alur penugasan — read-only
karena sumbernya workflow, bukan input manual.
