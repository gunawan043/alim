# Cycle 9.5 — Rancangan VPS Staging Terisolasi (Opsi B)

**Tanggal:** 2026-11-24 · **Status:** rancangan saja — **belum ada yang dibeli, dipasang, atau dibuka**
**Repo Core:** `alim`, branch `main`, commit lokal terakhir `7d5c0345`

Dokumen ini adalah **permintaan persetujuan**, bukan laporan pekerjaan selesai. Tidak ada VPS yang
dibuat, DNS yang diubah, atau akses publik yang dibuka.

---

## 0. Ringkasan keputusan yang diminta

| # | Keputusan | Perkiraan biaya/bln | Bila tidak disetujui |
| --- | --- | --- | --- |
| D1 | Sewa VPS 2 vCPU / 4 GB (staging Core) | Rp 80–150 rb | Tetap pakai staging lokal; TLS + egress nyata tidak terbukti |
| D2 | Subdomain **`alim-staging.abuhurairah.id`** (DNS record A) | Rp 0 | Tidak ada host untuk Connect |
| D3 | Sertifikat TLS Let's Encrypt | Rp 0 | — |
| D4 | Penyerahan CIDR egress Connect | Rp 0 | Allowlist tidak bisa diisi |
| D5 | Kredensial staging baru (2 kunci + 1 ingest) | Rp 0 | Tidak ada isolasi kredensial |

Catatan penting: keputusan Cycle 9.4 menetapkan **Core produksi** tetap di `alim.abuhurairah.id`
dan tidak ada subdomain staging. Opsi B **menghidupkan kembali** ide subdomain, tetapi di host
**lain** (VPS), bukan di document root produksi. Bila Anda ingin menghindari subdomain sama sekali,
alternatifnya memakai domain VPS sementara (mis. `sslip.io`) — lihat §8.

## 1. Mengapa opsi B, dan apa yang masih belum terbukti

Staging lokal sudah membuktikan kontrak (200, duplicate, 409, 422, 401, 403, SSO 200→409). Yang
**belum** terbukti dan hanya bisa lewat host nyata:

| # | Belum terbukti | Kenapa butuh VPS |
| --- | --- | --- |
| 1 | TLS sungguhan | `REQUIRE_HTTPS=true` tidak bermakna di `127.0.0.1` |
| 2 | Allowlist IP egress Connect | Butuh IP publik asal yang diketahui |
| 3 | Perilaku di PHP 8.3/8.4 produksi-like | Lokal memakai PHP versi mesin pengembang |
| 4 | Kinerja & timeout nyata lintas jaringan | Latensi lokal ~0 ms |
| 5 | Migrasi dari DB kosong di server bersih | Membuktikan tidak bergantung artefak lokal |

## 2. Spesifikasi minimum

Perkiraan berdasarkan ukuran aktual repo: kode ter-track 12.664 file, `public/build` 183 MB,
`vendor` ±215 MB setelah `composer install`, `storage` awal < 5 MB.

| Komponen | Minimum | Nyaman |
| --- | --- | --- |
| vCPU | 2 | 2 |
| RAM | 4 GB (composer + migrate butuh ~1 GB) | 4 GB |
| Disk | 25 GB SSD | 40 GB |
| OS | Ubuntu 24.04 LTS | sama |
| PHP | 8.3 atau 8.4 + `pdo_sqlite`, `sqlite3`, `mbstring`, `xml`, `curl`, `zip`, `fileinfo`, `openssl`, `tokenizer`, `ctype` | sama |
| Web server | nginx + `php-fpm` | sama |
| DB | SQLite berkas (2 DB: satu staging, satu pembanding) | sama |
| Cadangan | 3 snapshot harian, retensi 7 hari | sama |

`composer.json` mewajibkan `php ^8.3`, jadi PHP 8.2 **tidak** cukup.

**Catatan ukuran:** `git archive HEAD` = 268 MB, karena `public/build` (183 MB) ikut ter-track.
`vendor/` tidak ter-track dan harus di-`composer install` di VPS (±215 MB). Sisakan ≥ 5 GB bebas
untuk vendor + storage + cache.

**Biaya:** VPS 2/4 di penyedia umum Indonesia/Singapura sekitar Rp 80–150 rb/bulan; TLS dan
subdomain gratis. Tidak ada biaya lain karena queue `sync`, cache `file`, dan storage `local`.

## 3. Isolasi — bagaimana dijamin tidak menyentuh produksi

