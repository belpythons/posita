# P10 — API v1 & OpenAPI

**Fase:** F2 · **WP:** 2.1 · **Effort:** ± 1,5 pekan · **Prasyarat:** P01–P09

---

## PERAN
API engineer yang merancang kontrak yang akan dipakai aplikasi mobile
selama bertahun-tahun.

## KONTEKS
Semua logika bisnis sudah ada di Action & Service (P01–P09), tapi hanya bisa
diakses lewat Inertia. Aplikasi mobile mustahil dibangun tanpa lapisan API.

## TUJUAN
API JSON `/api/v1` yang lengkap, bertipe, terdokumentasi, dan memakai
**Action yang sama** dengan web — tanpa duplikasi logika bisnis.

## RUANG LINGKUP
1. Sanctum token auth + login PIN
2. Controller API tipis di `app/Http/Controllers/Api/V1/`
3. `JsonResource` untuk setiap entitas
4. Error envelope standar dengan kode error stabil
5. Middleware: `EnsureTenantContext`, `ResolveOutletContext`,
   `RequireIdempotencyKey`
6. Rate limiting per kelompok endpoint
7. OpenAPI 3.1 spec (generate atau manual)
8. Generate klien TypeScript dari spec
9. Postman/Bruno collection untuk testing manual

## DI LUAR LINGKUP
- ❌ Endpoint sync (itu P12)
- ❌ Webhook payment (itu P18)
- ❌ Aplikasi mobile (itu P11)
- ❌ GraphQL

---

## SPESIFIKASI

### Header standar

```
Authorization:    Bearer <sanctum-token>       wajib
X-Outlet-Id:      <id>                          wajib untuk endpoint operasional
Idempotency-Key:  <ulid>                        wajib untuk POST/PUT/PATCH mutasi
X-Client-Version: pos-mobile/1.4.2              wajib
X-Device-Id:      <uuid>                        wajib
Accept-Language:  id                            opsional, default id
```

### Bentuk respons — konsisten tanpa kecuali

```jsonc
// 200 tunggal
{ "data": {...}, "meta": {"server_time": "2026-09-06T10:22:31+07:00"} }

// 200 koleksi (cursor pagination)
{ "data": [...],
  "meta": {"cursor": "eyJ...", "has_more": true, "per_page": 100} }

// 4xx / 5xx — SELALU bentuk ini
{ "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Stok Susu UHT tidak mencukupi",
    "details": {"ingredient_id": 42, "required": 150, "available": 40},
    "trace_id": "01JBXR8K2M..."
}}

// 422 validasi
{ "error": {
    "code": "VALIDATION_FAILED",
    "message": "Data yang dikirim tidak valid",
    "details": {"quantity": ["Jumlah harus lebih dari 0"]},
    "trace_id": "..."
}}
```

### Katalog kode error

```php
enum ApiErrorCode: string
{
    case ValidationFailed      = 'VALIDATION_FAILED';        // 422
    case Unauthenticated       = 'UNAUTHENTICATED';          // 401
    case Forbidden             = 'FORBIDDEN';                // 403
    case NotFound              = 'NOT_FOUND';                // 404
    case Conflict              = 'CONFLICT';                 // 409
    case IdempotencyKeyMissing = 'IDEMPOTENCY_KEY_MISSING';  // 400
    case IdempotencyMismatch   = 'IDEMPOTENCY_MISMATCH';     // 409
    case OutletRequired        = 'OUTLET_REQUIRED';          // 400
    case OutletForbidden       = 'OUTLET_FORBIDDEN';         // 403
    case NoOpenShift           = 'NO_OPEN_SHIFT';            // 409
    case ShiftAlreadyOpen      = 'SHIFT_ALREADY_OPEN';       // 409
    case InsufficientStock     = 'INSUFFICIENT_STOCK';       // 409
    case DiscountExceedsLimit  = 'DISCOUNT_EXCEEDS_LIMIT';   // 403
    case OrderNotVoidable      = 'ORDER_NOT_VOIDABLE';       // 409
    case PaymentFailed         = 'PAYMENT_FAILED';           // 402
    case RateLimited           = 'RATE_LIMITED';             // 429
    case ClientTooOld          = 'CLIENT_TOO_OLD';           // 426
    case ServerError           = 'SERVER_ERROR';             // 500
}
```

**Kode ini adalah kontrak.** Aplikasi mobile bercabang berdasarkan kode
(retry, tampilkan dialog, fallback offline). Jangan ubah tanpa versi baru.

### Endpoint

Ikuti daftar di `docs/30-arsitektur-target.md` §3.3. Tambahan:

```
GET  /api/v1/health                    tanpa auth, untuk cek konektivitas cepat
GET  /api/v1/config                    setelan efektif outlet + feature flags
POST /api/v1/devices/register          daftarkan device untuk login PIN
```

### Middleware `RequireIdempotencyKey`

