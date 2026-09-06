# P23 — Meja, Dine-in & Kitchen Display

**Fase:** F4 · **WP:** 4.7 · **Effort:** ± 2 pekan · **Prasyarat:** P08, P11

---

## PERAN
Engineer yang membangun alur layanan meja. Target penggunanya bukan coffee
shop kecil, melainkan preset "Resto/Cafe Penuh" di P19.

## KONTEKS
Blueprint `docs/20-blueprint-produk.md` mendefinisikan tabel `tables` (M5),
dan P19 menyediakan preset "Resto Lengkap" yang menyalakan feature flag
`tables` dan `kds`. Tapi P08 secara eksplisit menaruh "Meja / dine-in / KDS"
di luar lingkup, dan **tidak ada WP yang membangunnya**.

Akibatnya preset "Resto Lengkap" menyalakan menu yang kosong. WP ini
menutup lubang itu.

## TUJUAN
Layanan dine-in dengan bill berjalan per meja, plus tampilan dapur yang
membuat pesanan tidak terlewat.

## RUANG LINGKUP
1. `table_areas` + `tables` + denah lantai sederhana
2. Order dine-in dengan **open bill** (tab berjalan)
3. Pindah meja, gabung bill, pisah bill
4. `kitchen_stations` + aturan routing per kategori
5. Alur status item: `pending → preparing → ready → served`
6. Layar KDS (web, dioptimalkan untuk tablet/TV di dapur)
7. Cetak ke printer dapur sebagai alternatif KDS
8. Feature flag `tables` & `kds` (menyempurnakan P19)

## DI LUAR LINGKUP
- ❌ Reservasi meja & waiting list
- ❌ Pemesanan mandiri pelanggan lewat QR meja (v1 hanya lihat menu)
- ❌ Manajemen pramusaji / tip pooling
- ❌ Denah lantai drag-drop canggih (cukup grid sederhana)

---

## SPESIFIKASI

### 1. Skema

```sql
table_areas
  id, tenant_id, outlet_id, name,       -- 'Indoor'|'Outdoor'|'Lantai 2'
  sort_order, is_active

tables
  id, tenant_id, outlet_id, table_area_id,
  name, capacity,                       -- 'A1', 4
  qr_token (unique),                    -- untuk menu QR per meja
  position_x, position_y,               -- denah sederhana
  is_active
  -- CATATAN: TIDAK ada kolom `status`. Lihat 3.

kitchen_stations
  id, tenant_id, outlet_id, name,       -- 'Bar'|'Dapur Panas'|'Pastry'
  category_ids json,                    -- kategori yang dirutekan ke sini
  printer_target (nullable),            -- jika pakai printer, bukan KDS
  auto_ready_after_minutes (nullable),
  sort_order, is_active

order_item_events                       -- jejak audit alur dapur
  id, order_item_id, from_status, to_status,
  station_id, user_id, occurred_at
```

Tambahan pada `orders` (dari P08): `table_id` sudah ada. Tambahkan
`merged_into_order_id` (nullable) untuk gabung bill.

### 2. Open bill

Berbeda dari alur takeaway di P08 yang langsung selesai:

```
Dine-in:
  1. Pilih meja → order dibuat status 'open'
  2. Item ditambah bertahap sepanjang tamu duduk
     → tiap penambahan langsung dikirim ke KDS/printer dapur
  3. Tamu minta bill → cetak bill sementara (BUKAN struk pajak)
  4. Bayar → status 'completed', stok dipotong, struk final dicetak

Takeaway (P08): keranjang → bayar → selesai, satu langkah.
```

**Penting:** stok dipotong saat order `completed`, konsisten dengan P06.
Tapi item yang sudah `preparing` dan bill-nya dibatalkan wajib dicatat
sebagai waste (`WasteReason::Remake`), bukan hilang begitu saja — ini sumber
kebocoran nyata yang dilaporkan P17.

### 3. Status meja adalah turunan, bukan kolom

```php
// JANGAN simpan tables.status — ia akan melenceng dari kenyataan.
// TURUNKAN dari order terbuka:

public function getStatusAttribute(): TableStatus
{
    $open = $this->orders()->where('status', 'open')->exists();
    return $open ? TableStatus::Occupied : TableStatus::Available;
}
```

Kolom status yang disimpan terpisah **selalu** melenceng: aplikasi crash,
sinkronisasi gagal, kasir lupa — dan meja tampak terisi padahal kosong.
Turunkan dari data transaksi.

### 4. Gabung & pisah bill

```
Gabung:  order A (meja 3) + order B (meja 4) → order A
  → item B dipindah ke A, B.status='voided', B.merged_into_order_id=A.id
  → jejak audit dipertahankan, tidak ada data dihapus

Pisah:   order A → order A + order baru C
  → pilih item mana ke bill mana
  → ATAU pisah rata N orang → WAJIB pakai Money::allocate()
```

`Money::allocate()` mutlak untuk pisah rata. Rp23.333 dibagi 3 harus
menghasilkan 7.778 + 7.778 + 7.777, bukan tiga kali 7.777 yang kurang
Rp2 dari total.

