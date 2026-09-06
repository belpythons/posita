# 03 — Payment Gateway, QRIS & E-Wallet

Tujuan: menemukan opsi pembayaran digital (e-wallet & mobile banking) dengan
**biaya masuk nol** untuk pengguna Posita, sambil tetap menyediakan jalur
upgrade ke rekonsiliasi otomatis.

> **Perlu verifikasi:** tarif payment gateway dan kebijakan MDR berubah.
> Angka di bawah adalah snapshot riset September 2026. Konfirmasi langsung ke
> penyedia sebelum ditampilkan ke pengguna.

---

## 1. Aturan Main: MDR QRIS 2026

Bank Indonesia mengatur *Merchant Discount Rate* (MDR) QRIS berdasarkan
kategori merchant dan nilai transaksi.

### Struktur yang berlaku

| Kategori merchant | Nilai transaksi | MDR |
|---|---|---|
| **UMi** (Usaha Mikro) | ≤ Rp500.000 | **0%** |
| **UMi** | > Rp500.000 | 0,3% |
| **UKE / UME / UBE** (Kecil/Menengah/Besar) | — | 0,7% |

### Perluasan mulai 1 Oktober 2026

Bank Indonesia memperluas kebijakan MDR 0%:

- Merchant **UMi**: transaksi hingga Rp500.000 tetap **0% MDR**.
- Merchant **UKE/UME/UBE**: mendapat **0% MDR** untuk transaksi hingga
  **Rp100.000**.
- Di atas ambang tersebut dikenakan MDR sesuai kategori (0,3% untuk UMi,
  0,7% untuk kategori lain).

> ⚠️ **Perlu verifikasi ke sumber primer BI.** Sumber sekunder yang kami
> temukan sedikit berbeda dalam menjelaskan ambang batas di atas Rp100.000
> untuk UKE/UME/UBE. Sebelum menampilkan kalkulator biaya di aplikasi,
> konfirmasi ke dokumen resmi Bank Indonesia atau ke PJP yang dipakai.

### Aturan yang WAJIB dipatuhi aplikasi

> **MDR adalah tanggungan merchant dan DILARANG dibebankan ke pembeli dalam
> bentuk apapun.**

**Implikasi implementasi:**

1. ❌ **JANGAN** buat fitur default "Biaya QRIS Rp X" yang ditambahkan ke
   total belanja pelanggan.
2. ✅ Sediakan field `mdr_estimated` yang **hanya muncul di laporan
   internal** untuk perhitungan margin owner.
3. ✅ Tampilkan peringatan kepatuhan di halaman setting pembayaran.
4. ✅ Laporan "Pendapatan Bersih Setelah MDR" untuk owner — ini legal dan
   sangat berguna, karena MDR memotong margin nyata.

**Cara menghitung dampak MDR ke margin (untuk dokumentasi in-app):**

```
Nilai transaksi QRIS   = Rp 100.000
MDR (0,3%)             = Rp     300
Diterima merchant      = Rp  99.700
Jika HPP 30%           = Rp  30.000
Laba kotor riil        = Rp  69.700  (bukan Rp 70.000)
```

Untuk kedai dengan omzet QRIS Rp30 juta/bulan, MDR 0,7% = Rp210.000/bulan.
Ini setara biaya langganan POS — angka yang layak ditampilkan ke owner.

---

## 2. Opsi Pembayaran — Tiga Tingkat

Posita harus mendukung **ketiga tingkat**, dan pengguna bebas pilih. Ini
bagian dari janji "full customable".

### Tier 0 — QRIS Statis Merchant (BIAYA MASUK: Rp0)

**Cara kerja.** Owner sudah punya QRIS statis dari bank atau aplikasi
merchant (GoPay Merchant, DANA Bisnis, ShopeePay Merchant, LinkAja, QRIS
bank). Satu QR dicetak dan ditempel di kasir. Pelanggan memasukkan nominal
sendiri.

| Aspek | Nilai |
|---|---|
| Biaya setup | **Rp0** |
| Biaya bulanan | **Rp0** |
| Biaya transaksi | MDR sesuai kategori merchant (0%–0,7%), langsung dipotong PJP |
| Waktu integrasi | Nol — hanya upload gambar QR ke aplikasi |
| Rekonsiliasi | **Manual** — kasir konfirmasi setelah lihat notifikasi masuk |
| Risiko | Kasir salah konfirmasi / pelanggan salah nominal / bukti palsu |

