# P21 — Billing & Subscription

**Fase:** F5 · **WP:** 5.7 · **Effort:** ± 2 pekan · **Prasyarat:** P01, P18, P19

---

## PERAN
Backend engineer yang membangun mesin pendapatan Posita sendiri. Ini bukan
fitur untuk pengguna — ini cara bisnisnya hidup.

## KONTEKS
`docs/50-gtm-pricing-legal.md` menjual tiga paket: Gratis, Pro Rp79.000, dan
Bisnis Rp179.000 per outlet per bulan. P01 sudah membuat kolom `tenants.plan`
dan `subscription_ends_at`. P19 sudah menegakkan feature flag dengan "batas
atas paket langganan".

Tapi **tidak ada yang membangun sistemnya**. P01 secara eksplisit menaruh
"Billing & langganan" di luar lingkup, dan tidak ada WP lain yang
mengambilnya. Tanpa WP ini, seluruh produk jalan sempurna tapi tidak ada cara
menagih siapa pun.

## TUJUAN
Sistem langganan yang bisa menagih, menerima pembayaran, menegakkan batas
paket, dan menangani gagal bayar — **tanpa pernah menyandera data pengguna.**

## RUANG LINGKUP
1. `plans` + `plan_limits` — definisi paket & batasnya
2. `subscriptions` — status langganan per tenant
3. `invoices` + `invoice_items` — tagihan
4. `subscription_payments` — **memakai ulang `PaymentProvider` dari P18**
5. `plan_changes` — upgrade/downgrade dengan proration
6. `usage_counters` — penghitung pemakaian untuk penegakan batas
7. `PlanLimitGuard` — penegakan batas, terhubung ke `FeatureFlagResolver` (P19)
8. Alur dunning: pengingat → jatuh tempo → masa tenggang → turun ke Gratis
9. Trial 14 hari paket Pro
10. UI: halaman langganan, riwayat tagihan, ganti paket, unduh invoice PDF

## DI LUAR LINGKUP
- ❌ Membangun payment gateway baru — **wajib pakai driver dari P18**
- ❌ Reseller / affiliate / kode promo bertingkat
- ❌ Akuntansi pendapatan (MRR, deferred revenue) — cukup laporan sederhana
- ❌ Penagihan berbasis pemakaian (metered billing)

---

## SPESIFIKASI

### 1. Skema

```sql
plans
  id, code (unique),                    -- 'free'|'pro'|'business'
  name, description,
  price_monthly, price_yearly,          -- bigint rupiah
  trial_days, is_active, is_public, sort_order,
  features json,                        -- feature flag ceiling (dibaca P19)
  limits json                           -- lihat 2

subscriptions
  id, tenant_id (unique), plan_id,
  status,                               -- 'trialing'|'active'|'past_due'
                                        -- |'grace'|'canceled'|'expired'
  billing_cycle,                        -- 'monthly'|'yearly'
  quantity,                             -- jumlah outlet berbayar
  unit_price, amount,
  current_period_start, current_period_end,
  trial_ends_at, grace_ends_at,
  canceled_at, cancel_at_period_end bool,
  next_invoice_at

invoices
  id, tenant_id, subscription_id,
  number (unique),                      -- INV-2609-0042
  period_start, period_end,
  subtotal, discount, tax, total,       -- tax = PPN atas jasa SaaS
  status,                               -- 'draft'|'open'|'paid'|'void'|'uncollectible'
  issued_at, due_at, paid_at,
  pdf_path, notes
invoice_items
  id, invoice_id, description,
  quantity, unit_price, amount,
  period_start, period_end,
  proration bool

subscription_payments
  id, tenant_id, invoice_id,
  payment_intent_id,                    -- FK ke payment_intents (P18)
  amount, status, paid_at, failure_reason

plan_changes
  id, tenant_id, from_plan_id, to_plan_id,
  from_quantity, to_quantity,
  type,                                 -- 'upgrade'|'downgrade'|'quantity'
  proration_amount, effective_at,
  applied_at, requested_by

usage_counters                          -- direset tiap periode
  id, tenant_id, metric, period_start, period_end,
  value, limit_value, exceeded_at
  UNIQUE(tenant_id, metric, period_start)
```

