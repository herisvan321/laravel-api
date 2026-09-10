# Catatan Dokumentasi & Konfigurasi Laravel API

Dokumentasi ini mencatat konfigurasi, penyesuaian error handling, dan optimasi performa/ukuran yang telah diterapkan pada project **Laravel API**.

---

## 1. Konfigurasi Routing & Prefix API

Konfigurasi rute diatur pada file [`bootstrap/app.php`](bootstrap/app.php).

### A. Menonaktifkan Rute Web (Pure API)
Rute web (`web: ...`) telah dinonaktifkan sehingga aplikasi sepenuhnya berjalan sebagai API:
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
```

### B. Mengubah Prefix API
Secara default, rute API memiliki prefix `/api` (misal: `/api/hello`). Jika ingin mengubah atau menghilangkan prefix:
```php
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'v1',      // Hasil: /v1/hello
        // apiPrefix: '',     // Hasil: /hello (tanpa prefix)
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
```

---

## 2. Format Respons Seragam & Standar API (`ApiResponse`)

Semua respons dari API mengikuti standar format seragam:
- **Sukses (2xx):**
  ```json
  {
    "success": true,
    "code": 200,
    "message": "Data retrieved successfully",
    "data": { ... }
  }
  ```
- **Error (4xx / 5xx):**
  ```json
  {
    "success": false,
    "code": 404,
    "message": "The route api/notfound could not be found."
  }
  ```
- **Validation Error (422):**
  ```json
  {
    "success": false,
    "code": 422,
    "message": "The given data was invalid.",
    "errors": {
      "email": ["The email field is required."]
    }
  }
  ```

### A. Helper `ApiResponse`
Tersedia di [`app/Http/Responses/ApiResponse.php`](app/Http/Responses/ApiResponse.php) untuk digunakan di Controller maupun Route:
```php
use App\Http\Responses\ApiResponse;

// Response Sukses
return ApiResponse::success($data, 'Data retrieved successfully', 200);

