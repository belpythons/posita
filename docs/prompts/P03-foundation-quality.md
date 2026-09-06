# P03 — Money VO, Test Harness, CI & Cleanup

**Fase:** F0 · **WP:** 0.3–0.7 · **Effort:** ± 2 pekan · **Prasyarat:** P01, P02

---

## PERAN
Engineer yang bertanggung jawab atas kesehatan jangka panjang codebase.
Pekerjaan ini tidak menghasilkan fitur, tapi menentukan apakah 40 pekan
berikutnya berjalan lancar atau tersendat.

## TUJUAN
Menjadikan codebase siap menerima pengembangan produksi: aritmetika uang
yang benar, test harness lengkap, CI yang menjaga kualitas, dan
pembersihan sisa-sisa fase akademik.

## RUANG LINGKUP
1. Value Object `Money` + `RoundingMode` + `MoneyCast`
2. Migrasi kolom uang `decimal(12,2)` → `bigint`
3. Value Object pendukung: `Quantity`, `Percentage`, `PhoneNumber`
4. Factory untuk semua model + seeder demo realistis
5. Test harness: base test case, helper, dataset
6. GitHub Actions CI
7. Docker Compose + setup satu perintah
8. Cleanup: README, komentar NIM, lisensi, `.env.example`, file biner,
   komponen duplikat

## DI LUAR LINGKUP
- ❌ Fitur bisnis
- ❌ Refactor besar di luar yang disebutkan
- ❌ Migrasi TypeScript penuh (cukup siapkan konfigurasinya)

---

## SPESIFIKASI

### 1. `Money` Value Object

```php
namespace App\Domain\ValueObjects;

final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(
        public int $amount,          // rupiah utuh (IDR tidak punya sen praktis)
        public string $currency = 'IDR',
    ) {}

    public static function zero(string $currency = 'IDR'): self;
    public static function rupiah(int|float|string $amount): self;
    public static function fromMinor(int $amount, string $currency = 'IDR'): self;

    public function plus(self $other): self;
    public function minus(self $other): self;
    public function times(int|float $factor, RoundingMode $mode = RoundingMode::HalfUp): self;
    public function dividedBy(int|float $divisor, RoundingMode $mode = RoundingMode::HalfUp): self;
    public function percent(float $percent, RoundingMode $mode = RoundingMode::HalfUp): self;
    public function negate(): self;
    public function abs(): self;

    public function isZero(): bool;
    public function isPositive(): bool;
    public function isNegative(): bool;
    public function greaterThan(self $other): bool;
    public function lessThan(self $other): bool;
    public function equals(self $other): bool;

    /**
     * Bagi jumlah ini menurut rasio TANPA kehilangan/menambah rupiah.
     * Memakai largest-remainder method.
     * @param array<int|float> $ratios
     * @return array<self>
     */
    public function allocate(array $ratios): array;

    /** Bagi rata ke n bagian tanpa kehilangan rupiah. */
    public function split(int $parts): array;

    /** Bulatkan ke kelipatan tertentu (untuk pembulatan struk). */
    public function roundTo(int $nearest, RoundingMode $mode): self;

    public function format(bool $withSymbol = true): string;   // "Rp 1.234.567"
    public function jsonSerialize(): array;
    public function __toString(): string;

    private function assertSameCurrency(self $other): void;
}

enum RoundingMode: string
{
    case Up       = 'up';
    case Down     = 'down';
    case HalfUp   = 'half_up';
    case HalfDown = 'half_down';
    case HalfEven = 'half_even';   // banker's rounding

    public function apply(float $value): int;
}
```

**Test wajib untuk `allocate()`:**
```php
it('allocates without losing rupiah', function () {
    $result = Money::rupiah(100)->allocate([1, 1, 1]);
    expect(array_sum(array_map(fn($m) => $m->amount, $result)))->toBe(100);
    expect($result[0]->amount)->toBe(34);   // sisa masuk ke bagian pertama
});
```

### 2. `MoneyCast`

