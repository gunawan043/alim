# ALIM — P0 Security Hardening Runbook

Dokumen ini adalah checklist eksekusi untuk server produksi. Jalankan sebagai
root hanya pada server yang benar. Jangan menjalankan semua perintah sekaligus
tanpa menyesuaikan nama domain, IP, dan path.

## 1. Cek konfigurasi aplikasi

```bash
php artisan security:preflight
php artisan about --only=environment
```

Target produksi:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-alim
APP_KEY=base64:...
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=IP_REVERSE_PROXY
```

- `TRUSTED_PROXIES` diisi IP Nginx/load balancer/Cloudflare. Jangan isi `*`
  bila server dapat diakses langsung.
- Jangan pernah commit `.env` atau `APP_KEY`.
- `SESSION_SECURE_COOKIE=true` hanya untuk HTTPS.

## 2. SSH dan firewall

Contoh Ubuntu/Debian (sesuaikan port SSH terlebih dahulu):

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

Hardening SSH:

```bash
sudo sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo systemctl restart ssh
```

Pastikan SSH key sudah diuji sebelum menutup password login.

Install fail2ban:

```bash
sudo apt update && sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
sudo fail2ban-client status
```

Jangan membuka port MySQL/Redis ke internet:

```bash
sudo ss -lntp
```

Port database seharusnya hanya mendengarkan `127.0.0.1` atau private network.

## 3. Laravel dan PHP

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan permission storage/log hanya untuk user aplikasi:

```bash
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Gunakan user PHP-FPM non-root. Jangan menjalankan `php artisan serve` di
produksi; gunakan Nginx + PHP-FPM.

## 4. Backup dan restore

Backup database + storage secara terjadwal, terenkripsi, dan di luar server.

Contoh backup MySQL (simpan kredensial di file yang hanya dapat dibaca root):

```bash
install -m 600 /dev/null /root/.alim-my.cnf
cat >/root/.alim-my.cnf <<'CNF'
[client]
user=alim_backup
password=GANTI_PASSWORD
host=127.0.0.1
CNF

mkdir -p /var/backups/alim
mysqldump --defaults-extra-file=/root/.alim-my.cnf \
  --single-transaction --quick --routines --triggers --events \
  DB_ALIM | gzip > /var/backups/alim/db-$(date +%F-%H%M).sql.gz

tar -czf /var/backups/alim/storage-$(date +%F-%H%M).tar.gz \
  -C /var/www/alim storage/app
```

Uji restore minimal di database staging:

```bash
gunzip -c /var/backups/alim/db-*.sql.gz | mysql -u root -p DB_ALIM_STAGING
```

- Simpan salinan di object storage/offsite.
- Batasi akses direktori backup.
- Uji restore secara berkala, bukan hanya melihat file backup.

## 5. Log dan alert

Kumpulkan minimal:

- Nginx access/error log
- `storage/logs/laravel.log`
- audit log aplikasi
- auth SSH (`/var/log/auth.log`)
- WAF/CDN
- database slow query/error log

Kirim ke server log terpisah atau layanan log. Buat alert untuk:

- lonjakan login gagal;
- lonjakan 401/403/500;
- login admin dari IP baru;
- perubahan role/permission;
- backup gagal;
- disk/CPU/RAM abnormal;
- request ke `.env`, `.git`, atau path sensitif;
- outbound traffic tidak normal.

Jangan hanya menyimpan log di server yang sama; penyerang dapat menghapusnya.

## 6. WAF dan DDoS

Aktifkan WAF/CDN (Cloudflare/AWS WAF atau setara) dengan aturan:

- rate limit login dan endpoint OTP;
- block pola SQL injection/XSS umum;
- challenge bot;
- geo/IP allowlist untuk area Super Admin jika memungkinkan.

Tetap verifikasi bahwa IP asli diteruskan lewat `TRUSTED_PROXIES`; jika tidak,
rate limit dan audit log akan mencatat IP proxy.

## 7. Dependensi

```bash
composer audit
npm audit --omit=dev
```

Jangan gunakan `npm audit fix --force` atau upgrade major secara buta.
Perubahan major seperti `swiper`, `quill`, dan `moment` harus diuji UI-nya.

## 8. Respons insiden singkat

1. Jangan hapus log atau langsung format server.
2. Ambil snapshot dan simpan bukti.
3. Isolasi server, blokir IP, nonaktifkan akun yang dicurigai.
4. Cabut session/token dan rotasi secret.
5. Patch/rebuild bila sistem tidak lagi dipercaya.
6. Restore dari backup yang bersih dan pantau ketat.
7. Susun timeline dan lakukan postmortem.
8. Jika data pribadi terekspos, ikuti kewajiban pelaporan yang berlaku.
