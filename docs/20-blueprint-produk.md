# 20 — Blueprint Produk & Domain Model

Spesifikasi lengkap Posita sebagai produk POS F&B yang fleksibel dan
customable, mencakup 14 modul, model data, aturan bisnis, dan engine
kustomisasi.

---

## 1. Prinsip Desain Produk

1. **Default masuk akal, semua bisa diubah.** Pengguna baru bisa jualan
   dalam 5 menit tanpa setting apapun. Pengguna mahir bisa mengubah hampir
   semuanya.
2. **Kompleksitas bertahap.** Fitur lanjutan (resep, mitra, absensi) tidak
   terlihat sampai diaktifkan. Tidak ada UI yang menakuti pengguna baru.
3. **Satu sumber kebenaran.** Harga menu di kasir, di menu cetak, di menu
   QR, dan di GoFood berasal dari satu tempat.
4. **Setiap angka bisa ditelusuri.** Setiap saldo stok, setiap rupiah,
   punya jejak audit sampai transaksi asalnya.
5. **Gagal dengan jujur.** Kalau offline, bilang offline. Kalau sinkronisasi
   gagal, tampilkan. Tidak ada kegagalan senyap.
6. **Data milik pengguna.** Ekspor penuh kapan saja, tanpa syarat.

---

## 2. Peta Modul

```
┌────────────────────────────────────────────────────────────────┐
│  M0  PLATFORM       Tenant · Outlet · User · RBAC · Settings   │
├────────────────────────────────────────────────────────────────┤
│  M1  CATALOG        Kategori · Produk · Varian · Modifier      │
│  M2  INVENTORY      Bahan · Satuan · Ledger · Opname · Waste   │
│  M3  RECIPE         BOM · Yield · HPP · Variance               │
│  M4  PROCUREMENT    Supplier · PO · Penerimaan (Restock)       │
├────────────────────────────────────────────────────────────────┤
│  M5  SALES          Keranjang · Order · Diskon · Pajak · Refund│
│  M6  PAYMENT        Driver · QRIS · Intent · Rekonsiliasi      │
│  M7  CASH & SHIFT   Sesi · Kas masuk/keluar · Selisih · Rekap  │
├────────────────────────────────────────────────────────────────┤
│  M8  PARTNER        Mitra · Batch titipan · Bagi hasil · Settle│
│  M9  ORDER AHEAD    Box order · Catering · DP · Jadwal produksi│
├────────────────────────────────────────────────────────────────┤
│  M10 HR             Karyawan · Absensi · Jadwal · Payroll-lite │
│  M11 FINANCE        Pengeluaran · Kategori · Arus kas · L/R    │
├────────────────────────────────────────────────────────────────┤
│  M12 NOTIFICATION   Rules · Template · Channel WA · Log        │
│  M13 MENU DESIGN    Template desain · Ekspor · Menu QR         │
│  M14 REPORTING      Dashboard · Variance · ABC · Ekspor data   │
├────────────────────────────────────────────────────────────────┤
│  MX  CUSTOMIZATION  Feature flag · Preset · Custom field ·     │
│                     Template struk · Branding                  │
└────────────────────────────────────────────────────────────────┘
```

---

## 3. M0 — Platform

### 3.1 Model data

```
tenants
  id, name, slug (unique), business_type, plan,
  owner_user_id, phone, email, timezone (default 'Asia/Jakarta'),
  currency (default 'IDR'), locale (default 'id'),
  trial_ends_at, subscription_ends_at, is_active,
  settings json, branding json, feature_flags json,
  created_at, updated_at, deleted_at

outlets
  id, tenant_id, name, code (unique per tenant),
  address, phone, latitude, longitude, geofence_radius_m,
  timezone, is_active,
  settings json,      -- override setting tenant
  operating_hours json,
  created_at, updated_at, deleted_at

users
  id, tenant_id (nullable untuk super admin), name, email (unique),
  phone, password, pin (hashed, untuk login cepat kasir),
  profile_photo_path, is_active, last_login_at,
  preferences json, created_at, updated_at, deleted_at

outlet_user               -- user bisa ditugaskan ke beberapa outlet
  outlet_id, user_id, is_primary

roles                     -- spatie/laravel-permission, scoped tenant
  id, tenant_id (nullable = role bawaan), name, guard_name, is_system
permissions
  id, name, group
role_has_permissions, model_has_roles
```

### 3.2 Role bawaan & izin

| Role | Cakupan | Izin utama |
|---|---|---|
| `owner` | Tenant | Semua, termasuk billing & hapus data |
| `manager` | Outlet | Semua operasional, tidak bisa billing/hapus tenant |
| `supervisor` | Outlet | Approve void/diskon, tutup shift, opname |
| `cashier` | Outlet | Jual, buka/tutup shift sendiri, lihat laporan sendiri |
| `barista` | Outlet | Lihat pesanan, tandai selesai, catat waste |
| `staff` | Outlet | Absensi, lihat jadwal |
| `partner` | Portal terbatas | Lihat laporan titipan & settlement miliknya sendiri |

**Semua role bisa diduplikat & dimodifikasi** — bagian dari janji
kustomisasi. Role bawaan (`is_system = true`) tidak bisa dihapus, tapi bisa
di-clone.

### 3.3 Izin granular (contoh)

```
sales.create · sales.void · sales.refund · sales.discount.manual
sales.discount.above_limit · sales.price.override
shift.open · shift.close · shift.close.others · shift.reopen
inventory.view · inventory.adjust · inventory.opname · inventory.waste
recipe.view · recipe.edit · recipe.cost.view
partner.manage · partner.settle
report.view.own · report.view.outlet · report.view.tenant
report.export · data.export.full
hr.attendance.view · hr.attendance.edit · hr.payroll.view
finance.expense.create · finance.expense.approve
settings.outlet · settings.tenant · settings.notification
```

### 3.4 Aturan

- `tenant_id` **selalu diisi dari konteks terautentikasi**, tidak pernah
  dari request body.
- Global scope `BelongsToTenant` diterapkan di base model. Setiap model
  tenant-scoped wajib memakainya.
- Middleware `EnsureTenantContext` menyetel konteks di awal request dan
  membersihkannya di akhir job queue.
- Test wajib: user tenant A tidak bisa membaca/menulis data tenant B lewat
  jalur manapun (route model binding, filter, ekspor, laporan).

---

## 4. M1 — Catalog

### 4.1 Model data