**Dukungan yang harus dibangun Posita:**
- Upload gambar QRIS statis per outlet, tampil di layar kasir saat metode
  QRIS dipilih.
- Layar konfirmasi manual dengan **wajib** input: nominal diterima + nama
  pengirim/referensi + opsi lampirkan screenshot bukti.
- Tandai transaksi sebagai `payment_verification: manual` di audit trail —
  supaya saat tutup shift bisa dicek satu per satu.
- Peringatan otomatis: "12 transaksi QRIS hari ini dikonfirmasi manual —
  cek mutasi rekeningmu".

> **Ini adalah default untuk pengguna baru.** Realistis, gratis, dan sudah
> jadi kebiasaan mayoritas warung Indonesia.

---

### Tier 1 — Payment Gateway Tanpa Setup Fee & Tanpa Biaya Bulanan (REKOMENDASI)

QRIS **dinamis**: nominal sudah terisi otomatis, callback masuk ke sistem,
rekonsiliasi otomatis, tidak bisa salah nominal.

#### Perbandingan penyedia

| Penyedia | Setup fee | Biaya bulanan | QRIS | VA / Transfer Bank | Kartu | Catatan |
|---|---|---|---|---|---|---|
| **Midtrans** | **Rp0** | **Rp0** | ~0,7% | ~Rp4.000 + PPN | ~2,9% + Rp2.000 | Dokumentasi terbaik, SDK PHP resmi, sandbox lengkap |
| **Duitku** | Rp0 | Rp0 | ~0,7% | Flat fee rendah | mulai ~2,5% | Pencairan 1–2 hari kerja |
| **Tripay** | Rp0 | Rp0 | ~0,7% | Flat fee | — | Populer untuk UMKM & developer lokal |
| **iPaymu** | Rp0 | Rp0 | ~0,7% | Flat fee | ~2,9% | Onboarding relatif mudah |
| **DOKU** | Rp0 | Rp0 | ~0,7% | ~Rp4.000 | ~2,9% | Pemain lama, korporat |
| **Xendit** | Rp0 | **~Rp25.000/sub-akun aktif** | ~0,7% | Lebih tinggi | ~2,9% | API sangat baik, tapi ada biaya bulanan → **kurang cocok untuk tier gratis** |
| **Mayar** | Rp0 | Rp0 | ~0,7% | Flat fee | — | Fokus creator/UMKM, onboarding cepat |

#### Rekomendasi: **Midtrans sebagai driver default**

Alasan:
1. Tidak ada biaya implementasi, maintenance, maupun bulanan — sesuai
   permintaan "gratisan".
2. SDK PHP resmi (`midtrans/midtrans-php`) → integrasi Laravel cepat.
3. Sandbox penuh untuk testing tanpa akun bisnis.
4. Dokumentasi & komunitas Indonesia paling luas → dukungan pengguna murah.
5. Mendukung QRIS, GoPay, ShopeePay, VA semua bank besar, kartu — satu
   integrasi mencakup e-wallet **dan** mobile banking.

**Alternatif yang wajib didukung juga:** Duitku dan Tripay, karena beberapa
merchant sudah punya akun di sana dan tarifnya kadang lebih baik untuk VA.

---

### Tier 2 — Pembayaran Non-Digital & Kredit

| Metode | Kebutuhan |
|---|---|
| Tunai | Cash drawer, kembalian, denominasi, selisih kas |
| Kasbon / bayar nanti | Piutang pelanggan, limit, jatuh tempo, reminder WA |
| Transfer manual | Upload bukti, verifikasi admin |
| EDC bank (kartu) | Input manual nomor approval, rekonsiliasi terpisah |
| Voucher / deposit pelanggan | Saldo prabayar, kartu member |

---

## 3. Arsitektur: Payment Driver Abstraction

Jangan mengikat aplikasi ke satu gateway. Ini keputusan arsitektur wajib.

### 3.1 Kontrak

```php
// app/Contracts/Payment/PaymentProvider.php
interface PaymentProvider
{
    public function key(): string;                // 'midtrans' | 'duitku' | 'manual_qris'
    public function supportedMethods(): array;    // ['qris','va_bca','gopay',...]

    /** Buat instruksi bayar (QR string, VA number, deeplink). */
    public function createCharge(PaymentIntent $intent): ChargeResult;

    /** Cek status ke provider (fallback jika webhook tidak sampai). */
    public function fetchStatus(string $providerRef): PaymentStatus;

    /** Verifikasi & normalisasi payload webhook menjadi event internal. */
    public function parseWebhook(Request $request): WebhookEvent;

    /** Refund penuh/parsial jika didukung. */
    public function refund(string $providerRef, Money $amount): RefundResult;
}
```