### 5. KDS

```
┌─────────────────────────────────────────────────────────────┐
│ DAPUR PANAS          3 pesanan aktif        14:32           │
├──────────────────┬──────────────────┬───────────────────────┤
│ #0042  Meja A1   │ #0043  Meja B3   │ #0044  Takeaway       │
│ 2 mnt      🟢    │ 7 mnt      🟠    │ 12 mnt        🔴      │
│                  │                  │                       │
│ 2× Nasi Goreng   │ 1× Ayam Bakar    │ 3× Mie Goreng         │
│    - pedas       │ 1× Nasi Putih    │    - tanpa sayur      │
│ 1× Es Teh        │                  │                       │
│                  │                  │                       │
│ [   SIAP   ]     │ [   SIAP   ]     │ [   SIAP   ]          │
└──────────────────┴──────────────────┴───────────────────────┘
```

Aturan:
- Warna berdasarkan lama tunggu: 🟢 <5 mnt · 🟠 5–10 mnt · 🔴 >10 mnt
  (ambang konfigurabel per station)
- Item baru masuk memicu suara + animasi — dapur berisik dan sibuk
- Satu ketukan besar untuk menandai siap; tidak ada menu tersembunyi
- **Auto-refresh via polling, bukan WebSocket.** Alasan: KDS berjalan di
  jaringan lokal kedai yang sering putus dari internet; polling 3 detik lebih
  tahan banting dan jauh lebih sederhana.
- Riwayat "baru saja siap" bisa dibatalkan (undo 30 detik) — salah tekan itu
  normal

### 6. Alternatif printer dapur

Banyak kedai tidak punya tablet untuk dapur. Sediakan mode cetak: item yang
dirutekan ke station tertentu dicetak ke printer station itu saat masuk
`preparing`. Memakai `PrinterService` dari P11.

---

## ACCEPTANCE CRITERIA

- [ ] Membuat area & meja, menampilkannya dalam denah grid sederhana
- [ ] Status meja terhitung dari order terbuka, bukan dari kolom tersimpan
- [ ] Open bill: item bisa ditambah bertahap, KDS menerima tiap penambahan
- [ ] Bill sementara tercetak dan ditandai jelas **bukan struk final**
- [ ] Bayar → stok terpotong, struk final tercetak, meja jadi tersedia
- [ ] Gabung bill memindahkan item dan mempertahankan jejak audit
- [ ] Pisah rata 3 orang untuk Rp23.333 menghasilkan total persis Rp23.333
- [ ] Pindah meja mempertahankan seluruh item dan riwayatnya
- [ ] Item dirutekan ke station sesuai kategorinya
- [ ] KDS memperbarui diri dalam ≤ 3 detik tanpa refresh manual
- [ ] Warna kartu berubah sesuai lama tunggu
- [ ] Undo 30 detik setelah menandai siap
- [ ] Membatalkan item yang sudah `preparing` tercatat sebagai waste
- [ ] Mode printer dapur mencetak ke station yang benar
- [ ] Feature flag `tables` mati → seluruh menu meja hilang dari navigasi
- [ ] KDS tetap berfungsi saat internet mati tapi jaringan lokal hidup

---

## TESTING WAJIB

```php
// tests/Feature/Dinein/TableTest.php
it('derives table status from open orders');
it('marks the table available after payment');
it('keeps items and history when moving to another table');

// tests/Feature/Dinein/BillTest.php
it('adds items to an open bill incrementally');
it('routes each item to the station matching its category');
it('merges two bills preserving the audit trail');
it('splits evenly without losing rupiah')->with(/* kasus acak */);
it('prints a provisional bill marked as not a final receipt');
it('deducts stock only on completion');
it('records waste when a prepared item is cancelled');

// tests/Feature/Dinein/KdsTest.php
it('lists only items for the requesting station');
it('advances item status through the flow');
it('allows undo within 30 seconds');
it('records every status change in order_item_events');
```

---

## PERINTAH
```bash
vendor/bin/pint && php artisan test --filter="Dinein|Table|Kds"
npm run build
```

---

## CATATAN

⚠️ **Jangan simpan `tables.status`.** Ini kesalahan klasik POS. Kolom status
akan melenceng dari kenyataan dalam hitungan hari, dan pramusaji akan berhenti
mempercayai layarnya.

⚠️ **Polling, bukan WebSocket, untuk KDS.** Jaringan kedai tidak stabil.
WebSocket yang putus diam-diam berarti pesanan tidak muncul di dapur — dan
tamu menunggu makanan yang tidak pernah dimasak.

⚠️ **Item yang dibatalkan setelah dimasak adalah kebocoran nyata.** Hubungkan
ke `waste_logs` (P05) agar muncul di laporan variance (P17). Ini salah satu
sumber selisih yang paling sering tidak tercatat.

⚠️ **WP ini opsional untuk coffee shop.** Target utamanya preset "Resto
Lengkap". Kalau waktu terbatas, ini kandidat penundaan yang wajar — tapi
kalau ditunda, **hapus dulu klaim `tables` dan `kds` dari preset di P19**
supaya tidak menjanjikan yang tidak ada.
