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

## 2. Format Error & 404 Selalu JSON (Standar API)

Agar tidak menampilkan halaman HTML atau debug trace yang panjang ketika terjadi error (misalnya route tidak ditemukan, method salah, atau server error), exception handling dikonfigurasi di [`bootstrap/app.php`](bootstrap/app.php):

```php
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

->withExceptions(function (Exceptions $exceptions): void {
    // 1. Seluruh respons error dipaksa dalam format JSON
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request, Throwable $e) => true,
    );

    // 2. Format bersih untuk 404 (Not Found)
    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        return response()->json([
            'message' => $e->getMessage() ?: 'The route could not be found.',
        ], 404);
    });

    // 3. Format untuk HTTP Exception lainnya (405 Method Not Allowed, 403 Forbidden, dsb.)
    $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
        return response()->json([
            'message' => $e->getMessage() ?: 'HTTP error occurred.',
        ], $e->getStatusCode());
    });

    // 4. Format untuk Internal Server Error (500)
    $exceptions->render(function (Throwable $e, Request $request) {
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return null; // Biarkan format standar bawaan Laravel untuk validasi dan autentikasi
        }

        return response()->json([
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
        ], 500);
    });
})
```

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