### 2. Struktur `limits`

```json
{
  "outlets": 1,
  "users": 3,
  "recipes": 30,
  "ingredients": 50,
  "partners": 3,
  "preorders_per_month": 10,
  "whatsapp_messages_per_month": 30,
  "menu_designs": 2,
  "data_retention_months": 3,
  "menu_exports_per_hour": 10
}
```

`null` = tak terbatas. Nilai ini adalah **batas atas** yang dibaca
`FeatureFlagResolver` (P19) — tenant tidak bisa menyalakan sesuatu di atas
paketnya.

### 3. `PlanLimitGuard`

```php
namespace App\Services\Billing;

final class PlanLimitGuard
{
    /** @throws PlanLimitExceededException */
    public function assertWithin(string $metric, int $increment = 1): void;

    public function check(string $metric, int $increment = 1): LimitCheck;
    public function usage(string $metric): int;
    public function limit(string $metric): ?int;
    public function remaining(string $metric): ?int;
}
```

Dipanggil sebelum membuat outlet, user, resep, mitra, dan sebelum mengirim
WhatsApp. Pesan errornya **wajib menyebut jalan keluarnya**:

> Paket Gratis dibatasi 30 resep. Kamu sudah punya 30. Naik ke paket Pro
> untuk resep tak terbatas, atau hapus resep yang tidak dipakai.

### 4. Alur dunning — dan aturan yang tidak boleh dilanggar

```
H-7   invoice dibuat, status 'open' → email + WA pengingat
H-3   pengingat kedua
H-0   jatuh tempo → status 'past_due'
H+1   percobaan tagih otomatis #1
H+3   percobaan tagih otomatis #2 + pengingat
H+7   percobaan tagih otomatis #3
H+7   masuk masa tenggang (grace) 7 hari, fitur berbayar masih hidup
H+14  turun otomatis ke paket Gratis
```

> **ATURAN MUTLAK: gagal bayar TIDAK PERNAH menghapus, mengunci, atau
> menyandera data tenant.**

Saat turun ke Gratis:
- Data **tetap utuh**, tidak ada yang dihapus
- Yang melebihi batas paket Gratis jadi **hanya-baca**, bukan hilang
  (mis. outlet ke-2 dan ke-3 tidak bisa transaksi, tapi datanya tetap ada
  dan bisa dilihat)
- **Ekspor data penuh tetap tersedia** — ini janji merek di
  `docs/50-gtm-pricing-legal.md`, dan berlaku tanpa syarat
- Naik paket lagi memulihkan semuanya seketika

Menyandera data pengguna yang menunggak akan menghancurkan seluruh
positioning anti-lock-in produk ini. Jangan pernah.

### 5. Proration

```
Upgrade di tengah periode:
  sisa_hari         = current_period_end - hari_ini
  kredit_paket_lama = harga_lama × (sisa_hari / total_hari)
  biaya_paket_baru  = harga_baru × (sisa_hari / total_hari)
  tagihan_proration = biaya_paket_baru - kredit_paket_lama
  → invoice langsung, berlaku seketika

Downgrade:
  → berlaku di akhir periode berjalan, tidak ada refund
  → jika pemakaian melebihi batas paket baru, peringatkan SEBELUM konfirmasi
```

Gunakan `Money::allocate()` untuk pembagian proration agar tidak ada rupiah
yang hilang.

### 6. Pembayaran

**Wajib memakai ulang `PaymentProvider` dari P18.** Jangan membangun
integrasi kedua.

```php
$intent = $this->payments->driver('midtrans')->createCharge(
    PaymentIntent::forInvoice($invoice)
);
```

