# P08 — Sales Core

**Fase:** F1 · **WP:** 1.5 · **Effort:** ± 2,5 pekan · **Prasyarat:** P04–P07

---

## PERAN
Backend engineer yang membangun jantung transaksi. Setiap bug di sini
berarti uang yang salah hitung di depan pelanggan.

## KONTEKS
Posita belum punya transaksi POS umum — hanya konsinyasi harian dan box
order. Modul ini menyatukan katalog (P04), stok (P05), dan resep (P06)
menjadi transaksi penjualan.

## TUJUAN
Sistem transaksi yang benar secara aritmetika, immutable setelah selesai,
idempoten, dan siap dipakai dari mobile (P11) maupun offline (P12).

## RUANG LINGKUP
1. `orders` + `order_items` + `order_item_modifiers`
2. `discounts` (master) + `order_discounts` (terpakai)
3. `tax_groups` + service charge + pembulatan konfigurabel
4. `payments` (multi/split payment)
5. `order_refunds` + void
6. `OrderCalculator` — urutan perhitungan yang konfigurabel
7. Actions: `CreateOrder`, `VoidOrder`, `RefundOrder`, `AddPayment`
8. Domain event `OrderCompleted`, `OrderVoided`, `OrderRefunded`
9. Nomor order per outlet per hari
10. Idempotency

## DI LUAR LINGKUP
- ❌ UI kasir mobile (itu P11)
- ❌ Payment gateway (itu P18) — cukup metode `cash` & `manual_qris` dulu
- ❌ Meja / dine-in / KDS
- ❌ Loyalty & member

---

## SPESIFIKASI

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M5 — Sales**.

Kritis:
- `orders.id` = **ULID yang dibuat klien**, bukan auto-increment.
- `orders.idempotency_key` unique per tenant.
- Semua field snapshot di `order_items` (nama, sku, harga, HPP).
- `orders.cost_total` dan `gross_profit` di-snapshot saat selesai.

### `OrderCalculator` — urutan konfigurabel

```php
namespace App\Services\Sales;

final class OrderCalculator
{
    public function calculate(OrderDraft $draft, OutletSettings $settings): CalculatedOrder;
}
```

Urutan default (setiap langkah bisa dinonaktifkan/diubah lewat setelan):

```
1. Per item:  unit_price = harga_dasar_resolved + Σ modifier
              line_subtotal = unit_price × qty
              line_discount  = diskon level item
              line_total     = line_subtotal − line_discount

2. subtotal   = Σ line_total

3. order_discount = diskon level order
                    (percent → dari subtotal; amount → nominal)
                    dibatasi max_discount_amount

4. base       = subtotal − order_discount

5. service_charge = base × service_charge_percent      [jika aktif]

6. tax_base   = sesuai setelan:
                'subtotal'      → base
                'with_service'  → base + service_charge   (default)

   tax        = tax_base × tax_rate                      [jika aktif]

   Jika tax_inclusive: harga sudah termasuk pajak,
     tax = total × rate / (100 + rate)   dan tidak ditambahkan lagi

7. total_before_rounding = base + service_charge + tax   (exclusive)
                         = base + service_charge         (inclusive)

8. rounding   = sesuai setelan:
                mode: 'none'|'up'|'nearest'|'down'
                nearest: 100 | 500 | 1000
   total      = total_before_rounding + rounding

9. cost_total = Σ (unit_cost_snapshot × qty)   [dari P06]
   gross_profit = subtotal − cost_total
```

**Wajib:** setiap langkah menghasilkan `Money`, dan pembulatan selalu
eksplisit. Total dari `Money::allocate()` untuk pembagian pajak per item
harus persis sama dengan pajak total.

### Idempotency

```php
final class CreateOrder
{
    public function handle(CreateOrderData $data, User $actor): Order
    {
        // 1. Cek idempotency_key
        if ($existing = Order::where('idempotency_key', $data->idempotencyKey)->first()) {
            return $existing;              // kembalikan hasil lama, TANPA efek samping
        }

        return DB::transaction(function () use ($data, $actor) {
            // 2. Hitung ulang SEMUA harga di server. Jangan percaya klien.
            $calculated = app(OrderCalculator::class)->calculate(...);

            // 3. Buat order + items + modifiers
            // 4. Buat payments
            // 5. Jika lunas → status completed, dispatch OrderCompleted
        });
    }
}
```

**Aturan keamanan:** klien mengirim `product_id`, `variant_id`,
`modifier_ids`, `quantity`, dan diskon yang diminta. Server menghitung
**semua** harga. Jangan pernah menerima `total` dari klien.

### Nomor order

Format: `{outlet_code}-{YYMMDD}-{urut}` → `KS-260906-0042`

