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

## 4. Perintah Berguna (Cheatsheet)

| Perintah | Deskripsi |
| :--- | :--- |
| `php artisan serve` | Menjalankan local development server (`http://127.0.0.1:8000`) |
| `php artisan route:list` | Melihat seluruh daftar rute API yang terdaftar |
| `composer update` | Memperbarui library & framework Laravel ke versi terbaru |
| `php artisan optimize:clear` | Membersihkan cache konfigurasi, route, dan view |