```
categories
  id, tenant_id, parent_id, name, slug, color, icon,
  sort_order, is_active, show_in_menu, image_path

products
  id, tenant_id, category_id,
  name, sku, description, image_path,
  type ('simple'|'variant'|'bundle'|'consignment'|'open'),
  ownership ('own'|'partner'),         -- ← kunci mitra vs non-mitra
  partner_id (nullable),
  track_stock bool,                    -- produk jadi yang stoknya dilacak
  base_price, cost_price_manual (nullable),
  tax_inclusive bool, tax_group_id,
  is_active, is_favorite, sort_order,
  available_channels json,             -- ['pos','online','gofood',...]
  available_hours json,                -- mis. menu sarapan 07-11
  custom_fields json,
  created_at, updated_at, deleted_at

product_variants                        -- ukuran, jenis susu, hot/ice
  id, tenant_id, product_id, name, sku,
  price_adjustment, price_absolute (nullable),
  is_default, sort_order, is_active

modifier_groups                          -- "Level Gula", "Topping"
  id, tenant_id, name,
  selection_type ('single'|'multiple'),
  min_select, max_select, is_required, sort_order

modifiers                                -- "Less Sugar", "Extra Shot"
  id, tenant_id, modifier_group_id, name,
  price_adjustment, is_default, is_active, sort_order,
  max_quantity

product_modifier_group                   -- pivot
  product_id, modifier_group_id, sort_order, is_required_override

price_tiers                              -- harga berbeda per konteks
  id, tenant_id, name,               -- 'Normal','Happy Hour','GoFood','Member'
  type ('channel'|'time'|'customer_group'),
  conditions json, is_active

product_prices                           -- harga per tier per outlet
  id, tenant_id, product_id, product_variant_id (nullable),
  outlet_id (nullable = semua), price_tier_id, price,
  valid_from, valid_until

bundles                                  -- paket/combo
  id, tenant_id, product_id (type='bundle'),
  bundle_price_mode ('fixed'|'sum_discount')
bundle_items
  bundle_id, product_id, product_variant_id, quantity, is_swappable
```

### 4.2 Aturan bisnis

- **Harga final** dihitung: `harga tier > harga varian absolut > harga dasar
  + penyesuaian varian`, lalu `+ Σ penyesuaian modifier`.
- Produk `type='open'` = harga diinput saat transaksi (untuk item tidak
  terdaftar). Perlu izin khusus.
- Produk `ownership='partner'` **wajib** punya `partner_id` dan tunduk pada
  aturan M8.
- Produk `track_stock=true` mengurangi stok produk jadi (mis. pastry beli
  jadi). Produk dengan resep mengurangi stok **bahan baku** lewat M3.
  Keduanya bisa aktif bersamaan (produk jadi + kemasan).
- `available_hours` memungkinkan menu waktu-tertentu (menu sarapan, happy
  hour) — kebutuhan nyata coffee shop.

---

## 5. M2 — Inventory

### 5.1 Model data

```
units                                    -- satuan
  id, tenant_id, name, symbol,
  type ('weight'|'volume'|'piece'|'length'),
  base_unit_id (nullable), conversion_factor
  -- contoh: kg → base g (×1000), L → base ml (×1000)

ingredients                              -- bahan baku
  id, tenant_id, name, sku, category,
  unit_id,                               -- satuan penyimpanan (mis. gram)
  purchase_unit_id, purchase_conversion,  -- mis. beli per 'kg' = 1000 g
  cost_method ('fifo'|'weighted_average'|'last_purchase'),
  current_cost_per_unit,                 -- di-cache, dihitung dari ledger
  min_stock, reorder_point, safety_stock, max_stock,
  lead_time_days, shelf_life_days,
  is_active, track_expiry bool,
  supplier_default_id, custom_fields json

stock_levels                             -- saldo per outlet (cache)
  id, tenant_id, outlet_id, ingredient_id (nullable),
  product_id (nullable),                 -- untuk produk jadi
  quantity, reserved_quantity, average_cost,
  last_movement_at, last_counted_at
  UNIQUE(outlet_id, ingredient_id, product_id)

stock_batches                            -- untuk FIFO & expiry
  id, tenant_id, outlet_id, ingredient_id,
  batch_code, quantity_initial, quantity_remaining,
  unit_cost, received_at, expires_at, source_type, source_id

stock_movements                          -- LEDGER — sumber kebenaran
  id, tenant_id, outlet_id,
  ingredient_id (nullable), product_id (nullable),
  batch_id (nullable),
  type ('purchase'|'sale'|'production'|'waste'|'adjustment'
        |'transfer_in'|'transfer_out'|'opname'|'return'
        |'consignment_in'|'consignment_out'|'consignment_return'),
  quantity,                              -- negatif = keluar
  unit_cost, total_cost,
  balance_after,                         -- snapshot untuk audit
  reference_type, reference_id,          -- polymorphic ke order/PO/opname
  reason_code, notes, user_id,
  occurred_at, created_at
  INDEX (tenant_id, outlet_id, ingredient_id, occurred_at)

waste_logs                               -- pencatatan waste bersebab
  id, tenant_id, outlet_id, shift_id,
  ingredient_id (nullable), product_id (nullable),
  quantity, unit_cost, total_cost,
  reason ('calibration'|'spoilage'|'expired'|'spillage'
          |'remake'|'staff_meal'|'sample'|'theft'|'other'),
  notes, photo_path, user_id, occurred_at

stock_takes                              -- opname
  id, tenant_id, outlet_id, code,
  type ('full'|'partial'|'spot'),
  status ('draft'|'counting'|'review'|'completed'|'cancelled'),
  started_at, completed_at, started_by, approved_by,
  total_variance_value, notes
stock_take_items
  id, stock_take_id, ingredient_id (nullable), product_id (nullable),
  system_quantity, counted_quantity, variance_quantity,
  unit_cost, variance_value, notes, counted_by

stock_transfers                          -- antar outlet
  id, tenant_id, from_outlet_id, to_outlet_id, code, status,
  sent_at, received_at, sent_by, received_by, notes
stock_transfer_items
  id, stock_transfer_id, ingredient_id, quantity_sent,
  quantity_received, unit_cost
```

### 5.2 Aturan bisnis — **tidak boleh dilanggar**

1. **Setiap perubahan stok WAJIB melalui `stock_movements`.** Tidak pernah
   `UPDATE stock_levels` langsung. `stock_levels` hanya cache yang bisa
   dibangun ulang dari ledger.
2. **Ledger append-only.** Koreksi = movement baru bertipe `adjustment`,
   bukan edit/hapus baris lama.
3. **Konversi satuan disimpan dalam satuan dasar.** Simpan gram, tampilkan
   kg. Mencegah error pembulatan berulang.
4. **`balance_after` disimpan di setiap movement.** Membuat audit bisa
   dibaca manusia tanpa menjumlah ulang seluruh riwayat.
