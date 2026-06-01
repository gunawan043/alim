# Deployment Guide — ALIM PUSTIK

## 1. Setup Awal (cukup sekali per server)

### A. SSH & Git
```bash
# Pastikan web-server user (mis. u1732055) bisa pull tanpa password
ssh-keygen -t ed25519 -C "deploy@alim" -N ""
# Salin isi ~/.ssh/id_ed25519.pub → Settings → Deploy keys (write access) di GitHub
ssh -T git@github.com   # harusnya: "Hi <user>/<repo>! You've been granted access."
```

### B. Set Secret Webhook
Tambahkan ke `.env` (atau hard-code di `config/app.php`):
```env
DEPLOY_WEBHOOK_SECRET=ganti-dengan-string-random-panjang-min-32-char
```
Generate secret yang kuat:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### C. Permission Folder
```bash
cd /home/u1732055/public_html
chown -R u1732055:u1732055 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### D. Symlink Storage (jika belum)
```bash
php artisan storage:link
```

---

## 2. Cara Trigger Deploy

### Cara A: Lewat URL (manual / cron / GitHub webhook)
```
GET https://alim.abuhurairah.id/api/deploy?secret=<DEPLOY_WEBHOOK_SECRET>&branch=main
```

Header alternatif (lebih aman, tidak bocor di log proxy):
```
GET /api/deploy
X-Webhook-Secret: <DEPLOY_WEBHOOK_SECRET>
```

Response:
```json
{
  "ok": true,
  "branch": "main",
  "ran": 6,
  "output": [ ... ],
  "message": "Deploy selesai tanpa error fatal."
}
```

### Cara B: Otomatis dari GitHub (recommended)
1. Buka **GitHub repo → Settings → Webhooks → Add webhook**
2. **Payload URL**: `https://alim.abuhurairah.id/api/deploy?secret=<DEPLOY_WEBHOOK_SECRET>`
3. **Content type**: `application/json`
4. **Which events**: Just the push event
5. Save → GitHub akan mengirim `POST` ke URL di atas setiap `git push` ke branch yang dipilih.

> Catatan: GitHub tidak mengirim `?secret=...` secara otomatis untuk webhook event. Solusi aman: gunakan field **Secret** di form webhook GitHub, lalu modifikasi `DeployWebhookController` untuk memvalidasi header `X-Hub-Signature-256` (saat ini controller hanya mendukung `?secret=` dan header `X-Webhook-Secret`).

---

## 3. Yang Dilakukan Webhook

Berjalan berurutan, berhenti log jika ada error fatal:

| # | Perintah | Tujuan |
|---|----------|--------|
| 1 | `git pull origin <branch>` | Tarik kode terbaru |
| 2 | `composer install --no-dev` | Install dependency production |
| 3 | `php artisan migrate --force` | Jalankan migration baru |
| 4 | `php artisan config:cache` | Cache konfigurasi |
| 5 | `php artisan route:cache` | Cache route |
| 6 | `php artisan view:cache` | Cache blade |

---

## 4. Troubleshooting

### `fatal: could not access 'origin/main'`
→ Deploy key belum di-add, atau branch default bukan `main`. Cek:
```bash
git remote -v
git branch -a
```

### `Please make sure you have the correct access rights`
→ Public key di server belum masuk ke GitHub. Ulangi langkah 1A.

### `Class "Redis" not found`
→ Enable extension redis di `php.ini` shared hosting, atau ganti `CACHE_DRIVER=file` di `.env`.

### Webhook balas 401
→ Secret di URL tidak sama dengan `DEPLOY_WEBHOOK_SECRET` di `.env`. Cek pakai `php artisan tinker`:
```php
config('app.deploy.webhook_secret') ?? env('DEPLOY_WEBHOOK_SECRET')
```

### Webhook balas 500 "secret belum diset"
→ `DEPLOY_WEBHOOK_SECRET` kosong di `.env`. Tambahkan, lalu:
```bash
php artisan config:clear
```

### Migration jalan tapi cache tidak ke-refresh
Tambahkan ke cron:
```
*/5 * * * * cd /home/u1732055/public_html && php artisan optimize:clear >> /dev/null 2>&1
```

---

## 5. Hardening (Optional tapi Disarankan)

1. **Batasi IP** — jika tahu range IP GitHub Actions / proxy, tambahkan di middleware `deploy.ip`:
   ```php
   // app/Http/Middleware/RestrictDeployIp.php
   if (! in_array($request->ip(), ['140.82.112.0/20'])) abort(403);
   ```
2. **Rate limit** — pasang throttle `10,1` di route.
3. **HTTPS only** — controller sudah di belakang TLS shared hosting, tidak perlu tambahan.
4. **Audit log** — semua attempt sudah di-log ke `storage/logs/laravel.log` (cari `Deploy webhook`).
