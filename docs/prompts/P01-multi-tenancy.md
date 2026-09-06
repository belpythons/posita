# P01 — Multi-Tenancy & Outlet

**Fase:** F0 · **WP:** 0.1 · **Effort:** ± 1,5 pekan · **Prasyarat:** P00 dibaca

---

## PERAN

Kamu adalah backend engineer senior Laravel dengan pengalaman membangun SaaS
multi-tenant produksi. Kamu paham bahwa kebocoran data antar tenant adalah
kegagalan katastrofik yang tidak boleh terjadi.

## KONTEKS

Posita saat ini **single-tenant**: satu instalasi = satu toko. Tabel yang ada
(`users`, `partners`, `product_templates`, `box_templates`, `shop_sessions`,
`daily_consignments`, `box_orders`, `box_order_items`, `daily_stats`) tidak
punya konsep tenant maupun outlet.

Ini adalah **blocker untuk semua pekerjaan berikutnya**. Tidak ada fitur baru
yang boleh dibangun sebelum fondasi ini ada.

## TUJUAN

Mengubah Posita menjadi aplikasi multi-tenant dengan isolasi **row-level**
(`tenant_id`), mendukung satu tenant memiliki banyak outlet, tanpa
kehilangan fungsionalitas yang sudah ada.

## RUANG LINGKUP

1. Tabel `tenants` dan `outlets`
2. Kolom `tenant_id` (dan `outlet_id` di mana relevan) pada semua tabel bisnis
3. Trait `BelongsToTenant` dengan global scope + auto-fill saat create
4. Middleware `EnsureTenantContext` dan `ResolveOutletContext`
5. `TenantContext` singleton (resolusi & pembersihan)
6. Queue tenant-aware (job membawa & memulihkan konteks tenant)
7. Migrasi data eksisting → tenant default
8. Test isolasi tenant yang menyeluruh

## DI LUAR LINGKUP — JANGAN DIKERJAKAN

- ❌ RBAC / permission (itu P02)
- ❌ Registrasi tenant / onboarding UI
- ❌ Billing & langganan
- ❌ Fitur bisnis baru apapun
- ❌ Perubahan UI selain penambahan pemilih outlet yang minimal

---

## SPESIFIKASI

### 1. Skema

```php
// tenants
$table->id();
$table->string('name');
$table->string('slug')->unique();
$table->string('business_type')->default('coffee_shop');
$table->string('plan')->default('free');
$table->foreignId('owner_user_id')->nullable();
$table->string('phone')->nullable();
$table->string('email')->nullable();
$table->string('timezone')->default('Asia/Jakarta');
$table->string('currency', 3)->default('IDR');
$table->string('locale', 5)->default('id');
$table->timestamp('trial_ends_at')->nullable();
$table->timestamp('subscription_ends_at')->nullable();
$table->boolean('is_active')->default(true);
$table->json('settings')->nullable();
$table->json('branding')->nullable();
$table->json('feature_flags')->nullable();
$table->timestamps();
$table->softDeletes();

// outlets
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->string('code');
$table->text('address')->nullable();
$table->string('phone')->nullable();
$table->decimal('latitude', 10, 7)->nullable();
$table->decimal('longitude', 10, 7)->nullable();
$table->unsignedInteger('geofence_radius_m')->default(100);
$table->string('timezone')->default('Asia/Jakarta');
$table->boolean('is_active')->default(true);
$table->json('settings')->nullable();
$table->json('operating_hours')->nullable();
$table->timestamps();
$table->softDeletes();
$table->unique(['tenant_id', 'code']);
$table->index(['tenant_id', 'is_active']);

// outlet_user (pivot)
$table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->boolean('is_primary')->default(false);
$table->primary(['outlet_id', 'user_id']);
```

### 2. Tabel yang mendapat `tenant_id`

Semua: `users` (nullable — super admin bernilai null), `partners`,
`product_templates`, `box_templates`, `shop_sessions`, `daily_consignments`,
`box_orders`, `box_order_items`, `daily_stats`.

Tambahkan `outlet_id` pada: `shop_sessions`, `daily_consignments`,
`box_orders`, `daily_stats`.

Setiap tabel wajib mendapat index:
```php
$table->index(['tenant_id', 'created_at']);
$table->index(['tenant_id', 'updated_at']);   // untuk cursor sync nanti
```

### 3. Trait `BelongsToTenant`

```php
namespace App\Models\Concerns;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = app(TenantContext::class)->id()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function (Model $model) {
            if (blank($model->tenant_id) && $tenantId = app(TenantContext::class)->id()) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Escape hatch — HANYA untuk job sistem & console command. */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
```

### 4. `TenantContext`

```php
final class TenantContext
{
    private ?int $tenantId = null;
    private ?int $outletId = null;

    public function set(?int $tenantId, ?int $outletId = null): void;
    public function id(): ?int;
    public function outletId(): ?int;
    public function forget(): void;

    /** Jalankan callback dalam konteks tenant lain lalu pulihkan. */
    public function runAs(int $tenantId, Closure $callback): mixed;
}
```

