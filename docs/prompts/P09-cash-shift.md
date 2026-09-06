# P09 — Cash & Shift Management

**Fase:** F1 · **WP:** 1.6 · **Effort:** ± 1 pekan · **Prasyarat:** P08

---

## PERAN
Engineer yang memahami bahwa manajemen kas adalah kontrol anti-fraud, bukan
sekadar pencatatan.

## KONTEKS
Posita sudah punya `shop_sessions` dengan desain yang benar
(`closing_cash_system` vs `closing_cash_actual`). Modul ini
mengembangkannya menjadi sistem shift lengkap.

## TUJUAN
Sistem shift yang membuat setiap rupiah di laci bisa dipertanggungjawabkan.

## RUANG LINGKUP
1. Migrasi `shop_sessions` → `shifts` dengan field tambahan
2. `cash_movements` — kas masuk/keluar di luar penjualan
3. `shift_templates` — jadwal shift (Pagi/Sore/Malam)
4. Input denominasi uang saat tutup
5. Perhitungan expected vs counted cash
6. Laporan shift lengkap
7. Dukungan shift lintas tengah malam
8. Event `ShiftOpened`, `ShiftClosed`, `CashVarianceDetected`

## DI LUAR LINGKUP
- ❌ Notifikasi WhatsApp (itu P14) — cukup dispatch event
- ❌ Payroll (itu P15)
- ❌ UI mobile (itu P11)

---

## SPESIFIKASI

### Migrasi `shop_sessions` → `shifts`

```
shop_sessions.opened_at             → shifts.opened_at
shop_sessions.closed_at             → shifts.closed_at
shop_sessions.opening_cash          → shifts.opening_cash
shop_sessions.closing_cash_system   → shifts.expected_cash
shop_sessions.closing_cash_actual   → shifts.counted_cash
shop_sessions.status                → shifts.status
                                    → shifts.cash_variance (dihitung)
```

Field baru: `code`, `shift_template_id`, `cash_in_total`, `cash_out_total`,
`sales_total`, `sales_by_method` (JSON), `transaction_count`, `void_count`,
`void_amount`, `discount_amount`, `refund_amount`, `cost_total`,
`gross_profit`, `denominations` (JSON), `closing_photo_path`, `closed_by`,
`reviewed_by`, `reviewed_at`.

### Perhitungan kas

```
expected_cash = opening_cash
              + penjualan_tunai
              + cash_in_total
              − cash_out_total
              − kembalian_yang_diberikan   (sudah termasuk di penjualan_tunai bersih)
              − refund_tunai

cash_variance = counted_cash − expected_cash

variance > 0  → lebih (surplus)   — juga mencurigakan
variance < 0  → kurang (shortage)
```

**Penting:** `sales_by_method` memisahkan tunai dari non-tunai. Hanya tunai
yang masuk perhitungan laci.

### Input denominasi

```
Pecahan          Jumlah    Nilai
100.000    ×     [ 12 ]  = 1.200.000
 50.000    ×     [  8 ]  =   400.000
 20.000    ×     [  5 ]  =   100.000
 10.000    ×     [  3 ]  =    30.000
  5.000    ×     [  4 ]  =    20.000
  2.000    ×     [  6 ]  =    12.000
  1.000    ×     [ 10 ]  =    10.000
    500    ×     [  8 ]  =     4.000
─────────────────────────────────────
TOTAL FISIK                 1.776.000
Sistem                      1.783.000
SELISIH                        -7.000  ⚠️
```

Denominasi bersifat **opsional tapi sangat dianjurkan** — memudahkan
menemukan sumber selisih (mis. kurang tepat Rp50.000 = satu lembar hilang).
Setelan `shift.require_denominations` bisa memaksanya.

### Cash movement

```php
enum CashMovementType: string
{
    case CashIn      = 'cash_in';       // tambah modal, setoran dari owner
    case CashOut     = 'cash_out';      // belanja, bayar supplier
    case BankDeposit = 'bank_deposit';  // setor ke bank
    case PettyCash   = 'petty_cash';    // kas kecil
    case Tip         = 'tip';           // tip masuk laci
}
```

Setiap movement butuh: nominal, alasan, dan (di atas batas tertentu) foto
bukti + persetujuan. Batas diatur di `roles.limits.cash_out_max_amount`.

### Shift lintas tengah malam

```php
// JANGAN: shift = tanggal kalender
// LAKUKAN: shift punya opened_at & closed_at eksplisit

// Untuk laporan "hari ini", pakai business_date:
//   business_date = tanggal dari opened_at, disesuaikan dengan
//   day_start_time outlet (default 04:00)
// Shift yang dibuka 21.00 dan ditutup 02.00 → business_date = tanggal buka
```