```php
// Untuk POST/PUT/PATCH pada endpoint mutasi:
// - Header Idempotency-Key wajib, format ULID
// - Simpan (key, endpoint, request_hash, response, status) di cache 24 jam
// - Request ulang dengan key sama & hash sama → kembalikan response tersimpan
// - Request ulang dengan key sama & hash BERBEDA → 409 IDEMPOTENCY_MISMATCH
```

### Rate limiting

```php
RateLimiter::for('api-auth',    fn($r) => Limit::perMinute(5)->by($r->ip()));
RateLimiter::for('api-pin',     fn($r) => Limit::perMinute(5)->by($r->header('X-Device-Id')));
RateLimiter::for('api-default', fn($r) => Limit::perMinute(120)->by($r->user()->id));
RateLimiter::for('api-write',   fn($r) => Limit::perMinute(60)->by($r->user()->id));
RateLimiter::for('api-export',  fn($r) => Limit::perHour(5)->by($r->user()->id));
RateLimiter::for('api-sync',    fn($r) => Limit::perMinute(30)->by($r->header('X-Device-Id')));
```

### Version gate

```php
// Middleware CheckClientVersion
// Jika X-Client-Version < minimum_supported_version (dari config):
//   426 Upgrade Required + link Play Store
// Jika < recommended_version:
//   header X-Upgrade-Available: true  (aplikasi tampilkan banner, tidak memblokir)
```

### Controller tipis — pola wajib

```php
final class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrder $action): JsonResource
    {
        $this->authorize('create', Order::class);

        $order = $action->handle(
            CreateOrderData::fromRequest($request),
            $request->user(),
        );

        return OrderResource::make($order);
    }
}
```

Tidak ada `if`, tidak ada perhitungan, tidak ada query di controller.

### OpenAPI

Pakai `dedoc/scramble` (auto-generate dari kode) atau tulis manual di
`openapi/posita-v1.yaml`. Wajib mencakup:
- Semua endpoint dengan request/response schema
- Semua kode error
- Contoh request & response
- Deskripsi header standar
- Skema autentikasi

Publikasikan di `/docs/api` (Scalar atau Redoc).

Generate klien TypeScript:
```bash
npx openapi-typescript openapi/posita-v1.yaml -o packages/api-client/src/schema.ts
```

---

## ACCEPTANCE CRITERIA

- [ ] Semua endpoint di daftar berfungsi dan terdokumentasi
- [ ] Controller API memanggil **Action yang sama** dengan controller web
      (verifikasi: tidak ada duplikasi logika)
- [ ] Setiap endpoint punya otorisasi (test tabel-driven membuktikan)
- [ ] Error selalu berbentuk envelope standar, termasuk 500
- [ ] `trace_id` di response error bisa ditemukan di log
- [ ] Idempotency: request identik 3× → 1 order, response sama
- [ ] Idempotency mismatch → 409 dengan pesan jelas
- [ ] Rate limit aktif dan mengembalikan 429 dengan `Retry-After`
- [ ] Klien versi lama mendapat 426
- [ ] OpenAPI spec valid (`npx @redocly/cli lint`)
- [ ] Klien TypeScript ter-generate dan bertipe
- [ ] Tenant isolation berlaku di semua endpoint API

---

## TESTING WAJIB

```php
// tests/Feature/Api/V1/ContractTest.php
it('returns the standard envelope on success')->with('all_endpoints');
it('returns the standard error envelope on failure')->with('all_endpoints');
it('requires authentication')->with('protected_endpoints');
it('enforces permissions')->with('permission_matrix');
it('isolates tenants')->with('tenant_scoped_endpoints');

// tests/Feature/Api/V1/IdempotencyTest.php
it('returns the cached response for a repeated key');
it('rejects a repeated key with a different payload');
it('requires the header on mutating endpoints');

// tests/Feature/Api/V1/RateLimitTest.php
it('limits login attempts');
it('returns Retry-After on 429');

// tests/Feature/Api/V1/VersionGateTest.php
it('rejects clients below the minimum version');
it('flags an upgrade for clients below the recommended version');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Api
npx @redocly/cli lint openapi/posita-v1.yaml
npx openapi-typescript openapi/posita-v1.yaml -o packages/api-client/src/schema.ts
```

---

## CATATAN

⚠️ **Kontrak API adalah janji.** Setelah aplikasi mobile dirilis ke Play
Store, kamu tidak bisa memaksa semua pengguna update. Endpoint v1 harus
didukung minimal 12 bulan.

⚠️ **Cursor pagination, bukan offset.** Offset pagination rusak saat data
berubah selama iterasi — dan sinkronisasi mobile akan mengiterasi ribuan
baris.

⚠️ **Jangan bocorkan detail internal di pesan error.** "SQLSTATE[23000]"
bukan pesan untuk pengguna. Map exception ke kode error dengan pesan
berbahasa Indonesia.

⚠️ **`/api/v1/config` akan sering dipanggil.** Cache agresif dengan
invalidasi saat setelan berubah, dan sertakan ETag.
