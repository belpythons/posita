# P06 — Recipe / BOM & HPP

**Fase:** F1 · **WP:** 1.3 · **Effort:** ± 1,5 pekan · **Prasyarat:** P04, P05

---

## PERAN
Backend engineer yang membangun **fitur signature produk ini**. Modul ini
yang membuat Posita berbeda dari sekadar mesin kasir.

## KONTEKS
Kompetitor mengunci resep/BOM dan HPP di paket Rp249rb–999rb/bulan. Posita
membukanya di tier gratis. Ini keputusan strategis, bukan teknis — dan modul
ini harus benar-benar bagus karena akan jadi alasan orang memilih Posita.

## TUJUAN
Sistem resep yang menghitung HPP secara hidup, memperbarui diri saat harga
bahan berubah, dan memotong stok otomatis saat penjualan.

## RUANG LINGKUP
1. `recipes` + `recipe_items` (dengan sub-resep rekursif)
2. `modifier_recipe_items` — modifier juga memotong stok
3. `recipe_cost_history` — untuk grafik tren
4. `RecipeCostCalculator` — perhitungan HPP
5. `DeductStockForOrder` action — potong stok saat order selesai
6. Rekalkulasi otomatis saat harga bahan berubah (event-driven)
7. Peringatan food cost melewati target
8. UI: editor resep dengan kalkulasi HPP langsung

## DI LUAR LINGKUP
- ❌ Laporan variance (itu P17) — tapi siapkan data yang dibutuhkannya
- ❌ Perencanaan produksi batch besar (P09 preorder)
- ❌ Optimasi resep otomatis / saran AI

---

## SPESIFIKASI

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M3 — Recipe/BOM**.

Tambahan wajib:
- `recipes.version` — resep berubah seiring waktu; order lama harus tetap
  merujuk versi yang berlaku saat itu.
- `recipes.effective_from` — kapan versi ini mulai berlaku.
- `recipe_items.sub_recipe_id` — untuk resep bertingkat.

### Resep bertingkat (sub-recipe)

Contoh nyata:
```
Simple Syrup (yield 1000 ml)
├── Gula pasir      500 g
└── Air             600 ml

Es Kopi Susu (yield 1 gelas)
├── Biji kopi        18 g
├── Susu UHT        150 ml
├── Simple Syrup     25 ml   ← sub-resep
├── Es batu         100 g
├── Cup 16oz          1 pcs
└── Sedotan           1 pcs
```

HPP Simple Syrup per ml dihitung dulu, lalu dipakai di HPP Es Kopi Susu.

**Wajib:** deteksi circular reference. Resep A tidak boleh memakai resep B
yang memakai resep A. Kedalaman maksimal 5 tingkat.

### `RecipeCostCalculator`

```php
namespace App\Services\Recipe;

final class RecipeCostCalculator
{
    /**
     * Hitung HPP satu resep.
     * @param int $depth  pelindung rekursi, maks 5
     * @throws CircularRecipeException
     */
    public function calculate(Recipe $recipe, int $outletId, int $depth = 0): RecipeCost;

    /** Hitung ulang & simpan HPP semua resep yang memakai bahan ini. */
    public function recalculateForIngredient(int $ingredientId, int $outletId): Collection;

    /** Hitung HPP baris order termasuk modifier. */
    public function costForOrderLine(
        Product $product,
        ?ProductVariant $variant,
        Collection $modifiers,
        int $outletId,
    ): Money;
}

final readonly class RecipeCost
{
    public Money $ingredientCost;
    public Money $subRecipeCost;
    public Money $packagingCost;
    public Money $totalCost;
    public Money $costPerYield;
    public float $foodCostPercent;
    public Money $grossMargin;
    public array $breakdown;         // per bahan, untuk ditampilkan
    public array $warnings;          // bahan tanpa harga, food cost tinggi, dll
}
```

**Formula:**
```
biaya_bahan     = Σ ( qty × biaya_satuan × (1 + waste_factor/100) )
biaya_sub_resep = Σ ( qty × HPP_sub_resep_per_yield )
HPP_total       = biaya_bahan + biaya_sub_resep + biaya_kemasan
HPP_per_porsi   = HPP_total / yield_quantity
food_cost_%     = HPP_per_porsi / harga_jual × 100
margin_kotor    = harga_jual - HPP_per_porsi
```

`waste_factor` diterapkan per item (jika ada override) atau per resep
(default 3%).

### Pemotongan stok saat penjualan

```php
namespace App\Actions\Inventory;

final class DeductStockForOrder
{
    /**
     * Dipanggil oleh listener OrderCompleted.
     * Untuk setiap order_item:
     *   - jika produk punya resep → potong bahan sesuai resep × qty
     *   - jika produk track_stock  → potong stok produk jadi
     *   - untuk setiap modifier dengan resep → potong bahannya juga
     *   - jika produk ownership='partner' → potong batch konsinyasi (P13)
     * Semua dalam SATU transaksi via StockLedger::recordBatch().
     */
    public function handle(Order $order): void;
}
```

**Penting:**
- Pemotongan terjadi saat order `completed`, bukan saat item masuk keranjang.
- Jika stok tidak cukup dan `allow_negative=false`, order **tetap selesai**
  tapi catat `stock_shortage` dan kirim alert. Menolak transaksi yang sudah
  dibayar pelanggan adalah kegagalan produk.
