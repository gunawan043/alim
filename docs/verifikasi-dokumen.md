# Verifikasi Dokumen & Kesiapan PAdES

## Ringkasan

ALIM menerbitkan dokumen resmi dengan dua mode validasi:

| Mode | Validasi | Halaman publik |
|---|---|---|
| `qr` | Registry dokumen + token QR + HMAC | Ya, tanpa login |
| `pades` | Signature kriptografis PDF + registry | Ya, metadata; signature dicek di pembaca PDF |

Stempel dan scan TTD tetap dicetak pada kedua mode. Keduanya adalah unsur
visual resmi; mode validasi menentukan cara pembuktian keaslian.

## Yang sudah aktif

- Registry `document_signatures` dan `document_signature_signers`.
- Token QR acak; database hanya menyimpan hash token.
- Halaman publik `GET /verifikasi-dokumen/{token}` tanpa login.
- Pencatatan jumlah dan waktu pemindaian.
- Pencabutan dokumen tanpa menghapus riwayat.
- Deteksi payload yang diubah (status **Integritas Tidak Cocok**).
- QR footer untuk:
  - rapor (single dan massal),
  - surat mutasi masuk,
  - surat mutasi keluar/lulus/DO,
  - SK.
- Dukungan dua penandatangan rapor: KSP dan wali kelas.

## Mode PAdES

PAdES **belum menandatangani PDF secara kriptografis** karena provider PSrE
belum dipilih dan sertifikat belum tersedia. Yang sudah disiapkan:

- kolom `validation_mode`, `pades_profile`, metadata sertifikat per penandatangan;
- `PadesSigningService` sebagai adaptor remote signing;
- penolakan eksplisit bila mode `pades` dipilih tanpa konfigurasi;
- konfigurasi `config/documents.php` dan variabel `.env.example`.

### Yang dibutuhkan untuk mengaktifkan PAdES

1. Provider PSrE: BSrE, Privy, VIDA, Digisign, atau lainnya.
2. Sertifikat personal untuk setiap pejabat penandatangan.
3. Remote signing API/HSM; private key tidak boleh disimpan di server aplikasi.
4. Sertifikat chain dan TSA URL (untuk PAdES-B-T ke atas).
5. Implementasi pemanggilan provider di `PadesSigningService::sign()`.

Setelah tersedia, isi:

```env
DOCUMENTS_PADES_ENABLED=true
DOCUMENTS_PADES_ENDPOINT=https://...
DOCUMENTS_PADES_CLIENT_ID=...
DOCUMENTS_PADES_CLIENT_SECRET=...
DOCUMENTS_PADES_PROFILE=PAdES-B-T
DOCUMENTS_PADES_TSA_URL=https://...
```

Lalu ubah mode per dokumen:

```env
DOCUMENTS_MODE_SK=pades
DOCUMENTS_MODE_MUTASI=pades
```

## Kunci tanda tangan QR

Isi `DOCUMENTS_SIGNING_KEY` dengan nilai acak panjang dan simpan di secret
manager. Jika kosong, sistem memakai `APP_KEY`; ini hanya fallback agar
dokumen lama tidak langsung gagal, bukan pilihan produksi yang disarankan.

## Catatan hukum

- Mode `qr` adalah verifikasi keaslian internal ALIM, bukan tanda tangan
  elektronik tersertifikasi.
- PAdES dengan sertifikat PSrE memiliki kekuatan pembuktian lebih tinggi.
- Untuk dokumen tertentu, konsultasikan kebutuhan hukum dengan pihak yayasan
  dan provider PSrE.

## Pengujian

`tests/Feature/DocumentSigningTest.php` menguji:

- verifikasi publik tanpa login,
- token palsu,
- payload yang diubah,
- pencabutan,
- penolakan PAdES tanpa sertifikat,
- konsistensi hash dan signature,
- render QR ke PDF.

## Pilihan tampilan stempel & scan TTD

Mode validasi (QR/PAdES) **tidak menghapus** stempel dan scan TTD. Keduanya
tetap dapat ditampilkan sebagai unsur visual resmi.

| Dokumen | Pengaturan |
|---|---|
| Rapor | Pengaturan tata letak rapor: `show_ttd_ksp`, `show_ttd_wali`, `show_stamp`, ukuran gambar |
| Surat mutasi | Modal media sekolah: Tampilkan/Sembunyikan TTD KSP dan Stempel |
| SK | Modal media sekolah: Tampilkan/Sembunyikan TTD KSP dan Stempel |

Pengaturan disimpan di `schools.document_visual_settings` dalam bentuk JSON
per jenis dokumen. Rapor tetap memakai `rapor_distribution_settings` karena
memiliki kebutuhan ukuran dan tata letak yang lebih rinci.

Halaman **Upload Kop, TTD & Stempel** pada edit sekolah sekarang memiliki
pilihan terpisah untuk Surat Mutasi dan SK. Pilihan ini hanya mengatur
tampilan; validasi dokumen tetap mengikuti mode QR/PAdES.

Dokumen lain dapat memakai pola yang sama dengan menambahkan key jenis dokumen
pada `DocumentVisualService::TYPES` dan memanggil `viewData()` saat render PDF.

## Ukuran, margin, dan pemisahan QR

- TTD KSP dan stempel memiliki pengaturan **lebar, tinggi, dan margin** (mm)
  per jenis dokumen untuk surat mutasi, SK, dan SKL.
- Rapor memiliki pengaturan lebar/tinggi sendiri dan margin tambahan untuk
  TTD KSP, TTD wali, serta stempel.
- QR verifikasi dipisahkan dari blok TTD/stempel. QR berada pada blok
  **Verifikasi Dokumen** tersendiri, bukan digabung dengan tanda tangan.
- Preview surat mutasi menampilkan TTD/stempel sesuai pengaturan. QR final
  hanya diterbitkan setelah dokumen disimpan, karena token terikat pada
  dokumen resmi.

## Aturan mode validasi

- **visual** — hanya TTD/stempel; tidak ada QR.
- **qr** — hanya QR registry; TTD/stempel tidak dicetak karena QR adalah pengesahan.
- **pades** — signature PAdES + QR registry; TTD/stempel tidak dicetak.

Ukuran TTD/stempel dan margin hanya berlaku pada mode visual. Pada mode QR/PAdES,
kontrol tersebut dinonaktifkan dan gambar tidak dirender.
