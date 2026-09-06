# P22 — Customer & Piutang (Kasbon)

**Fase:** F1 · **WP:** 1.7 · **Effort:** ± 1 pekan · **Prasyarat:** P08

---

## PERAN
Backend + frontend engineer yang memahami bahwa "kasbon" adalah praktik nyata
warung Indonesia, bukan fitur enterprise.

## KONTEKS
Blueprint merujuk `orders.customer_id`, `preorders.customer_id`, dan
`customer_credits` — tapi **tidak ada WP yang membuat entitas pelanggannya**.

Riset `docs/riset/01-pasar-fnb-indonesia.md` mencatat kasbon sebagai kebutuhan
nyata: *"Bayar nanti / kasbon — umum untuk pelanggan langganan & karyawan"*.
Tanpa modul ini, kasbon dicatat di buku tulis dan sering tidak tertagih.

## TUJUAN
Entitas pelanggan yang ringan, plus pencatatan piutang yang membuat kasbon
tertagih — tanpa membebani kasir dengan form panjang.

## RUANG LINGKUP
1. `customers` + `customer_groups`
2. `customer_credits` — kasbon/piutang
3. `credit_payments` — pelunasan (sebagian atau penuh)
4. Quick-add pelanggan di kasir (nama + telepon saja)
5. Limit kasbon per pelanggan + peringatan/blokir
6. Riwayat belanja pelanggan
7. Integrasi ke shift report: kasbon **bukan** kas di laci
8. Event untuk pengingat jatuh tempo (dikirim P14)
9. Kepatuhan UU PDP: consent pemasaran, hak hapus

## DI LUAR LINGKUP
- ❌ Loyalty point / membership berjenjang (feature flag `loyalty`, default mati)
- ❌ Kartu member fisik / NFC
- ❌ Kampanye pemasaran & segmentasi
- ❌ Deposit prabayar pelanggan

---

## SPESIFIKASI

### 1. Skema

```sql
customer_groups
  id, tenant_id, name,                  -- 'Umum'|'Langganan'|'Karyawan'|'Reseller'
  price_tier_id (nullable),             -- harga khusus (P04)
  default_discount_percent,
  credit_limit,                         -- batas kasbon default grup
  is_active

customers
  id, tenant_id, code,
  name, phone, email, address, birthday,
  customer_group_id (nullable),
  credit_limit (nullable),              -- override grup
  notes, tags json, custom_fields json,
  marketing_consent bool, marketing_consent_at,
  total_spent, visit_count, last_visit_at,   -- di-cache, dihitung dari orders
  is_active
  INDEX (tenant_id, phone)              -- BUKAN unique, lihat catatan

customer_credits                        -- kasbon
  id, tenant_id, outlet_id, customer_id,
  order_id (nullable),                  -- null = kasbon manual
  code, amount, paid_amount, remaining_amount,
  due_date,
  status,                               -- 'open'|'partial'|'paid'|'written_off'
  notes, created_by, written_off_by, written_off_reason

credit_payments
  id, tenant_id, customer_credit_id, shift_id,
  amount, method,                       -- 'cash'|'qris'|'transfer'
  payment_id (nullable),                -- FK ke payments (P08)
  paid_at, received_by, notes, proof_path
```

### 2. Quick-add di kasir — desain kritis

Kasir tidak akan mengisi form 8 field saat ada antrean. Alurnya:

```
Layar bayar → [ Kasbon ]
  ┌──────────────────────────────────────┐
  │ 🔍 Cari nama / nomor HP              │  ← cari dulu, bukan bikin baru
  │                                      │
  │ Bu Ani        0812-3456-7890         │
  │   Kasbon aktif Rp 45.000 · limit 200rb│
  │ Pak Budi      0813-1111-2222         │
  │   Lunas                              │
  │                                      │
  │ [ + Pelanggan Baru ]                 │
  └──────────────────────────────────────┘

Pelanggan baru = HANYA dua field:
  Nama:    [ Bu Ani        ]
  No. HP:  [ 0812...       ]   ← opsional
  [ SIMPAN & KASBON ]
```

Sisanya (alamat, ulang tahun, grup) diisi belakangan di back office kalau
perlu. **Maksimal 3 ketukan** dari layar bayar sampai kasbon tercatat.

### 3. Limit kasbon

```php
// setelan outlet: customer.credit_mode = 'off' | 'warn' | 'block'
$outstanding = $customer->credits()->whereIn('status', ['open','partial'])->sum('remaining_amount');
$limit = $customer->credit_limit ?? $customer->group?->credit_limit;

// 'warn'  → tampilkan peringatan, kasir boleh lanjut (default)
// 'block' → tolak, butuh izin supervisor untuk override
```

Default **`warn`, bukan `block`**. Alasan yang sama dengan stok negatif di
P05: memblokir transaksi di depan pelanggan langganan akan merusak hubungan
pemilik warung dengan pelanggannya, dan mereka akan berhenti memakai
aplikasinya.

### 4. Kasbon bukan kas — aturan akuntansi yang wajib benar