### 3.2 Driver yang disediakan

| Driver | Kelas | Untuk |
|---|---|---|
| `manual_qris` | `ManualQrisProvider` | Tier 0 — QRIS statis, konfirmasi kasir |
| `manual_transfer` | `ManualTransferProvider` | Transfer bank + upload bukti |
| `midtrans` | `MidtransProvider` | Default Tier 1 |
| `duitku` | `DuitkuProvider` | Alternatif |
| `tripay` | `TripayProvider` | Alternatif |
| `cash` | `CashProvider` | Tunai (tetap lewat kontrak yang sama demi konsistensi laporan) |

Konfigurasi per **outlet**, bukan global — satu tenant bisa punya outlet
dengan gateway berbeda.

### 3.3 Aturan implementasi yang tidak boleh dilanggar

1. **Idempotency.** Setiap `PaymentIntent` punya `idempotency_key` dari
   klien (ULID). Retry tidak boleh membuat charge ganda.
2. **Webhook adalah sumber kebenaran, bukan respons klien.** Order hanya
   `paid` setelah webhook terverifikasi ATAU `fetchStatus` mengonfirmasi.
3. **Verifikasi signature wajib.** Midtrans: SHA512 dari
   `order_id + status_code + gross_amount + server_key`. Tolak yang tidak
   cocok, catat sebagai insiden keamanan.
4. **Webhook harus idempotent.** Provider mengirim ulang. Simpan
   `provider_event_id` dengan unique index.
5. **Jangan percaya nominal dari klien.** Hitung ulang total di server dari
   `order_items` sebelum membuat charge.
6. **Reconciliation job harian.** Cocokkan transaksi lokal vs settlement
   report provider. Selisih → alert ke owner.
7. **Timeout & expiry.** QRIS dinamis expired (mis. 15 menit). Order harus
   otomatis kembali ke `pending` dan bisa dibayar ulang/ganti metode.
8. **Mode offline.** Saat internet mati, QRIS dinamis tidak bisa dibuat.
   Kasir harus otomatis ditawari fallback ke `manual_qris` atau `cash`.
   Ini alur yang **wajib** dites.

### 3.4 Skema tabel

```sql
payment_providers        -- konfigurasi per outlet (kredensial terenkripsi)
  id, outlet_id, driver, is_active, is_default,
  credentials (encrypted json), settings json

payment_intents
  id, tenant_id, outlet_id, order_id, idempotency_key (unique),
  provider_driver, method, amount, currency, status,
  provider_ref, qr_string, va_number, deeplink,
  expires_at, paid_at, raw_response json

payment_events           -- log webhook mentah, anti-duplikat
  id, provider_driver, provider_event_id (unique), payload json,
  signature_valid bool, processed_at, payment_intent_id

payment_settlements      -- hasil rekonsiliasi harian
  id, outlet_id, provider_driver, settlement_date,
  gross_amount, mdr_amount, net_amount, tx_count,
  matched_count, unmatched_count, report_url
```

---

## 4. Alur Pembayaran (Sequence)

### QRIS dinamis — jalur bahagia

```
Kasir            App (RN)         API (Laravel)      Midtrans        Pelanggan
  │                 │                   │                │              │
  ├─ Bayar QRIS ───►│                   │                │              │
  │                 ├─ POST /payments ─►│                │              │
  │                 │   idempotency_key │                │              │
  │                 │                   ├─ charge() ────►│              │
  │                 │                   │◄── qr_string ──┤              │
  │                 │◄─ qr_string ──────┤                │              │
  │◄─ Tampil QR ────┤                   │                │              │
  │                 │                   │                │◄── scan ─────┤
  │                 │                   │◄── webhook ────┤              │
  │                 │                   ├─ verify sig    │              │
  │                 │                   ├─ order.paid    │              │
  │                 │                   ├─ potong stok   │              │
  │                 │◄── push/poll ─────┤                │              │
  │◄─ "LUNAS" ──────┤                   │                │              │
  │  cetak struk    │                   │                │              │
```

### Fallback saat offline

