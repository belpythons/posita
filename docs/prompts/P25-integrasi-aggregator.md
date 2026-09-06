# P25 — Integrasi Aggregator (GoFood / GrabFood / ShopeeFood)

**Fase:** F5 · **WP:** 5.5 · **Effort:** ± 2,5 pekan · **Prasyarat:** P04, P08, P17

---

## PERAN
Integration engineer yang menyambungkan Posita ke tiga platform pihak ketiga
yang masing-masing punya API, aturan, dan proses kemitraan berbeda.

## KONTEKS
Riset `docs/riset/02-analisis-kompetitor-pos.md` menemukan bahwa integrasi
aggregator sudah jadi komoditas di POS menengah–atas Indonesia (iSeller,
Klikit, Odoo). Bagi kedai yang 30–50% omzetnya dari delivery, mengelola
tablet terpisah per platform adalah beban nyata.

Roadmap sudah menandai WP5.5 sebagai **boleh ditunda** — kompleks, butuh
kemitraan, dan bisa ditangani manual dulu.

## TUJUAN
Pesanan dari tiga platform masuk ke POS yang sama, menu tersinkron dari satu
sumber, dan komisi terekonsiliasi.

## RUANG LINGKUP
1. Abstraksi `AggregatorProvider` + registry driver
2. Driver per platform (sesuai akses kemitraan yang didapat)
3. `integration_channels` — kredensial & status per outlet per platform
4. `channel_item_mappings` — pemetaan produk internal ↔ item eksternal
5. Pesanan masuk → membuat `Order` dengan `channel` yang benar
6. Sinkronisasi menu (dorong): produk & harga → platform
7. Sinkronisasi ketersediaan: stok habis → item dinonaktifkan
8. Sinkronisasi status balik: diterima / disiapkan / siap
9. `channel_settlements` — rekonsiliasi komisi & payout
10. Laporan profitabilitas per channel (setelah komisi)

## DI LUAR LINGKUP
- ❌ Mengurus proses kemitraan bisnis dengan Gojek/Grab/Shopee — itu urusan
  pemilik produk, bukan engineering
- ❌ Manajemen promo/kampanye di platform
- ❌ Integrasi kurir mandiri
- ❌ Menu berbeda per platform di luar harga (v1 cukup satu menu, harga
  berbeda lewat price tier P04)

---

## SPESIFIKASI

### 0. Prasyarat bisnis — baca dulu sebelum menulis kode

**Akses API ketiga platform butuh kemitraan resmi**, bukan pendaftaran
mandiri. Ini penghalang bisnis, bukan teknis. Sebelum WP ini dimulai,
pastikan minimal satu dari:

1. Kemitraan langsung dengan salah satu platform (proses berbulan), **atau**
2. Berlangganan middleware aggregator pihak ketiga yang sudah punya akses,
   **atau**
3. Menerima bahwa v1 hanya mendukung **impor manual** (unggah CSV laporan
   penjualan harian dari dashboard merchant)

**Kalau tidak ada satu pun, hentikan WP ini dan laporkan.** Membangun driver
untuk API yang tidak bisa diakses adalah kerja sia-sia.

Opsi 3 tetap bernilai dan jauh lebih murah — rekonsiliasi komisi dan laporan
profitabilitas per channel sudah berguna walau order tidak masuk otomatis.

### 1. Abstraksi

```php
namespace App\Support\Aggregator\Contracts;

interface AggregatorProvider
{
    public function key(): string;            // 'gofood'|'grabfood'|'shopeefood'|'manual_csv'
    public function label(): string;
    public function capabilities(): array;    // ['orders','menu_push','availability','status_sync','settlement']

    public function verifyCredentials(IntegrationChannel $c): bool;
    public function parseIncomingOrder(Request $r): ExternalOrder;
    public function acknowledgeOrder(string $externalId, OrderAck $ack): void;
    public function updateOrderStatus(string $externalId, ExternalOrderStatus $s): void;
    public function pushMenu(IntegrationChannel $c, MenuSnapshot $m): PushResult;
    public function setItemAvailability(string $externalItemId, bool $available): void;
    public function fetchSettlement(IntegrationChannel $c, CarbonInterface $d): SettlementReport;
}
```

Setiap platform berbeda dalam autentikasi, bentuk payload, dan kemampuan.
`capabilities()` memungkinkan UI menyembunyikan fitur yang tidak didukung
platform tertentu — jangan berpura-pura semuanya sama.

### 2. Skema

```sql
integration_channels
  id, tenant_id, outlet_id, provider,
  external_merchant_id, external_store_id,
  credentials (encrypted json),
  status,                               -- 'connected'|'error'|'disconnected'
  capabilities json,
  commission_percent,                   -- untuk estimasi & rekonsiliasi
  price_tier_id,                        -- markup channel (P04)
  auto_accept_orders bool,
  last_sync_at, last_error, is_active

channel_item_mappings
  id, integration_channel_id,
  product_id, product_variant_id (nullable),
  external_item_id, external_item_name,
  last_pushed_at, last_push_status
  UNIQUE(integration_channel_id, external_item_id)

channel_orders                          -- jejak mentah, untuk sengketa
  id, tenant_id, integration_channel_id,
  order_id (nullable),                  -- Order internal setelah dibuat
  external_order_id (unique per channel),
  external_status, raw_payload json,
  gross_amount, commission_amount, net_amount,
  received_at, acknowledged_at, error

channel_settlements
  id, tenant_id, integration_channel_id,
  settlement_date, gross_amount, commission_amount,
  adjustment_amount, net_amount,
  order_count, matched_count, unmatched_count,
  raw_report_path
```

### 3. Pesanan masuk