5. **Stok negatif diizinkan dengan peringatan**, tidak diblokir. Alasan:
   realitas lapangan — barista sering baru mencatat penerimaan setelah
   memakai barangnya. Memblokir akan membuat mereka berhenti memakai
   aplikasi. Tapi tampilkan alert.
6. **Opname mengunci** item yang sedang dihitung dari penjualan? **Tidak.**
   Justru catat semua movement selama periode hitung dan sesuaikan. Kedai
   tidak bisa berhenti jualan untuk opname.

### 5.3 Rumus

```
Days of cover     = quantity_on_hand / avg_daily_usage_7d
Reorder point     = avg_daily_usage × lead_time_days + safety_stock
Weighted avg cost = Σ(qty × unit_cost) / Σ(qty)   [semua batch tersisa]
Nilai persediaan  = Σ(quantity × average_cost)
Waste rate %      = Σ waste_cost / Σ purchase_cost × 100
```

---

## 6. M3 — Recipe / BOM (Fitur Signature)

### 6.1 Model data

```
recipes
  id, tenant_id,
  product_id, product_variant_id (nullable),
  name, yield_quantity, yield_unit_id,
  waste_factor_percent,        -- default 3-5%
  prep_time_minutes, instructions text, photo_path,
  version, is_active, effective_from,
  computed_cost, computed_cost_updated_at

recipe_items
  id, recipe_id,
  ingredient_id (nullable),
  sub_recipe_id (nullable),    -- resep bertingkat (simple syrup, base kopi)
  quantity, unit_id,
  is_optional, waste_factor_percent (override),
  notes, sort_order

modifier_recipe_items         -- modifier juga memotong stok
  id, modifier_id, ingredient_id, quantity, unit_id

recipe_cost_history           -- riwayat HPP untuk grafik tren
  id, recipe_id, cost, food_cost_percent, calculated_at, trigger
```

### 6.2 Perhitungan HPP

```
HPP_bahan      = Σ ( qty_bahan × biaya_per_satuan × (1 + waste_factor) )
HPP_sub_resep  = Σ ( qty × HPP_sub_resep_per_satuan )      [rekursif]
HPP_kemasan    = Σ ( qty_kemasan × biaya_kemasan )
HPP_total      = HPP_bahan + HPP_sub_resep + HPP_kemasan
HPP_per_porsi  = HPP_total / yield_quantity

Food cost %    = HPP_per_porsi / harga_jual × 100
Margin kotor   = harga_jual - HPP_per_porsi
Markup         = (harga_jual - HPP_per_porsi) / HPP_per_porsi × 100
```

**Contoh nyata — Es Kopi Susu (harga Rp22.000):**

| Bahan | Qty | Biaya/satuan | Subtotal |
|---|---|---|---|
| Biji kopi | 18 g | Rp180/g | Rp3.240 |
| Susu UHT | 150 ml | Rp16/ml | Rp2.400 |
| Gula aren cair | 25 ml | Rp30/ml | Rp750 |
| Es batu | 100 g | Rp1/g | Rp100 |
| Cup 16oz + tutup | 1 pcs | Rp1.100 | Rp1.100 |
| Sedotan | 1 pcs | Rp150 | Rp150 |
| **Subtotal** | | | **Rp7.740** |
| Waste factor 4% | | | Rp310 |
| **HPP total** | | | **Rp8.050** |
| **Food cost** | | | **36,6%** ⚠️ di atas target 32% |
| **Laba kotor** | | | **Rp13.950** |

Sistem menampilkan peringatan ini **saat menyusun resep**, bukan saat sudah
rugi tiga bulan.

### 6.3 Laporan Variance — **fitur pembeda utama**

```
Untuk setiap bahan dalam periode:

Pemakaian teoretis = Σ (qty_produk_terjual × qty_bahan_per_resep)
Pemakaian aktual   = stok_awal + pembelian + transfer_masuk
                     − stok_akhir − transfer_keluar
Waste tercatat     = Σ waste_logs

Variance           = Pemakaian aktual − Pemakaian teoretis − Waste tercatat
Variance %         = Variance / Pemakaian teoretis × 100
Nilai variance     = Variance × biaya_per_satuan
```

**Tampilan yang harus dilihat owner:**

```
LAPORAN KEBOCORAN — 1–30 September 2026 · Kopi Senja

Bahan            Teoretis  Waste   Aktual   Variance   Nilai      Status
─────────────────────────────────────────────────────────────────────────
Biji Arabika      12,4 kg  0,3 kg  14,1 kg   +1,4 kg   Rp252.000  🔴 11,3%
Susu UHT           186 L   2,0 L    194 L     +6,0 L   Rp 96.000  🟠  3,2%
Gula Aren         31,0 L   0,4 L   31,6 L     +0,2 L   Rp  6.000  🟢  0,6%
Cup 16oz          1.240    12      1.310       +58     Rp 63.800  🟠  4,7%
─────────────────────────────────────────────────────────────────────────
TOTAL KEBOCORAN TIDAK TERJELASKAN                      Rp417.800

Setara 4,2% dari omzet bulan ini (Rp9.950.000).
Jika ditekan ke 1%, laba bertambah ± Rp318.000/bulan.

Dugaan penyebab Biji Arabika:
• Waste kalibrasi tidak dicatat (shift pagi 0 catatan waste dalam 30 hari)
• Takaran tidak konsisten — cek grinder & timbangan
• 3 transaksi void setelah minuman dibuat (Rp66.000)
```

Ini adalah laporan yang membuat pengguna berkata "aplikasi ini membayar
dirinya sendiri".

---

## 7. M4 — Procurement (Restock)

```
suppliers
  id, tenant_id, name, contact_person, phone, email, address,
  payment_terms_days, notes, is_active, custom_fields json

supplier_ingredients                     -- katalog harga supplier
  id, supplier_id, ingredient_id, supplier_sku,
  unit_id, price, min_order_qty, lead_time_days, last_price_at

purchase_orders
  id, tenant_id, outlet_id, supplier_id, code,
  status ('draft'|'sent'|'partial'|'received'|'cancelled'),
  order_date, expected_date, subtotal, tax, shipping, total,
  notes, created_by, approved_by
purchase_order_items
  id, purchase_order_id, ingredient_id, quantity_ordered,
  quantity_received, unit_id, unit_price, subtotal

goods_receipts                           -- penerimaan / restock
  id, tenant_id, outlet_id, purchase_order_id (nullable),
  supplier_id (nullable), code,
  receipt_date, invoice_number, photo_path,
  subtotal, tax, total, payment_status, paid_at,
  received_by, notes
goods_receipt_items
  id, goods_receipt_id, ingredient_id, quantity, unit_id,
  unit_price, subtotal, batch_code, expires_at
```

