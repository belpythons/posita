# PROGRESS — Status Work Package Posita

> **Sumber kebenaran status WP.** Perbarui di akhir setiap sesi, sebelum commit.
> Protokol kerjanya ada di [`EXECUTE.md`](EXECUTE.md).
>
> **Tapi jangan percaya file ini secara buta.** Ini catatan, bukan kebenaran —
> ia bisa basi. Selalu verifikasi prasyarat ke kode dan test sebelum melanjutkan.

**Berikutnya: WP0.2 — [P02 RBAC & Permission](P02-rbac.md).**
**Sedang berjalan:** —

---

## Baseline Repo — audit 6 Sep 2026

Fakta hasil audit, supaya sesi berikutnya tidak perlu mengaudit ulang.

**Saat audit ditulis, semua WP belum dimulai.** Sejak itu WP0.1 selesai di
`feat/tenancy` — tabel status di bawah yang berlaku, bukan paragraf ini.

Audit asli: `docs/prompts/` masuk sebagai dokumen saja di
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
| Test | 6 file, semuanya bawaan Breeze (auth + profile). **Nol test logika bisnis.** Sejak F0 (6 Sep 2026): `php artisan test` **hijau, 25 lulus, exit code 0** — lihat catatan di bawah. |
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

## Baseline Test — diperbaiki di F0, 6 Sep 2026

Branch `fix/test-baseline`. `php artisan test` **tanpa flag** sebelumnya keluar
dengan exit code 2 (abort) dan menyembunyikan 3 kegagalan; sekarang **25 lulus,
exit code 0**.

| Masalah | Diagnosis | Perbaikan |
|---|---|---|
| `php artisan test` abort | `phpunit.xml` menunjuk `tests/Unit` yang tidak ada | Direktori dibuat (`.gitkeep`). Testsuite kosong ternyata **tidak** membuat exit code non-zero, jadi tidak perlu test isian. P03 yang mengisinya. |
| `RegistrationTest` (2 test) | **Testnya usang.** `fe27e64` menghapus route register **dan** kelas `RegisteredUserController`; registrasi publik memang sengaja dibuang | Ditulis ulang jadi penjaga: route tidak terdaftar, `GET`/`POST /register` → 404, tetap guest, user tidak terbuat |
| `AuthenticationTest > users can authenticate` | **Testnya usang.** Kode sengaja redirect per role lewat `AuthService::getRedirectPath()`; test masih memakai ekspektasi `/dashboard` bawaan Breeze. Aktual: `/pos/open` | Diganti dua test yang menutup kedua cabang `match`: employee → `/pos/open`, admin → `/admin` |

**Nol bug kode ditemukan** — ketiganya test yang tertinggal di belakang refactor.
Tidak ada test yang di-skip, di-disable, atau dihapus; jumlah test justru naik 23 → 25.
Nol perubahan di `app/`, `routes/`, `database/`, `config/`.

---

## Status Work Package

Legenda: `⬜ Belum` · `🟡 Jalan` · `✅ Selesai` · `⛔ Terblokir` · `⏸️ Ditunda`

### Fase 0 — Fondasi & Hardening

> **Gate keluar:** demo 2 tenant, 3 outlet, 5 user beda role; buktikan isolasi
> data dan penegakan izin.

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP0.1 | [P01 Multi-Tenancy & Outlet](P01-multi-tenancy.md) | — | 1,5 pekan | ✅ Selesai | `feat/tenancy` | 6 Sep 2026 |
| WP0.2 | [P02 RBAC & Permission](P02-rbac.md) | P01 | 1 pekan | ⬜ Belum | — | — |
| WP0.3–0.7 | [P03 Money VO, Test Harness, CI, Cleanup](P03-foundation-quality.md) | P01, P02 | 2 pekan | ⬜ Belum | — | — |

### Fase 1 — Domain Inti POS

