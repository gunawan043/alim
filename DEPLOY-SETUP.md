# ALIM — Auto-Deploy via GitHub Webhook (Opsi B)

Push → GitHub → webhook → server langsung `git pull` + restart. Tanpa SSH tunnel, tanpa pihak ketiga.

---

## 1. Persiapan Server (5 menit, sekali saja)

### 1.1 Buat SSH deploy key
```bash
# Di lokal (laptop), kalau belum ada
ssh-keygen -t ed25519 -C "alim-deploy" -f ~/.ssh/alim_deploy -N ""

# Tambahkan public key ke GitHub repo: Settings → Deploy keys → Add
# Centang "Allow write access" TIDAK perlu, cukup read
cat ~/.ssh/alim_deploy.pub
```

### 1.2 Setup di server
```bash
# Login ke server
ssh user@server

# Tambah public key ke server (jika belum)
mkdir -p ~/.ssh && chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys   # paste public key dari lokal

# Clone repo (atau pull yang sudah ada)
sudo mkdir -p /var/www/alim
sudo chown -R $USER:www-data /var/www/alim
cd /var/www/alim
git clone git@github.com:<org>/<repo>.git .

# Atau jika sudah ada, set remote SSH
git remote set-url origin git@github.com:<org>/<repo>.git

# Test SSH ke GitHub
ssh -T git@github.com
```

### 1.3 Copy & permission deploy script
```bash
# deploy.sh sudah ada di root project (dari git pull di atas)
cd /var/www/alim
chmod +x deploy.sh

# Test syntax
bash -n deploy.sh
```

### 1.4 Konfigurasi `.env` di server
Tambahkan:
```env
DEPLOY_SECRET=<string-random-32-karakter>
DEPLOY_BRANCH=main
DEPLOY_SCRIPT=/var/www/alim/deploy.sh
APP_URL=https://alim.sekolah.sch.id
```

Generate secret:
```bash
openssl rand -hex 32
```

Edit `APP_DIR` di `deploy.sh` sesuai server:
```bash
nano /var/www/alim/deploy.sh
# Cari baris: APP_DIR="/var/www/alim"   ← sesuaikan
```

### 1.5 Sudo tanpa password (untuk reload PHP-FPM)
```bash
sudo visudo
# Tambahkan:
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload php*-fpm
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
```

Atau jika pakai user berbeda, sesuaikan `deploy.sh` di bagian restart.

---

## 2. Setup GitHub Webhook (2 menit)

1. Buka repo → **Settings** → **Webhooks** → **Add webhook**
2. Isi:
   ```
   Payload URL  : https://alim.sekolah.sch.id/webhook/deploy
   Content type : application/json
   Secret       : <paste DEPLOY_SECRET dari .env>
   SSL verify   : ✓ Enable
   ```
3. **Which events**: "Just the push event"
4. **Active** ✓
5. Klik **Add webhook**

GitHub akan kirim **ping** untuk test. Kalau hijau, sukses.

---

## 3. Test Alur

### 3.1 Test webhook manual (dari lokal)
```bash
# Simulasi GitHub ping
curl -X POST https://alim.sekolah.sch.id/webhook/deploy \
     -H "Content-Type: application/json" \
     -H "X-GitHub-Event: ping" \
     -d '{"zen": "test"}'
# Expected: 401 (karena tanpa signature) — webhook hidup ✓

# Health check
curl https://alim.sekolah.sch.id/health
# Expected: {"status":"ok",...}
```

### 3.2 Test push beneran
```bash
# Di lokal
echo "# test" >> README.md
git add . && git commit -m "test deploy"
git push origin main

# Tunggu 3–5 detik
# Cek log:
ssh user@server "tail -f /var/www/alim/storage/logs/deploy-$(date +%Y-%m-%d).log"
```

Output yang diharapkan:
```
============================================================
DEPLOY DIMULAI
Direktori : /var/www/alim
Branch    : main
============================================================
[1/7] Git pull...
HEAD is now at abc1234 ...
Sekarang di commit: abc1234
[2/7] Composer install...
...
[7/7] Restart workers...
...
============================================================
DEPLOY SELESAI dalam 45 detik
```

---

## 4. Health Check (Opsional)

Buat endpoint `/health` sederhana. Sudah ditambahkan di `DeployController`:
```bash
curl https://alim.sekolah.sch.id/health
# → {"status":"ok","time":"...","app":"ALIM","env":"production"}
```

Untuk monitoring otomatis, bisa pakai **UptimeRobot** (gratis) pointing ke `/health`.

---

## 5. Troubleshooting

### Webhook merah di GitHub
Cek **Settings → Webhooks → Recent Deliveries → klik request → Response tab**.
- `401`: signature salah → cek `DEPLOY_SECRET` sama dengan di GitHub
- `500`: server error → cek `storage/logs/laravel.log`
- `Ignored: push to X, watching Y`: push ke branch lain, normal

### Deploy mulai tapi gagal
Lihat log:
```bash
tail -50 /var/www/alim/storage/logs/deploy-$(date +%Y-%m-%d).log
```

### Permission error
```bash
# Pastikan www-data bisa baca
sudo chown -R www-data:www-data /var/www/alim
sudo chmod -R 755 /var/www/alim
sudo chmod +x /var/www/alim/deploy.sh
```

### Script tidak ada
Pastikan `DEPLOY_SCRIPT` di `.env` mengarah ke path absolut yang benar.

---

## 6. Keamanan

- ✅ **Signature validation** (HMAC SHA-256) — request tanpa signature ditolak 401
- ✅ **Branch filter** — hanya push ke branch `DEPLOY_BRANCH` yang diproses
- ✅ **Flock lock** — anti race (2 push cepat = 1 deploy)
- ✅ **Fail-fast** — `set -euo pipefail` di script
- ✅ **HTTPS only** — GitHub menolak webhook ke plain HTTP
- ⚠ **Secret rotation**: jika curiga bocor, ubah `DEPLOY_SECRET` di `.env` + GitHub webhook

---

## 7. Ringkasan Alur

```
Developer                GitHub                  Server
   │                       │                        │
   │ git push origin main  │                        │
   ├──────────────────────►│                        │
   │                       │ POST /webhook/deploy   │
   │                       │ (signed, JSON)         │
   │                       ├───────────────────────►│
   │                       │                        │ DeployController
   │                       │                        │ → validasi signature ✓
   │                       │                        │ → validasi branch ✓
   │                       │                        │ → nohup deploy.sh &
   │                       │  202 Accepted          │
   │                       │◄───────────────────────┤
   │                       │                        │ git pull
   │                       │                        │ composer install
   │                       │                        │ migrate
   │                       │                        │ npm build
   │                       │                        │ cache:clear+cache
   │                       │                        │ queue:restart
   │                       │                        │ → selesai
   │  (deploy sukses)      │                        │
   │◄──────────────────────────────────────────────┤
```
