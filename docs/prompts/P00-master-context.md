# P00 — Master Context

> **Muat prompt ini di awal setiap sesi kerja Posita.** Prompt lain
> mengasumsikan isi dokumen ini sudah dibaca.

---

## Tentang Produk

**Posita** adalah aplikasi POS (Point of Sale) multi-tenant untuk bisnis
Food & Drink Indonesia, dengan fokus **coffee shop**, yang menangani
penjualan produk sendiri **dan** produk titipan mitra (konsinyasi) dalam
satu sistem.

**Tiga pilar diferensiasi** — setiap keputusan teknis harus melayani salah
satunya:

1. **Profit Guard** — resep/BOM, HPP hidup, waste tracking, laporan
   kebocoran (variance aktual vs teoretis), alert WhatsApp.
2. **Mitra Ready** — konsinyasi kelas satu: batch titipan, bagi hasil
   fleksibel, settlement, transparansi ke mitra.
3. **Punya Kamu, Bentuk Kamu** — ekspor data penuh, API terbuka, engine
   kustomisasi, preset tipe bisnis, template struk & menu milik user.

Dokumen lengkap ada di `docs/`. Yang paling sering dibutuhkan:
- `docs/20-blueprint-produk.md` — model data & aturan bisnis semua modul
- `docs/30-arsitektur-target.md` — layering, kontrak API, keamanan
- `docs/10-audit-project.md` — kondisi kode & aturan rekayasa

---

## Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.3+ |
| Database | PostgreSQL 16 (produksi), MySQL 8 (didukung), SQLite (test) |
| Cache & Queue | Redis + Laravel Horizon |
| Auth web | Session (Breeze) |
| Auth API | Laravel Sanctum (token) |
| Web back office | Inertia.js 2 + Vue 3 + TypeScript + Tailwind 3 |
| Mobile | React Native + Expo (dev client) + TypeScript |
| DB lokal mobile | expo-sqlite / op-sqlite + Drizzle ORM |
| Test backend | Pest 4 |
| Static analysis | PHPStan level 6, Laravel Pint |

---

## Aturan Rekayasa — WAJIB

Setiap PR harus memenuhi semua poin ini.

### Arsitektur

1. **Tidak ada logika bisnis di controller.** Controller hanya: validasi
   (FormRequest) → otorisasi (Policy) → panggil Action → kembalikan
   Resource/Inertia response.
2. **Satu Action = satu use case**, dengan satu method `handle()`. Bisa
   dites tanpa HTTP. Letakkan di `app/Actions/<Modul>/`.
3. **Model tidak memanggil Action.** Model hanya relasi, scope, cast,
   accessor.
4. **Efek samping lewat domain event.** Contoh: `OrderCompleted` memicu
   potong stok, evaluasi notifikasi, update statistik — via listener.
5. **Integrasi eksternal di balik interface** + driver. Wajib ada
   implementasi `log`/`fake` untuk test.

### Data

6. **Semua tabel milik tenant punya `tenant_id`** + index komposit
   `(tenant_id, id)` dan `(tenant_id, created_at)`.
7. **`tenant_id` SELALU diisi server-side** dari konteks terautentikasi.
   Tidak pernah dari request body. Ini aturan keamanan, bukan konvensi.
8. **Semua uang lewat `Money` value object.** Disimpan sebagai `bigint`
   rupiah utuh. Tidak ada `float`, tidak ada aritmetika langsung pada
   `decimal`.
9. **Semua mutasi stok lewat `stock_movements`.** Tidak pernah `UPDATE
   stock_levels` langsung. `stock_levels` hanya cache.
10. **Ledger append-only.** Koreksi = baris baru bertipe `adjustment`.
11. **Snapshot data historis.** `order_items` menyimpan nama, harga, dan
    HPP saat transaksi. Mengubah master tidak boleh mengubah riwayat.
12. **Soft delete** pada semua entitas bisnis.
13. **Tidak ada `enum` DB baru.** Pakai `string` + PHP backed enum.
14. **Setiap operasi tulis dari klien butuh `idempotency_key`** (ULID) dengan
    unique constraint.
15. **Migrasi harus reversible** dan lulus di PostgreSQL & MySQL.