> **Gate keluar:** simulasi 1 hari operasi kedai (80 transaksi, void, refund,
> restock, waste, tutup shift) dengan semua angka konsisten & bisa ditelusuri.

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP1.1 | [P04 Catalog: Produk, Varian, Modifier](P04-catalog.md) | P03 | 2 pekan | ⬜ Belum | — | — |
| WP1.2 | [P05 Inventory & Stock Ledger](P05-inventory.md) | P04 | 2,5 pekan | ⬜ Belum | — | — |
| WP1.3 | [P06 Recipe/BOM & HPP](P06-recipe-hpp.md) | P04, P05 | 1,5 pekan | ⬜ Belum | — | — |
| WP1.4 | [P07 Procurement & Restock](P07-procurement.md) | P05, P06 | 1 pekan | ⬜ Belum | — | — |
| WP1.5 | [P08 Sales Core](P08-sales.md) | P04–P07 | 2,5 pekan | ⬜ Belum | — | — |
| WP1.6 | [P09 Cash & Shift](P09-cash-shift.md) | P08 | 1 pekan | ⬜ Belum | — | — |
| WP1.7 | [P22 Customer & Piutang (Kasbon)](P22-customer-piutang.md) | P08 | 1 pekan | ⬜ Belum | — | — |

### Fase 2 — API & Aplikasi Kasir

> **Gate keluar:** uji lapangan 1 minggu di 1 kedai nyata, sistem lama sebagai
> cadangan.

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP2.1 | [P10 API v1 & OpenAPI](P10-api-v1.md) | P01–P09 | 1,5 pekan | ⬜ Belum | — | — |
| WP2.2–2.6 | [P11 Aplikasi Kasir Expo](P11-mobile-pos.md) | P10 | 5 pekan | ⬜ Belum | — | — |

### Fase 3 — Offline-First

> **Gate keluar — RILIS v1.0:** uji lapangan 2 minggu di 3 kedai (termasuk 1
> lokasi internet buruk), nol kehilangan transaksi.

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP3.1–3.5 | [P12 Offline Sync Engine](P12-offline-sync.md) | P11 | 5 pekan | ⬜ Belum | — | — |

### Fase 4 — Pembeda Kompetitif

> **Gate keluar — RILIS v1.5**

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP4.1 | [P13 Konsinyasi Mitra](P13-konsinyasi.md) | P08, P12 | 2,5 pekan | ⬜ Belum | — | — |
| WP4.2 | [P14 Notifikasi WhatsApp](P14-whatsapp.md) | P05, P09, P13 | 2 pekan | ⬜ Belum | — | — |
| WP4.3–4.4 | [P15 HR & Finance](P15-hr-finance.md) | P09, P14 | 3 pekan | ⬜ Belum | — | — |
| WP4.5 | [P16 Menu Designer & Ekspor](P16-menu-designer.md) | P04 | 2 pekan | ⬜ Belum | — | — |
| WP4.6 | [P17 Laporan Variance / Kebocoran](P17-variance-report.md) | P05, P06 | 1 pekan | ⬜ Belum | — | — |
| WP4.7 | [P23 Meja, Dine-in & KDS](P23-meja-dinein-kds.md) | P08, P11 | 2 pekan | ⬜ Belum | — | — |

### Fase 5 — Skala & Ekosistem

> **Gate keluar — RILIS v2.0**

| WP | Prompt | Prasyarat | Effort | Status | PR | Tanggal |
|---|---|---|---|---|---|---|
| WP5.1 | [P18 Payment Gateway & QRIS](P18-payment.md) | P08, P12 | 2 pekan | ⬜ Belum | — | — |
| WP5.2 | [P19 Engine Kustomisasi & Preset](P19-customization.md) | semua modul | 2 pekan | ⬜ Belum | — | — |
| WP5.3 | [P20 Ekspor Data & Rilis](P20-release.md) | semua | 1,5 pekan | ⬜ Belum | — | — |
| WP5.4 | [P24 Aplikasi Staf](P24-aplikasi-staf.md) | P11, P12, P15 | 1 pekan | ⬜ Belum | — | — |
| WP5.5 | [P25 Integrasi Aggregator](P25-integrasi-aggregator.md) | P04, P08, P17 | 2,5 pekan | ⬜ Belum | — | — |
| WP5.6 | [P26 Paket Self-Hosted](P26-self-hosted.md) | P20, P21 | 0,5 pekan | ⬜ Belum | — | — |
| WP5.7 | [P21 Billing & Subscription](P21-billing-subscription.md) | P01, P18, P19 | 2 pekan | ⬜ Belum | — | — |

**Ringkasan:** ✅ 1 / 26 selesai · 🟡 0 jalan · ⬜ 25 belum · sisa effort ≈ 50 pekan.