Generasi harus tahan konkurensi dan tahan offline:
- Online: dari server, dengan lock per (outlet, tanggal).
- Offline: klien memakai prefix device + urutan lokal
  (`KS-260906-D3-0007`), diganti nomor final saat sinkronisasi, **tapi
  nomor lokal tetap disimpan** di `local_order_number` agar struk yang sudah
  dicetak bisa dicocokkan.

### Void vs Refund

| | Void | Refund |
|---|---|---|
| Kapan | Sebelum/segera setelah selesai, umumnya shift sama | Setelah selesai, bisa beda hari |
| Efek uang | Transaksi dianggap tidak terjadi | Uang dikembalikan, transaksi tetap tercatat |
| Efek stok | Kembalikan (opsional) | Kembalikan (opsional) |
| Laporan | Dikeluarkan dari penjualan, dihitung sebagai `void_count` | Tetap di penjualan, dikurangi sebagai `refund` |
| Otorisasi | `sales.void` + alasan wajib | `sales.refund` + alasan wajib |

Keduanya **tidak menghapus data**. Order asli tetap ada dengan status baru.

### Diskon

```php
// Validasi diskon manual terhadap batas role
if ($discount->isManual()) {
    $limit = $actor->roleLimit('discount_max_percent');
    if ($discount->percent > $limit) {
        throw new DiscountExceedsLimitException(
            "Diskon maksimal untuk peran Anda adalah {$limit}%. "
            . "Minta persetujuan supervisor."
        );
    }
}
```

Diskon master (`discounts`) mendukung: percent, amount, min purchase, max
discount, periode berlaku, hari & jam berlaku, batas pemakaian,
stackable/tidak.

---

## ACCEPTANCE CRITERIA

- [ ] Transaksi 3 item dengan varian & modifier menghasilkan total yang benar
- [ ] Pajak 10% atas subtotal + service charge 5% terhitung sesuai setelan
- [ ] Mode `tax_inclusive` menghasilkan total yang sama dengan harga menu
- [ ] Pembulatan ke 500 terdekat berfungsi dan tercatat terpisah
- [ ] Split payment (tunai Rp50.000 + QRIS Rp32.000) tercatat benar dengan
      kembalian yang tepat
- [ ] Mengirim request yang sama 3× dengan idempotency key sama menghasilkan
      **1** order
- [ ] Order selesai memotong stok sesuai resep
- [ ] Void dengan restock mengembalikan stok
- [ ] Diskon 25% oleh kasir berbatas 20% ditolak dengan pesan Indonesia
- [ ] `order_items` menyimpan snapshot; mengubah harga produk tidak mengubah
      order lama
- [ ] Nomor order unik per outlet per hari di bawah konkurensi (uji 100
      order paralel)
- [ ] Klien yang mengirim `total` palsu tetap dihitung ulang server

---

## TESTING WAJIB

```php
// tests/Unit/Sales/OrderCalculatorTest.php
it('calculates a simple order');
it('applies item-level then order-level discounts in order');
it('computes tax on subtotal plus service charge by default');
it('computes tax on subtotal only when configured');
it('handles tax-inclusive pricing');
it('rounds to the nearest 500');
it('allocates tax across items without losing rupiah');
it('caps discount at max_discount_amount');

// tests/Feature/Sales/CreateOrderTest.php
it('is idempotent for the same key');
it('recalculates prices server-side ignoring client totals');
it('deducts stock on completion');
it('generates unique order numbers under concurrency');
it('rejects a discount above the role limit');
it('records split payments and change');
it('snapshots product name, price and cost');
it('does not change historical orders when master price changes');

// tests/Feature/Sales/VoidRefundTest.php
it('requires a reason to void');
it('restores stock when restock is requested');
it('excludes voided orders from sales totals');
it('keeps refunded orders in sales but subtracts the refund');
it('forbids void without permission');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Sales
```

---

## CATATAN

⚠️ **Jangan pernah percaya total dari klien.** Ini bukan paranoia — aplikasi
mobile bisa dimodifikasi, dan kasir yang tidak jujur adalah ancaman nyata di
POS.

⚠️ **Urutan perhitungan pajak berbeda antar daerah.** Buat konfigurabel
sejak awal. Mengubahnya nanti berarti seluruh riwayat jadi tidak konsisten.

⚠️ **ULID dari klien**, bukan UUID acak. ULID terurut waktu → index database
tidak terfragmentasi.

⚠️ **`OrderCompleted` event akan punya banyak listener** (stok, notifikasi,
statistik, konsinyasi). Pastikan semua queued dan idempoten — event bisa
diproses ulang.

⚠️ **Uji dengan angka rupiah nyata**, bukan 1,00 dan 2,00. Kasus seperti
"Rp23.333 dibagi 3 orang" adalah tempat bug pembulatan bersembunyi.
