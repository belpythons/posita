# 10 — Audit Kondisi Project Saat Ini

Penilaian jujur terhadap codebase Posita per September 2026, sebagai dasar
perencanaan. Tujuannya bukan mengkritik, tapi memetakan **apa yang layak
dipertahankan, apa yang perlu diperbaiki, dan apa yang belum ada**.

---

## 1. Ringkasan

| Aspek | Nilai | Catatan |
|---|:---:|---|
| Kualitas struktur kode | 🟢 Baik | Service layer sudah dipisah, controller relatif tipis |
| Kelengkapan fitur POS | 🔴 ~20% | Yang ada adalah niche (kantin + konsinyasi + box), bukan POS umum |
| Kesiapan multi-tenant | 🔴 Belum ada | Single-tenant hard-coded |
| Kesiapan produksi | 🔴 Belum | Tidak ada CI, test bisnis, monitoring, backup |
| Cakupan test | 🔴 ~5% | Hanya test bawaan Breeze (auth & profile) |
| Kesiapan mobile | 🔴 Belum ada | Tidak ada lapisan API sama sekali |
| **Verdict** | **Fondasi bagus, bukan produk** | **Evolusi, bukan rewrite** |

**Kesimpulan utama:** codebase ini adalah *prototype akademik yang ditulis
dengan rapi*. Arsitekturnya (service layer, ViewModel, observer, activity
log) menunjukkan disiplin yang cukup untuk dijadikan fondasi produk. Yang
kurang bukan kualitas kode, melainkan **cakupan domain dan kesiapan
operasional**.

---

## 2. Inventaris Yang Sudah Ada

### 2.1 Model domain (8 model)

| Model | Fungsi | Nilai untuk produk |
|---|---|---|
| `User` | Auth + role (`admin`/`employee`) | 🟡 Perlu evolusi ke RBAC & multi-tenant |
| `Partner` | Mitra konsinyasi | 🟢 **Aset berharga** — perluas |
| `ProductTemplate` | Produk milik mitra + harga dasar & harga jual | 🟢 Cikal bakal katalog mitra |
| `DailyConsignment` | Titipan harian: qty awal/laku/sisa, markup | 🟢 **Diferensiator utama** — perdalam jadi batch + settlement |
| `ShopSession` | Sesi buka/tutup toko + kas awal/sistem/fisik | 🟢 Fondasi cash management yang benar |
| `BoxTemplate` | Template box (heavy meal / snack box) + `items_json` | 🟢 Modul catering, tetap dipertahankan |
| `BoxOrder` + `BoxOrderItem` | Pesanan box, pickup, bukti bayar, status | 🟢 Pertahankan, perlu DP & jadwal produksi |

### 2.2 Service layer (7 service, ~1.500 baris)

`AuthService` · `ShopSessionService` · `ConsignmentService` · `BoxOrderService`
· `DashboardService` · `AdminDataService` · `ReportService`

🟢 **Pemisahan yang benar.** Logika bisnis sudah keluar dari controller. Ini
yang membuat ekstraksi API di Fase 1 jadi murah — service yang sama dipanggil
dari controller web *dan* controller API.

### 2.3 Fitur yang berjalan

- ✅ Autentikasi Breeze (login, register, reset password, verifikasi email)
- ✅ Role dua tingkat via `RoleMiddleware` (`admin`, `employee`)
- ✅ Buka/tutup sesi toko dengan rekonsiliasi kas fisik vs sistem
- ✅ Konsinyasi harian: input titipan, update terjual, hitung pendapatan
- ✅ Box order: template, item custom, upload bukti bayar, status, struk PDF
- ✅ Dashboard admin dengan statistik & tren
- ✅ Laporan PDF: penjualan harian, laporan sesi, struk box
- ✅ Agregasi statistik harian (`AggregateDailyStats` command + `daily_stats`)
- ✅ Activity log (spatie) untuk audit
- ✅ Print thermal dari browser (`resources/js/Services/ThermalPrinter.js`)
- ✅ UI modern: Tailwind, komponen Shadcn-style, dark mode, command menu

---

## 3. Hutang Teknis & Risiko

### 🔴 Kritis (blocker rilis)