```php
final class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        return $value === null ? null : Money::fromMinor((int) $value);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) return [$key => null];
        if ($value instanceof Money) return [$key => $value->amount];
        return [$key => Money::rupiah($value)->amount];
    }
}
```

### 3. Migrasi kolom uang

Semua `decimal(12,2)` dan `decimal(15,2)` → `bigint`. Nilai dikonversi
dengan pembulatan ke rupiah utuh.

Kolom terdampak: `product_templates.base_price`,
`product_templates.default_selling_price`, `box_templates.price`,
`shop_sessions.opening_cash`, `closing_cash_system`, `closing_cash_actual`,
`daily_consignments.base_price`, `selling_price`, `subtotal_income`,
`box_orders.total_price`, `box_order_items.unit_price`, `subtotal`,
`daily_stats.total_revenue`.

Migrasi harus:
1. Tambah kolom baru `<name>_new bigint`
2. Backfill dengan `ROUND(<name>)`
3. Drop kolom lama, rename kolom baru
4. Reversible

### 4. Value Object pendukung

```php
Quantity      // nilai + unit, aritmetika dengan konversi satuan
Percentage    // 0-100, aplikasi ke Money, pembulatan eksplisit
PhoneNumber   // normalisasi 08xx/+62xx/62xx → 62xx, validasi
```

### 5. Factory & Seeder

- Factory untuk **setiap** model, dengan state yang berguna
  (`->forTenant($t)`, `->active()`, `->withItems(5)`).
- `DemoSeeder` yang menghasilkan tenant realistis: 1 coffee shop dengan
  ± 40 produk, 25 bahan baku, 30 resep, 3 mitra, 90 hari riwayat transaksi
  dengan pola realistis (ramai pagi & sore, sepi siang, weekend lebih tinggi).
- Data demo harus bisa dihapus dengan satu perintah:
  `php artisan posita:demo:clear`.

### 6. Test Harness

```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->for($this->tenant)->create();
        app(TenantContext::class)->set($this->tenant->id, $this->outlet->id);
    }

    protected function actingAsRole(string $role, array $attributes = []): User;
    protected function actingAsOwner(): User;
    protected function actingAsCashier(): User;
    protected function otherTenant(): Tenant;
    protected function assertTenantIsolated(string $modelClass): void;
    protected function assertMoneyEquals(Money $expected, Money $actual): void;
}
```

Dataset bersama di `tests/Datasets/`.

### 7. CI — `.github/workflows/ci.yml`

```yaml
name: CI
on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', coverage: xdebug }
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/pint --test
      - run: vendor/bin/phpstan analyse --error-format=github
      - run: composer audit

  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        db: [pgsql, mysql, sqlite]
    services:
      postgres: { image: postgres:16, env: { POSTGRES_PASSWORD: secret }, ports: ['5432:5432'] }
      mysql:    { image: mysql:8,     env: { MYSQL_ROOT_PASSWORD: secret }, ports: ['3306:3306'] }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction
      - run: php artisan test --coverage --min=70
        env: { DB_CONNECTION: ${{ matrix.db }} }

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20, cache: npm }
      - run: npm ci
      - run: npm run lint
      - run: npm run build
      - run: npm audit --audit-level=high
```

Tambahkan `phpstan.neon` level 6 dan `.github/dependabot.yml`.

### 8. Docker & Setup

`docker-compose.yml`: app (PHP 8.3-FPM), caddy, postgres:16, redis:7,
minio, mailpit.

`Makefile`:
```makefile
setup:    ## Setup lengkap dari repo bersih
	cp -n .env.example .env || true
	docker compose up -d
	docker compose exec -T app composer install
	docker compose exec -T app php artisan key:generate
	docker compose exec -T app php artisan migrate:fresh --seed
	npm install && npm run build
	@echo "✅ Siap di http://localhost:8000"

test:     ## Jalankan seluruh test
fresh:    ## Reset database + seed demo
lint:     ## Format & analisa
```

