# P04 — Catalog: Produk, Varian & Modifier

**Fase:** F1 · **WP:** 1.1 · **Effort:** ± 2 pekan · **Prasyarat:** P01–P03

---

## PERAN
Backend + frontend engineer yang memahami bahwa katalog adalah jantung POS —
salah desain di sini akan merembet ke seluruh sistem.

## KONTEKS
Posita saat ini hanya punya `product_templates` (produk mitra) dan
`box_templates` (paket catering). Tidak ada katalog produk sendiri, tidak ada
varian, tidak ada modifier. Coffee shop mustahil beroperasi tanpa ini:
*"Es Kopi Susu, Large, Less Sugar, Extra Shot"* adalah transaksi normal.

## TUJUAN
Katalog produk lengkap yang mendukung produk sendiri **dan** produk mitra,
dengan varian, modifier, dan harga bertingkat.

## RUANG LINGKUP
1. `categories` (hierarkis)
2. `products` dengan `ownership` (`own` / `partner`)
3. `product_variants` (ukuran, jenis susu, panas/dingin)
4. `modifier_groups` + `modifiers` + pivot ke produk
5. `price_tiers` + `product_prices` (harga per channel/waktu/outlet)
6. `bundles` + `bundle_items`
7. `PricingCalculator` — satu tempat perhitungan harga
8. UI CRUD di back office + import/export CSV
9. Migrasi `product_templates` → `products` dengan `ownership='partner'`

## DI LUAR LINGKUP
- ❌ Transaksi penjualan (itu P08)
- ❌ Resep/BOM (itu P06)
- ❌ Stok (itu P05)
- ❌ Menu designer (itu P16)
- ❌ Sinkronisasi ke aggregator

---

## SPESIFIKASI

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M1 — Catalog** secara persis.
Tambahan yang wajib:

- Semua tabel: `tenant_id`, soft delete, `(tenant_id, updated_at)` index.
- `products.type`: `simple` | `variant` | `bundle` | `consignment` | `open`
- `products.ownership`: `own` | `partner` (+ `partner_id` nullable, wajib
  jika `ownership='partner'` — tegakkan dengan check constraint atau
  validasi model)
- `products.available_hours` JSON: `{"mon":[["07:00","11:00"]], ...}`
- `products.available_channels` JSON: `["pos","online","gofood"]`

### `PricingCalculator` — kontrak

```php
namespace App\Services\Pricing;

final class PricingCalculator
{
    /**
     * Hitung harga satu baris item, sebelum diskon order & pajak.
     */
    public function priceLine(
        Product $product,
        ?ProductVariant $variant,
        Collection $modifiers,      // Collection<Modifier>
        int $quantity,
        PricingContext $context,    // outlet, channel, waktu, customer group
    ): PricedLine;
}

final readonly class PricedLine
{
    public Money $unitBasePrice;      // harga dasar setelah tier & varian
    public Money $modifiersTotal;     // Σ penyesuaian modifier
    public Money $unitPrice;          // base + modifiers
    public Money $subtotal;           // unitPrice × quantity
    public Money $unitCost;           // HPP satuan (diisi P06; sementara 0)
    public array  $breakdown;         // untuk debugging & tampilan
}
```

**Urutan resolusi harga (dari prioritas tertinggi):**
```
1. product_prices untuk (produk, varian, outlet, tier) yang berlaku sekarang
2. product_prices untuk (produk, varian, tier) tanpa outlet
3. product_variants.price_absolute
4. products.base_price + product_variants.price_adjustment
5. products.base_price
Lalu: + Σ modifiers.price_adjustment × modifier_quantity
```

### Aturan validasi

- Produk `type='variant'` **wajib** punya ≥1 varian, salah satunya
  `is_default`.
- Produk `ownership='partner'` **wajib** punya `partner_id`.
- `modifier_groups.min_select` ≤ `max_select`.
- Grup `is_required=true` harus punya `min_select` ≥ 1.
- Grup `selection_type='single'` memaksa `max_select = 1`.
- SKU unik per tenant (nullable, tapi jika diisi harus unik).
- Harga tidak boleh negatif; `price_adjustment` modifier boleh negatif
  (mis. "tanpa gula −Rp0" atau diskon ukuran kecil).

### Import / Export CSV

