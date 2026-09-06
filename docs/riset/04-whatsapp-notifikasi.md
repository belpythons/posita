# 04 — Notifikasi WhatsApp Otomatis

Tujuan: merancang sistem peringatan proaktif yang mengirim informasi
operasional penting ke WhatsApp owner/staf/mitra — terutama **stok menipis**,
**tutup shift**, dan **settlement mitra**.

> **Perlu verifikasi:** harga layanan WhatsApp gateway berubah cepat.
> Snapshot riset September 2026.

---

## 1. Kenapa WhatsApp, Bukan Email atau Push Notification

Pemilik kedai kopi Indonesia tidak membuka dashboard setiap hari. Mereka
membuka WhatsApp puluhan kali sehari. Serah terima shift, laporan kas, dan
komunikasi dengan supplier sudah terjadi di WhatsApp — dalam bentuk foto
buku tulis dan pesan teks yang tidak terstruktur.

Posita tidak perlu memindahkan mereka. Cukup **menggantikan foto buku tulis
dengan pesan terstruktur yang dikirim otomatis**.

---

## 2. Pilihan Teknologi

### 2.1 Official — WhatsApp Cloud API (Meta)

| Aspek | Detail |
|---|---|
| Legalitas | Resmi, didukung Meta |
| Risiko banned | **Tidak ada** (jika patuh kebijakan) |
| Verified badge | Bisa (centang hijau) |
| Format pesan | Wajib **template** yang disetujui Meta untuk pesan proaktif |
| Biaya | Per-percakapan, berbeda per kategori (utility / marketing / authentication / service) |
| Onboarding | Perlu Meta Business verification, nomor khusus, BSP atau langsung |
| Waktu setup | Hari–minggu |

**Cocok untuk:** pesan ke **pelanggan** (konfirmasi pesanan, pengingat
pickup box order, promo). Volume besar, butuh kepercayaan merek.

### 2.2 Unofficial — Gateway Lokal

Layanan yang memakai WhatsApp Web otomatis; nomor biasa di-scan lewat QR.