Ini kesalahan paling umum di POS UMKM.

```
Order Rp 50.000 dibayar kasbon:
  ✅ orders.total          = 50.000   (penjualan tercatat)
  ✅ payments.method       = 'credit'
  ❌ TIDAK menambah kas laci
  ✅ customer_credits      = 50.000 (piutang)

Saat dilunasi tunai di kemudian hari:
  ✅ credit_payments       = 50.000
  ✅ menambah kas laci di shift SAAT PELUNASAN
  ❌ TIDAK membuat order baru (penjualannya sudah tercatat dulu)
```

**Konsekuensi ke P09:** `expected_cash` shift harus mengurangi penjualan
kasbon dan menambah pelunasan kasbon. Laporan shift wajib menampilkan
keduanya sebagai baris terpisah.

### 5. Pengingat jatuh tempo

Event yang di-dispatch untuk dikonsumsi P14:

| Kode | Pemicu | Penerima |
|---|---|---|
| `CREDIT_DUE_SOON` | H-1 sebelum jatuh tempo | Pelanggan, Owner |
| `CREDIT_OVERDUE` | Lewat jatuh tempo | Pelanggan, Owner |
| `CREDIT_LIMIT_REACHED` | Kasbon mencapai limit | Owner |

Pesan ke pelanggan harus sopan dan tidak mempermalukan — ini tetangga dan
pelanggan langganan pemilik warung, bukan debitur bank.

### 6. UU PDP

- Nomor telepon & nama = data pribadi.
- `marketing_consent` **terpisah** dari pencatatan transaksi. Mencatat kasbon
  tidak butuh consent pemasaran; mengirim promo butuh.
- Hak hapus: data pribadi dihapus, riwayat transaksi dipertahankan dalam
  bentuk anonim (`customer_id` → null, nama → "Pelanggan Umum") demi
  integritas pembukuan.

---

## ACCEPTANCE CRITERIA

- [ ] Quick-add pelanggan di kasir ≤ 3 ketukan, hanya nama + telepon
- [ ] Pencarian pelanggan menemukan berdasarkan nama **dan** nomor HP parsial
- [ ] Transaksi kasbon tercatat sebagai penjualan **tanpa** menambah kas laci
- [ ] Pelunasan kasbon menambah kas laci di shift saat pelunasan
- [ ] Laporan shift (P09) menampilkan penjualan kasbon dan pelunasan kasbon
      sebagai baris terpisah, dan `expected_cash` tetap benar
- [ ] Mode `warn` memperingatkan tapi tidak memblokir saat limit terlampaui
- [ ] Mode `block` menolak, dan supervisor bisa override
- [ ] Pelunasan sebagian menghitung `remaining_amount` dengan benar
- [ ] Riwayat belanja pelanggan menampilkan order-nya
- [ ] Event `CREDIT_DUE_SOON` dan `CREDIT_OVERDUE` ter-dispatch tepat waktu
- [ ] Hak hapus menganonimkan data pribadi tanpa merusak pembukuan
- [ ] Dua pelanggan boleh punya nomor HP sama (satu keluarga)
- [ ] Isolasi tenant berlaku

---

## TESTING WAJIB

```php
// tests/Feature/Customer/CreditTest.php
it('records a credit sale without increasing drawer cash');
it('increases drawer cash on credit repayment in the repaying shift');
it('does not create a new order on repayment');
it('computes remaining amount on partial repayment');
it('warns but allows when over the credit limit in warn mode');
it('blocks when over the credit limit in block mode');
it('allows supervisor override in block mode');

// tests/Feature/Customer/ShiftIntegrationTest.php
it('keeps expected_cash correct with credit sales and repayments');
it('lists credit sales and repayments separately in the shift report');

// tests/Feature/Customer/CustomerTest.php
it('allows two customers to share a phone number');
it('finds customers by partial phone number');
it('anonymizes personal data on deletion while keeping order history');
it('separates marketing consent from transaction recording');
it('isolates customers between tenants');
```

---

## PERINTAH
```bash
vendor/bin/pint && php artisan test --filter="Customer|Credit"
```

---

## CATATAN

⚠️ **Nomor HP bukan unique key.** Satu keluarga sering pakai satu nomor.
Memaksa unique akan membuat kasir tidak bisa mencatat pelanggan kedua dan
mereka akan berhenti memakai fiturnya.

⚠️ **Kasbon yang salah dicatat sebagai kas akan merusak rekonsiliasi shift
setiap hari.** Ini titik integrasi paling penting di WP ini — uji bersama
P09, bukan sendirian.

⚠️ **Jangan bangun loyalty di sini.** Feature flag `loyalty` sudah ada di P19
dengan default mati. Menambahkannya "sekalian" akan menggandakan lingkup WP
ini.

⚠️ **Nada pesan penagihan itu penting.** Pelanggan kasbon adalah tetangga
pemilik warung. Pesan yang terasa seperti debt collector akan membuat pemilik
mematikan fiturnya.
