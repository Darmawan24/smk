# Expose SIAKAD ke Internet via Reverse Proxy

Aplikasi berjalan di **server private** (10.0.0.222:8080). Agar bisa diakses dari internet, gunakan **server proxy** (151.245.85.86) sebagai reverse proxy.

## Arsitektur

```
Internet → 151.245.85.86 (proxy) → 10.0.0.222:8080 (SIAKAD)
```

## Setup di Server Proxy (151.245.85.86)

### 1. Pasang Nginx (jika belum)

```bash
sudo apt update
sudo apt install -y nginx
```

### 2. Buat konfigurasi virtual host

```bash
sudo nano /etc/nginx/sites-available/siakad
```

Isi (ganti `your-domain.com` dengan domain Anda, atau pakai IP 151.245.85.86):

```nginx
server {
    listen 80;
    server_name your-domain.com;   # atau 151.245.85.86 jika tanpa domain

    location / {
        proxy_pass http://10.0.0.222:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

Simpan (Ctrl+O, Enter, Ctrl+X).

### 3. Aktifkan site dan reload Nginx

```bash
sudo ln -s /etc/nginx/sites-available/siakad /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Pastikan firewall mengizinkan port 80

```bash
sudo ufw allow 80
sudo ufw status
```

## Setup di Server Private (10.0.0.222)

### 1. Set APP_URL sesuai akses publik

Di `~/smk/.env` di server 10.0.0.222, set URL yang dipakai user (domain atau IP proxy):

```env
APP_URL=http://151.245.85.86
```

Atau jika pakai domain:

```env
APP_URL=https://your-domain.com
```

Lalu restart app:

```bash
cd ~/smk
sudo docker compose restart app
```

### 2. (Opsional) Trust proxy di Laravel

Agar Laravel mengenali IP asli dan scheme (HTTP/HTTPS) dari proxy, di server 10.0.0.222 tambahkan middleware trust proxy. Di aplikasi Laravel 11, biasanya sudah di-handle oleh `TrustProxies`; pastikan di `app/Http/Middleware/TrustProxies.php` (jika ada) trusted proxies mencakup IP proxy:

- `151.245.85.86` atau subnet yang dipakai (mis. `151.245.85.0/24`).

Jika tidak ada file tersebut, Laravel 11 mungkin memakai config; cek dokumentasi Laravel "Trusting Proxies".

## Akses

- Via IP: **http://151.245.85.86**
- Via domain: **http://your-domain.com**

## HTTPS (opsional, dengan domain)

Jika punya domain mengarah ke 151.245.85.86:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

Lalu di `.env` di 10.0.0.222 set `APP_URL=https://your-domain.com` dan restart app.
