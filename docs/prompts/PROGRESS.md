# PROGRESS — Status Work Package Posita

> **Sumber kebenaran status WP.** Perbarui di akhir setiap sesi. Protokol kerjanya
> ada di [`EXECUTE.md`](EXECUTE.md).

**Berikutnya: WP0.2 — [P02 RBAC & Permission](P02-rbac.md).**

---

## Baseline Repo — audit 6 Sep 2026

Fakta hasil audit, supaya sesi berikutnya tidak perlu mengaudit ulang.

> Baseline di bawah ini adalah kondisi **sebelum** WP0.1. Sejak WP0.1 selesai, tenancy sudah ada — lihat tabel status.

**Saat audit ditulis, semua WP P01–P20 belum dimulai.** `docs/prompts/` masuk sebagai dokumen saja di
commit `787a877`, tanpa kode pendamping. Grep `tenant` case-insensitive di
`app/ database/ resources/ routes/ config/` → **nol hit**.

Yang ada sekarang: aplikasi konsinyasi / box-order **single-tenant**.

| Aspek | Kondisi |
|---|---|
| Migrasi | 15 file. Tabel: `users`, `partners`, `product_templates`, `box_templates`, `shop_sessions`, `daily_consignments`, `box_orders`, `box_order_items`, `daily_stats`, `activity_log`, + tabel bawaan Laravel |
| Model | 8 — `BoxOrder`, `BoxOrderItem`, `BoxTemplate`, `DailyConsignment`, `Partner`, `ProductTemplate`, `ShopSession`, `User` |
| Service | 7 — `AdminData`, `Auth`, `BoxOrder`, `Consignment`, `Dashboard`, `Report`, `ShopSession` |
| Lain | `Observers/ShopSessionObserver`, `ViewModels/PosDashboardViewModel`, `Console/Commands/AggregateDailyStats` |
| **Tidak ada** | `app/Actions`, `app/Policies`, `app/Domain`, `app/Enums`, `app/Support` |
| Otorisasi | Kolom `users.role` enum `admin\|employee` + `RoleMiddleware` string-match. Nol policy. |
| Routing | Hanya `routes/web.php` + `auth.php`. **Belum ada `routes/api.php`** (lingkup P10). |
| Test | 6 file, semuanya bawaan Breeze (auth + profile). **Nol test logika bisnis.** |
| Factory | Hanya `UserFactory`. Nol factory model bisnis. |
| Dependency | Laravel 12, Inertia 2, Sanctum 4, `spatie/laravel-activitylog`, dompdf, ziggy. Dev: Pest 4, Pint, Breeze. |
| Tooling lokal | PHP 8.4.12, Composer 2.9.4, npm 11.16, node 24.18 — tersedia. `vendor/` & `node_modules/` **belum di-install**. |

Titik sentuh yang sudah diketahui untuk P01 (raw query lolos global scope):

- `app/Console/Commands/AggregateDailyStats.php:41` — `DB::table('box_order_items')`
- `app/Console/Commands/AggregateDailyStats.php:57` — `DB::table('daily_stats')`
- `app/Services/DashboardService.php:220` — `DB::table('daily_stats')`

Route model binding yang perlu diamankan: 6 route di `routes/web.php`
(`{session}` ×2, `{consignment}`, `{order}` ×3).

---

## Status Work Package

Legenda: `⬜ Belum` · `🟡 Jalan` · `✅ Selesai` · `⛔ Terblokir`