### Kualitas

16. **Setiap fitur baru wajib punya test**: minimal 1 happy path + 1 edge
    case + 1 authorization test + 1 tenant isolation test.
17. **Coverage ≥70%** pada modul yang disentuh.
18. **PHPStan level 6** tanpa error baru dan tanpa menambah baseline.
19. **Pint** harus bersih.

### Bahasa

20. **Kode, komentar, nama variabel, commit message: bahasa Inggris.**
21. **Semua teks yang dilihat pengguna: bahasa Indonesia.** Lewat file
    `lang/id/`, bukan string keras di kode.
22. Format angka: `Rp 1.234.567` (titik ribuan). Tanggal: `6 Sep 2026`.
    Zona waktu default `Asia/Jakarta`.

---

## Konvensi Penamaan

```
Tabel            : snake_case jamak            (order_items)
Kolom            : snake_case                  (created_at, tenant_id)
Model            : PascalCase tunggal          (OrderItem)
Action           : VerbNoun                    (CreateOrder, VoidOrder)
Service          : NounService                 (PricingService)
Event            : NounPastTense               (OrderCompleted)
Job              : VerbNounJob                 (SendWhatsAppJob)
Enum             : PascalCase                  (OrderStatus)
Route API        : kebab-case jamak            (/api/v1/stock-movements)
Route name       : dot.notation                (api.orders.store)
Vue component    : PascalCase                  (ProductGrid.vue)
File TS/JS       : camelCase                   (useCart.ts)
Permission       : modul.aksi[.kualifikasi]    (sales.discount.manual)
```

---

## Struktur Direktori Target

```
app/
├── Actions/<Modul>/          satu use case per kelas
├── Domain/
│   ├── Enums/                PHP backed enum
│   ├── Events/               domain event
│   └── ValueObjects/         Money, Quantity, Percentage
├── Http/
│   ├── Controllers/Api/V1/   controller API
│   ├── Controllers/Web/      controller Inertia
│   ├── Requests/             FormRequest
│   ├── Resources/            JsonResource
│   └── Middleware/
├── Models/
├── Policies/
├── Services/                 orkestrasi & query kompleks
├── Support/
│   ├── Payment/              kontrak + driver
│   ├── Notification/         kontrak + driver
│   ├── Printing/
│   └── Export/
└── Jobs/

database/migrations/
lang/id/
resources/js/                 Inertia + Vue back office
tests/
├── Feature/<Modul>/
└── Unit/<Modul>/
docs/                         dokumen perencanaan (JANGAN diubah tanpa alasan)
```

---

## Perintah yang Harus Lulus Sebelum Commit

```bash
vendor/bin/pint                      # format
vendor/bin/phpstan analyse           # static analysis level 6
php artisan test                     # seluruh test suite
npm run build                        # build frontend
```

---

## Definisi Selesai (Definition of Done) Global

Sebuah work package dianggap selesai jika:

- [ ] Semua Acceptance Criteria di prompt terpenuhi & terverifikasi
- [ ] Test ditulis dan hijau (termasuk tenant isolation)
- [ ] `pint`, `phpstan`, `test`, `build` semuanya lulus
- [ ] Migrasi reversible & teruji di PostgreSQL + MySQL
- [ ] Teks pengguna dalam bahasa Indonesia via `lang/id/`
- [ ] Tidak ada rahasia/kredensial masuk repo
- [ ] Dokumen `docs/` diperbarui jika ada keputusan yang menyimpang
- [ ] Commit message jelas, dalam bahasa Inggris, conventional commits

---

## Anti-Pattern yang Dilarang

❌ Logika bisnis di controller atau di Vue component
❌ `float` untuk uang
❌ `UPDATE stock SET qty = qty - N` tanpa mencatat movement
❌ `tenant_id` diambil dari request
❌ Mengubah `order_items` setelah order selesai
❌ String teks pengguna langsung di kode PHP/Vue
❌ Memanggil API eksternal langsung tanpa interface & driver
❌ Menambahkan fitur di luar Ruang Lingkup prompt ("sekalian saja")
❌ `enum` di level database
❌ Menghapus baris ledger atau audit log
