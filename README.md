# Laravel REST API

Proyek backend **Pure RESTful API** berbasis Laravel 13 yang dioptimasi untuk kecepatan, ukuran ringan (*lightweight*), dan format respons JSON yang konsisten.

---

## ✨ Fitur Utama

- **Pure API Mode:** Rute web dinonaktifkan (`web.php` dimatikan), tanpa *overhead* Blade ataupun frontend assets.
- **Standar Respons JSON Konsisten:** Semua error (404, 405, 500) otomatis di-render sebagai respons JSON yang bersih, tanpa halaman HTML atau debug trace bocor.
- **Autentikasi Siap Pakai:** Terintegrasi dengan **Laravel Sanctum** untuk autentikasi berbasis Bearer Token (cocok untuk Mobile App Flutter/Kotlin/Swift maupun SPA React/Vue/Next.js).
- **Vendor Ringan (~55 MB):** Paket pengembangan yang tidak krusial telah dieliminasi untuk menjaga footprint tetap minimal.
- **Database Portabel:** Menggunakan SQLite secara default, siap dijalankan tanpa setup database server eksternal.

---

## 📋 Persyaratan Sistem

- **PHP** >= 8.3
- **Composer**
- Ekstensi PHP: `sqlite3`, `curl`, `mbstring`, `openssl`

---

## 🚀 Memulai (Quick Start)

### 1. Kloning & Instal Dependensi
```bash
composer install
```

### 2. Salin Konfigurasi Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Jalankan Migrasi Database
```bash
php artisan migrate
```

### 4. Jalankan Server Lokal
Pilih mode server yang diinginkan:

- **Mode Super Cepat (Laravel Octane + Swoole + Auto-Reload Otomatis):**
  ```bash
  composer octane
  # Server aktif di http://127.0.0.1:8000
  # Otomatis me-reload worker saat file diedit (100% Pure PHP, tanpa node_modules!)
  ```
- **Mode Standar (Bawaan PHP):**
  ```bash
  php artisan serve
  ```
Server akan aktif di: `http://127.0.0.1:8000`

---

## 📡 Daftar Endpoint API

Base URL: `http://127.0.0.1:8000`

| Method | Endpoint | Deskripsi | Autentikasi |
| :--- | :--- | :--- | :--- |
| `GET` | `/up` | Health check endpoint aplikasi | Publik |
| `GET` | `/api/hello` | Tes endpoint API sederhana | Publik |
| `GET` | `/api/user` | Mendapatkan data profil pengguna yang login | `Bearer Token` (Sanctum) |

### Contoh Pemanggilan Endpoint:
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