| Penyedia | Harga (indikatif) | Model |
|---|---|---|
| **Fonnte** | Entry ~Rp25.000 / 1.000 pesan • Paket **Master ~Rp175.000/bln unlimited** | Kuota / unlimited |
| **Wablas** | Rp22.000 – Rp139.000/bln | Berbasis fitur, bukan kuota |
| **Watzap.id** | Rp449.000 – Rp649.000/**tahun** unlimited | Tahunan |
| **LoginWA** | ~$1 / 1.000 pesan di entry level | Kuota |

| Aspek | Detail |
|---|---|
| Legalitas | Di luar ToS WhatsApp |
| Risiko banned | **Nyata & permanen.** Perlu fitur anti-ban (jeda acak, batas laju) |
| Biaya | Flat bulanan, jauh lebih murah untuk volume kecil–menengah |
| Onboarding | Menit — scan QR |

**Cocok untuk:** pesan **internal** ke owner & staf sendiri (alert stok,
rekap shift). Volume rendah, penerima adalah orang yang sudah kenal
pengirim, risiko dianggap spam minimal.

### 2.3 Rekomendasi: Strategi Hibrida + Driver Abstraction

```
┌─────────────────────────────────────────────────────────┐
│                  NotificationEngine                     │
│         (rules, dedup, quiet hours, rate limit)         │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┬──────────────┐
         ▼               ▼               ▼              ▼
   WaCloudDriver    FonnteDriver   WablasDriver    LogDriver
   (pelanggan)      (internal)     (alternatif)    (dev/test)
```

| Jenis penerima | Driver default | Alasan |
|---|---|---|
| Owner & staf sendiri | Fonnte / Wablas | Murah, cepat setup, volume kecil |
| Mitra konsinyasi | Fonnte / Wablas | Relasi bisnis eksisting |
| Pelanggan akhir | WhatsApp Cloud API | Compliance & kepercayaan merek |
| Development | Log driver | Tidak mengirim apapun, ditulis ke log |

**Tenant bebas memilih drivernya sendiri, termasuk memasukkan kredensial
Fonnte/Wablas milik mereka.** Ini penting: menghindari Posita menanggung
biaya pengiriman, sekaligus bagian dari janji "full customable".

---

## 3. Katalog Peringatan (Alert Catalog)

Ini spesifikasi lengkap peringatan yang harus dibangun.

### 3.1 Stok & Inventory

| Kode | Pemicu | Penerima | Prioritas |
|---|---|---|---|
| `STOCK_LOW` | Stok ≤ reorder point | Owner, Manajer | Tinggi |
| `STOCK_CRITICAL` | Stok ≤ safety stock (bisa habis hari ini) | Owner, Manajer, Barista | Kritis |
| `STOCK_OUT` | Stok = 0 pada bahan aktif | Owner, Manajer | Kritis |
| `STOCK_EXPIRING` | Batch kedaluwarsa dalam ≤ 3 hari | Manajer | Sedang |
| `STOCK_VARIANCE_HIGH` | Variance aktual vs teoretis > ambang (default 5%) | Owner | Tinggi |
| `RESTOCK_RECEIVED` | Penerimaan barang dicatat | Owner | Rendah |

**Contoh pesan `STOCK_CRITICAL`:**

```
🔴 STOK KRITIS — Kopi Senja (Cabang Utama)

• Susu UHT Full Cream    : 2,1 L  (cukup ± 0,4 hari)
• Sirup Vanilla          : 180 ml (cukup ± 0,7 hari)
• Gelas 16oz             : 24 pcs (cukup ± 0,3 hari)

Pemakaian rata-rata 7 hari terakhir jadi dasar hitungan.
Estimasi belanja: Rp 385.000

Catat restock ▸ posita.app/r/8Kx2m
```

### 3.2 Shift & Kas

| Kode | Pemicu | Penerima |
|---|---|---|
| `SHIFT_OPENED` | Shift dibuka | Owner (opsional) |
| `SHIFT_CLOSED` | Shift ditutup | Owner |
| `CASH_VARIANCE` | Selisih kas > ambang (default Rp10.000) | Owner |
| `SHIFT_NOT_CLOSED` | Shift belum ditutup > X jam setelah jam tutup | Owner, Manajer |
| `VOID_SPIKE` | Void/diskon manual melebihi ambang dalam 1 shift | Owner |

**Contoh pesan `SHIFT_CLOSED`:**

```
📊 TUTUP SHIFT — Kopi Senja
Sore • 14:00–21:30 • Kasir: Rina

Penjualan       Rp 3.842.000  (127 transaksi)
├ Tunai         Rp 1.210.000
├ QRIS          Rp 2.502.000
└ Kasbon        Rp   130.000

Kas laci
├ Modal awal    Rp   500.000
├ Sistem        Rp 1.710.000
├ Fisik         Rp 1.703.000
└ Selisih       Rp    -7.000  ⚠️

HPP hari ini    Rp 1.191.000  (31,0%)  ✅ target ≤32%
Laba kotor      Rp 2.651.000

Top 3: Es Kopi Susu 41 · Americano 22 · Croissant 14
Void: 2 transaksi (Rp48.000) — disetujui Rina

Detail ▸ posita.app/s/7Qn4p
```

### 3.3 Mitra / Konsinyasi

| Kode | Pemicu | Penerima |
|---|---|---|
| `CONSIGNMENT_DAILY` | Rekap harian titipan mitra | Mitra |
| `SETTLEMENT_DUE` | Siklus bayar mitra jatuh tempo | Owner |
| `SETTLEMENT_PAID` | Pembayaran ke mitra dicatat | Mitra |
| `CONSIGNMENT_SLOW` | Produk mitra tidak laku > X hari | Owner, Mitra |

**Contoh pesan `CONSIGNMENT_DAILY` (ke mitra):**

```
📦 Laporan Titipan — 6 September 2026
Bu Sari Bakery @ Kopi Senja

Produk              Titip  Laku  Sisa   Pendapatan
Roti Coklat            20    17     3   Rp 170.000
Donat Gula             15    15     0   Rp 105.000
Bolu Pandan            10     4     6   Rp  48.000

Total laku                  36        Rp 323.000
Bagian toko (20%)                     Rp  64.600
Hak Anda                              Rp 258.400

Sisa 9 pcs siap diambil.
Pembayaran periode ini: Jumat, 12 Sep 2026

Laporan lengkap ▸ posita.app/m/3Vb9t
```

### 3.4 Penjualan & Profitabilitas

| Kode | Pemicu | Penerima |
|---|---|---|
| `DAILY_RECAP` | Ringkasan harian pada jam tertentu | Owner |
| `WEEKLY_RECAP` | Ringkasan mingguan + tren | Owner |
| `SALES_DROP` | Penjualan hari ini < X% dari rata-rata 4 minggu | Owner |
| `FOOD_COST_HIGH` | Food cost % melewati target | Owner |
| `TARGET_REACHED` | Target harian tercapai | Owner, Staf (motivasi) |

### 3.5 Pesanan & Pelanggan

| Kode | Pemicu | Penerima |
|---|---|---|
| `BOX_ORDER_CREATED` | Pesanan box baru | Owner, Manajer |
| `BOX_ORDER_REMINDER` | H-1 sebelum pickup | Pelanggan, Manajer |
| `BOX_ORDER_DP_DUE` | DP belum dibayar mendekati tenggat | Pelanggan |
| `RECEIPT_DIGITAL` | Struk digital pasca-transaksi | Pelanggan (opt-in) |

### 3.6 Karyawan

| Kode | Pemicu | Penerima |
|---|---|---|
| `ATTENDANCE_LATE` | Terlambat > X menit | Manajer |
| `ATTENDANCE_MISSING` | Belum absen padahal jadwal sudah mulai | Manajer, Karyawan |
| `NO_CLOCKOUT` | Lupa absen pulang | Karyawan, Manajer |

---

## 4. Desain Engine Notifikasi

### 4.1 Skema tabel

```sql
notification_channels
  id, tenant_id, driver ('fonnte'|'wablas'|'wa_cloud'|'log'),
  name, credentials (encrypted json), is_active, is_default,
  daily_quota, sent_today, last_reset_at

notification_rules
  id, tenant_id, outlet_id (nullable = semua outlet),
  event_code ('STOCK_LOW'|...),
  is_enabled, channel_id,
  conditions json,      -- {"threshold_percent":5,"min_amount":10000}
  recipients json,      -- [{"type":"role","value":"owner"},{"type":"phone","value":"628..."}]
  schedule json,        -- {"mode":"immediate"} | {"mode":"cron","expr":"0 22 * * *"}
  quiet_hours json,     -- {"from":"22:30","to":"06:30","timezone":"Asia/Jakarta"}
  cooldown_minutes,     -- anti-spam per event+entity
  template_id

notification_templates
  id, tenant_id (nullable = template bawaan), event_code,
  locale, body (dengan variabel {{...}}), is_default

notification_logs
  id, tenant_id, rule_id, event_code, channel_id,
  recipient_phone, rendered_body, status,
  provider_message_id, error, dedup_key, sent_at, delivered_at
```

### 4.2 Aturan wajib

1. **Deduplikasi.** `dedup_key = hash(event_code + entity_id + window)`.
   Alert "Susu menipis" tidak boleh dikirim tiap 5 menit. Default cooldown
   6 jam per bahan.
2. **Quiet hours.** Jangan kirim jam 2 pagi. Antre sampai jam buka, kecuali
   prioritas kritis.
3. **Rate limit per channel.** Terutama untuk gateway unofficial — jeda acak
   3–10 detik antar pesan mengurangi risiko banned.
4. **Semua lewat queue.** Tidak ada pengiriman WA di request cycle. Gunakan
   `ShouldQueue` + retry dengan exponential backoff.
5. **Batching.** Kumpulkan beberapa bahan menipis jadi **satu** pesan, bukan
   satu pesan per bahan.
6. **Kill switch.** Toggle global per tenant dan per rule. Wajib ada — kalau
   spam, pengguna akan uninstall.
7. **Template dapat dikustom user.** Bagian dari "full customable". Editor
   dengan daftar variabel yang tersedia + tombol pratinjau.
8. **Tautan pendek.** Setiap alert menyertakan deep link ke halaman terkait
   (magic link tertanda, kedaluwarsa 24 jam).
9. **Fallback.** Jika driver gagal 3×, catat kegagalan dan tampilkan lonceng
   in-app. Jangan gagal diam-diam.
10. **Kepatuhan.** Nomor penerima harus opt-in tercatat. Untuk pelanggan,
    wajib consent eksplisit (UU PDP).

### 4.3 Alur eksekusi

```
Domain Event (StockLevelChanged, ShiftClosed, ...)
        │
        ▼
  RuleEvaluator  ──► apakah ada rule aktif untuk event ini?
        │                 │ tidak → berhenti
        ▼ ya
  ConditionMatcher ──► apakah kondisi terpenuhi? (threshold dll)
        │                 │ tidak → berhenti
        ▼ ya
  DedupGuard ──► sudah pernah dikirim dalam window cooldown?
        │                 │ ya → berhenti, catat 'suppressed'
        ▼ tidak
  RecipientResolver ──► role → daftar nomor telepon
        │
        ▼
  TemplateRenderer ──► render body dengan data
        │
        ▼
  QuietHoursGate ──► di luar jam? → jadwalkan; kritis? → kirim
        │
        ▼
  SendWhatsAppJob (queued, retry 3×, backoff)
        │
        ▼
  Driver.send() ──► catat ke notification_logs
```

---

## 5. Contoh Implementasi Driver

```php
// app/Services/Notification/Drivers/FonnteDriver.php
final class FonnteDriver implements WhatsAppDriver
{
    public function __construct(
        private readonly string $token,
        private readonly HttpClient $http,
    ) {}

    public function send(WhatsAppMessage $message): SendResult
    {
        $response = $this->http
            ->withHeaders(['Authorization' => $this->token])
            ->asForm()
            ->timeout(15)
            ->retry(2, 1000)
            ->post('https://api.fonnte.com/send', [
                'target'      => $this->normalizePhone($message->to),
                'message'     => $message->body,
                'countryCode' => '62',
                'delay'       => '3-10',   // jeda acak, anti-ban
            ]);

        return $response->successful() && ($response->json('status') === true)
            ? SendResult::ok($response->json('id.0'))
            : SendResult::failed($response->json('reason') ?? $response->body());
    }

    /** 08xx / +62xx / 62xx → 62xx */
    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        return str_starts_with($digits, '0')  ? '62' . substr($digits, 1)
             : (str_starts_with($digits, '62') ? $digits : '62' . $digits);
    }
}
```

---

## 6. Checklist Implementasi

- [ ] Interface `WhatsAppDriver` + registry
- [ ] Driver: `log` (dev), `fonnte`, `wablas`, `wa_cloud`
- [ ] Tabel channels / rules / templates / logs
- [ ] `NotificationEngine` dengan dedup, quiet hours, rate limit, batching
- [ ] Domain event untuk setiap kode alert di katalog
- [ ] Katalog alert bawaan (di-seed saat tenant dibuat)
- [ ] UI: aktif/nonaktif per alert, atur ambang, atur penerima, edit template
- [ ] Editor template dengan pratinjau + daftar variabel
- [ ] Kill switch global per tenant
- [ ] Halaman riwayat notifikasi (terkirim / gagal / ditahan)
- [ ] Magic link pendek bertanda-tangan untuk deep link
- [ ] Kuota harian per channel + peringatan saat mendekati batas
- [ ] Uji: dedup, quiet hours, retry, format nomor, driver gagal

---

## Sumber

- [15 WhatsApp API Terbaik Indonesia 2026 — ChakraHQ](https://chakrahq.com/article/solusi-whatsapp-api-terbaik-indonesia-2026/)
- [Fonnte vs LoginWA vs Wablas: Perbandingan 2026 — LoginWA](https://api.loginwa.com/blog/fonnte-vs-loginwa-vs-wablas)
- [WhatsApp Gateway Murah 2026: Bandingkan Harga & Fitur — LoginWA](https://loginwa.com/blog/whatsapp-gateway-murah-2026)
- [Unofficial WhatsApp API Gateway Indonesia — Fonnte](https://fonnte.com/)
- [Official WhatsApp API (WABA) — Api.co.id](https://api.co.id/whatsapp-api-gateway/)
- [WhatsApp API Pricing: Cara Hitung Biaya per Kategori Pesan — Api.co.id](https://api.co.id/blog/whatsapp-api-pricing/)