Metode: QRIS, VA, kartu. Untuk pembayaran manual (transfer + upload bukti),
pakai driver `manual_transfer` dengan verifikasi admin.

### 7. PPN

Posita sebagai penyedia jasa SaaS dikenakan PPN atas biaya langganan bila
memenuhi ketentuan. Tarif harus **konfigurabel**, bukan hardcode. Invoice
menampilkan rincian: subtotal, PPN, total.

---

## ACCEPTANCE CRITERIA

- [ ] Tenant baru otomatis dapat trial Pro 14 hari
- [ ] Trial berakhir tanpa pembayaran → turun ke Gratis, data utuh
- [ ] Invoice tergenerate otomatis H-7 sebelum periode berikutnya
- [ ] Pembayaran lewat QRIS (driver P18) menandai invoice `paid` dan
      memperpanjang periode
- [ ] Upgrade di tengah periode menghitung proration dengan benar
- [ ] Downgrade berlaku di akhir periode, dengan peringatan jika melebihi batas
- [ ] `PlanLimitGuard` menolak pembuatan outlet ke-2 di paket Gratis, dengan
      pesan yang menyebut jalan keluarnya
- [ ] Kuota WhatsApp habis → pengiriman berhenti, tenant diberi tahu
- [ ] **Tenant menunggak tetap bisa mengekspor seluruh datanya**
- [ ] **Turun paket tidak menghapus satu baris pun data**
- [ ] Outlet melebihi batas jadi hanya-baca, bukan hilang
- [ ] Invoice PDF tergenerate dengan rincian PPN
- [ ] Webhook pembayaran idempoten (mewarisi jaminan P18)
- [ ] Isolasi tenant berlaku di semua endpoint billing

---

## TESTING WAJIB

```php
// tests/Unit/Billing/ProrationTest.php
it('computes upgrade proration for a mid-cycle change');
it('never loses a rupiah in proration')->with(/* 1000 kasus acak */);
it('defers downgrade to the end of the period');

// tests/Feature/Billing/DunningTest.php
it('generates an invoice seven days before renewal');
it('moves the subscription to past_due after the due date');
it('enters grace before downgrading');
it('downgrades to free after the grace period');
it('never deletes tenant data on downgrade');
it('keeps over-limit outlets readable but not transactable');
it('still allows full data export while past_due');

// tests/Feature/Billing/PlanLimitTest.php
it('blocks creating a second outlet on the free plan');
it('returns an actionable Indonesian message when a limit is hit');
it('stops WhatsApp sending when the monthly quota is exhausted');
it('resets usage counters at the period boundary');

// tests/Feature/Billing/PaymentTest.php
it('reuses the P18 payment driver');
it('marks the invoice paid and extends the period on webhook');
it('is idempotent for a duplicate webhook');
```

---

## PERINTAH
```bash
vendor/bin/pint && php artisan test --filter=Billing
php artisan posita:billing:generate-invoices --dry-run
php artisan posita:billing:run-dunning --dry-run
```

---

## CATATAN

⚠️ **Jangan pernah menyandera data.** Ini bukan pilihan desain, ini janji
merek. Produk yang menjual "datamu bisa dibawa pulang kapan saja" lalu
mengunci data saat telat bayar adalah produk yang berbohong.

⚠️ **Pakai ulang P18.** Dua tumpukan pembayaran di satu codebase berarti dua
kali permukaan bug, dua kali rekonsiliasi, dua kali audit.

⚠️ **Pesan batas paket adalah momen penjualan.** "Batas tercapai" itu buntu.
"Paket Gratis dibatasi 30 resep — naik ke Pro untuk tak terbatas" itu
tawaran. Tulis semuanya seperti yang kedua.

⚠️ **Uji perpindahan waktu.** Bug billing paling sering muncul di batas
periode, tahun kabisat, dan zona waktu. Semua perhitungan periode memakai
`Asia/Jakarta`, bukan UTC.