Didaftarkan sebagai **singleton** di `AppServiceProvider`.

### 5. Middleware

```php
EnsureTenantContext   // web + api: set konteks dari user terautentikasi;
                      // 403 jika user tidak punya tenant & bukan super admin
ResolveOutletContext  // api: baca header X-Outlet-Id, validasi user punya akses,
                      // set konteks; web: baca dari session
```

Daftarkan di `bootstrap/app.php` pada grup `web` dan `api`, setelah
middleware autentikasi.

### 6. Queue tenant-aware

Buat trait `TenantAwareJob`:

```php
trait TenantAwareJob
{
    public ?int $tenantId = null;
    public ?int $outletId = null;

    public function captureTenantContext(): void   // dipanggil di constructor
    public function restoreTenantContext(): void   // dipanggil di awal handle()
}
```

Alternatif yang lebih aman: daftarkan listener global pada
`Queue::before` / `Queue::after` untuk set & bersihkan konteks dari payload
job. Pilih salah satu, dokumentasikan pilihannya.

**Penting:** konteks tenant harus **dibersihkan** setelah job selesai —
worker adalah proses jangka panjang, kebocoran konteks antar job adalah bug
keamanan.

### 7. Migrasi data eksisting

Buat migrasi yang:
1. Membuat satu tenant default (`name: 'Toko Utama'`, `slug: 'default'`).
2. Membuat satu outlet default untuk tenant tersebut.
3. Mengisi `tenant_id` dan `outlet_id` semua baris yang ada dengan nilai
   tersebut.
4. Baru kemudian menambahkan constraint `NOT NULL` dan foreign key.

Urutan ini penting agar migrasi tidak gagal pada database berisi data.

---

## ACCEPTANCE CRITERIA

- [ ] `php artisan migrate:fresh --seed` berhasil di PostgreSQL dan MySQL
- [ ] Seeder membuat ≥2 tenant, masing-masing ≥2 outlet, dengan data terpisah
- [ ] User tenant A **tidak bisa** membaca data tenant B melalui:
      route model binding, listing, filter, pencarian, laporan, ekspor
- [ ] Membuat record baru otomatis terisi `tenant_id` dari konteks
- [ ] Job yang di-queue memulihkan konteks tenant dengan benar
- [ ] Konteks tenant dibersihkan setelah job selesai (uji dengan 2 job
      berbeda tenant berurutan di worker yang sama)
- [ ] Semua fitur yang sudah ada tetap berfungsi (open/close shop,
      consignment, box order, dashboard)
- [ ] `withoutTenantScope()` hanya dipakai di console command & job sistem
      (verifikasi dengan grep)

---

## TESTING WAJIB

```php
// tests/Feature/Tenancy/TenantIsolationTest.php
it('scopes queries to the current tenant');
it('prevents route model binding across tenants');           // 404, bukan 403
it('auto-fills tenant_id on create');
it('prevents mass-assigning a foreign tenant_id');
it('isolates aggregate queries and reports');
it('isolates data exports');

// tests/Feature/Tenancy/OutletContextTest.php
it('resolves outlet from X-Outlet-Id header');
it('rejects an outlet the user has no access to');
it('falls back to the user primary outlet when header is absent');

// tests/Feature/Tenancy/QueueTenantContextTest.php
it('restores tenant context inside a queued job');
it('clears tenant context after the job finishes');
it('does not leak context between jobs of different tenants');
```

Buat helper test:
```php
// tests/TestCase.php
protected function actingAsTenantUser(Tenant $tenant, array $attributes = []): User;
protected function withTenant(Tenant $tenant, Closure $callback): mixed;
```

---

## PERINTAH

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
php artisan test --filter=Tenancy
php artisan test
DB_CONNECTION=pgsql php artisan migrate:fresh --seed
DB_CONNECTION=mysql php artisan migrate:fresh --seed
```

---

## CATATAN & JEBAKAN

⚠️ **Route model binding adalah kebocoran paling umum.** `Route::get('/orders/{order}')`
akan menemukan order tenant lain jika global scope tidak aktif saat resolusi
binding. Pastikan middleware tenant berjalan **sebelum** substitusi binding,
atau override `resolveRouteBinding()`.

⚠️ **Response 404, bukan 403,** untuk resource tenant lain. Mengembalikan 403
membocorkan informasi bahwa resource tersebut ada.

⚠️ **Global scope tidak berlaku pada raw query.** Audit setiap `DB::table()`,
`DB::select()`, dan query builder mentah — tambahkan filter tenant manual.

⚠️ **Jangan pakai `stancl/tenancy`** untuk kasus ini. Package tersebut
dirancang untuk database-per-tenant; row-level scoping lebih sederhana
dikelola sendiri dan lebih mudah dites.

⚠️ **`users.tenant_id` nullable** karena super admin platform tidak dimiliki
tenant manapun. Pastikan global scope pada `User` menangani ini.