```
Kasir memilih QRIS → API tidak reachable
      │
      ├─► Aplikasi deteksi offline (< 3 detik timeout)
      ├─► Tampilkan: "Internet mati. Pakai QRIS tempel atau tunai?"
      ├─► Kasir pilih QRIS tempel → tampilkan gambar QRIS statis tersimpan
      ├─► Kasir konfirmasi manual + input nominal + (opsional) foto bukti
      ├─► Order tersimpan lokal, status paid, flag: verification=manual
      └─► Saat online: masuk antrean rekonsiliasi, muncul di
          "Perlu Diverifikasi" pada laporan tutup shift
```

---

## 5. Checklist Implementasi

- [ ] Interface `PaymentProvider` + registry driver
- [ ] Driver `cash`, `manual_qris`, `manual_transfer` (tanpa dependensi eksternal)
- [ ] Driver `midtrans` (Core API — QRIS, GoPay, ShopeePay, VA)
- [ ] Driver `duitku`, `tripay` (fase berikutnya)
- [ ] Endpoint webhook dengan verifikasi signature + anti-replay
- [ ] Tabel `payment_intents`, `payment_events`, `payment_settlements`
- [ ] Job rekonsiliasi harian + alert selisih via WhatsApp
- [ ] Laporan "Pendapatan Bersih Setelah MDR"
- [ ] UI setting pembayaran per outlet + peringatan kepatuhan MDR
- [ ] Alur fallback offline (uji dengan mematikan jaringan)
- [ ] Test suite: webhook duplikat, signature salah, race condition
      webhook-sebelum-respons-charge, expiry, refund parsial

---

## 6. Estimasi Biaya untuk Pengguna (Kalkulator In-App)

Untuk kedai dengan omzet Rp40 juta/bulan, 60% QRIS:

| Skenario | Biaya/bulan |
|---|---|
| Tier 0 — QRIS statis, merchant UMi, mayoritas transaksi < Rp500rb | **Rp0** |
| Tier 1 — Midtrans QRIS 0,7% atas Rp24 juta | **± Rp168.000** |
| Tier 1 — merchant UMi, transaksi < Rp500rb (0% MDR) | **± Rp0–50.000** |

> Tampilkan kalkulator ini di onboarding. Kejujuran soal biaya adalah bagian
> dari positioning anti-lock-in.

---

## Sumber

- [Mulai 1 Oktober 2026, Transaksi QRIS hingga Rp500.000 Bebas MDR — Kompas](https://www.kompas.com/tren/read/2026/08/19/110000365/mulai-1-oktober-2026-transaksi-qris-hingga-rp-500.000-bebas-mdr-ini)
- [Kupas Tuntas MDR dan Potongan QRIS 2026 Buat UMKM — Ezeelink](https://ezeelink.co.id/blog/potongan-qris-2026/)
- [Tarif MDR QRIS 2026, Cara Hitung Biar Untung — Pointru](https://www.pointru.com/2026/08/tarif-mdr-qris-2026-cara-hitung.html)
- [Biaya MDR QRIS 2026 untuk Merchant — GetQRIS](https://getqris.id/biaya-mdr-qris-2026-getqris/)
- [Ragam Tarif QRIS — Indonesia Baik](https://indonesiabaik.id/infografis/ragam-tarif-qris)
- [Biaya transaksi payment gateway — Midtrans](https://midtrans.com/id/biaya)
- [Perbandingan Payment Gateway Indonesia — iPaymu](https://ipaymu.com/id/price-comparison/)
- [Payment Gateway Indonesia 2026: Midtrans vs Xendit vs Doku vs Oy! — Panduan Usaha](https://panduanusaha.id/artikel/payment-gateway-indonesia-midtrans-xendit)
- [5 Payment Gateway Termurah Di Indonesia — LinkQu](https://www.linkqu.id/en/artikel/payment-gateway-termurah-di-indonesia/)
- [12 Daftar Payment Gateway Indonesia Terpopuler — LamanWP](https://lamanwp.com/payment-gateway-indonesia/)
- [10 Perbedaan QRIS Statis vs Dinamis — Pivot Payment](https://pivot-payment.com/blog/perbedaan-qris-statis-vs-dinamis/)
- [Integrasi API QRIS: Alur, Keamanan, dan Checklist Teknis — Ezeelink](https://ezeelink.co.id/blog/integrasi-api-qris/)
- [QRIS API: Pengertian, Cara Kerja, dan Manfaatnya — Ottodigital](https://ottodigital.id/artikel/qris-api/)