| Objek | Produksi | VPS staging | Jaminan |
| --- | --- | --- | --- |
| Host | `alim.abuhurairah.id` (shared hosting) | VPS terpisah | Mesin berbeda, akun SSH berbeda |
| Direktori | `~/alim-app` | `/srv/alim-staging` | Tidak ada akses lintas mesin |
| Database | MySQL/berkas produksi | `database/staging.sqlite` | Berkas berbeda, mesin berbeda |
| `.env` | `.env` produksi | `.env` baru dari secret manager staging | `APP_KEY` baru, tidak menyalin |
| `CONNECT_*` | **tidak ada** (tetap kosong) | kunci staging | Produksi tidak diberi kredensial |
| Data | data nyata santri/GTK | seeder sintetis saja | Tidak ada dump/salinan data |
| Cache/queue | produksi | `file` + `sync` | Tidak berbagi Redis/queue |

**Tidak pernah dilakukan:** menyalin `.env` produksi, menyalin database produksi, menyalin
`storage/app`, atau memakai `APP_KEY` produksi.

## 4. Sumber kode — dari commit yang diverifikasi

```
git -C alim archive --format=tar <COMMIT> | ssh vps 'tar -x -C /srv/alim-staging'
```

- Commit yang dipakai dicatat eksplisit (mis. `7d5c0345`) dan diverifikasi dengan
  `git verify-commit` bila ditandatangani, atau minimal `git log -1 --format=%H` yang dicocokkan.
- `vendor/` **di-generate** di VPS lewat `composer install --no-dev --optimize-autoloader`
  dari `composer.lock` — tidak disalin dari mesin pengembang.
- `public/build` ikut ter-track sehingga ikut di dalam `git archive`; tidak perlu build ulang.

## 5. Data uji — sintetis, tanpa data pribadi

Sudah tersedia dan **sudah sintetis**: `ConnectStagingTestDataSeeder` memakai domain
`@staging.invalid` dan nomor `628000…` palsu, membuat santri/rombel/kamar sendiri, serta menolak
berjalan bila `APP_ENV` bukan `staging` atau driver bukan SQLite.

| Data | Nilai |
| --- | --- |
| Akun personel | `kepala.asrama@staging.invalid` |
| Akun SSO | `sso.penguji@staging.invalid`, `sso.tanpaizin@staging.invalid` |
| Aktor WhatsApp | `6280000000001` (verified) |
| Santri | `STG-0001` — "Santri Uji Connect (staging)" |
| Permit | 2 `pending` (aktif & kedaluwarsa) |

Tidak ada nama santri, NIS, NISN, atau nomor WA yang berasal dari produksi.

## 6. Kredensial — staging khusus, tiga kunci

| Kunci | Pemilik | Dipakai |
| --- | --- | --- |
| `CONNECT_SSO_API_KEY` (scope `sso:exchange`) | Core staging | SSO masuk |
| `CONNECT_APPROVAL_API_KEY` (scope `approval:command`) | Core staging | Approval masuk |
| `ALIM_CONNECT_INGEST_KEY` | **Connect** | Core → Connect (`POST /approval/requests`) |

Ketiganya **baru**, bukan turunan kunci produksi (produksi memang tidak punya). Disimpan di secret
manager VPS (berkas 0600 di luar webroot, atau `systemd EnvironmentFile`), tidak pernah dicetak.
`APP_KEY` staging dibuat baru.

## 7. Instalasi (ringkas, terkontrol)

```
1. Buat VPS, user non-root `deploy`, SSH key only, ufw: 22 (IP admin), 443, 80 (untuk ACME saja).
2. Pasang nginx, php-fpm 8.4 + ekstensi, composer, certbot, git.
3. git archive <COMMIT> | tar -x -C /srv/alim-staging  (tanpa .env, tanpa DB).
4. composer install --no-dev --optimize-autoloader.
5. Pasang .env staging (0600) dari secret manager; DB_CONNECTION=sqlite.
6. touch database/staging.sqlite; php artisan migrate --force.
7. php artisan db:seed --class=ConnectStagingTestDataSeeder --force.
8. php artisan alim:verify-connect-schema  → harus exit 0.
9. nginx: root /srv/alim-staging/public; deny .env, deny storage/.
10. certbot --nginx -d alim-staging.abuhurairah.id.
11. Isi allowlist IP egress Connect, set REQUIRE_HTTPS=true.
12. php artisan config:cache route:cache.
```

Setiap langkah gagal → berhenti. Tidak ada langkah yang menyentuh mesin produksi.

## 8. HTTPS dan DNS — dua jalur

| Jalur | Domain | Butuh keputusan |
| --- | --- | --- |
| B1 | `alim-staging.abuhurairah.id` → A record ke IP VPS | Anda perlu menambah record DNS |
| B2 | `alim-staging.<ip>.sslip.io` (tanpa DNS) | Tidak perlu ubah DNS, tetapi domain terlihat tidak resmi |