| # | Masalah | Dampak | Perbaikan |
|---|---|---|---|
| K1 | **Tidak ada multi-tenancy.** Satu instalasi = satu toko | Tidak bisa dijual sebagai SaaS sama sekali | Tambah `tenants` + `outlets` + `tenant_id` di semua tabel + global scope |
| K2 | **Role sebagai string di kolom `users.role`** | Tidak bisa RBAC granular; tidak bisa role custom | Ganti ke roles & permissions (spatie/laravel-permission) dengan scope tenant |
| K3 | **Tidak ada lapisan API** | Aplikasi mobile mustahil dibangun | Ekstrak `/api/v1` + Sanctum |
| K4 | **Cakupan test ~5%** — hanya test bawaan Breeze | Refactor besar akan merusak diam-diam | Test bisnis untuk kalkulasi uang, stok, sesi (target ≥70% pada modul kritis) |
| K5 | **Tidak ada CI/CD** | Regresi lolos ke main | GitHub Actions: pint, phpstan, pest, build |
| K6 | **Uang disimpan sebagai `decimal(12,2)` tanpa Value Object** | Rawan floating point & inkonsistensi pembulatan | `Money` VO berbasis integer (rupiah minor unit) |
| K7 | **Tidak ada penanganan konkurensi** pada stok & sesi | Dua kasir bisa menjual stok yang sama | Pessimistic lock / atomic decrement + constraint DB |
| K8 | **Tidak ada backup & disaster recovery** | Kehilangan data pelanggan = akhir bisnis | Backup otomatis harian + uji restore |

### 🟠 Tinggi

| # | Masalah | Perbaikan |
|---|---|---|
| T1 | Tidak ada modul **inventory / bahan baku** sama sekali | Modul Inventory + stock ledger |
| T2 | Tidak ada **resep / BOM / HPP** | Modul Recipe |
| T3 | Tidak ada **katalog produk sendiri** (hanya produk mitra & box) | Modul Catalog: kategori, produk, varian, modifier |
| T4 | Tidak ada **transaksi POS umum** (keranjang, split payment, diskon, pajak) | Modul Sales |
| T5 | Tidak ada **absensi & HR** | Modul HR |
| T6 | Tidak ada **pencatatan pengeluaran** | Modul Finance |
| T7 | Tidak ada **notifikasi** apapun | Modul Notification |
| T8 | Tidak ada **payment gateway** | Modul Payment |
| T9 | `.env.example` masih template Laravel default (`APP_NAME=Laravel`, locale `en`) | Sesuaikan: `id`, `Asia/Jakarta`, variabel Posita |
| T10 | Locale `en` — aplikasi berbahasa Indonesia tapi tidak ada i18n | `lang/id/` + helper format rupiah/tanggal |
| T11 | Tidak ada rate limiting pada endpoint sensitif | Throttle login, webhook, ekspor |
| T12 | Tidak ada observability (error tracking, metrics) | Sentry + Laravel Pulse |

### 🟡 Sedang

| # | Masalah | Perbaikan |
|---|---|---|
| S1 | README masih berisi pembagian tugas kuliah + NIM mahasiswa | Tulis ulang sebagai dokumentasi produk |
| S2 | Komentar header file berisi nama & NIM mahasiswa | Bersihkan (pindah kredit ke CONTRIBUTORS.md) |
| S3 | Lisensi tertulis "unlicense" di README tapi `composer.json` bilang MIT | Tentukan lisensi resmi, konsisten |
| S4 | File biner `retailer` (106 KB) ter-commit tanpa penjelasan | Investigasi & hapus jika artefak |
| S5 | Tidak ada TypeScript di frontend | Migrasi bertahap ke TS |
| S6 | Duplikasi komponen (`Components/Badge.vue` vs `Components/ui/Badge.vue`, `DataTable` ganda) | Konsolidasi ke satu design system |
| S7 | Tidak ada Docker / setup satu perintah | `docker-compose.yml` + `make setup` |
| S8 | `enum` di kolom DB (`status`, `type`) | Ganti ke string + PHP enum — enum DB sulit dimigrasi |
| S9 | Tidak ada soft delete pada entitas bisnis | Tambah — penting untuk audit & sync |
| S10 | Tidak ada `updated_at` cursor-friendly index untuk sync | Tambah index `(tenant_id, updated_at)` |

---

## 4. Gap Analysis: Sekarang vs Target Produk

| Modul | Sekarang | Target | Gap |
|---|:---:|:---:|:---:|
| Autentikasi & user | 🟡 Dasar | RBAC multi-tenant, PIN kasir | Besar |
| Multi-tenant & outlet | ❌ | Penuh | **Total** |
| Katalog & menu | 🟡 Hanya box & produk mitra | Kategori, produk, varian, modifier, harga bertingkat | Besar |
| **Bahan baku & stok** | ❌ | Ledger penuh, opname, PO, supplier, waste | **Total** |
| **Resep & HPP** | ❌ | BOM, yield, waste factor, HPP hidup, variance | **Total** |
| Transaksi POS | ❌ | Keranjang, split payment, diskon, pajak, void/refund | **Total** |
| Sesi & kas | 🟢 Baik | + kas masuk/keluar, denominasi, multi-shift/hari | Kecil |
| **Konsinyasi mitra** | 🟢 Dasar kuat | + batch, expiry, settlement, portal mitra, retur | Sedang |
| Box order / catering | 🟢 Baik | + DP, jadwal produksi, kalkulasi bahan | Kecil |
| **Absensi & HR** | ❌ | Selfie + geofence, jadwal, lembur, payroll-lite | **Total** |
| **Keuangan** | 🟡 Hanya pendapatan | Pengeluaran, kategori, arus kas, laba rugi sederhana | Besar |
| **Notifikasi WA** | ❌ | Alert engine penuh | **Total** |
| **Payment gateway** | ❌ | Driver abstraction + QRIS | **Total** |
| **Menu designer & ekspor** | ❌ | Template kustom, ekspor multi-format, menu QR | **Total** |
| Laporan | 🟡 Dasar | Variance, food cost, ABC menu, per-jam, per-kasir | Besar |
| **Kustomisasi** | ❌ | Feature flag, preset, custom field, template | **Total** |
| **Mobile app** | ❌ | Kasir + staf, offline-first | **Total** |
| Integrasi aggregator | ❌ | GoFood/Grab/Shopee | **Total** (Fase 5) |