### Aturan

- **Restock cepat tanpa PO adalah jalur utama.** Realitas: barista beli susu
  ke minimarket saat habis. Alur harus: buka aplikasi → pilih bahan → input
  qty & harga → foto nota → selesai. **Maksimal 4 ketukan.** PO formal
  adalah fitur opsional untuk yang butuh.
- Setiap `goods_receipt_item` membuat `stock_movement` bertipe `purchase`
  dan `stock_batch` baru.
- Harga beli terakhir otomatis memperbarui `ingredients.current_cost_per_unit`
  → memicu **rekalkulasi HPP semua resep terdampak** → memicu alert jika
  food cost melewati target.
- Foto nota disimpan; menjadi bukti untuk rekonsiliasi pengeluaran (M11).

---

## 8. M5 — Sales

```
orders
  id (ULID dari klien), tenant_id, outlet_id, shift_id,
  order_number,                          -- nomor urut per outlet per hari
  type ('dine_in'|'takeaway'|'delivery'|'online'|'pre_order'),
  channel ('pos'|'gofood'|'grabfood'|'shopeefood'|'web'|'whatsapp'),
  table_id (nullable), customer_id (nullable),
  customer_name, customer_phone,
  guest_count,
  status ('draft'|'open'|'completed'|'voided'|'refunded'),
  subtotal, discount_total, service_charge, tax_total,
  rounding, total, paid_total, change_amount,
  cost_total,                            -- HPP snapshot saat transaksi
  gross_profit,
  notes, source_device_id, idempotency_key (unique),
  opened_at, completed_at, voided_at, void_reason, voided_by,
  created_by, created_at, updated_at

order_items
  id (ULID), order_id, product_id, product_variant_id,
  ownership ('own'|'partner'), partner_id (nullable),
  consignment_batch_id (nullable),
  name_snapshot, sku_snapshot,           -- nama & sku saat transaksi
  quantity, unit_price, discount_amount, subtotal,
  tax_amount, total,
  unit_cost_snapshot, total_cost,        -- HPP saat transaksi
  partner_share, shop_share,             -- untuk item mitra
  status ('pending'|'preparing'|'ready'|'served'|'cancelled'),
  notes, sort_order

order_item_modifiers
  id, order_item_id, modifier_id,
  name_snapshot, quantity, unit_price, total

order_discounts
  id, order_id, order_item_id (nullable),
  discount_id (nullable),                -- null = diskon manual
  type ('percent'|'amount'), value, amount,
  reason, approved_by

discounts                                -- master promo
  id, tenant_id, name, code,
  type ('percent'|'amount'|'bogo'|'bundle'),
  value, max_discount_amount, min_purchase,
  applies_to ('order'|'category'|'product'),
  applicable_ids json,
  valid_from, valid_until, valid_days json, valid_hours json,
  usage_limit, usage_count, per_customer_limit,
  requires_approval, is_active, is_stackable

tax_groups
  id, tenant_id, name,                   -- 'PB1 10%'
  rate, type ('inclusive'|'exclusive'),
  applies_to_service_charge bool, is_active

order_refunds
  id, order_id, code, type ('full'|'partial'),
  amount, reason, restock bool,
  approved_by, refunded_at, payment_method

tables                                   -- opsional, untuk dine-in
  id, tenant_id, outlet_id, area, name, capacity, qr_token, status
```

### 8.1 Urutan perhitungan (harus konfigurabel)

```
1. Item subtotal   = Σ ((harga_satuan + Σ modifier) × qty)
2. Diskon item     = diskon per item
3. Subtotal        = Σ (item subtotal − diskon item)
4. Diskon order    = diskon level order
5. Base            = Subtotal − Diskon order
6. Service charge  = Base × sc_percent                    [jika aktif]
7. Pajak (PB1)     = (Base + SC) × tax_rate               [jika aktif; basis konfigurabel]
8. Total           = Base + SC + Pajak
9. Pembulatan      = sesuai kebijakan (ke atas/terdekat/tidak ada, nominal 100/500/1000)
10. Total akhir    = Total + pembulatan
```

> **Wajib konfigurabel** karena praktik berbeda: sebagian daerah menghitung
> PB1 hanya atas subtotal, sebagian atas subtotal+SC. Sebagian resto memakai
> harga *tax inclusive* (harga menu sudah termasuk pajak).

### 8.2 Aturan bisnis

- **Snapshot semuanya.** Nama produk, harga, biaya, nama modifier disimpan
  di `order_items`. Mengubah master produk **tidak boleh** mengubah riwayat
  transaksi.
- **Order selesai bersifat immutable.** Koreksi lewat void atau refund.
- **Void butuh alasan + otorisasi** jika melewati batas izin.
- **HPP di-snapshot saat transaksi** → laba kotor per transaksi akurat
  secara historis.
- **Split payment**: satu order bisa punya banyak `payments`.
- **Stok dipotong saat order `completed`**, bukan saat item ditambah ke
  keranjang. Untuk KDS (kitchen display) bisa dikonfigurasi memotong saat
  item masuk `preparing`.
- `idempotency_key` unique — pilar sinkronisasi offline.

---

## 9. M6 — Payment

Detail lengkap ada di [riset/03-payment-gateway-qris.md](riset/03-payment-gateway-qris.md).

```
payments
  id (ULID), tenant_id, outlet_id, order_id, shift_id,
  method ('cash'|'qris'|'transfer'|'card'|'ewallet'|'credit'|'voucher'),
  provider_driver, payment_intent_id (nullable),
  amount, received_amount, change_amount,
  fee_amount,                            -- estimasi MDR (internal saja)
  status ('pending'|'paid'|'failed'|'expired'|'refunded'),
  verification ('auto'|'manual'), verified_by,
  reference, proof_path, paid_at, notes

payment_intents · payment_events · payment_settlements
  -- lihat dokumen riset 03

customer_credits                         -- kasbon / piutang
  id, tenant_id, customer_id, order_id,
  amount, paid_amount, due_date, status, notes
```

---

## 10. M7 — Cash & Shift

Mengembangkan `ShopSession` yang sudah ada.