```
Webhook / polling platform
  → simpan raw_payload ke channel_orders SEBELUM diproses
  → petakan item eksternal → produk internal via channel_item_mappings
  → item tidak terpetakan → JANGAN tolak pesanan; buat order dengan
    produk 'open' bernama sesuai item eksternal, tandai untuk ditinjau
  → buat Order: channel='gofood', type='delivery', status='open'
  → potong stok sesuai resep (P06) — sama seperti penjualan biasa
  → tampilkan di KDS (P23) jika aktif, atau cetak ke printer dapur
  → acknowledge ke platform
```

**Aturan penting:** pesanan yang gagal diproses **tidak boleh hilang**.
Simpan payload mentah dulu, proses kemudian. Pesanan yang tidak muncul di
dapur berarti pelanggan menunggu makanan yang tidak dimasak, dan rating
merchant turun.

### 4. Komisi & profitabilitas — nilai sebenarnya dari WP ini

Komisi aggregator 15–25% dari GMV channel tersebut. Ini memakan margin jauh
lebih dalam daripada MDR QRIS, dan **sering tidak disadari pemilik**.

```
LABA PER CHANNEL — September 2026

Channel      Omzet       HPP      Komisi    Laba     Margin
Kasir     28.400.000  9.230.000        0  19.170.000  67,5%
GoFood     8.900.000  2.980.000  1.780.000 4.140.000  46,5%
GrabFood   4.200.000  1.410.000    924.000 1.866.000  44,4%
ShopeeFood 1.100.000    370.000    253.000   477.000  43,4%
──────────────────────────────────────────────────────────
TOTAL     42.600.000 13.990.000  2.957.000 25.653.000  60,2%

⚠️ Komisi Rp2.957.000 bulan ini — setara 6,9% dari total omzet.
   Margin GoFood 21 poin lebih rendah dari penjualan kasir.
   Pertimbangkan markup harga channel (saat ini 0%).
```

Laporan ini masuk ke P17 dan dashboard owner. Inilah alasan WP ini layak
dibangun, bukan sekadar "order masuk otomatis".

### 5. Sinkronisasi menu & ketersediaan

- **Satu sumber kebenaran**: katalog internal (P04). Harga channel lewat
  `price_tier` (markup untuk menutup komisi).
- Perubahan harga di katalog → tandai channel "perlu sinkronisasi" → dorong
  batch, jangan per-item.
- Stok bahan habis (P05) → item terkait otomatis dinonaktifkan di platform.
  Ini mencegah pesanan yang tidak bisa dipenuhi — penyebab utama rating buruk.
- Rate limit platform ketat: **wajib** antre dan batch, jangan dorong tiap
  perubahan.

---

## ACCEPTANCE CRITERIA

- [ ] Prasyarat bisnis (§0) terverifikasi sebelum kode driver ditulis
- [ ] Driver `manual_csv` berfungsi tanpa kemitraan apa pun
- [ ] Payload mentah tersimpan sebelum diproses; kegagalan proses tidak
      menghilangkan pesanan
- [ ] Pesanan masuk membuat `Order` dengan `channel` benar dan memotong stok
      sesuai resep
- [ ] Item tidak terpetakan tetap membuat pesanan, ditandai untuk ditinjau
- [ ] Pesanan duplikat (webhook dikirim ulang) diproses sekali
- [ ] Stok bahan habis menonaktifkan item di platform
- [ ] Dorong menu memakai batch dan menghormati rate limit
- [ ] Laporan laba per channel menampilkan komisi dan margin dengan benar
- [ ] Rekonsiliasi settlement mencocokkan pesanan lokal vs laporan platform
      dan melaporkan selisih
- [ ] `capabilities()` menyembunyikan fitur yang tidak didukung platform
- [ ] Kredensial terenkripsi, tidak pernah muncul di response API
- [ ] Isolasi tenant berlaku

---

## TESTING WAJIB

```php
it('stores the raw payload before processing');
it('creates an internal order from an external order');
it('maps external items to internal products');
it('creates an open-price item for unmapped external items');
it('processes a duplicate external order only once');
it('deducts stock via the recipe like any other sale');
it('disables an item on the platform when its ingredient runs out');
it('batches menu pushes instead of pushing per change');
it('computes per-channel profit after commission');
it('reconciles settlements and reports unmatched orders');
it('hides unsupported features based on capabilities');
it('works end to end with the manual_csv driver');
```

Semua test memakai driver `fake`. **Jangan pernah** memanggil API platform
sungguhan di CI.

---

## PERINTAH
```bash
vendor/bin/pint && php artisan test --filter=Aggregator
php artisan posita:aggregator:sync-menu --channel=1 --dry-run
php artisan posita:aggregator:reconcile --date=2026-09-05 --dry-run
```

---

## CATATAN

⚠️ **Penghalang terbesar WP ini bukan teknis, tapi akses.** Jangan mulai
menulis driver sebelum §0 terjawab. Driver untuk API yang tidak bisa diakses
adalah kode mati.

⚠️ **Mulai dari `manual_csv`.** Ia memberi 70% nilai (rekonsiliasi komisi dan
laporan profitabilitas per channel) dengan 10% usaha, dan tidak butuh
kemitraan apa pun. Baru bangun driver API kalau aksesnya benar-benar ada.

⚠️ **Pesanan hilang lebih buruk daripada integrasi tidak ada.** Kalau pemilik
mengandalkan Posita untuk pesanan GoFood dan satu pesanan tidak muncul di
dapur, kepercayaannya hilang seketika. Simpan mentah dulu, proses kemudian,
dan sediakan layar "pesanan bermasalah".

⚠️ **Komisi memakan margin lebih dalam daripada yang disadari pemilik.**
Laporan di §4 mungkin bagian paling berharga dari WP ini — bahkan tanpa
integrasi order otomatis.
