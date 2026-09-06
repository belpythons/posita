# P13 — Konsinyasi Mitra (Diferensiator)

**Fase:** F4 · **WP:** 4.1 · **Effort:** ± 2,5 pekan · **Prasyarat:** P08, P12

---

## PERAN
Engineer yang membangun modul dengan **hampir tanpa kompetitor langsung**.
Setiap rupiah di sini adalah uang orang lain — akurasi adalah kepercayaan.

## KONTEKS
Posita sudah punya `Partner`, `ProductTemplate`, dan `DailyConsignment` —
fondasi yang bagus tapi terlalu sederhana untuk produksi. Riset menunjukkan
**tidak ada POS mainstream Indonesia yang menangani konsinyasi sebagai warga
kelas satu**. Ini blue ocean kecil tapi nyata.

## TUJUAN
Modul konsinyasi lengkap yang menghilangkan sumber konflik antara toko dan
mitra: setiap angka transparan, terdokumentasi, dan bisa diverifikasi kedua
pihak.

## RUANG LINGKUP
1. Evolusi `partners` dengan data bank & siklus settlement
2. `partner_products` (dari `product_templates`)
3. `consignment_batches` + items dengan expiry & foto serah terima
4. Skema bagi hasil fleksibel: markup / komisi % / fee flat / bertingkat
5. Integrasi ke penjualan: `order_items.ownership='partner'`
6. Rekonsiliasi harian saat tutup shift
7. `consignment_returns` — retur barang
8. `partner_settlements` — pembayaran + PDF laporan
9. Portal mitra (akses terbatas, read-only)
10. Migrasi `daily_consignments` → skema baru

## DI LUAR LINGKUP
- ❌ Notifikasi WA ke mitra (itu P14) — cukup dispatch event
- ❌ Pembayaran otomatis ke rekening mitra
- ❌ Marketplace mitra / pencarian mitra baru

---

## SPESIFIKASI

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M8 — Partner/Konsinyasi**.

### Skema bagi hasil

```php
enum RevenueShareType: string
{
    case Markup           = 'markup';
    case CommissionPercent = 'commission_percent';
    case FlatFee          = 'flat_fee';
    case Tiered           = 'tiered';
}

final class RevenueShareCalculator
{
    /**
     * @return array{partner: Money, shop: Money}
     */
    public function split(
        Money $sellingPrice,
        int $quantity,
        RevenueShareType $type,
        array $config,
    ): array;
}
```

| Skema | Config | Contoh (jual Rp10.000) |
|---|---|---|
| `markup` | `{partner_price: 8000}` | mitra Rp8.000, toko Rp2.000 |
| `commission_percent` | `{percent: 20}` | mitra Rp8.000, toko Rp2.000 |
| `flat_fee` | `{fee: 1500}` | mitra Rp8.500, toko Rp1.500 |
| `tiered` | `{tiers: [{max_qty: 50, percent: 15}, {max_qty: null, percent: 20}]}` | tergantung volume periode |

**Wajib:** gunakan `Money::allocate()` untuk pembagian agar total pecahan
persis sama dengan total penjualan. Satu rupiah yang hilang berulang kali
akan memicu sengketa.

Konfigurasi berlaku berjenjang: **default mitra → override per produk →
override per batch**.

### Alur 1 — Terima Titipan

```
Layar: Terima Titipan
  Mitra:        [Bu Sari Bakery ▾]
  ──────────────────────────────────
  Roti Coklat        [ 20 ] pcs
    Harga mitra 8.000 · jual 10.000 · toko 20%
  Donat Gula         [ 15 ] pcs
  Bolu Pandan        [ 10 ] pcs
    Kedaluwarsa: [ 8 Sep 2026 ]
  ──────────────────────────────────
  [+ Tambah produk]
  📷 Foto serah terima (opsional)
  ✍️ Tanda tangan mitra (opsional)
  [ TERIMA TITIPAN ]

→ consignment_batch dibuat dengan code CB-260906-001
→ stock_movement type='consignment_in' per item
→ event ConsignmentReceived (→ WA ke mitra di P14)
```