### 9. Cleanup

| Item | Aksi |
|---|---|
| `README.md` | Tulis ulang sebagai dokumentasi produk. Pindahkan pembagian tugas kuliah + NIM ke `CONTRIBUTORS.md` |
| Komentar header berisi nama & NIM di file PHP/Vue | Hapus. Kredit ada di `CONTRIBUTORS.md` dan git history |
| Lisensi | Tentukan satu (rekomendasi: proprietary untuk produk komersial, atau MIT jika open-core). Konsistenkan `LICENSE` + `composer.json` + README |
| File biner `retailer` (106 KB) | Investigasi isinya. Jika artefak build → hapus + tambahkan ke `.gitignore` |
| `.env.example` | Sesuaikan: `APP_NAME=Posita`, `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`, `DB_CONNECTION=pgsql`, tambah variabel Posita (WA driver, payment driver, storage) |
| `lang/id/` | Buat file terjemahan: `validation.php`, `auth.php`, `passwords.php`, `pagination.php`, `posita.php` |
| Komponen duplikat | Konsolidasi `Components/Badge.vue` + `Components/ui/Badge.vue` → satu; `DataTable.vue` ganda → satu |
| `enum` di DB | Ubah migrasi `shop_sessions.status`, `box_orders.status`, `box_templates.type` dari `enum` ke `string` + PHP enum |
| TypeScript | Tambahkan `tsconfig.json`, `vue-tsc` di CI, izinkan `.ts`/`.vue` bertipe secara bertahap |

---

## ACCEPTANCE CRITERIA

- [ ] Tidak ada `float` atau aritmetika langsung untuk uang di seluruh
      codebase (verifikasi dengan grep + review)
- [ ] `Money::allocate()` tidak pernah kehilangan atau menambah rupiah
      (property-based test dengan 1.000 kasus acak)
- [ ] Semua kolom uang bertipe `bigint`
- [ ] `make setup` menghasilkan aplikasi berjalan dari clone bersih
- [ ] CI hijau di PostgreSQL, MySQL, dan SQLite
- [ ] Coverage ≥ 70%
- [ ] PHPStan level 6 tanpa error
- [ ] Tidak ada NIM/nama mahasiswa di komentar kode
- [ ] `.env.example` mencerminkan konfigurasi Posita
- [ ] Seeder demo menghasilkan data 90 hari yang terlihat realistis di
      dashboard

---

## TESTING WAJIB

```php
// tests/Unit/Domain/MoneyTest.php
it('never loses rupiah when allocating')->with(/* 1000 kasus acak */);
it('rounds according to the specified mode');
it('rejects operations across currencies');
it('formats as Indonesian rupiah');
it('splits evenly without remainder loss');
it('rounds to the nearest 500 for receipt rounding');

// tests/Unit/Domain/QuantityTest.php
it('converts between units of the same type');
it('rejects conversion between incompatible unit types');

// tests/Unit/Domain/PhoneNumberTest.php
it('normalizes 08xx to 62xx');
it('normalizes +62xx to 62xx');
it('rejects invalid numbers');
```

---

## PERINTAH

```bash
make setup
make lint
make test
DB_CONNECTION=pgsql php artisan test
DB_CONNECTION=mysql php artisan test
```

---

## CATATAN

⚠️ **Migrasi uang harus dijalankan saat data masih dummy.** Jika sudah ada
data produksi, buat script verifikasi yang membandingkan total sebelum &
sesudah.

⚠️ **`allocate()` adalah fungsi paling kritis di seluruh codebase.** Dipakai
untuk split bill dan bagi hasil mitra. Satu rupiah yang hilang berulang kali
akan menimbulkan sengketa dengan mitra. Uji dengan property-based test.

⚠️ **Jangan berlebihan dengan seeder demo.** Data yang terlalu sempurna
menyembunyikan bug. Sertakan kasus tidak rapi: transaksi void, selisih kas,
stok negatif, mitra dengan sisa barang.