```
shifts                                   -- ex shop_sessions
  id, tenant_id, outlet_id, user_id,
  shift_template_id (nullable),
  code, opened_at, closed_at,
  opening_cash,
  expected_cash, counted_cash, cash_variance,
  cash_in_total, cash_out_total,
  sales_total, sales_by_method json,
  transaction_count, void_count, void_amount,
  discount_amount, refund_amount,
  cost_total, gross_profit,
  denominations json,                    -- rincian pecahan uang
  status ('open'|'closing'|'closed'|'reviewed'),
  notes, closing_photo_path,
  closed_by, reviewed_by, reviewed_at

cash_movements                           -- kas masuk/keluar di luar penjualan
  id, tenant_id, outlet_id, shift_id,
  type ('cash_in'|'cash_out'|'bank_deposit'|'petty_cash'|'tip'),
  category, amount, reason, proof_path,
  user_id, approved_by, occurred_at

shift_templates                          -- jadwal shift
  id, tenant_id, outlet_id, name,        -- 'Pagi','Sore','Malam'
  start_time, end_time, default_opening_cash,
  grace_period_minutes
```

### Aturan

- **Satu user hanya boleh punya satu shift terbuka per outlet.**
- Tutup shift **wajib** input hitungan kas fisik. Rincian denominasi
  opsional tapi sangat dianjurkan (memudahkan menemukan sumber selisih).
- Selisih di luar toleransi → butuh catatan + memicu `CASH_VARIANCE` alert.
- Shift yang sudah ditutup **tidak bisa dibuka ulang** kecuali oleh role
  dengan izin `shift.reopen`, dan tercatat di audit log.
- Laporan shift otomatis dikirim ke WhatsApp owner saat ditutup.
- Shift bisa lintas tengah malam (kedai buka sampai jam 2 pagi) — jangan
  mengasumsikan shift = kalender harian.

---

## 11. M8 — Partner / Konsinyasi (Diferensiator)

Mengembangkan `Partner`, `ProductTemplate`, `DailyConsignment` yang sudah ada.

### 11.1 Model data

```
partners
  id, tenant_id, code, name, type ('individual'|'business'),
  contact_person, phone, email, address,
  bank_name, bank_account_number, bank_account_name,
  settlement_cycle ('daily'|'weekly'|'biweekly'|'monthly'|'on_request'),
  settlement_day,                        -- mis. Jumat, atau tanggal 25
  default_revenue_share_type ('markup'|'commission_percent'|'flat_fee'),
  default_revenue_share_value,
  contract_start, contract_end, contract_file_path,
  is_active, notes, custom_fields json

partner_products                         -- ex product_templates
  id, tenant_id, partner_id, product_id,  -- terhubung ke katalog M1
  partner_price,                          -- harga dari mitra
  selling_price,                          -- harga jual di toko
  revenue_share_type, revenue_share_value, -- override per produk
  is_active

consignment_batches                       -- titipan (ex daily_consignments)
  id, tenant_id, outlet_id, partner_id, code,
  received_at, expires_at,
  status ('active'|'settled'|'returned'|'expired'),
  handover_photo_path, handover_signature_path,
  received_by, notes
consignment_batch_items
  id, consignment_batch_id, partner_product_id, product_id,
  quantity_received, quantity_sold, quantity_returned,
  quantity_damaged, quantity_remaining,
  partner_price, selling_price,
  revenue_share_type, revenue_share_value,
  partner_amount, shop_amount,           -- dihitung saat settle
  expires_at, batch_code

consignment_returns                       -- retur ke mitra
  id, tenant_id, consignment_batch_id, code,
  returned_at, returned_by, received_by_partner,
  photo_path, signature_path, notes
consignment_return_items
  id, consignment_return_id, consignment_batch_item_id,
  quantity, condition ('good'|'damaged'|'expired')

partner_settlements                       -- pembayaran ke mitra
  id, tenant_id, partner_id, code,
  period_start, period_end,
  total_sold_quantity, gross_sales,
  partner_amount, shop_amount,
  adjustments, adjustment_notes,
  net_payable,
  status ('draft'|'confirmed'|'paid'|'disputed'),
  paid_at, payment_method, payment_proof_path,
  confirmed_by_partner_at, dispute_notes,
  report_pdf_path
partner_settlement_items
  id, partner_settlement_id, consignment_batch_item_id,
  quantity_sold, gross_amount, partner_amount, shop_amount
```

### 11.2 Skema bagi hasil (fleksibel)

| Skema | Cara hitung | Contoh |
|---|---|---|
| **Markup** | Toko menaikkan harga dari harga mitra. Bagian toko = selisih | Mitra Rp8.000, jual Rp10.000 → toko Rp2.000, mitra Rp8.000 |
| **Komisi %** | Toko ambil X% dari harga jual | Jual Rp10.000, komisi 20% → toko Rp2.000, mitra Rp8.000 |
| **Fee flat** | Toko ambil nominal tetap per unit | Jual Rp10.000, fee Rp1.500 → toko Rp1.500, mitra Rp8.500 |
| **Bertingkat** | Komisi berubah sesuai volume | ≤50 pcs: 15%, >50 pcs: 20% |

Konfigurasi berlaku berjenjang: **default mitra → override per produk →
override per batch**.

### 11.3 Alur kerja

```
1. TERIMA TITIPAN
   Mitra datang → pilih mitra → pilih produk → input qty
   → foto serah terima + tanda tangan digital (opsional)
   → batch dibuat, stok masuk (movement 'consignment_in')
   → WA otomatis ke mitra: "Titipan diterima: 20 roti, 15 donat"

2. PENJUALAN
   Kasir menjual seperti produk biasa
   → order_item.ownership = 'partner', terhubung ke batch
   → hitung partner_share & shop_share langsung saat transaksi
   → stok batch berkurang (movement 'consignment_out')

3. REKONSILIASI HARIAN (saat tutup shift)
   Sistem: "Titipan Bu Sari — awal 20, terjual 17, sisa seharusnya 3"
   Kasir menghitung fisik → input sisa aktual
   → jika beda: catat sebagai rusak/hilang, wajib alasan
   → WA laporan harian ke mitra

4. RETUR
   Barang sisa/kedaluwarsa dikembalikan
   → catat qty & kondisi, foto, tanda tangan
   → movement 'consignment_return'

5. SETTLEMENT
   Sesuai siklus (mingguan/bulanan) → sistem generate draft
   → owner review & konfirmasi → bayar → upload bukti
   → PDF laporan + bukti bayar dikirim WA ke mitra
   → mitra bisa konfirmasi terima / ajukan sengketa lewat link
```

### 11.4 Kenapa ini penting

Titipan mitra adalah **barang orang lain di toko Anda**. Setiap selisih
adalah potensi konflik hubungan bisnis. Sistem yang membuat setiap angka
transparan dan terdokumentasi menghilangkan sumber konflik terbesar dalam
model ini — dan itulah nilai jual modul ini.

---

## 12. M9 — Order Ahead / Catering

