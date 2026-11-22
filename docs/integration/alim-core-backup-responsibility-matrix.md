---
name: alim-core-backup-responsibility-matrix
description: Pembagian tanggung jawab backup, penyimpanan, dan review restore ALIM Core
metadata:
  type: governance
  date: 2026-11-21
  status: draft
  scope: production-safety
  depends-on:
    - docs/integration/alim-core-backup-restore-runbook.md
---

# Matriks Tanggung Jawab Backup & Restore — ALIM Core

> **Dokumen tata kelola.** Nama PIC akan ditetapkan pemilik sistem. Dokumen ini hanya menetapkan peran, tanggung jawab, dan pemisahan tugas.

## 1. Prinsip

1. **Pelaksana backup tidak boleh menjadi satu-satunya reviewer restore.**
2. **Pemegang encryption key terpisah dari pemegang media backup.**
3. **Setiap backup harus memiliki bukti checksum dan metadata.**
4. **Restore produksi memerlukan persetujuan pemilik sistem.**
5. **Backup database tidak disimpan di GitHub** (keputusan pemilik sistem; lihat §6).

## 2. Peran

| Kode | Peran | Tanggung jawab utama |
|---|---|---|
| R1 | **System Owner** | Menyetujui kebijakan backup, retensi, RPO/RTO, dan restore produksi |
| R2 | **Infrastructure/DB PIC** | Menjalankan backup, restore test, dan restore produksi |
| R3 | **Storage Custodian** | Mengelola media lokal/offsite, enkripsi, retensi, dan akses |
| R4 | **Restore Reviewer** | Memverifikasi hasil restore, integritas data, dan smoke test |
| R5 | **Security Reviewer** | Meninjau enkripsi, akses, secret, dan audit |
| R6 | **Application Owner** | Memastikan aplikasi berjalan setelah restore dan tidak ada regresi |
| R7 | **Data Owner** | Memverifikasi kebenaran data bisnis setelah restore |

## 3. RACI

**R** = Responsible, **A** = Accountable, **C** = Consulted, **I** = Informed.

| Aktivitas | R1 | R2 | R3 | R4 | R5 | R6 | R7 |
|---|---|---|---|---|---|---|---|
| Menetapkan kebijakan backup/retensi | A | C | C | I | C | I | I |
| Menjalankan backup harian | I | **R** | C | I | I | I | I |
| Enkripsi backup | I | C | **R** | I | C | I | I |
| Menyimpan media lokal/offsite | I | I | **R/A** | I | C | I | I |
| Menjaga encryption key | I | I | C | I | **R/A** | I | I |
| Verifikasi checksum | I | **R** | C | C | I | I | I |
| Restore test ke staging | I | **R** | I | **A** | C | C | C |
| Smoke test aplikasi | I | C | I | C | I | **R/A** | C |
| Verifikasi data bisnis | I | I | I | C | I | C | **R/A** |
| Restore produksi darurat | **A** | **R** | C | C | C | C | C |
| Post-mortem insiden | **A** | R | C | C | C | C | C |
| Audit akses backup | I | C | C | I | **R/A** | I | I |

## 4. Pemisahan tugas (separation of duties)

| Fungsi | Tidak boleh dirangkap oleh |
|---|---|
| Pelaksana backup | Reviewer restore (idealnya) |
| Pemegang encryption key | Pemegang media offsite |
| Pembuat backup | Pemberi persetujuan restore produksi |
| Operator restore | Satu-satunya verifikator data bisnis |

Jika jumlah personel terbatas, lakukan **compensating control**:

- review berpasangan,
- log akses,
- checksum independen,
- persetujuan tertulis System Owner.

## 5. Bukti yang harus disimpan

- Log eksekusi backup (waktu, operator, ukuran, durasi).
- Checksum file.
- Lokasi media lokal/offsite.
- Metadata versi aplikasi/commit.
- Hasil restore test (tanggal, reviewer, hasil smoke test).
- Persetujuan restore produksi.
- Log akses media/enkripsi.

**Jangan** menyimpan passphrase/private key di dokumen ini atau repository.

## 6. Keputusan penyimpanan

Berdasarkan keputusan pemilik sistem:

- **Utama:** lokal + offsite terenkripsi.
- **GitHub:** tidak digunakan untuk dump database, termasuk ciphertext, sampai ada keputusan dan repo privat terpisah.
- Jika nanti digunakan: hanya ciphertext, key terpisah, repo privat, akses minimum, dan uji restore.

## 7. Eskalasi

| Kondisi | Eskalasi |
|---|---|
| Backup gagal 2× berturut-turut | R2 → R1 |
| Checksum tidak cocok | R2 → R1 + R5 |
| Restore test gagal | R2 → R1 + R6 |
| Backup hilang/rusak | R1 + R5 |
| Dugaan kebocoran backup | R1 + R5 segera |
| Restore produksi | Persetujuan R1 |

## 8. Penetapan PIC

| Peran | Nama PIC | Backup/Pengganti | Tanggal berlaku |
|---|---|---|---|
| System Owner | _TBD_ | _TBD_ | _TBD_ |
| Infrastructure/DB PIC | _TBD_ | _TBD_ | _TBD_ |
| Storage Custodian | _TBD_ | _TBD_ | _TBD_ |
| Restore Reviewer | _TBD_ | _TBD_ | _TBD_ |
| Security Reviewer | _TBD_ | _TBD_ | _TBD_ |
| Application Owner | _TBD_ | _TBD_ | _TBD_ |
| Data Owner | _TBD_ | _TBD_ | _TBD_ |

## 9. Larangan

- Jangan menunjuk satu orang sebagai pelaksana dan satu-satunya reviewer.
- Jangan menyimpan key di media backup yang sama.
- Jangan mengunggah dump database ke GitHub.
- Jangan melakukan restore produksi tanpa persetujuan.
- Jangan menghapus backup sebelum masa retensi berakhir.
