# Laravel REST API

Proyek backend **Pure RESTful API** berbasis Laravel 13 yang dioptimasi untuk performa ultra-tinggi (*high throughput*), ukuran ringan (*lightweight*), dan format respons JSON yang konsisten.

---

## ✨ Fitur Utama

- **Pure API Mode:** Rute web dinonaktifkan (`web.php` dimatikan), tanpa *overhead* Blade ataupun dependensi Node.js / `node_modules`.
- **Dukungan Dual-Engine Laravel Octane:**
  - **FrankenPHP:** Dikonfigurasi penuh untuk deployment Docker di server production (mendukung HTTP/3, Auto-SSL Caddy, dan Worker Mode via `public/frankenphp-worker.php`).
  - **Swoole:** Tersedia untuk local development berkecepatan tinggi (>5.000 req/s) dengan auto-reload watcher pure PHP.
- **Autentikasi JWT Siap Pakai:** Terintegrasi dengan **JWT (JSON Web Token)** modern via `php-open-source-saver/jwt-auth` (cocok untuk Microservices, Mobile App Flutter/Kotlin/Swift, maupun SPA React/Vue/Next.js).
- **Berbagi Auth Antar Microservice:** Panduan lengkap verifikasi JWT di **NestJS** & **Rust** tersedia di [MICROSERVICES_AUTH.md](MICROSERVICES_AUTH.md).
- **Proteksi Brute-Force & Rate Limiting:** Dilengkapi limiter 10 req/menit untuk `/api/auth/login` dan `/api/auth/register`, serta 60 req/menit untuk endpoint umum API.
- **Arsitektur Bersih (Clean Architecture):** Validasi terisolasi menggunakan dedicated Form Requests (`RegisterRequest`, `LoginRequest`) dan proteksi atribut sensitif via `UserResource`.
- **Health Check & Service Monitoring (`/api/health`):** Memonitor latensi database, cache, deteksi runtime Octane, dan pemakaian memori secara real-time.
- **Standar Respons JSON Konsisten:** Semua error (404, 405, 422, 429, 500) otomatis di-render sebagai respons JSON yang bersih.

---

## 📋 Persyaratan Sistem

- **PHP** >= 8.3
- **Composer**
- Ekstensi PHP: `sqlite3`, `curl`, `mbstring`, `openssl`, `pcntl`, `swoole` (atau runtime Docker FrankenPHP di server).

---

## 🚀 Memulai (Quick Start)

### 1. Kloning & Instal Dependensi
```bash
composer install
```

### 2. Salin Konfigurasi Environment & Generate Secret
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 3. Jalankan Migrasi Database
```bash
php artisan migrate
```

### 4. Jalankan Server Lokal
Pilih mode server yang diinginkan:

- **Mode Laravel Octane (Auto-Reload Pure PHP, Tanpa node_modules):**
  ```bash
  composer octane
  # Server aktif di http://127.0.0.1:8000
  # Menggunakan engine yang disetel pada OCTANE_SERVER di .env (frankenphp atau swoole)
  ```
- **Mode Standar (Bawaan PHP CLI):**
  ```bash
  php artisan serve
  ```
Server akan aktif di: `http://127.0.0.1:8000`

---

## 📡 Daftar Endpoint API

Base URL: `http://127.0.0.1:8000`

| Method | Endpoint | Deskripsi | Autentikasi |
| :--- | :--- | :--- | :--- |
| `GET` | `/up` | Health check endpoint bawaan aplikasi | Publik |
| `GET` | `/api/health` | Service monitoring (Database, Cache, Octane, Memori) | Publik |
| `GET` | `/api/hello` | Tes endpoint API sederhana | Publik |
| `POST` | `/api/auth/register` | Mendaftarkan akun user baru & return JWT token | Publik |
| `POST` | `/api/auth/login` | Login dengan email & password | Publik |
| `GET` | `/api/auth/me` | Mendapatkan data profil pengguna yang login | `Bearer <JWT>` |
| `GET` | `/api/user` | Alias untuk profil pengguna yang login | `Bearer <JWT>` |
| `POST` | `/api/auth/refresh` | Me-refresh token JWT yang akan kedaluwarsa | `Bearer <JWT>` |
| `POST` | `/api/auth/logout` | Menghanguskan / blacklist token JWT saat ini | `Bearer <JWT>` |

