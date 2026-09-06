# P05 — Inventory & Stock Ledger

**Fase:** F1 · **WP:** 1.2 · **Effort:** ± 2,5 pekan · **Prasyarat:** P04

---

## PERAN
Backend engineer yang paham akuntansi persediaan. Ledger stok adalah sistem
buku besar — sekali salah desain, angka tidak akan pernah bisa dipercaya
lagi.

## KONTEKS
Posita **tidak punya modul inventory sama sekali**. Ini gap terbesar
terhadap target produk, dan fondasi untuk Recipe/HPP (P06) dan laporan
kebocoran (P17) — dua fitur pembeda utama.

## TUJUAN
Sistem persediaan berbasis ledger append-only yang bisa dipercaya, dengan
dukungan satuan majemuk, batch, expiry, waste, opname, dan transfer.

## RUANG LINGKUP
1. `units` + konversi satuan
2. `ingredients` (bahan baku)
3. `stock_movements` — **ledger, sumber kebenaran**
4. `stock_levels` — cache saldo, bisa dibangun ulang
5. `stock_batches` — FIFO & expiry
6. `waste_logs` — pencatatan waste bersebab
7. `stock_takes` + items — opname
8. `stock_transfers` + items — antar outlet
9. `StockLedger` service — satu-satunya jalan mengubah stok
10. Command rekonstruksi: `posita:stock:rebuild`
11. UI back office + mobile-friendly untuk waste & restock cepat

## DI LUAR LINGKUP
- ❌ Resep (itu P06) — tapi siapkan hook-nya
- ❌ Purchase order formal (itu P07)
- ❌ Laporan variance (itu P17)
- ❌ Prediksi permintaan / auto-reorder

---

## SPESIFIKASI

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M2 — Inventory**.

### `StockLedger` — satu-satunya pintu masuk

```php
namespace App\Services\Inventory;

final class StockLedger
{
    /**
     * Catat pergerakan stok. SATU-SATUNYA cara mengubah stok.
     * Menulis stock_movements, memperbarui stock_levels & stock_batches
     * dalam satu transaksi database.
     *
     * @throws InsufficientStockException  jika allow_negative = false
     */
    public function record(StockMovementData $data): StockMovement;

    /** Catat beberapa pergerakan atomik (mis. potong resep). */
    public function recordBatch(array $movements): Collection;

    /** Saldo saat ini (dari cache). */
    public function balance(int $outletId, int $ingredientId): Quantity;

    /** Saldo pada waktu tertentu (dari ledger — lambat, untuk audit). */
    public function balanceAt(int $outletId, int $ingredientId, CarbonInterface $at): Quantity;

    /** Bangun ulang stock_levels dari ledger. */
    public function rebuild(int $outletId, ?int $ingredientId = null): void;

    /** Biaya rata-rata tertimbang saat ini. */
    public function averageCost(int $outletId, int $ingredientId): Money;

    /** Alokasi FIFO untuk pengeluaran stok. */
    public function allocateFifo(int $outletId, int $ingredientId, Quantity $qty): array;
}
```

### Aturan yang TIDAK BOLEH dilanggar

1. **Tidak ada kode lain yang boleh menulis ke `stock_levels` atau
   `stock_batches`.** Hanya `StockLedger`. Tegakkan dengan review + test
   yang menghitung penulis tabel tersebut.
2. **`stock_movements` append-only.** Tidak ada `update()` atau `delete()`.
   Koreksi = movement `adjustment` baru.
3. **Setiap movement menyimpan `balance_after`** — snapshot saldo setelah
   pergerakan, agar audit bisa dibaca tanpa menjumlah ulang.
4. **Semua kuantitas disimpan dalam satuan dasar** (gram, ml, pcs).
   Konversi terjadi di lapisan presentasi.
5. **Stok negatif diizinkan secara default** (`allow_negative = true` di
   setelan outlet) tapi memicu peringatan. Realitas lapangan: barista sering
   mencatat penerimaan setelah memakai barangnya. Memblokir → mereka berhenti
   pakai aplikasi.
6. **Operasi stok harus atomik dan tahan konkurensi.** Gunakan
   `SELECT ... FOR UPDATE` pada `stock_levels` di dalam transaksi, atau
   atomic decrement dengan verifikasi.

### Satuan & konversi

```php
// units
name: 'Kilogram', symbol: 'kg', type: 'weight',
base_unit_id: <id Gram>, conversion_factor: 1000

// ingredients
unit_id:              <id Gram>      // satuan penyimpanan & ledger
purchase_unit_id:     <id Kilogram>  // satuan pembelian
purchase_conversion:  1000           // 1 kg = 1000 g
```

`Quantity` VO menangani konversi:
```php
Quantity::of(2, $kg)->toBase()        // → Quantity(2000, gram)
Quantity::of(1500, $gram)->to($kg)    // → Quantity(1.5, kg)
Quantity::of(1, $kg)->to($liter)      // → throws IncompatibleUnitException
```

### Tipe movement