// Response Error
return ApiResponse::error('Something went wrong', 400, $errors);
```

### B. Middleware `ForceJsonResponse`
Dibuat di [`app/Http/Middleware/ForceJsonResponse.php`](app/Http/Middleware/ForceJsonResponse.php) dan didaftarkan di [`bootstrap/app.php`](bootstrap/app.php):
- Memaksa request header `Accept: application/json`.
- Mencegah redirect web saat auth gagal atau validasi gagal.
- Membungkus response teks biasa secara otomatis.

### C. Global Exception Handler
Dikonfigurasi di [`bootstrap/app.php`](bootstrap/app.php) menggunakan `ApiResponse::error()` sehingga seluruh error HTTP (401, 404, 405, 422, 500) menghasilkan struktur JSON seragam.

- **Keamanan untuk Production (OWASP Standard):**
  Khusus untuk error 500 (*Internal Server Error*), penanganan error memeriksa status `config('app.debug')`:
  ```php
  config('app.debug') ? $e->getMessage() : 'Internal server error.'
  ```
  - **Di Local / Development (`APP_DEBUG=true`):** Menampilkan pesan error asli PHP/database untuk memudahkan proses debugging.
  - **Di Production (`APP_DEBUG=false`):** Secara otomatis **menyembunyikan detail sensitif** (seperti struktur tabel database, query SQL, path direktori server, dan stack trace) dan hanya mengembalikan pesan aman `"Internal server error."`.
  - **Internal Logging:** Seluruh detail trace error asli tetap tersimpan secara aman di file log internal server (`storage/logs/laravel.log`).

---

## 3. Optimasi Ukuran Vendor

Ukuran awal project: **~88 MB**.

### Dependensi Dev yang Dihapus
Paket development berikut telah dihapus untuk menghemat kapasitas tanpa memengaruhi fungsi API ataupun proses `composer update`:
1. **`laravel/pint`** (~21 MB): Tool perapi format kode (linter CLI).
2. **`fakerphp/faker`** (~11 MB): Library pembuat fake data dummy untuk factory/seeder.

**Hasil Optimasi:**
- Ukuran total project turun dari **88 MB** menjadi **55 MB** (hemat **~33 MB**).

### Cara Mengembalikan (Jika Dibutuhkan Nanti)
Jika di kemudian hari memerlukan kembali Faker (untuk seeding database) atau Pint:
```bash
composer require --dev laravel/pint fakerphp/faker
```

### Optimasi Lanjutan Saat Deploy (Production)
Untuk deployment ke server production, pasang dependensi tanpa paket development:
```bash
composer install --no-dev --optimize-autoloader
```
*(Ukuran vendor akan semakin ramping menjadi ~35-40 MB).*

---

## 4. Optimasi Performa Maksimal: Menyamai & Melampaui Lumen (Laravel Octane + Swoole)

### Mengapa Lumen Dulu Cepat dan Mengapa Octane Sekarang Jauh Lebih Unggul?
- **Lumen (Traditional PHP-FPM):** Memangkas service provider & middleware agar *bootstrapping* framework lebih cepat (~10–20 ms per request). Namun pada setiap request, PHP tetap harus membaca file, mem-parsing script, dan membangun container dari awal.
- **Laravel Octane (Swoole Worker Mode):** Alih-alih melakukan cold boot berulang kali, Octane memuat framework Laravel **sekali saja ke dalam memori RAM**. Setiap request yang masuk langsung dieksekusi di RAM dalam hitungan **sub-milidetik hingga 3 ms**.

### Hasil Benchmark Nyata (Lokal macOS, ApacheBench 500 req, concurrency 20):
| Server Engine | Throughput (Req / Detik) | Latensi Rata-rata | Peningkatan |
| :--- | :--- | :--- | :--- |
| **Standard PHP Server** | ~340 req/detik | ~58.7 ms | Baseline |
| **Laravel + `artisan optimize`** | ~376 req/detik | ~26.5 ms | +10% |
| **Laravel Octane (Swoole)** | **~5.165 req/detik** | **~3.8 ms** | **15x Lebih Cepat!** |

> Hasil di atas membuktikan bahwa Laravel Octane tidak hanya menyamai performa Lumen (~500–800 req/detik di PHP-FPM), melainkan **melampauinya hingga 6x–10x lipat**.

---

### Cara Menjalankan Laravel Octane (Swoole)

1. **Mode Development (Pure PHP + Auto-Reload Otomatis):**
   ```bash
   composer octane
   # atau: php artisan octane:dev
   ```
   > **Fitur Unggulan:** Berjalan 100% di PHP tanpa `node_modules` ataupun Node.js. Setiap kali file di `app/`, `routes/`, `config/`, `database/`, `bootstrap/`, `.env`, atau `composer.json` diubah/disimpan, server akan **otomatis me-reload worker seketika (<1 detik)**!

2. **Mode Background / Standar Tanpa Watcher:**
   ```bash
   composer run octane:start
   # atau: php artisan octane:start --server=swoole
   ```

3. **Mengecek Status Server Octane:**
   ```bash
   composer run octane:status
   ```

4. **Production Mode (Performa Maksimal Tanpa Watcher):**
   Di server live/production, file kode tidak diedit langsung sehingga file watcher dimatikan untuk performa maksimal 100%:
   ```bash
   # Kompilasi cache route, config, dan autoloader classmap
   composer run optimize:prod

   # Jalankan Octane multi-worker sesuai core CPU server
   php artisan octane:start --server=swoole --port=8000 --workers=auto
   ```
   > **Rekomendasi Live Server:** Gunakan process manager seperti **Supervisor** atau **Systemd** agar server Octane otomatis berjalan di background dan otomatis restart jika server me-reboot.

5. **Menghentikan Server Octane:**
   - Jika dijalankan via `composer octane`: Cukup tekan `Ctrl + C`.
   - Atau melalui perintah: `composer run octane:stop`

---

## 5. Perintah Berguna (Cheatsheet)

| Perintah | Deskripsi |
| :--- | :--- |
| `composer octane` | Menjalankan server Octane dengan **Auto-Reload otomatis** saat file diedit (Pure PHP) |
| `composer run octane:start` | Menjalankan server Octane standar tanpa file watcher |
| `composer run octane:reload` | Merefresh worker Octane secara manual (jika server sedang aktif) |
| `composer run octane:status` | Mengecek apakah server Octane sedang berjalan atau mati |
| `composer run octane:stop` | Menghentikan server Octane yang sedang berjalan |
| `composer run optimize:prod` | Mengoptimasi classmap composer dan meng-cache konfigurasi/route |
| `php artisan serve` | Menjalankan local development server bawaan PHP (`http://127.0.0.1:8000`) |
| `php artisan test` | Menjalankan automated test suite |
| `php artisan route:list` | Melihat seluruh daftar rute API yang terdaftar |
| `php artisan optimize:clear` | Membersihkan cache konfigurasi, route, dan view |
| `composer update` | Memperbarui library & framework Laravel ke versi terbaru |