| WP | Prompt | Fase | Status | PR | Tanggal |
|---|---|---|---|---|---|
| WP0.1 | [P01 Multi-Tenancy & Outlet](P01-multi-tenancy.md) | F0 | ✅ Selesai | `feat/tenancy` | 6 Sep 2026 |
| WP0.2 | [P02 RBAC & Permission](P02-rbac.md) | F0 | ⬜ Belum | — | — |
| WP0.3–0.7 | [P03 Money VO, Test Harness, CI, Cleanup](P03-foundation-quality.md) | F0 | ⬜ Belum | — | — |
| WP1.1 | [P04 Catalog: Produk, Varian, Modifier](P04-catalog.md) | F1 | ⬜ Belum | — | — |
| WP1.2 | [P05 Inventory & Stock Ledger](P05-inventory.md) | F1 | ⬜ Belum | — | — |
| WP1.3 | [P06 Recipe/BOM & HPP](P06-recipe-hpp.md) | F1 | ⬜ Belum | — | — |
| WP1.4 | [P07 Procurement & Restock](P07-procurement.md) | F1 | ⬜ Belum | — | — |
| WP1.5 | [P08 Sales Core](P08-sales.md) | F1 | ⬜ Belum | — | — |
| WP1.6 | [P09 Cash & Shift](P09-cash-shift.md) | F1 | ⬜ Belum | — | — |
| WP2.1 | [P10 API v1 & OpenAPI](P10-api-v1.md) | F2 | ⬜ Belum | — | — |
| WP2.2–2.6 | [P11 Aplikasi Kasir Expo](P11-mobile-pos.md) | F2 | ⬜ Belum | — | — |
| WP3.1–3.5 | [P12 Offline Sync Engine](P12-offline-sync.md) | F3 | ⬜ Belum | — | — |
| WP4.1 | [P13 Konsinyasi Mitra](P13-konsinyasi.md) | F4 | ⬜ Belum | — | — |
| WP4.2 | [P14 Notifikasi WhatsApp](P14-whatsapp.md) | F4 | ⬜ Belum | — | — |
| WP4.3–4.4 | [P15 HR & Finance](P15-hr-finance.md) | F4 | ⬜ Belum | — | — |
| WP4.5 | [P16 Menu Designer & Ekspor](P16-menu-designer.md) | F4 | ⬜ Belum | — | — |
| WP4.6 | [P17 Laporan Variance / Kebocoran](P17-variance-report.md) | F4 | ⬜ Belum | — | — |
| WP5.1 | [P18 Payment Gateway & QRIS](P18-payment.md) | F5 | ⬜ Belum | — | — |
| WP5.2 | [P19 Engine Kustomisasi & Preset](P19-customization.md) | F5 | ⬜ Belum | — | — |
| WP5.3+ | [P20 Ekspor Data & Rilis](P20-release.md) | F5 | ⬜ Belum | — | — |

---

## Ledger Utang Teknis

Hal yang sudah diketahui hilang atau salah. Setiap baris punya WP penebusnya —
**jangan diperbaiki di WP yang salah.**

| Utang | Bukti | Ditebus di |
|---|---|---|
| PHPStan/larastan belum terpasang, padahal P00 mewajibkan level 6 | tidak ada `phpstan.neon`, tidak ada di `composer.json` | P03 |
| Tidak ada CI sama sekali | tidak ada direktori `.github/` | P03 |
| `phpunit.xml` mendeklarasikan testsuite `tests/Unit` yang direktorinya tidak ada | `phpunit.xml` vs `ls tests/` | P03 |
| Nol test logika bisnis — 7 service tanpa satu pun test | `tests/` hanya berisi bawaan Breeze | P03 |
| Uang disimpan `decimal(12,2)` (13 kolom), bukan `bigint` + Money VO | seluruh migrasi | P03 |
| Tidak ada `pint.json` — Pint jalan dengan preset default | root repo | P03 |
| `enum` level database di 4 tempat: `users.role`, `box_templates.type`, `shop_sessions.status`, `box_orders.status` | migrasi terkait | P02 (role) / P08 (order) / P09 (session) |
| Migrasi belum pernah diverifikasi di PostgreSQL maupun MySQL | tidak ada server DB di lingkungan ini | P03 |
| File `retailer` (106 KB, tanpa ekstensi) tercatat di git root, tidak dirujuk config manapun | `git ls-files \| grep -x retailer` | P03 (cleanup) |
| 7 index lama di `2025_12_25_000002` kini redundan: setelah query ter-scope, `tenant_id` bukan kolom terdepan sehingga index `status, created_at` dsb. tidak terpakai | migrasi tsb. vs index bertenant baru | P03 (cleanup) |
| `activity_log` tanpa `tenant_id`; Spatie menulis `logOnly(['*'])` dari User, Partner, DailyConsignment | migrasi `activity_log` | P02 — belum ada jalur baca sama sekali di aplikasi, jadi belum jadi kebocoran nyata |
| `config/app.php` timezone hardcoded `'UTC'`, padahal P00 menuntut `Asia/Jakarta`; batas hari `daily_stats` ikut UTC | `config/app.php:68` | P03 |
| `PosDashboardViewModel` mati dan rusak: tidak pernah diinstansiasi, merujuk 6 kolom yang tidak ada di `daily_consignments` | `app/ViewModels/PosDashboardViewModel.php` | P03 (hapus) |
| `RegistrationTest` menguji route registrasi yang sudah dihapus dari `routes/auth.php` (2 test merah sejak sebelum WP0.1) | `php artisan test --testsuite=Feature` | P03 |
| `AuthenticationTest` mengharap redirect ke `route('dashboard')`, tapi `bootstrap/app.php` mengarahkan per role (1 test merah sejak sebelum WP0.1) | idem | P03 |
| Pint belum pernah dijalankan repo-wide; WP0.1 hanya memformat file yang disentuhnya agar diff tetap fokus | `vendor/bin/pint --test` | P03 |
| Belum ada `routes/api.php`; middleware outlet berbasis header `X-Outlet-Id` belum punya grup `api` untuk didaftarkan | `bootstrap/app.php` `withRouting()` tanpa `api:` | P10 |