**Perkiraan cakupan terhadap target produk: ± 20%.**

---

## 5. Apa yang Harus Dipertahankan (Jangan Dibuang)

1. **Service layer pattern** — sudah benar, tinggal diperluas dengan Action
   pattern untuk operasi kompleks.
2. **Model konsinyasi** — ini keunggulan kompetitif. Perdalam, jangan
   ganti.
3. **Model ShopSession dengan `closing_cash_system` vs `closing_cash_actual`**
   — desain cash reconciliation yang sudah benar sejak awal.
4. **Box order dengan template + item custom** — pola "template dengan
   override" ini tepat dan akan dipakai ulang di produk & menu.
5. **`template_details` JSON di `box_templates`** — insting yang benar untuk
   fleksibilitas. Pola ini akan diperluas jadi engine kustomisasi.
6. **Activity log (spatie)** — fondasi audit trail yang dibutuhkan untuk
   deteksi fraud.
7. **Design system UI** — Tailwind + komponen Shadcn-style, dark mode.
   Jadikan dasar `packages/design-tokens`.
8. **Pest 4** — framework test-nya sudah tepat, tinggal ditulis testnya.

---

## 6. Prioritas Perbaikan (Urutan Eksekusi)

```
FASE 0 — FONDASI (blocker semua hal lain)
├── Multi-tenancy (tenants, outlets, tenant_id, global scope)
├── RBAC (roles, permissions, scope tenant)
├── Money value object + kebijakan pembulatan
├── Test harness + factory untuk semua model
├── CI (pint, phpstan level 6, pest, coverage gate)
├── Docker Compose + setup satu perintah
└── Bersih-bersih: README, komentar NIM, lisensi, .env.example, file biner

FASE 1 — DOMAIN INTI
├── Catalog (kategori, produk, varian, modifier)
├── Inventory (bahan, satuan, konversi, stock ledger, opname)
├── Recipe/BOM (+ HPP, waste factor)
└── Sales (keranjang, order, split payment, diskon, pajak, void/refund)

FASE 2 — API & MOBILE
├── /api/v1 + Sanctum + OpenAPI
├── Aplikasi kasir Expo (online)
└── Print thermal + template struk

FASE 3 — OFFLINE
└── SQLite lokal + outbox sync + resolusi konflik

FASE 4 — PEMBEDA
├── Konsinyasi lanjutan (batch, settlement, portal mitra)
├── Notifikasi WhatsApp
├── HR (absensi) & Finance (pengeluaran)
├── Menu designer & ekspor
└── Laporan variance / profit leak

FASE 5 — SKALA
├── Payment gateway (Midtrans + alternatif)
├── Engine kustomisasi & preset tipe bisnis
├── Integrasi aggregator
└── Self-hosted package
```

Rincian per fase ada di [40 — Roadmap Rilis](40-roadmap-rilis.md).

---

## 7. Aturan Rekayasa Untuk Ke Depan

Aturan yang harus dipatuhi setiap PR mulai sekarang:

1. **Tidak ada logika bisnis di controller.** Controller memvalidasi,
   memanggil Action/Service, mengembalikan Resource.
2. **Semua uang lewat `Money` VO.** Tidak ada `float` untuk uang. Tidak ada
   perkalian langsung pada `decimal` di PHP.
3. **Semua tabel tenant punya `tenant_id`** + index komposit. Diisi
   server-side, tidak pernah dari input.
4. **Setiap fitur baru wajib punya test.** Minimal happy path + satu edge
   case + satu authorization test.
5. **Setiap operasi tulis dari klien punya idempotency key.**
6. **Setiap mutasi stok lewat stock ledger.** Tidak pernah `UPDATE
   stock SET qty = qty - N` langsung tanpa mencatat pergerakan.
7. **Setiap integrasi eksternal di balik interface.** Payment, WhatsApp,
   storage, printer — semua punya driver + implementasi `log`/`fake` untuk
   test.
8. **Migrasi harus reversible** dan diuji di PostgreSQL & MySQL.
9. **Tidak ada `enum` DB baru.** Pakai string + PHP enum.
10. **Bahasa Indonesia untuk semua teks pengguna**, English untuk kode,
    komentar, dan commit message.