B1 lebih layak untuk bukti serius karena Connect perlu mendaftarkan origin yang stabil. B2 hanya
untuk percobaan awal.

## 9. Pemeriksaan kesehatan dan pengujian kontrak (di VPS)

| # | Uji | Harapan |
| --- | --- | --- |
| H1 | `php artisan alim:verify-connect-schema` | exit 0, kolom `idempotency_key` + `api_version` ada |
| H2 | `curl https://<host>/health` | 200, `env` = `staging` (bukti pemisahan; `/up` **tidak ada** — 404) |
| H3 | `POST /sso/exchange` tanpa key | 401 |
| H4 | `POST /approval-commands` tanpa key | 401 |
| H5 | key salah | 401 |
| H6 | versi `2` | 422 `unsupported_api_version` |
| H7 | versi `1`, payload sah, permit pending | 200 |
| H8 | replay identik | 200 `duplicate:true` |
| H9 | `command_id` sama + payload beda | 409 `command_id_reused` |
| H10 | aktor tak terpetakan | 403 `actor_identity_unmapped` |
| H11 | SSO kode baru | 200, `core_user_id`, tanpa token di body |
| H12 | SSO kode dipakai ulang | 409 `code_already_used` |
| H13 | Audit | `DORMITORY_PERMIT_APPROVED` dengan `user_id` terisi |
| H14 | Isolasi data | `dormitory_permits` hanya 2 baris sintetis; tidak ada nama produksi |
| H14b | Isolasi lingkungan | `/health` mengembalikan `env=staging`, sedangkan produksi `env=production` |
| H15 | Request dari IP di luar allowlist | 403 |
| H16 | Kebocoran internal | body error tanpa `SQLSTATE`/stack/path |

Semua langkah memakai permit sintetis; setelah selesai, permit di-reset dengan skrip yang sama
seperti di staging lokal.

## 10. Backup dan rollback

**Backup** (di VPS): snapshot penyedia harian (retensi 7 hari) + `sqlite3 .backup` harian ke
`/var/backups/alim-staging/` (retensi 7 hari) + `.env` tidak ikut backup otomatis (disimpan di
secret manager).

**Rollback** — tidak ada yang perlu dibatalkan di produksi karena staging tidak menyentuhnya:

| Langkah | Aksi |
| --- | --- |
| R1 | Tutup port 443 (ufw) → uji berhenti seketika |
| R2 | `systemctl stop nginx` atau hapus vhost |
| R3 | `rm -rf /srv/alim-staging` (kode, .env, DB staging) |
| R4 | Cabut sertifikat (`certbot delete`) |
| R5 | Hapus A record subdomain |
| R6 | Rotasi ketiga kunci staging, lalu samakan di Connect |
| R7 | Destroy VPS (atau simpan snapshot untuk forensik) |

Produksi tidak tersentuh di semua langkah karena mesin, direktori, DB, dan kredensialnya terpisah.

## 11. Langkah Connect (pemilik: operator Connect)

| # | Hal |
| --- | --- |
| C1 | Daftarkan `ALIM_CORE_BASE_URL=https://<host-staging>` |
| C2 | Daftarkan `ALIM_CORE_WEB_ORIGIN` = origin Connect staging |
| C3 | Terima 2 kunci Core lewat secret manager |
| C4 | Berikan CIDR egress untuk allowlist |
| C5 | Jalankan `npm run test:core-integration -- --approval`, lalu `--sso-code` |

Catatan: runner Connect masih punya tiga cacat yang ditemukan di Cycle 9.3 (URL ditimpa mock, kunci
ditimpa kunci test, lane hijau tanpa bukti). Uji mutasi nyata layak ditunggu setelah itu diperbaiki,
**atau** diverifikasi dari log Core — seperti yang saya lakukan di staging lokal.

## 12. Yang diminta dari Anda

1. **Setuju/tolak D1** (sewa VPS 2 vCPU/4 GB, Rp 80–150 rb/bln).
2. **Pilih jalur domain**: B1 (tambah A record `alim-staging.abuhurairah.id`) atau B2 (sslip.io).
3. Konfirmasi bahwa **CIDR egress Connect** akan diminta ke operator Connect.
4. Konfirmasi bahwa **kunci staging baru** boleh diterbitkan (bukan memakai kunci mana pun yang ada).

Setelah empat hal itu disetujui, saya baru menyiapkan skrip instalasi VPS dan menjalankannya —
dengan persetujuan terpisah sebelum akses publik dibuka.
