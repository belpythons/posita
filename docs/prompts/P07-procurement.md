# P07 — Procurement & Restock

**Fase:** F1 · **WP:** 1.4 · **Effort:** ± 1 pekan · **Prasyarat:** P05, P06

---

## PERAN
Engineer yang mengoptimalkan untuk **kecepatan input di lapangan**, bukan
kelengkapan fitur ERP.

## KONTEKS
Realitas: barista menyadari susu habis di jam 5 sore, lari ke minimarket,
beli 6 liter, dan kembali dengan nota kertas yang akan hilang besok. Jika
mencatatnya di aplikasi butuh 2 menit dan 8 layar, dia tidak akan
melakukannya — dan seluruh data stok jadi tidak berguna.

## TUJUAN
Pencatatan restock yang bisa selesai dalam **< 20 detik**, dengan jalur
formal (supplier + PO) sebagai opsi bagi yang membutuhkan.

## RUANG LINGKUP
1. `suppliers` + `supplier_ingredients` (katalog harga)
2. `goods_receipts` + items — jalur utama
3. `purchase_orders` + items — opsional
4. Alur **Restock Cepat** (4 ketukan) — prioritas utama
5. Update harga beli → memicu `IngredientCostChanged`
6. Riwayat harga bahan + grafik tren
7. Foto nota tersimpan

## DI LUAR LINGKUP
- ❌ Approval workflow bertingkat
- ❌ Integrasi supplier / EDI
- ❌ Auto-reorder otomatis (cukup alert di P14)
- ❌ Hutang usaha / aging supplier lengkap

---

## SPESIFIKASI

### Alur Restock Cepat — **prioritas desain tertinggi**

```
Layar 1: Pilih bahan
  [Pencarian]  ← fokus otomatis, keyboard muncul
  Daftar bahan diurutkan: (1) stok kritis, (2) sering direstock, (3) A-Z
  Bisa pilih beberapa sekaligus

Layar 2: Input per bahan
  Susu UHT Full Cream
  Jumlah:  [  6  ] Liter ▾     ← satuan pembelian, bukan satuan dasar
  Harga:   [ 96.000 ] total     ← total, bukan per satuan (lebih natural)
           → sistem hitung: Rp16.000/L = Rp16/ml
  [Sebelumnya: Rp15.500/L]      ← perbandingan harga terakhir

Layar 3: Ringkasan
  3 bahan · Total Rp 247.000
  Bayar dari: [Kas Laci ▾]      ← terhubung ke cash movement (P09)
  [📷 Foto Nota]  (opsional)
  [SIMPAN]

→ Selesai. 4 ketukan untuk 1 bahan.
```

**Detail penting:**
- Input **harga total**, bukan harga satuan. Nota tertulis "6 L = Rp96.000",
  bukan "Rp16.000/L". Jangan paksa pengguna berhitung.
- Tampilkan **perbandingan dengan harga terakhir**. Kenaikan > 10% ditandai.
- Satuan default = `purchase_unit` bahan tersebut.
- Restock bisa dicatat **tanpa supplier** (beli di minimarket).
- Jika dibayar dari kas laci, otomatis buat `cash_movement` bertipe
  `cash_out`.

### Dampak ke sistem lain

```
GoodsReceipt disimpan
  ├── StockLedger::record(type: 'purchase') per item
  ├── stock_batches baru dengan unit_cost
  ├── ingredients.current_cost_per_unit diperbarui
  │     (sesuai cost_method: last_purchase / weighted_average / fifo)
  ├── event IngredientCostChanged → rekalkulasi HPP (P06)
  ├── cash_movement jika dibayar dari laci (P09)
  └── expense otomatis kategori 'COGS' (P15)
```

### Purchase Order (opsional)

Alur formal untuk yang membutuhkan:
```
Draft → Kirim ke supplier (PDF/WA) → Terima sebagian/penuh → Selesai
```
Penerimaan parsial harus didukung: pesan 10 kg, datang 7 kg, sisa 3 kg
tetap outstanding.

### Katalog harga supplier

Menyimpan harga terakhir per supplier per bahan, memungkinkan:
- Perbandingan harga antar supplier
- Peringatan "Supplier B menawarkan Rp2.000 lebih murah"
- Estimasi biaya belanja saat alert stok menipis

---

## ACCEPTANCE CRITERIA

- [ ] Restock 1 bahan selesai dalam ≤ 4 ketukan dan < 20 detik (diukur)
- [ ] Input harga total menghasilkan harga satuan yang benar
- [ ] Harga beli baru memperbarui `current_cost_per_unit` sesuai
      `cost_method`
- [ ] Perubahan harga memicu rekalkulasi HPP resep terdampak
- [ ] Kenaikan harga > 10% ditandai di UI
- [ ] Restock dari kas laci membuat `cash_movement` yang benar
- [ ] Foto nota tersimpan dan bisa dilihat kembali
- [ ] PO dengan penerimaan parsial menyisakan qty outstanding yang benar
- [ ] Grafik tren harga bahan menampilkan 12 bulan terakhir

---

## TESTING WAJIB

```php
it('creates stock movements and batches from a goods receipt');
it('derives unit price from total price and quantity');
it('updates ingredient cost using the configured cost method');
it('dispatches IngredientCostChanged');
it('creates a cash movement when paid from the drawer');
it('flags a price increase above 10 percent');
it('handles partial PO receipt leaving the balance outstanding');
it('records the receipt without a supplier');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test --filter=Procurement
```

---

## CATATAN

⚠️ **Kecepatan adalah fiturnya.** Jika alur restock terasa seperti mengisi
formulir, modul ini gagal. Uji dengan stopwatch pada orang yang belum pernah
melihat aplikasinya.

⚠️ **`cost_method` default = `last_purchase`** untuk UMKM. Weighted average
lebih akurat secara akuntansi tapi membingungkan pemilik warung yang bertanya
"kenapa harga kopi saya Rp178 padahal saya beli Rp180?".

⚠️ **PO jangan dipaksakan.** Mayoritas pengguna tidak akan pernah memakainya.
Sembunyikan di balik feature flag, default mati.