> **Catatan penomoran:** nomor file P21–P26 mencerminkan urutan penulisan, bukan
> urutan eksekusi — keenamnya ditambahkan belakangan untuk menutup lubang cakupan.
> **Urutan tabel di atas yang mengikat**, bukan urutan nomor file.

---

## Ledger Utang Teknis

Hal yang sudah diketahui hilang atau salah. Setiap baris punya WP penebusnya —
**jangan diperbaiki di WP yang salah.**

| Utang | Bukti | Ditebus di |
|---|---|---|
| PHPStan/larastan belum terpasang, padahal P00 mewajibkan level 6 | tidak ada `phpstan.neon`, tidak ada di `composer.json` | P03 |
| Tidak ada CI sama sekali | tidak ada direktori `.github/` | P03 |
| Nol test logika bisnis — 7 service tanpa satu pun test | `tests/` hanya berisi bawaan Breeze | P03 |
| Uang disimpan `decimal(12,2)` (13 kolom), bukan `bigint` + Money VO | seluruh migrasi | P03 |
| Tidak ada `pint.json` — Pint jalan dengan preset default | root repo | P03 |
| `enum` level database di 4 tempat: `users.role`, `box_templates.type`, `shop_sessions.status`, `box_orders.status` | migrasi terkait | P02 (role) / P08 (order) / P09 (session) |
| Migrasi belum pernah diverifikasi di PostgreSQL maupun MySQL | tidak ada server DB di lingkungan ini | P03 |
| File `retailer` (106 KB, tanpa ekstensi) tercatat di git root, tidak dirujuk config manapun | `git ls-files \| grep -x retailer` | P03 (cleanup) |
| `resources/js/Pages/Auth/Register.vue` yatim: halaman Vue untuk fitur yang controller-nya (`RegisteredUserController`) sudah dihapus di `fe27e64`; tidak bisa dirender karena route-nya 404 | `git ls-tree` vs `resources/js/Pages/Auth/` | P03 (hapus) |
| `UserFactory` tidak mengeset `role` maupun `is_active`, jadi model in-memory bernilai `null` sementara DB memakai default kolom. `RoleMiddleware` menolak `!$user->is_active`, sehingga `actingAs(User::factory()->create())` gagal di route ber-middleware `role` dengan pesan membingungkan | `database/factories/UserFactory.php` | P03 |
| `routes/auth.php` menyisakan baris kosong bekas import `RegisteredUserController` | `routes/auth.php` baris 9-10 | P03 |
| 7 index lama di `2025_12_25_000002` kini redundan: setelah query ter-scope, `tenant_id` bukan kolom terdepan sehingga index `status, created_at` dsb. tidak terpakai | migrasi tsb. vs index bertenant baru | P03 (cleanup) |
| `activity_log` tanpa `tenant_id`; Spatie menulis `logOnly(['*'])` dari User, Partner, DailyConsignment | migrasi `activity_log` | P02 — belum ada jalur baca sama sekali di aplikasi, jadi belum jadi kebocoran nyata |
| `config/app.php` timezone hardcoded `'UTC'`, padahal P00 menuntut `Asia/Jakarta`; batas hari `daily_stats` ikut UTC | `config/app.php:68` | P03 |
| `PosDashboardViewModel` mati dan rusak: tidak pernah diinstansiasi, merujuk 6 kolom yang tidak ada di `daily_consignments` | `app/ViewModels/PosDashboardViewModel.php` | P03 (hapus) |
| Pint belum pernah dijalankan repo-wide; WP0.1 hanya memformat file yang disentuhnya agar diff tetap fokus | `vendor/bin/pint --test` | P03 |
| `P06-recipe-hpp.md:32` merujuk "P09 preorder" — P09 adalah Cash & Shift; modul preorder tidak ada di WP manapun | `P06:32` vs `P09-cash-shift.md` | butuh WP baru (lihat Lubang Cakupan #1) |
| `P21-billing-subscription.md:112` menetapkan kuota `preorders_per_month` untuk tabel yang tidak pernah dibuat WP manapun | `P21:112` | idem |
| `P14-whatsapp.md:222` menjadikan alert "box order H-1" salah satu dari 6 default aktif, tanpa modul sumbernya | `P14:222` | idem |
| Urutan eksekusi menempatkan P14 (WhatsApp) sebelum P17 (variance) & P15 (finance), padahal katalog alertnya memuat `STOCK_VARIANCE_HIGH`, `FOOD_COST_HIGH`, dan alert absensi yang event-nya baru lahir belakangan | `P14` katalog vs urutan di `README.md` | P14 (turunkan default) atau urutkan ulang |
| 16 file sumber masih memuat header `NIM: 2023xxxxx` (audit S2), dan `CONTRIBUTORS.md` tidak ada. Catatan: audit S1 (README berisi pembagian tugas kuliah) **sudah selesai** — jangan dianggarkan dua kali | grep `NIM:` di `app/` & `resources/js/` | P03 (cleanup) |
| Belum ada `routes/api.php`; middleware outlet berbasis header `X-Outlet-Id` belum punya grup `api` untuk didaftarkan | `bootstrap/app.php` `withRouting()` tanpa `api:` | P10 |
| Preset "Resto Lengkap" di P19 menyalakan feature flag `tables` & `kds`, modulnya baru dibangun di P23 | `P19-customization.md` §18.2 vs `P23` | P23 (atau hapus klaim presetnya) |
| `orders.customer_id` & `customer_credits` dirujuk blueprint sejak M5/M6 tapi entitasnya baru dibuat di P22 | `20-blueprint-produk.md` baris 481, 593 | P22 |
| `tenants.plan` & `subscription_ends_at` dibuat di P01 tapi tidak ada yang mengisinya sampai P21 | `P01` §1 vs `P21` | P21 |

---

## Catatan Penyimpangan

Format: `WP · tanggal · apa yang menyimpang dari prompt · alasan`

Kalau penyimpangannya signifikan, perbarui juga dokumen aslinya di `docs/`
agar tidak ada dua sumber kebenaran.

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

---

## Lubang Cakupan Perencanaan

Hasil pencocokan `docs/riset/*` + `20-blueprint-produk.md` + `50-gtm-pricing-legal.md`
terhadap seluruh `docs/prompts/` (audit 6 Sep 2026). Ini keputusan riset yang
**tidak dimiliki work package manapun** — bukan belum dikerjakan, tapi belum
punya tempat untuk dikerjakan. Diurutkan dari yang paling merugikan.

| # | Lubang | Sumber | Kenapa merugikan |
|---|---|---|---|
| 1 | **M9 Preorder / Catering** — 3 tabel (`preorders`, `preorder_items`, `package_templates`), DP, jadwal produksi, kuota harian, quotation PDF, kalkulasi kebutuhan bahan | `20-blueprint-produk.md` §12; persona P4 di riset 01 §8 (WTP Rp50–150rb) | Empat prompt lain sudah bergantung padanya (lihat 4 baris cacat referensi di ledger). Kode legacy `BoxOrder` dibawa ke multi-tenant oleh P01 lalu ditinggalkan tanpa WP modernisasi |
| 2 | **M14 Reporting** — 12 laporan wajib + dashboard operasional & owner | `20-blueprint-produk.md` §17 | Hanya variance (P17) & finance (P15) yang tertutup. North star GTM ("owner membuka laporan kebocoran") mengandaikan ada tempat membukanya |
| 3 | Laporan omzet & pajak: rekap PPh Final UMKM 0,5% dan setoran PB1 ke Bapenda | riset 01 §7; GTM §4.2 | Kewajiban regulasi, disebut dua kali, nol prompt |
| 4 | Instrumentasi metrik produk (north star + 7 metrik: aktivasi, retensi 30 hari, konversi, churn, adopsi resep, NPS) | GTM §3 | Tanpa event tracking, **tidak satu pun angka itu terukur setelah rilis** |
| 5 | Batas riwayat data per paket (3 bln / 24 bln / tak terbatas) | GTM §2.2 | Pembeda harga sekaligus kewajiban retensi UU PDP; P21 hanya membatasi jumlah entitas |
| 6 | Voucher / deposit prabayar / kartu member | riset 03 §2 (Tier 2) | P08 dan P22 sama-sama membuangnya eksplisit, tanpa WP penerima — penundaan de-facto tanpa keputusan tertulis |
| 7 | `packages/core-logic`, `design-tokens`, `receipt-renderer` | riset 05 §8 (alasan utama memilih React Native) | `P11:93` & `P24:53` sudah **mengimpor** dari `design-tokens`; `P04:209` menjanjikan `core-logic` "nanti". Kalau tidak dibuat, kalkulasi harga/pajak/HPP terduplikasi PHP↔TS — persis yang ADR-001 larang |
| 8 | Laporan konsolidasi lintas outlet | riset 02; persona P2 (Rp150–500rb/bln, jalur monetisasi utama) | P01 membuat entitas outlet, P05 transfer stok; agregat lintas outlet tidak ada |
| 9 | Kalkulator biaya QRIS/MDR di onboarding | riset 03 §6 | "Kejujuran soal biaya bagian dari positioning anti-lock-in"; P18 hanya membangun laporan pasca-transaksi |
| 10 | Onboarding checklist in-app + data contoh sekali-hapus | GTM §5 | Investasi penurun beban support; P19 hanya menutup pemilihan preset |
| 11 | Kalkulator HPP publik (lead magnet SEO) & program referral | GTM §3 | Dua taktik akuisisi eksplisit yang butuh kode; nol prompt |
| 12 | Importer migrasi dari format kompetitor | GTM §3 Fase 1 | Disebut sebagai penghalang terbesar pindah vendor; P04 hanya impor CSV format sendiri |
| 13 | Struk digital ke pelanggan (`RECEIPT_DIGITAL`), alur EDC kartu, penjadwalan stock opname, tampilan resep untuk barista di kasir | riset 01 §3, riset 03, `P14` katalog | Masing-masing kecil, tapi semuanya ada di katalog/riset tanpa lingkup di WP manapun |

**Tindakan yang disarankan:** tulis prompt baru untuk #1 dan #2 sebelum Fase 1
dimulai — keduanya punya WP lain yang sudah bergantung padanya. Sisanya bisa
diputuskan pemilik produk (bangun / tunda / buang), tapi keputusannya harus tertulis.

---

## Perlu Verifikasi Pemilik Produk

Dibawa dari fase riset — **belum terverifikasi**. Jangan dipakai di materi
komersial atau di-hardcode sebelum dicek ke sumber primer.

| # | Item | Sumber yang harus dicek | Dibutuhkan sebelum |
|---|---|---|---|
| 1 | Ambang MDR QRIS 0% per 1 Okt 2026 untuk kategori UKE/UME/UBE | Dokumen resmi Bank Indonesia atau PJP yang dipakai | P18 — kalkulator biaya in-app |
| 2 | Harga & fitur kompetitor (Majoo, Olsera, Pawoon, dll) | Situs resmi masing-masing vendor | Materi pemasaran & perbandingan publik |
| 3 | Tarif PB1/PBJT & ambang omzet per kabupaten/kota | Perda / Bapenda daerah target | P08 — default tarif pajak |
| 4 | Status lisensi Bank Indonesia payment gateway yang dipilih | Daftar PJP berizin BI | P18 — sebelum integrasi |
| 5 | Lisensi resmi project (README bilang unlicense, `composer.json` bilang MIT) | Keputusan pemilik produk | P03 — cleanup |

---

## Catatan Sesi

> Entri terbaru di atas. Ringkas — detail ada di commit & PR.

**6 Sep 2026 (sesi 4)** — F1 selesai: `fix/test-baseline` di-merge ke `feat/tenancy`
supaya dokumentasi tidak mundur dari 26 WP ke 20 WP saat P01 di-merge. `feat/tenancy`
sekarang memuat kode P01 **dan** baseline test hijau. Sekaligus dilakukan pencocokan
menyeluruh riset↔prompt↔kode: hasilnya bagian **Lubang Cakupan Perencanaan** di atas
(13 keputusan riset tanpa WP) dan 4 cacat referensi silang di ledger. Belum ada yang
masuk `main` — ketiga branch masih menunggu review (F2).

**6 Sep 2026 (sesi 3)** — F0: baseline test merah diperbaiki, `php artisan test` exit 0.

**6 Sep 2026 (sesi 2)** — WP0.1 (P01 Multi-Tenancy) selesai di `feat/tenancy`.

**6 Sep 2026** — Dokumentasi perencanaan selesai & merge (PR #25). Dua sesi
paralel membuat `EXECUTE.md` + `PROGRESS.md` secara terpisah (branch
`claude/dreamy-newton-8fdoap` dan `docs/execution-protocol`); keduanya
digabung lewat merge, bukan dibuang salah satu. Eksekusi kode belum dimulai.