| Tipe | Tanda | Sumber |
|---|---|---|
| `purchase` | + | Goods receipt (P07) |
| `sale` | − | Order completed (P08) via resep (P06) |
| `production` | +/− | Produksi batch (simple syrup, cold brew) |
| `waste` | − | Waste log |
| `adjustment` | +/− | Koreksi manual, butuh alasan |
| `transfer_in` / `transfer_out` | +/− | Antar outlet |
| `opname` | +/− | Hasil stock take |
| `return` | − | Retur ke supplier |
| `consignment_in` / `consignment_out` / `consignment_return` | +/− | Modul mitra (P13) |

### Waste log — alasan wajib

```php
enum WasteReason: string
{
    case Calibration = 'calibration';   // dialing-in espresso
    case Spoilage    = 'spoilage';      // basi
    case Expired     = 'expired';
    case Spillage    = 'spillage';      // tumpah
    case Remake      = 'remake';        // salah bikin / komplain
    case StaffMeal   = 'staff_meal';
    case Sample      = 'sample';        // tester / promosi
    case Theft       = 'theft';
    case Other       = 'other';         // wajib isi catatan
}
```

Pencatatan waste **harus cepat**: pilih bahan → qty → alasan → simpan.
Maksimal 4 ketukan. Jika sulit, barista tidak akan mencatatnya, dan seluruh
laporan variance jadi tidak berguna.

### Opname (stock take)

Alur:
```
1. Buat stock take (full / partial / spot) → status 'draft'
2. Sistem membekukan `system_quantity` per item saat mulai → 'counting'
3. Petugas menghitung fisik, input `counted_quantity`
   (penjualan TETAP berjalan selama proses ini)
4. Review: sistem menampilkan variance = counted - (system + movements selama hitung)
5. Approve → status 'completed'
   → membuat movement `opname` untuk setiap selisih
   → total nilai selisih dicatat
```

**Penting:** jangan membekukan penjualan selama opname. Kedai tidak bisa
berhenti berjualan.

### Rekonstruksi

```bash
php artisan posita:stock:rebuild --outlet=1
php artisan posita:stock:rebuild --outlet=1 --ingredient=42
php artisan posita:stock:verify           # bandingkan cache vs ledger, laporkan selisih
```

`posita:stock:verify` harus dijalankan di scheduler harian dan mengirim
alert internal jika ada ketidakcocokan.

---

## ACCEPTANCE CRITERIA

- [ ] Mencatat pembelian 5 kg biji kopi → saldo 5.000 g, batch tercipta
- [ ] Mengeluarkan 18 g → saldo 4.982 g, `balance_after` tercatat benar
- [ ] `posita:stock:rebuild` menghasilkan `stock_levels` identik dengan
      sebelum rebuild
- [ ] FIFO mengalokasikan dari batch tertua terlebih dahulu
- [ ] Batch kedaluwarsa terdeteksi dan bisa difilter
- [ ] Waste tercatat dengan alasan; laporan waste per alasan tersedia
- [ ] Opname menghasilkan movement `opname` yang benar walau ada penjualan
      selama proses hitung
- [ ] Transfer antar outlet mengurangi asal dan menambah tujuan secara atomik
- [ ] **Uji konkurensi:** 50 pengeluaran paralel pada bahan yang sama
      menghasilkan saldo akhir yang benar, tanpa lost update
- [ ] Konversi satuan tidak kehilangan presisi setelah 1.000 operasi
- [ ] Stok negatif tercatat dengan peringatan, tidak error

---

## TESTING WAJIB

```php
// tests/Unit/Inventory/StockLedgerTest.php
it('records a movement and updates the cached level');
it('stores balance_after on every movement');
it('allocates FIFO from the oldest batch');
it('computes weighted average cost correctly');
it('rebuilds levels identical to the ledger');
it('allows negative stock with a warning when configured');
it('throws when negative stock is disallowed');

// tests/Feature/Inventory/ConcurrencyTest.php
it('handles 50 concurrent deductions without lost updates');

// tests/Unit/Inventory/QuantityTest.php
it('converts kg to g without precision loss');
it('rejects incompatible unit conversion');
it('survives 1000 round-trip conversions');

// tests/Feature/Inventory/StockTakeTest.php
it('computes variance accounting for movements during counting');
it('creates opname movements on approval');
it('does not block sales during counting');

// tests/Feature/Inventory/WasteTest.php
it('requires a reason');
it('requires notes when reason is other');
it('aggregates waste cost by reason');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Inventory
php artisan posita:stock:verify
```

---

## CATATAN

⚠️ **Ini adalah modul paling kritis untuk integritas data.** Luangkan waktu
ekstra pada test konkurensi. Bug di sini akan menghancurkan kepercayaan
pengguna pada seluruh aplikasi.

⚠️ **`stock_movements` akan menjadi tabel terbesar.** Rencanakan partisi per
bulan sejak awal (PostgreSQL declarative partitioning) walau belum
diaktifkan.

⚠️ **Jangan pakai `decimal` untuk kuantitas.** Simpan sebagai integer dalam
satuan dasar terkecil (miligram, mikroliter) jika perlu presisi tinggi, atau
`decimal(15,4)` jika cukup. Putuskan dan dokumentasikan; jangan campur.

⚠️ **Pencatatan waste yang lambat = fitur mati.** Uji alurnya dengan
stopwatch. Jika > 15 detik, sederhanakan.