### Alur 2 — Penjualan

Produk mitra dijual seperti produk biasa di kasir. Yang berbeda di belakang
layar:

```php
// Saat OrderCompleted:
foreach ($order->items->where('ownership', 'partner') as $item) {
    // 1. Alokasikan dari batch aktif (FIFO by expiry, lalu by received_at)
    $batch = $this->allocateFromBatch($item->partner_id, $item->product_id, $item->quantity);

    // 2. Hitung bagi hasil
    $split = $this->revenueShare->split(
        $item->unit_price, $item->quantity,
        $batch->revenue_share_type, $batch->revenue_share_config
    );

    // 3. Simpan di order_item
    $item->update([
        'consignment_batch_id' => $batch->id,
        'partner_share' => $split['partner'],
        'shop_share'    => $split['shop'],
    ]);

    // 4. Kurangi batch
    $batch->increment('quantity_sold', $item->quantity);

    // 5. Stock movement
    $this->ledger->record(type: 'consignment_out', ...);
}
```

**Penting:** jika stok batch tidak cukup, transaksi **tetap selesai**
(pelanggan sudah bayar), tapi catat sebagai `consignment_shortage` dan
tandai untuk ditinjau.

### Alur 3 — Rekonsiliasi Harian (saat tutup shift)

```
REKONSILIASI TITIPAN — 6 September 2026

Bu Sari Bakery                    CB-260906-001
  Produk          Titip  Terjual  Sisa Sistem  Sisa Fisik
  Roti Coklat        20       17            3  [  3  ] ✅
  Donat Gula         15       15            0  [  0  ] ✅
  Bolu Pandan        10        4            6  [  5  ] ⚠️
                                                └─ Selisih 1
                                                   Alasan: [Rusak ▾]

Pak Budi Snack                    CB-260905-003
  Keripik Singkong   30       22            8  [  8  ] ✅

[ SIMPAN REKONSILIASI ]
```

Selisih **wajib** diberi alasan: `damaged`, `expired`, `lost`, `taken_back`,
`counting_error`. Tercatat di audit log.

### Alur 4 — Retur

```
→ Pilih batch → pilih item & qty → kondisi (baik/rusak/kedaluwarsa)
→ Foto + tanda tangan mitra (opsional)
→ consignment_return dibuat
→ stock_movement type='consignment_return'
→ batch.quantity_returned bertambah
→ jika semua terjual/kembali → batch.status = 'settled'
```

### Alur 5 — Settlement

```
Sistem generate draft sesuai siklus mitra (harian/mingguan/bulanan)

SETTLEMENT — Bu Sari Bakery
Periode: 1–7 September 2026            ST-260907-001

Produk           Terjual  Harga  Kotor      Bagian Toko  Hak Mitra
Roti Coklat          112 10.000  1.120.000     224.000    896.000
Donat Gula            89  7.000    623.000     124.600    498.400
Bolu Pandan           34 12.000    408.000      81.600    326.400
──────────────────────────────────────────────────────────────────
TOTAL                235         2.151.000     430.200  1.720.800

Penyesuaian:
  Barang rusak (2 Bolu)                              -24.000
                                          ─────────────────
  DIBAYARKAN                                       1.696.800

Transfer ke: BCA 1234567890 a.n. Sari Wulandari

[ KONFIRMASI ]  → status 'confirmed'
[ TANDAI SUDAH DIBAYAR ] + upload bukti → status 'paid'
→ PDF laporan + bukti dikirim ke mitra (WA di P14)
→ mitra bisa konfirmasi terima / ajukan sengketa lewat link
```

### Portal Mitra