### Contoh Pemanggilan Endpoint Health Check:
```bash
curl -i http://127.0.0.1:8000/api/health
```
**Respons (200 OK):**
```json
{
  "success": true,
  "code": 200,
  "message": "System is healthy",
  "data": {
    "status": "healthy",
    "timestamp": "2026-09-10T16:10:36+00:00",
    "octane": {
      "running": true,
      "server": "swoole"
    },
    "services": {
      "database": {
        "status": "healthy",
        "connection": "sqlite",
        "latency_ms": 1.11
      },
      "cache": {
        "status": "healthy",
        "driver": "database",
        "latency_ms": 5.91
      }
    },
    "system": {
      "php_version": "8.4.17",
      "laravel_version": "13.30.1",
      "environment": "local",
      "memory_usage_mb": 24,
      "memory_peak_mb": 24
    }
  }
}
```

### Contoh Pemanggilan Endpoint Hello:
```bash
curl -i http://127.0.0.1:8000/api/hello
```
**Respons (200 OK):**
```json
{
  "success": true,
  "code": 200,
  "message": "Data retrieved successfully",
  "data": {
    "framework": "Laravel",
    "version": "13.30.1",
    "status": "active"
  }
}
```

---

## 🛡️ Standar Format Respons Error

Semua permintaan ke API dijamin menghasilkan respons JSON dengan atribut `success`, `code`, dan `message`:

#### 1. Rute Tidak Ditemukan (`404 Not Found`)
```http
HTTP/1.0 404 Not Found
Content-Type: application/json

{
  "success": false,
  "code": 404,
  "message": "The route api/contoh could not be found."
}
```

#### 2. Metode HTTP Tidak Sesuai (`405 Method Not Allowed`)
```http
HTTP/1.0 405 Method Not Allowed
Content-Type: application/json

{
  "success": false,
  "code": 405,
  "message": "The POST method is not supported for route api/hello. Supported methods: GET, HEAD."
}
```

#### 3. Belum Terautentikasi (`401 Unauthorized`)
```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "code": 401,
  "message": "Unauthenticated."
}
```

#### 4. Error Server (`500 Internal Server Error`)
```http
HTTP/1.0 500 Internal Server Error
Content-Type: application/json

{
  "success": false,
  "code": 500,
  "message": "Internal server error."
}
```
> **Catatan Keamanan Production (OWASP):** Pada server live (`APP_DEBUG=false`), detail query database, path sistem, dan stack trace disembunyikan otomatis. Pada local development (`APP_DEBUG=true`), pesan error asli ditampilkan untuk mempermudah debugging. Log lengkap tetap tersimpan di `storage/logs/laravel.log`.

---

## 🛠️ Perintah Pengembangan

```bash
# Menjalankan server super-cepat Octane dengan Auto-Reload otomatis (Pure PHP)
composer octane

# Menjalankan server Octane standar tanpa file watcher
composer run octane:start

# Me-reload worker Octane secara manual
composer run octane:reload

# Mengecek apakah server Octane sedang berjalan
composer run octane:status

# Menghentikan server Octane
composer run octane:stop

# Menjalankan server lokal standar PHP
php artisan serve

# Menjalankan pengujian (automated tests)
php artisan test

# Melihat daftar seluruh route API
php artisan route:list

# Menjalankan migrasi database
php artisan migrate

# Membersihkan seluruh cache (config, route)
php artisan optimize:clear

# Update dependensi framework
composer update
```

---

## 🚀 Deployment ke Production

Untuk performa maksimal setara/melampaui Lumen pada server production:

```bash
# 1. Pasang dependensi tanpa paket dev & optimasi autoloader
composer install --no-dev --optimize-autoloader

# 2. Aktifkan cache route, config, dan autoloader classmap
composer run optimize:prod

# 3. Jalankan Laravel Octane di background atau via systemd/supervisor
php artisan octane:start --server=swoole --port=8000 --workers=auto
```

---

## 📄 Lisensi

Proyek ini open-source di bawah lisensi [MIT](LICENSE).