---

## Catatan Penyimpangan

Format: `WP · tanggal · apa yang menyimpang dari prompt · alasan`

- **WP0.1 · 6 Sep 2026 · Kolom `users.is_super_admin` ditambahkan.** P01 menuntut
  middleware menolak user yang "tidak punya tenant DAN bukan super admin", tapi
  konsep super admin belum ada. Karena `users.tenant_id` wajib nullable, "tenant
  null = super admin" akan menjadikan siapa pun tanpa tenant sebagai super admin.
  Satu kolom boolean lebih aman daripada mengubah enum `role` (itu lingkup P02).
- **WP0.1 · 6 Sep 2026 · Lubang otorisasi non-tenant ikut ditutup.**
  `ConsignmentService::bulkUpdateSoldQuantities` dan
  `bulkUpdateRemainingQuantities` menerima array id dari request tanpa
  mencocokkannya dengan sesi pemanggil, sehingga sesama karyawan satu tenant bisa
  menimpa data rekannya. Diperbaiki di service (kedua pemanggil lewat sana) dengan
  menambah parameter `ShopSession`. Di luar lingkup ketat P01, disetujui user.
- **WP0.1 · 6 Sep 2026 · `unique:users,email` sengaja dibiarkan global**, tidak
  per-tenant. `AuthService::login` mencari user by email sebelum konteks tenant
  ada; email unik per-tenant membuat login memilih tenant sembarang.
- **WP0.1 · 6 Sep 2026 · Tujuh index dari
  `2025_12_25_000002_add_performance_indexes.php` tidak disentuh.** Migrasi itu
  men-drop index by name di `down()`-nya; menghapusnya dari migrasi lain membuat
  rollback gagal. Index bertenant ditambahkan terpisah; yang lama kini redundan
  (masuk ledger utang).
- **WP0.1 · 6 Sep 2026 · Tenant kedua diberi dataset ringan**, bukan salinan penuh
  seeder tenant pertama. `BoxOrderSeeder` membuat ~700 order tiga bulan dan
  memanggil `stats:aggregate-daily` per hari; menggandakannya melipatduakan waktu
  setiap `migrate:fresh --seed` tanpa menambah bukti isolasi.
- **WP0.1 · 6 Sep 2026 · Row Level Security PostgreSQL tidak dikerjakan.**
  `docs/30-arsitektur-target.md` menyebutnya sebagai lapis kedua, tapi tidak ada
  di Ruang Lingkup P01 dan tidak ada server PostgreSQL untuk mengujinya.
- **WP0.1 · 6 Sep 2026 · `routes/api.php` tidak dibuat.** `ResolveOutletContext`
  sudah membaca header `X-Outlet-Id` agar siap dipakai grup `api`, tapi grup itu
  sendiri lingkup P10. Middleware diuji lewat route web.