- Void/refund dengan `restock=true` membuat movement kebalikan.

### Rekalkulasi otomatis

```php
// Event: IngredientCostChanged (dipicu oleh goods receipt di P07)
// Listener: RecalculateAffectedRecipes (queued)
//   → cari semua resep yang memakai bahan ini (langsung & via sub-resep)
//   → hitung ulang HPP
//   → simpan ke recipes.computed_cost + recipe_cost_history
//   → jika food_cost_percent > target outlet → dispatch FoodCostExceeded event
```

Target food cost disimpan di setelan outlet (default 32%).

### UI Editor Resep

```
Admin/Recipes/Form.vue

┌─────────────────────────────────────────────────────────┐
│ Resep: Es Kopi Susu (Regular)          Hasil: 1 gelas   │
├─────────────────────────────────────────────────────────┤
│ Bahan                Qty    Satuan   Biaya    % HPP     │
│ ─────────────────────────────────────────────────────── │
│ Biji Arabika          18    g        3.240    40,2% ▓▓▓ │
│ Susu UHT             150    ml       2.400    29,8% ▓▓  │
│ Simple Syrup ↳        25    ml         750     9,3% ▓   │
│ Es Batu              100    g         100     1,2%      │
│ Cup 16oz + Tutup       1    pcs     1.100    13,7% ▓    │
│ Sedotan                1    pcs       150     1,9%      │
│ ─────────────────────────────────────────────────────── │
│ Subtotal                            7.740               │
│ Waste factor 4%                       310               │
│ ═══════════════════════════════════════════════════════ │
│ HPP per gelas                       8.050               │
│                                                          │
│ Harga jual        Rp 22.000                             │
│ Food cost         36,6%  ⚠️  di atas target 32%          │
│ Laba kotor        Rp 13.950                             │
│                                                          │
│ 💡 Agar food cost 32%, harga jual minimal Rp 25.200     │
│    atau tekan HPP ke Rp 7.040                           │
└─────────────────────────────────────────────────────────┘
```

Kalkulasi harus **langsung** (tanpa reload) saat qty diubah.

---

## ACCEPTANCE CRITERIA

- [ ] Membuat resep Es Kopi Susu dengan 6 bahan menghasilkan HPP yang benar
      sesuai contoh di atas
- [ ] Sub-resep (Simple Syrup) dihitung dengan benar dan biayanya masuk ke
      resep induk
- [ ] Circular reference terdeteksi dan ditolak dengan pesan jelas
- [ ] Kedalaman > 5 tingkat ditolak
- [ ] Menjual 1 Es Kopi Susu memotong 18 g biji, 150 ml susu, 25 ml syrup,
      1 cup, 1 sedotan
- [ ] Modifier "Extra Shot" memotong tambahan 18 g biji
- [ ] Menaikkan harga beli susu memicu rekalkulasi HPP semua resep yang
      memakainya, dalam < 5 detik untuk 100 resep
- [ ] Food cost melewati target memicu event (alert diuji di P14)
- [ ] Void dengan restock membuat movement kebalikan yang benar
- [ ] Order lama tetap menampilkan HPP yang berlaku saat transaksi terjadi
      (snapshot), bukan HPP terbaru

---

## TESTING WAJIB

```php
// tests/Unit/Recipe/RecipeCostCalculatorTest.php
it('calculates cost for a flat recipe');
it('calculates cost including a sub-recipe');
it('applies per-item waste factor override');
it('detects circular recipe references');
it('rejects recipes deeper than 5 levels');
it('warns when an ingredient has no cost');
it('computes food cost percent against selling price');

// tests/Feature/Recipe/StockDeductionTest.php
it('deducts ingredients on order completion');
it('deducts modifier ingredients too');
it('deducts finished-product stock when the product has no recipe');
it('completes the order even when stock is insufficient');
it('records a shortage event when stock goes negative');
it('reverses movements on void with restock');

// tests/Feature/Recipe/RecalculationTest.php
it('recalculates all affected recipes when ingredient cost changes');
it('recalculates recipes that use the ingredient via a sub-recipe');
it('stores cost history for trend charts');
it('dispatches FoodCostExceeded when above target');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Recipe
```

---

## CATATAN

⚠️ **Rekalkulasi harus di-queue.** Mengubah harga 1 bahan bisa memicu
rekalkulasi ratusan resep. Jangan lakukan di request cycle.

⚠️ **Kemasan adalah bahan.** Cup, tutup, sedotan, kantong — semuanya harus
masuk resep. Ini komponen HPP yang paling sering dilupakan pemilik kedai,
dan bisa mencapai 15% dari HPP.

⚠️ **Snapshot HPP di `order_items.unit_cost_snapshot`** wajib. Tanpa ini,
laporan laba historis akan berubah setiap kali harga bahan berubah — dan
angka masa lalu yang berubah akan menghancurkan kepercayaan.

⚠️ **Tampilkan HPP ke kasir hanya jika diizinkan.** Setelan
`recipe.show_cost_to_cashier` default `false` — banyak owner tidak ingin
karyawan tahu margin.