Tambahkan `shifts.business_date` sebagai kolom terhitung dan di-index.

### Aturan

1. Satu user hanya boleh punya **satu shift terbuka per outlet**.
2. Membuka shift wajib input `opening_cash`.
3. Tidak bisa membuat order tanpa shift terbuka (kecuali order channel
   online).
4. Menutup shift wajib input `counted_cash`.
5. Selisih di luar toleransi (`shift.variance_tolerance`, default Rp10.000)
   wajib diisi catatan.
6. Shift tertutup tidak bisa dibuka ulang tanpa izin `shift.reopen`, dan
   tercatat di audit log.
7. Shift yang belum ditutup > `X` jam setelah jam tutup outlet memicu event
   (alert di P14).

### Laporan shift

```
LAPORAN SHIFT — KS-260906-SORE
Kopi Senja · Sore · 14:00 – 21:34 · Kasir: Rina

PENJUALAN
  Kotor                        Rp 3.910.000
  Diskon                       Rp    68.000  (12 transaksi)
  Refund                       Rp     0
  Bersih                       Rp 3.842.000  (127 transaksi)

METODE BAYAR
  Tunai                        Rp 1.210.000  (43 transaksi)
  QRIS                         Rp 2.502.000  (81 transaksi)
  Kasbon                       Rp   130.000  (3 transaksi)

KAS LACI
  Modal awal                   Rp   500.000
  + Penjualan tunai            Rp 1.210.000
  + Kas masuk                  Rp         0
  − Kas keluar                 Rp     7.000  (beli es batu)
  ────────────────────────────────────────
  = Seharusnya                 Rp 1.703.000
    Hitungan fisik             Rp 1.696.000
    SELISIH                    Rp    -7.000  ⚠️

PROFITABILITAS
  HPP                          Rp 1.191.000  (31,0%)
  Laba kotor                   Rp 2.651.000  (69,0%)

PENGAWASAN
  Void                         2 transaksi   Rp 48.000
  Diskon manual                3 transaksi   Rp 35.000
  Konfirmasi QRIS manual       0 transaksi

TOP PRODUK
  1. Es Kopi Susu              41 · Rp 902.000
  2. Americano                 22 · Rp 396.000
  3. Croissant                 14 · Rp 350.000
```

---

## ACCEPTANCE CRITERIA

- [ ] Data `shop_sessions` lama termigrasi ke `shifts` tanpa kehilangan
- [ ] Membuka shift kedua di outlet yang sama oleh user yang sama ditolak
- [ ] `expected_cash` terhitung benar termasuk cash in/out dan refund tunai
- [ ] Input denominasi menghasilkan total yang cocok dengan `counted_cash`
- [ ] Selisih di atas toleransi memaksa input catatan
- [ ] Shift dibuka 21.00 ditutup 02.00 memiliki `business_date` = tanggal buka
- [ ] Laporan shift menampilkan semua angka di atas dengan benar
- [ ] Membuka ulang shift tanpa izin ditolak dan tercatat di audit log
- [ ] Order tidak bisa dibuat tanpa shift terbuka
- [ ] Event `ShiftClosed` dan `CashVarianceDetected` ter-dispatch

---

## TESTING WAJIB

```php
it('prevents a second open shift for the same user and outlet');
it('computes expected cash including cash movements');
it('excludes non-cash payments from the drawer calculation');
it('computes variance correctly');
it('requires notes when variance exceeds tolerance');
it('assigns business_date correctly for overnight shifts');
it('forbids creating an order without an open shift');
it('forbids reopening a shift without permission');
it('logs shift reopening to the audit trail');
it('dispatches CashVarianceDetected above the threshold');
it('sums denominations to the counted cash total');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test --filter=Shift
```

---

## CATATAN

⚠️ **Selisih lebih (surplus) sama mencurigakannya dengan kurang.** Kasir
yang tidak menginput transaksi tunai akan menghasilkan surplus. Alert harus
memicu di kedua arah.

⚠️ **`business_date` bukan `DATE(opened_at)`.** Kedai buka sampai jam 2 pagi
adalah normal di Indonesia. Salah menangani ini membuat semua laporan harian
salah.

⚠️ **Jangan hitung ulang `sales_total` dari tabel orders setiap kali laporan
dibuka.** Simpan hasilnya di `shifts` saat penutupan — angka historis harus
beku.