Mengembangkan `BoxOrder` yang sudah ada.

```
preorders                                -- ex box_orders
  id, tenant_id, outlet_id, code,
  customer_id, customer_name, customer_phone, customer_address,
  type ('box'|'catering'|'bulk'|'event'),
  event_date, delivery_datetime,
  fulfillment ('pickup'|'delivery'),
  subtotal, discount, delivery_fee, tax, total,
  deposit_required, deposit_paid, deposit_paid_at,
  balance_due, balance_due_date,
  status ('draft'|'quoted'|'confirmed'|'in_production'
          |'ready'|'delivered'|'completed'|'cancelled'),
  production_start_at,
  notes, internal_notes, cancellation_reason,
  created_by
preorder_items
  id, preorder_id, product_id, package_template_id (nullable),
  name_snapshot, quantity, unit_price, subtotal,
  customizations json, notes

package_templates                        -- ex box_templates
  id, tenant_id, name, type, description, image_path,
  price, items json, is_customizable,
  min_order_quantity, lead_time_hours,
  is_active, custom_fields json
```

### Fitur tambahan yang dibutuhkan

- **Kalkulasi kebutuhan bahan.** "Pesanan 200 box untuk Jumat butuh 20 kg
  ayam, 15 kg beras. Stok sekarang 5 kg ayam → **kurang 15 kg**."
  Ini menghubungkan M9 ke M3 dan M2 — sangat berharga untuk catering.
- **DP / uang muka** dengan pengingat WA otomatis.
- **Jadwal produksi** dengan mundur dari waktu pengiriman.
- **Kuota per hari** (maks 300 box/hari) agar tidak overbooking.
- **Quotation PDF** yang bisa dikirim ke calon pelanggan lewat WA.

---

## 13. M10 — HR

```
employees
  id, tenant_id, user_id (nullable), employee_code,
  name, phone, email, photo_path,
  position, employment_type ('full_time'|'part_time'|'daily'|'intern'),
  join_date, end_date,
  salary_type ('monthly'|'daily'|'hourly'),
  base_salary, hourly_rate, overtime_rate_multiplier,
  bank_name, bank_account_number,
  emergency_contact json, documents json,
  is_active, custom_fields json

work_schedules
  id, tenant_id, outlet_id, employee_id,
  shift_template_id, date, start_time, end_time,
  status ('scheduled'|'confirmed'|'swapped'|'absent'), notes

attendances
  id, tenant_id, outlet_id, employee_id, work_schedule_id (nullable),
  date,
  clock_in_at, clock_in_photo_path, clock_in_lat, clock_in_lng,
  clock_in_accuracy_m, clock_in_within_geofence bool, clock_in_device_id,
  clock_out_at, clock_out_photo_path, clock_out_lat, clock_out_lng,
  clock_out_within_geofence bool,
  worked_minutes, late_minutes, early_leave_minutes, overtime_minutes,
  break_minutes,
  status ('present'|'late'|'absent'|'leave'|'sick'|'holiday'|'off'),
  notes, approved_by, is_manual_entry, manual_reason

leave_requests
  id, tenant_id, employee_id,
  type ('annual'|'sick'|'permit'|'unpaid'|'maternity'),
  start_date, end_date, days, reason, attachment_path,
  status ('pending'|'approved'|'rejected'), reviewed_by, reviewed_at

payroll_periods
  id, tenant_id, code, period_start, period_end,
  status ('draft'|'calculated'|'approved'|'paid'), paid_at
payroll_items
  id, payroll_period_id, employee_id,
  base_amount, overtime_amount, allowances json,
  deductions json, bonus_amount, total_amount,
  worked_days, worked_hours, notes, payslip_path
```

### Aturan & kepatuhan

- Foto selfie & lokasi GPS adalah **data pribadi** → tunduk UU PDP:
  - Persetujuan eksplisit saat karyawan pertama kali memakai aplikasi.
  - Kebijakan retensi (mis. foto absensi dihapus otomatis setelah 90 hari).
  - Karyawan bisa melihat & meminta penghapusan datanya.
  - Tujuan pengumpulan dijelaskan jelas.
- **Geofence bersifat peringatan, bukan blokir** secara default. Absen di
  luar radius tetap tercatat tapi ditandai untuk ditinjau manajer. Realitas:
  GPS meleset, dan memblokir absensi menciptakan konflik.
- Payroll di v1 cukup **payroll-lite**: hitung dari kehadiran, hasilkan slip
  gaji PDF. Perhitungan PPh 21 & BPJS ditunda ke fase berikutnya (kompleks &
  berisiko salah).

---

## 14. M11 — Finance

```
expense_categories
  id, tenant_id, name, parent_id, type ('operational'|'cogs'|'capex'|'other'),
  is_recurring, budget_monthly, color, sort_order

expenses
  id, tenant_id, outlet_id, shift_id (nullable),
  expense_category_id, code,
  amount, description,
  payment_method, paid_from ('cash_drawer'|'bank'|'petty_cash'|'owner'),
  vendor_name, invoice_number, photo_path,
  expense_date, recurring_id (nullable),
  status ('draft'|'pending'|'approved'|'rejected'|'paid'),
  created_by, approved_by, approved_at

recurring_expenses                       -- sewa, gaji, langganan
  id, tenant_id, outlet_id, expense_category_id,
  name, amount, frequency ('monthly'|'weekly'|'yearly'),
  next_due_date, auto_create, is_active

other_incomes                            -- pendapatan non-penjualan
  id, tenant_id, outlet_id, category, amount, description,
  received_date, payment_method, proof_path, created_by
```

### Laporan yang dihasilkan

- **Arus kas harian/bulanan** — masuk, keluar, saldo.
- **Laba rugi sederhana** — Pendapatan − HPP − Beban Operasional = Laba
  Bersih.
- **Realisasi vs anggaran** per kategori.
- **Biaya per kategori** dengan tren.
- **Break-even point** — berapa omzet minimum untuk menutup biaya tetap.

Semua dalam bahasa yang dimengerti pemilik warung, bukan bahasa akuntan.

---

## 15. M12 — Notification

Lihat [riset/04-whatsapp-notifikasi.md](riset/04-whatsapp-notifikasi.md)
untuk spesifikasi lengkap.

---

## 16. M13 — Menu Designer & Ekspor

Menjawab permintaan: *"pencatatan menu dan resep dengan export menu dengan
kustomisasi design menyesuaikan kemauan user"*.

### 16.1 Model data