Format import produk:
```csv
sku,name,category,type,ownership,partner_code,base_price,description,is_active
KOPI-001,Es Kopi Susu,Kopi Susu,variant,own,,22000,Signature kami,1
```

Format import varian & modifier terpisah, direferensikan lewat SKU induk.

**Wajib:** validasi baris-per-baris dengan pesan error berbahasa Indonesia
yang menyebut nomor baris. Import bersifat transaksional — semua berhasil
atau semua batal.

### UI Back Office

```
Admin/Catalog/
├── Categories/Index.vue        drag-drop urutan, hierarki
├── Products/Index.vue          tabel, filter kategori/status/ownership, bulk action
├── Products/Form.vue           form dengan tab: Info · Varian · Modifier · Harga · Stok
├── Modifiers/Index.vue         kelola grup & item modifier
└── Import.vue                  upload CSV, pratinjau, validasi, konfirmasi
```

Prioritas UX: **input cepat.** Owner akan memasukkan 40 produk saat
onboarding. Sediakan quick-add (nama + harga + kategori, sisanya default).

### Migrasi data

`product_templates` → `products`:
```
product_templates.name             → products.name
product_templates.base_price       → products.cost_price_manual
product_templates.default_selling_price → products.base_price
product_templates.partner_id       → products.partner_id
                                   → products.ownership = 'partner'
                                   → products.type = 'consignment'
```
Simpan `product_templates` sebagai tabel deprecated selama satu rilis
(jangan langsung drop), lalu hapus di rilis berikutnya.

---

## ACCEPTANCE CRITERIA

- [ ] Bisa membuat produk "Es Kopi Susu" dengan varian Regular/Large dan
      grup modifier "Level Gula" (single, required) + "Topping" (multiple,
      max 3)
- [ ] `PricingCalculator` menghasilkan harga benar untuk kombinasi:
      Large (+Rp5.000) + Extra Shot (+Rp5.000) + Less Sugar (Rp0) = Rp32.000
- [ ] Harga tier "GoFood" (+20%) menggantikan harga normal saat
      `channel='gofood'`
- [ ] Produk mitra tercipta dengan `ownership='partner'` dan tidak bisa
      disimpan tanpa `partner_id`
- [ ] Import 100 produk dari CSV berhasil dalam < 5 detik dengan validasi
- [ ] Import dengan 1 baris error membatalkan seluruh import dan menampilkan
      nomor baris yang salah
- [ ] Data `product_templates` lama termigrasi tanpa kehilangan
- [ ] Produk dengan `available_hours` tidak muncul di luar jam tersebut

---

## TESTING WAJIB

```php
// tests/Unit/Pricing/PricingCalculatorTest.php
it('resolves price from the outlet-specific tier first');
it('falls back to base price plus variant adjustment');
it('sums modifier adjustments');
it('applies negative modifier adjustments');
it('respects modifier quantity');
it('uses channel-specific pricing for gofood');
it('returns the default variant price when none is specified');

// tests/Feature/Catalog/ProductTest.php
it('requires partner_id for partner-owned products');
it('requires at least one variant for variant-type products');
it('enforces unique sku per tenant');
it('allows the same sku in different tenants');
it('hides products outside their available hours');
it('isolates catalog between tenants');

// tests/Feature/Catalog/ImportTest.php
it('imports valid csv rows');
it('rolls back the whole import when one row fails');
it('reports the failing row number in Indonesian');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test --filter=Catalog
npm run build
```

---

## CATATAN

⚠️ **Jangan letakkan logika harga di Vue.** Perhitungan harga harus ada di
`PricingCalculator` (PHP) dan nanti di `packages/core-logic` (TS) dengan
test yang sama. Frontend hanya menampilkan.

⚠️ **Modifier bisa punya resep sendiri** (Extra Shot = +18 g kopi). Sediakan
tabel `modifier_recipe_items` sekarang walau baru dipakai di P06 — mengubah
skema nanti lebih mahal.

⚠️ **`available_hours` sering diabaikan tapi sangat dibutuhkan** — menu
sarapan, happy hour, dan menu yang habis di sore hari.

⚠️ Simpan `products.cost_price_manual` untuk produk yang **tidak** punya
resep (mis. pastry beli jadi). HPP-nya diinput manual, bukan dihitung.