Guard terpisah (`partner`), akses **read-only** ke:
- Titipan aktif & riwayat
- Penjualan harian produk mereka
- Riwayat settlement + status pembayaran
- Unduh laporan PDF
- Tombol "Konfirmasi Terima" / "Ajukan Sengketa"

Akses lewat **magic link** yang dikirim via WhatsApp (tanpa password —
mitra UMKM tidak akan mengelola password). Link berlaku 30 hari, bisa
diperbarui.

### Migrasi

```
daily_consignments → consignment_batches + consignment_batch_items
  shop_session_id  → (cari outlet_id dari shift)
  partner_id       → consignment_batches.partner_id
  product_name     → cari/buat products dengan ownership='partner'
  qty_initial      → quantity_received
  qty_sold         → quantity_sold
  qty_remaining    → quantity_remaining
  base_price       → partner_price
  selling_price    → selling_price
  markup_percent   → revenue_share_type='markup'
```

---

## ACCEPTANCE CRITERIA

- [ ] Alur lengkap berjalan: terima → jual → rekonsiliasi → retur →
      settlement
- [ ] Keempat skema bagi hasil menghasilkan angka yang benar
- [ ] `Money::allocate()` memastikan Σ(bagian mitra + bagian toko) = total
      penjualan, persis, tanpa selisih rupiah
- [ ] Menjual produk mitra memotong batch yang benar (FIFO by expiry)
- [ ] Selisih rekonsiliasi wajib diberi alasan dan tercatat di audit log
- [ ] Settlement menghasilkan PDF yang benar dan bisa diunduh
- [ ] Mitra bisa membuka portal lewat magic link tanpa password
- [ ] Mitra hanya melihat datanya sendiri (uji isolasi)
- [ ] Data `daily_consignments` lama termigrasi tanpa kehilangan
- [ ] Batch kedaluwarsa terdeteksi dan bisa difilter
- [ ] Penjualan melebihi stok batch tetap selesai dengan flag shortage

---

## TESTING WAJIB

```php
// tests/Unit/Partner/RevenueShareCalculatorTest.php
it('splits by markup');
it('splits by commission percent');
it('splits by flat fee');
it('splits by tiered rate based on period volume');
it('never loses a rupiah in the split')->with(/* 1000 kasus acak */);
it('applies product-level override over partner default');
it('applies batch-level override over product override');

// tests/Feature/Partner/ConsignmentFlowTest.php
it('creates stock movements when receiving a batch');
it('allocates sales from the batch expiring soonest');
it('records partner and shop share on the order item');
it('completes the sale even when the batch is short');
it('requires a reason for reconciliation differences');
it('marks the batch settled when fully sold or returned');

// tests/Feature/Partner/SettlementTest.php
it('generates a settlement for the period');
it('applies adjustments for damaged goods');
it('produces a PDF report');
it('prevents double settlement of the same batch items');

// tests/Feature/Partner/PortalTest.php
it('grants access via a valid magic link');
it('rejects an expired magic link');
it('shows only the partner own data');
it('forbids any write operation');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Partner
```

---

## CATATAN

⚠️ **Ini uang orang lain.** Bug pembagian di sini bukan sekadar angka salah
— itu merusak hubungan bisnis pengguna dengan mitranya, dan mereka akan
menyalahkan aplikasi. Uji `RevenueShareCalculator` dengan property-based
test.

⚠️ **Foto & tanda tangan serah terima adalah fitur kepercayaan, bukan
kelengkapan.** Buat semudah mungkin, tapi jangan wajibkan — mitra yang
buru-buru tidak akan mau.

⚠️ **Magic link, bukan password.** Mitra UMKM tidak akan mengelola akun.
Kirim link lewat WhatsApp; itu satu-satunya kanal yang pasti mereka buka.

⚠️ **Settlement harus bisa di-undo sebelum status `paid`.** Kesalahan input
akan terjadi.

⚠️ **Jangan hapus batch yang sudah di-settle.** Arsipkan. Sengketa bisa
muncul berbulan kemudian.