```
menu_designs
  id, tenant_id, name, description,
  template_key,                          -- 'classic'|'modern'|'minimal'|'chalkboard'|'custom'
  format,                                -- 'a4'|'a5'|'tent_card'|'x_banner'|'ig_feed'|'ig_story'|'digital'
  orientation ('portrait'|'landscape'),
  config json,                           -- lihat 16.2
  preview_path, is_active, is_default,
  created_by, created_at, updated_at

menu_design_sections
  id, menu_design_id, name,              -- 'Kopi Susu','Manual Brew','Snack'
  category_id (nullable), sort_order,
  show_prices, show_descriptions, show_images, show_badges,
  layout ('list'|'grid'|'two_column'|'three_column'),
  custom_items json                      -- item manual di luar katalog

menu_exports                             -- riwayat ekspor
  id, tenant_id, menu_design_id,
  format, file_path, file_size,
  generated_by, generated_at, expires_at

digital_menus                            -- menu QR
  id, tenant_id, outlet_id, menu_design_id,
  slug (unique), qr_style json,
  is_active, view_count, last_viewed_at,
  show_out_of_stock, allow_ordering
```

### 16.2 Struktur `config` (yang bisa dikustom user)

```json
{
  "theme": {
    "palette": {
      "background": "#FAF7F2",
      "surface": "#FFFFFF",
      "primary": "#2C1810",
      "accent": "#C8763C",
      "text": "#2C1810",
      "textMuted": "#7A6A5F",
      "divider": "#E3D9CE"
    },
    "typography": {
      "headingFont": "Playfair Display",
      "bodyFont": "Inter",
      "headingScale": 1.35,
      "baseSize": 14,
      "letterSpacing": 0.02
    },
    "spacing": { "density": "comfortable", "sectionGap": 28, "itemGap": 12 },
    "decoration": {
      "borderStyle": "hairline",
      "cornerRadius": 8,
      "backgroundImage": null,
      "backgroundOpacity": 0.06,
      "pattern": null
    }
  },
  "branding": {
    "logo": { "path": "brand/logo.png", "position": "top-center", "maxWidth": 140 },
    "businessName": "Kopi Senja",
    "tagline": "Sejak 2021 · Slow Bar",
    "showAddress": true,
    "showPhone": true,
    "showSocial": true,
    "socials": { "instagram": "@kopisenja", "whatsapp": "6281..." }
  },
  "layout": {
    "columns": 2,
    "sectionStyle": "underline",
    "priceStyle": "aligned-right",
    "priceFormat": "22K",
    "showCurrency": false,
    "imageStyle": "circle",
    "imageSize": "small"
  },
  "content": {
    "showDescriptions": true,
    "descriptionMaxChars": 80,
    "showAllergens": false,
    "showCalories": false,
    "badges": ["best_seller", "new", "signature", "spicy", "vegan"],
    "hideOutOfStock": true,
    "sortBy": "manual"
  },
  "footer": {
    "text": "Harga sudah termasuk pajak",
    "showQr": true,
    "qrTarget": "digital_menu",
    "showWifiPassword": false
  },
  "export": {
    "bleedMm": 3,
    "dpi": 300,
    "colorProfile": "sRGB",
    "includeCropMarks": false
  }
}
```

### 16.3 Format ekspor

| Format | Ukuran | Kegunaan |
|---|---|---|
| PDF A4 | 210×297 mm | Menu meja, cetak di percetakan |
| PDF A5 | 148×210 mm | Menu genggam |
| PDF Tent Card | 100×150 mm lipat | Kartu meja |
| PDF X-Banner | 60×160 cm | Banner depan toko |
| PNG Feed IG | 1080×1080 | Konten Instagram |
| PNG Story IG | 1080×1920 | Story / status WA |
| PNG Papan Harga | 1920×1080 | Layar TV di toko |
| Menu QR (web) | Responsif | Menu digital dengan link permanen |
| CSV / XLSX | — | Untuk diunggah ke GoFood/Grab/Shopee |

### 16.4 Alur kerja pengguna

```
1. Kelola menu di katalog (M1) — sudah ada, tidak perlu input ulang
2. Buat desain menu → pilih template awal
3. Sesuaikan: warna, font, layout, logo, badge, seksi
4. Pratinjau langsung (side-by-side dengan editor)
5. Ekspor → pilih format → unduh
   atau → Terbitkan sebagai Menu QR → dapat link + QR bermerek

Saat harga berubah di katalog:
  → sistem menandai desain "perlu diperbarui"
  → satu klik regenerasi
  → Menu QR terbarui otomatis
```

**Kunci nilainya:** menu cetak dan harga di kasir **tidak akan pernah beda
lagi**. Ini masalah nyata yang dialami setiap kedai.

### 16.5 Implementasi

- Render lewat **Browsershot (Puppeteer headless Chrome)** — mendukung CSS
  modern, web font, dan output PDF berkualitas cetak.
- Template = komponen Blade/Vue yang menerima `config` JSON.
- Job antrean untuk ekspor (render bisa 3–10 detik), notifikasi saat siap.
- Simpan hasil di S3-compatible dengan URL bertanda-tangan.
- Pustaka font: Google Fonts subset Latin + font Indonesia populer.

---

## 17. M14 — Reporting

### Dashboard operasional (untuk manajer, realtime)
Penjualan hari ini vs kemarin · transaksi berjalan · shift aktif · stok
kritis · item terlaris hari ini · rata-rata nilai transaksi.

### Dashboard owner (untuk keputusan)
Tren omzet 30 hari · food cost % vs target · **nilai kebocoran** · laba
kotor · perbandingan outlet · top & bottom produk berdasarkan profit.

### Laporan wajib

| Laporan | Isi |
|---|---|
| Penjualan harian/bulanan | Per produk, kategori, jam, kasir, channel, metode bayar |
| Laporan shift | Rekap penuh + selisih kas + void + diskon |
| **Variance / Kebocoran** | Teoretis vs aktual per bahan (fitur signature) |
| Food cost | Per produk, tren, peringatan melewati target |
| Nilai persediaan | Nilai stok saat ini, umur stok, slow-moving |
| Analisis menu (ABC / Menu Engineering) | Bintang / Kuda Beban / Teka-teki / Anjing |
| Laba rugi | Pendapatan − HPP − Beban |
| Arus kas | Masuk, keluar, saldo |
| Laporan mitra | Per mitra: terjual, bagi hasil, settlement |
| Absensi | Kehadiran, keterlambatan, lembur |
| Pajak | Omzet kena PB1, estimasi setoran |
| Penjualan per jam | Untuk keputusan penjadwalan staf |

### Ekspor data (janji anti-lock-in)

- Setiap laporan → CSV, XLSX, PDF.
- **Ekspor penuh akun** → arsip ZIP berisi semua tabel dalam CSV + JSON +
  file media. Tersedia di semua paket, **termasuk gratis**.
