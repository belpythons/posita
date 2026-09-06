# PROGRESS — Status Work Package Posita

> **Sumber kebenaran status WP.** Perbarui di akhir setiap sesi. Protokol kerjanya
> ada di [`EXECUTE.md`](EXECUTE.md).

**Berikutnya: WP0.1 — [P01 Multi-Tenancy & Outlet](P01-multi-tenancy.md).**

---

## Baseline Repo — audit 6 Sep 2026

Fakta hasil audit, supaya sesi berikutnya tidak perlu mengaudit ulang.

**Semua WP P01–P20 belum dimulai.** `docs/prompts/` masuk sebagai dokumen saja di
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
| WP0.1 | [P01 Multi-Tenancy & Outlet](P01-multi-tenancy.md) | F0 | ⬜ Belum | — | — |
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
| Belum ada `routes/api.php`; middleware outlet berbasis header `X-Outlet-Id` belum punya grup `api` untuk didaftarkan | `bootstrap/app.php` `withRouting()` tanpa `api:` | P10 |

---

## Catatan Penyimpangan

Format: `WP · tanggal · apa yang menyimpang dari prompt · alasan`

_(belum ada)_
