# Security Rotation Guide — ALIM

Laporan ini berisi instruksi bagi administrator untuk memutar ulang seluruh rahasia yang ada di produksi.
Lakukan segera bila ada keraguan bahwa rahasia telah terekspos.

> ⚠️ Setiap kali memutar ulang kunci, seluruh sesi pengguna yang aktif akan berakhir.

---

## 1. APP_KEY

Regenerasi `APP_KEY` me-reencrypt seluruh data yang di-encrypt via Laravel Crypt/Encryptable trait.

```bash
cd /var/www/alim
php artisan key:generate --force
php artisan config:clear
php artisan cache:clear
```

Dampak: semua data yang di-encrypt dengan key lama menjadi tidak bisa dibaca ulang.

---

## 2. Database Credentials

Langkah:

1. Buat kredensial baru dari provider (cPanel, MySQL root, dsb).
2. Di server, edite `.env`:

```ini
DB_USERNAME=xxx
DB_PASSWORD=yyy
```

3. Uji koneksi:

```bash
php artisan tinker --execute="DB::connection()->getPdo()"
```

4. Tidak ada downtime karena kredensial hanya dipakai oleh proses baru.

---

## 3. SMTP Credentials

Edite `.env`:

```ini
MAIL_USERNAME=xxx
MAIL_PASSWORD=yyy
```

Uji kirim:

```bash
php artisan tinker --execute="Mail::raw('test', fn($m) => \$m->to('test@example.com')->subject('Test'))"
```

---

## 4. Broadcast / Websocket Secrets (Pusher)

Edit `.env`:

```ini
PUSHER_APP_KEY=xxx
PUSHER_APP_SECRET=yyy
```

Tidak membutuhkan restart — Laravel membaca ulang dari `.env` tiap request.

Untuk Pusher Channels, buat aplikasi baru di console.pusher.com dan salin kredensial baru.

---

## 5. Recruitment API Token

Edite `.env`:

```ini
RECRUITMENT_API_TOKEN=xxxxxxxxxxxxx
```

Uji:

```bash
curl -H "Authorization: Bearer $RECRUITMENT_API_TOKEN" https://recruitment.abuhurairah.id/api/health
```

---

## 6. Google OAuth Client Secret

Edite `.env`:

```ini
GOOGLE_CLIENT_SECRET=xxxxxxxxxxxx
```

Untuk memutar ulang:

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Cari project ALIM
3. Revoke client secret lama, buat yang baru
4. Salin ke `.env`

---

## 7. Deploy Webhook Secret

```bash
openssl rand -hex 32
```

Salin hasil ke `.env`:

```ini
DEPLOY_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Di GitHub: Settings → Webhooks → Secret → ganti dengan nilai baru.

Uji:

```bash
curl -X POST https://alim.sekolah.sch.id/webhook/deploy \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=$(echo -n '{}' | openssl dgst -sha256 -hmac $(cat .env | grep DEPLOY_SECRET | cut -d= -f2) | cut -d' ' -f2)" \
  -d '{}'
```

---

## 8. Pusher Demo Keys

`.env` dan `.env.example` memiliki kunci `*_DEMO`. Hapus setelah produksi berjalan.

```ini
# Hapus atau kosongkan di .env production:
PUSHER_APP_ID_DEMO=
PUSHER_APP_KEY_DEMO=
PUSHER_APP_SECRET_DEMO=
```

Demo keys ini tidak berbahaya (sandbox Pusher), namun sebaiknya tidak terlihat di kode.