- API terbuka dengan dokumentasi untuk integrasi sendiri.

---

## 18. MX — Engine Kustomisasi

Ini yang membuat *"full customable supaya fleksibel untuk beberapa kasus toko
sekaligus"* menjadi nyata dan bukan slogan.

### 18.1 Lima lapis kustomisasi

```
Lapis 1 — PRESET TIPE BISNIS
  Satu pilihan saat onboarding menyiapkan seluruh konfigurasi

Lapis 2 — FEATURE FLAG
  Nyalakan/matikan modul per tenant & per outlet

Lapis 3 — PENGATURAN
  Ratusan opsi berjenjang: sistem → tenant → outlet → user

Lapis 4 — TEMPLATE
  Struk, menu, laporan, pesan notifikasi — semua bisa diedit

Lapis 5 — CUSTOM FIELD
  Tambah field sendiri di produk, pelanggan, mitra, karyawan
```

### 18.2 Preset tipe bisnis

| Preset | Modul aktif | Default penting |
|---|---|---|
| **Coffee Shop** | Catalog, Recipe, Inventory, Sales, Cash, HR, Notification, Menu | Modifier (ukuran/gula/es) aktif, PB1 10%, kategori kopi/non-kopi/makanan |
| **Coffee Shop + Mitra** | + Partner | Pastry/snack dari mitra |
| **Kantin / Koperasi Mitra** | Partner, Sales, Cash, Notification (Recipe mati) | Fokus konsinyasi, banyak mitra, settlement mingguan |
| **Warung Kopi Sederhana** | Catalog, Sales, Cash (Recipe & Inventory mati) | Paling sederhana, tanpa pajak, tanpa modifier |
| **Catering / Box** | Preorder, Recipe, Inventory, Finance | Fokus pesanan terjadwal, kalkulasi bahan |
| **Resto / Cafe Penuh** | Semua | Meja, dine-in, service charge, KDS — modul meja/KDS dibangun di **P23**; jangan aktifkan preset ini sebelum P23 selesai |
| **Food Truck** | Catalog, Sales, Cash, Inventory | Offline-heavy, satu kasir, tanpa meja |
| **Custom** | Pilih sendiri | Untuk pengguna mahir |

Preset **hanya mengatur nilai awal** — semuanya tetap bisa diubah setelahnya.
Ganti preset tidak menghapus data.

### 18.3 Feature flag

```json
{
  "inventory":        { "enabled": true,  "track_expiry": true, "allow_negative": true },
  "recipe":           { "enabled": true,  "show_cost_to_cashier": false },
  "partner":          { "enabled": true,  "portal_enabled": true },
  "preorder":         { "enabled": true,  "require_deposit": false },
  "hr_attendance":    { "enabled": true,  "require_selfie": true, "geofence_mode": "warn" },
  "hr_payroll":       { "enabled": false },
  "finance_expense":  { "enabled": true,  "require_approval": false },
  "tables":           { "enabled": false },
  "kds":              { "enabled": false },
  "loyalty":          { "enabled": false },
  "online_menu":      { "enabled": true },
  "notifications_wa": { "enabled": true },
  "multi_outlet":     { "enabled": false }
}
```

Feature flag berlaku di **tiga lapis**: paket langganan (batas atas) →
setelan tenant → setelan outlet.

### 18.4 Pengaturan berjenjang

```
Prioritas (yang di bawah menang):
  1. Default sistem
  2. Preset tipe bisnis
  3. Setelan tenant
  4. Setelan outlet
  5. Preferensi user (khusus UI)
```

Contoh kelompok pengaturan:

```
pajak      : aktif, tarif, nama, inclusive/exclusive, basis hitung
service    : aktif, persen, kena pajak?
pembulatan : mode (atas/terdekat/tidak), nominal (100/500/1000)
struk      : template, footer, logo, cetak otomatis, jumlah salinan
kasir      : PIN wajib, timeout auto-logout, tampilkan HPP, izin diskon manual maks
shift      : wajib hitung denominasi, toleransi selisih, tutup otomatis jam
stok       : izinkan negatif, potong stok saat (bayar/siapkan), ambang alert
mitra      : siklus settlement, skema bagi hasil default, kirim laporan otomatis
notifikasi : jam tenang, channel default, ambang per alert
tampilan   : bahasa, tema, format tanggal, format angka, mata uang
```

### 18.5 Custom field

```
custom_field_definitions
  id, tenant_id, entity_type,          -- 'product'|'customer'|'partner'|'employee'|'ingredient'
  key, label, type,                    -- 'text'|'number'|'date'|'select'|'boolean'|'file'
  options json, is_required, default_value,
  show_in_list, show_in_form, sort_order, is_active
```

Nilai disimpan di kolom `custom_fields json` pada entitas terkait —
sederhana, tidak butuh join, cukup untuk kebutuhan UMKM.

### 18.6 Branding / white-label

```json
{
  "app_name": "Kopi Senja POS",
  "logo_path": "brand/logo.png",
  "logo_dark_path": "brand/logo-dark.png",
  "favicon_path": "brand/favicon.png",
  "primary_color": "#C8763C",
  "accent_color": "#2C1810",
  "receipt_header": "KOPI SENJA",
  "receipt_footer": "Terima kasih! IG: @kopisenja",
  "email_from_name": "Kopi Senja",
  "custom_domain": "pos.kopisenja.id"
}
```

Tersedia di paket berbayar; custom domain di paket tertinggi. Ini juga jalur
monetisasi untuk reseller/agensi yang ingin menjual ulang.

---

## 19. Ringkasan Model Data

Total ± **85 tabel**. Yang paling kritis untuk integritas:

| Tabel | Kenapa kritis |
|---|---|
| `stock_movements` | Sumber kebenaran seluruh stok. Append-only. |
| `orders` + `order_items` | Sumber kebenaran pendapatan. Immutable setelah selesai. |
| `payments` | Rekonsiliasi uang. Idempoten. |
| `shifts` | Akuntabilitas kas. |
| `consignment_batch_items` | Kepercayaan mitra. |
| `activity_log` | Audit trail untuk deteksi fraud. |

**Aturan integritas:**
- Foreign key dengan `RESTRICT` untuk data transaksi (tidak boleh hilang
  karena penghapusan master).
- Soft delete di semua entitas bisnis.
- Unique constraint pada semua idempotency key.
- Index komposit `(tenant_id, outlet_id, <kolom waktu>)` pada tabel besar.
- Check constraint pada nilai uang (tidak negatif kecuali di tabel yang
  memang mengizinkan).
