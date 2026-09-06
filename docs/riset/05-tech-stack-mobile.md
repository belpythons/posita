# 05 — Tech Stack & Re-engineering ke Mobile

Tujuan: menentukan arsitektur teknis yang bisa membawa Posita dari aplikasi
web monolit Laravel+Inertia menjadi produk POS mobile-first yang
offline-capable, tanpa membuang investasi yang sudah ada.

---

## 1. Kondisi Saat Ini

| Layer | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (Composition API) + Inertia.js 2 |
| Styling | Tailwind CSS 3 + Shadcn-style components (radix-vue) |
| Database | MySQL |
| Auth | Laravel Breeze (session-based) |
| PDF | barryvdh/laravel-dompdf |
| Audit | spatie/laravel-activitylog |
| Build | Vite 5 |
| Test | Pest 4 |

**Karakter arsitektur:** monolit server-rendered lewat Inertia. Tidak ada
lapisan API. Autentikasi berbasis session cookie. Tidak ada state client
yang persist. Tidak bisa dipakai offline.

---

## 2. Kenapa Perlu Mobile

1. **Perangkat nyata di kasir Indonesia** adalah tablet Android murah
   (Rp1,5–3 juta) atau HP Android, bukan laptop. Browser di perangkat ini
   lambat, tidak stabil di jam sibuk, dan tidak punya akses hardware.
2. **Akses hardware.** Printer thermal Bluetooth, cash drawer via printer,
   kamera untuk scan barcode & foto absensi, GPS untuk geofence absensi,
   NFC untuk kartu member. Semua ini terbatas atau tidak mungkin di browser.
3. **Offline.** Internet putus di jam sibuk adalah kejadian rutin, bukan
   edge case. Aplikasi native memberi kontrol penuh atas penyimpanan lokal
   dan antrean sinkronisasi.
4. **Persepsi pasar.** "Aplikasi kasir" di benak owner Indonesia = aplikasi
   yang di-install dari Play Store. Web app terasa seperti "website", bukan
   produk.

---

## 3. Evaluasi Opsi

### 3.1 Opsi A — PWA (kembangkan yang ada)

| Aspek | Penilaian |
|---|---|
| Biaya | ⭐⭐⭐⭐⭐ Termurah — reuse hampir semua kode |
| Offline | ⭐⭐ Service worker + IndexedDB bisa, tapi rapuh; iOS Safari membatasi kuota & bisa evict data |
| Printer Bluetooth | ⭐ Web Bluetooth tidak didukung iOS, tidak stabil di Android WebView |
| Distribusi | ⭐⭐ Tidak di Play Store; onboarding "Add to Home Screen" membingungkan pengguna awam |
| Performa | ⭐⭐⭐ Cukup, tapi terasa lambat di tablet murah |
| **Verdict** | ❌ Tidak cukup untuk POS produksi. **Tapi bagus untuk back-office & portal mitra.** |

### 3.2 Opsi B — Flutter

| Aspek | Penilaian |
|---|---|
| Performa Android murah | ⭐⭐⭐⭐⭐ Terbaik — kompilasi AOT, konsisten |
| Offline | ⭐⭐⭐⭐⭐ `drift` (SQLite) / Isar sangat matang |
| Printer thermal | ⭐⭐⭐⭐ Library ESC/POS tersedia & aktif |
| Ekosistem Indonesia | ⭐⭐⭐⭐ Banyak dipakai, talenta cukup |
| **Reuse dengan tim saat ini** | ⭐ **Dart adalah bahasa baru.** Tim saat ini menulis Vue/JS |
| Reuse logika web | ⭐ Nol |
| **Verdict** | 🟡 Pilihan teknis sangat baik, tapi biaya switching bahasa tinggi untuk tim kecil |

### 3.3 Opsi C — React Native + Expo ✅ **REKOMENDASI**

| Aspek | Penilaian |
|---|---|
| Performa Android murah | ⭐⭐⭐⭐ New Architecture (JSI, Fabric, TurboModules) di RN 0.73+ menutup sebagian besar gap |
| Offline | ⭐⭐⭐⭐⭐ `expo-sqlite` / `op-sqlite` + Drizzle ORM, atau WatermelonDB |
| Printer thermal | ⭐⭐⭐⭐ Ekosistem library ESC/POS Bluetooth/USB/LAN cukup matang |
| **Reuse dengan tim saat ini** | ⭐⭐⭐⭐⭐ **JavaScript/TypeScript sama** dengan frontend Vue. Tim tidak ganti bahasa |
| Reuse logika bisnis | ⭐⭐⭐⭐⭐ Paket TypeScript bersama (kalkulasi harga, pajak, HPP) dipakai web **dan** mobile |
| Build & distribusi | ⭐⭐⭐⭐⭐ Expo EAS Build (cloud, tanpa Mac untuk Android), EAS Update untuk OTA patch |
| Talenta Indonesia | ⭐⭐⭐⭐⭐ Paling melimpah & termurah |
| **Verdict** | ✅ **Terbaik untuk konteks ini** |

### 3.4 Opsi D — Rewrite total (Next.js + Node/NestJS)

❌ **Ditolak.** Membuang ~2.000 baris logika domain yang sudah teruji,
memperkenalkan risiko besar, tanpa keuntungan yang sepadan. Laravel adalah
framework yang sangat produktif untuk domain ini (queue, scheduler, ORM,
validasi, PDF, ekspor semuanya sudah tersedia).

---

## 4. Keputusan Arsitektur (ADR)

### ADR-001 — Pertahankan Laravel sebagai core, ubah jadi API-first

**Status:** Diterima
**Konteks:** Sudah ada domain logic yang bekerja (session, konsinyasi, box
order). Tim menguasai Laravel. Kebutuhan baru menuntut klien selain browser.
**Keputusan:** Ekstrak lapisan API JSON (`/api/v1`) dari service layer yang
sudah ada. Inertia web tetap hidup, memanggil service yang **sama**. Tidak
ada duplikasi logika bisnis.
**Konsekuensi:** Perlu disiplin — logika bisnis wajib di service/action,
controller hanya tipis. Perlu Sanctum token auth berdampingan dengan session.

---

### ADR-002 — React Native + Expo untuk aplikasi kasir

**Status:** Diterima
**Alternatif ditolak:** Flutter (biaya switching bahasa), PWA (offline &
hardware tidak memadai).
**Keputusan:** Aplikasi kasir dan aplikasi karyawan dibangun dengan Expo
(dev client, bukan Expo Go, karena butuh native module printer).
**Konsekuensi:** Perlu EAS Build. Perlu paket TS bersama untuk logika
kalkulasi. Perlu pengujian di perangkat Android low-end nyata.

---

### ADR-003 — Back-office tetap web (Inertia + Vue 3)

**Status:** Diterima
**Konteks:** Kerja back-office (kelola resep, laporan, desain menu, atur
notifikasi) lebih nyaman di layar besar dengan keyboard.
**Keputusan:** Modernisasi web yang ada — tambahkan TypeScript, bukan
diganti. Tambah aplikasi mobile terpisah, bukan mengganti web.
**Konsekuensi:** Dua frontend. Dikelola dengan paket TS bersama + design
token bersama.

---

### ADR-004 — Offline-first dengan SQLite + Outbox Sync

**Status:** Diterima
**Keputusan:** Aplikasi mobile menulis ke SQLite lokal terlebih dulu; server
adalah *eventual consistency* target. Sinkronisasi lewat outbox pattern
dengan idempotency key.
**Alternatif dipertimbangkan:** PowerSync / ElectricSQL — matang, tapi
menambah dependensi & biaya berlangganan, dan model data POS punya aturan
konflik yang spesifik (order bersifat append-only sehingga konflik jarang).
**Konsekuensi:** Perlu bangun sync engine sendiri. Butuh test suite khusus
untuk skenario jaringan.

---

### ADR-005 — Multi-tenancy dengan isolasi row-level (`tenant_id`)

**Status:** Diterima
**Keputusan:** Satu database bersama, kolom `tenant_id` pada setiap tabel
yang dimiliki tenant, ditegakkan dengan Laravel global scope + middleware.
Bukan database-per-tenant.
**Alasan:** Kompleksitas operasional terendah, biaya per-tenant terendah,
analitik lintas tenant mudah, dan skala ribuan tenant tercapai. Ini pola
dominan 2026.
**Konsekuensi wajib:**
- Setiap tabel tenant punya `tenant_id` + index komposit `(tenant_id, id)`
  dan `(tenant_id, created_at)`.
- `tenant_id` **selalu diisi server-side**, tidak pernah dari klien.
- Global scope di base model + test yang membuktikan kebocoran mustahil.
- Job & event queue harus tenant-aware.

---

### ADR-006 — PostgreSQL sebagai target, MySQL didukung

**Status:** Diusulkan
**Konteks:** PostgreSQL memberi Row Level Security sebagai jaring pengaman
kedua untuk isolasi tenant, tipe JSONB yang lebih baik untuk engine
kustomisasi, dan window function yang lebih kuat untuk laporan variance.
**Keputusan:** Target produksi PostgreSQL 16+. Pertahankan kompatibilitas
MySQL 8 untuk pengguna self-hosted.
**Konsekuensi:** Hindari SQL spesifik-vendor di query builder. Migrasi harus
diuji di kedua engine di CI.

---

## 5. Arsitektur Target

```
┌──────────────────────────────────────────────────────────────────┐
│                          KLIEN                                   │
├──────────────────┬──────────────────┬───────────────┬────────────┤
│  Posita Kasir    │  Posita Staf     │  Back Office  │ Menu Publik│
│  (React Native)  │  (React Native)  │ (Inertia+Vue) │  (SSR/PWA) │
│                  │                  │               │            │
│ • Jual offline   │ • Absensi selfie │ • Resep & HPP │ • Menu QR  │
│ • SQLite lokal   │ • Lihat jadwal   │ • Laporan     │ • Portal   │
│ • Print thermal  │ • Slip gaji      │ • Menu design │   mitra    │
│ • Scan barcode   │ • Ajukan izin    │ • Notif rules │            │
└────────┬─────────┴────────┬─────────┴───────┬───────┴─────┬──────┘
         │ REST/JSON        │                 │ Inertia     │
         │ + Sync Protocol  │                 │             │
         ▼                  ▼                 ▼             ▼
┌──────────────────────────────────────────────────────────────────┐
│                     LARAVEL 12 — API FIRST                       │
│                                                                  │
│  HTTP Layer      : Controllers (tipis) • FormRequest • Resource  │
│  Application     : Actions / Services / ViewModels               │
│  Domain          : Entities • Value Objects • Domain Events      │
│  Infrastructure  : Repositories • Drivers • Integrations         │
│                                                                  │
│  Modul: Catalog · Inventory · Recipe · Sales · Cash · Partner    │
│         · HR · Finance · Notification · Payment · MenuDesign     │
│         · Customization · Reporting · Sync                       │
└───────┬──────────────┬──────────────┬───────────────┬───────────┘
        │              │              │               │
        ▼              ▼              ▼               ▼
┌──────────────┐ ┌───────────┐ ┌────────────┐ ┌──────────────────┐
│ PostgreSQL16 │ │  Redis    │ │ S3-compat  │ │ Integrasi Luar   │
│ (row-level   │ │ cache +   │ │ storage    │ │ • Midtrans/Duitku│
│  tenancy)    │ │ queue     │ │ (foto,PDF) │ │ • Fonnte/WA Cloud│
└──────────────┘ └───────────┘ └────────────┘ │ • GoFood/Grab(F5)│
                                              └──────────────────┘
```

---

## 6. Protokol Sinkronisasi Offline

### 6.1 Prinsip

1. **Order bersifat append-only.** Transaksi yang sudah selesai tidak
   diedit; koreksi dilakukan lewat void/refund yang juga append-only. Ini
   membuat konflik hampir tidak pernah terjadi pada data paling kritis.
2. **ID dibuat di klien.** ULID (`01JBXR...`) — terurut waktu, unik global,
   tidak butuh koordinasi server.
3. **Idempotency key wajib** pada setiap operasi tulis.
4. **Server otoritatif untuk data referensi** (produk, harga, resep, stok).
   Klien hanya membacanya.
5. **Klien otoritatif untuk data transaksi** (order, pembayaran, absensi).

### 6.2 Alur

```
── PUSH (klien → server) ──────────────────────────────────────
outbox (SQLite lokal)
  id, entity_type, entity_id, op, payload,
  idempotency_key, attempts, created_at, synced_at

POST /api/v1/sync/push
  { device_id, entries: [ {idempotency_key, entity_type, op, payload}, ... ] }

Server:
  → untuk setiap entry: cek idempotency_key sudah pernah diproses?
      ya  → kembalikan hasil sebelumnya (tanpa efek samping)
      tidak → jalankan dalam transaksi DB, catat key
  → kembalikan per-entry: accepted | rejected(alasan) | duplicate

Klien:
  → accepted/duplicate → tandai synced
  → rejected           → pindah ke "perlu perhatian", tampilkan ke kasir
                          (JANGAN buang diam-diam)

── PULL (server → klien) ──────────────────────────────────────
GET /api/v1/sync/pull?cursor=<opaque>&outlet_id=...

Respons:
  { changes: {products:[...], recipes:[...], stock_levels:[...], ...},
    deletions: [{type, id}, ...],
    next_cursor: "...",
    has_more: bool,
    server_time: "..." }

Cursor = (updated_at, id) terakhir. Semua tabel tersinkron punya
`updated_at` dan soft delete agar penghapusan bisa dipropagasi.
```

### 6.3 Resolusi konflik

| Data | Aturan |
|---|---|
| Order & pembayaran | Append-only → tidak ada konflik. Duplikat dicegah idempotency key |
| Level stok | **Server otoritatif.** Klien menampilkan estimasi lokal + tanda "perkiraan" saat offline |
| Harga & resep | **Server otoritatif.** Order offline menyimpan *snapshot harga saat transaksi* di `order_items` — harga historis tidak berubah walau master berubah |
| Sesi kas | Klien otoritatif untuk data yang diinput kasir; server memvalidasi & menghitung |
| Absensi | Klien otoritatif; server memvalidasi geofence & waktu |

### 6.4 Batasan mode offline (harus jujur ke pengguna)

Saat offline, hal berikut **tidak** bisa dilakukan — aplikasi wajib
mengatakannya secara eksplisit, bukan gagal diam-diam:

- Membuat QRIS dinamis → fallback ke QRIS statis / tunai
- Melihat data outlet lain
- Level stok real-time (hanya estimasi lokal)
- Mengirim notifikasi WhatsApp (diantre)
- Laporan yang membutuhkan agregasi lintas-shift

**Indikator UI wajib:** badge status sinkronisasi yang selalu terlihat —
🟢 Tersinkron · 🟡 N transaksi menunggu · 🔴 Offline (M transaksi tersimpan
lokal).

---

## 7. Print Thermal

| Kebutuhan | Solusi |
|---|---|
| Bluetooth ESC/POS (paling umum di Indonesia) | Library RN ESC/POS Bluetooth |
| USB / LAN (printer kasir permanen) | Library yang mendukung multi-transport |
| Epson TM series | SDK ePOS resmi via wrapper RN |
| Struk grafis (logo, QR) | Render ke bitmap lalu kirim sebagai raster |
| Cash drawer | Perintah kick-out lewat printer (ESC p) |

**Rekomendasi:** abstraksi `PrinterService` dengan implementasi per
transport, plus **template struk block-based** (JSON) yang di-render ke
perintah ESC/POS. Template ini bisa dikustom user — bagian dari janji
kustomisasi.

```json
{
  "width": 32,
  "blocks": [
    {"type":"image","source":"logo","align":"center","maxWidth":200},
    {"type":"text","value":"{{outlet.name}}","align":"center","bold":true,"size":"double"},
    {"type":"text","value":"{{outlet.address}}","align":"center","size":"small"},
    {"type":"divider","char":"="},
    {"type":"kv","left":"No","right":"{{order.number}}"},
    {"type":"kv","left":"Kasir","right":"{{order.cashier_name}}"},
    {"type":"divider"},
    {"type":"items","showModifiers":true,"showUnitPrice":true},
    {"type":"divider"},
    {"type":"kv","left":"Subtotal","right":"{{order.subtotal|rupiah}}"},
    {"type":"kv","left":"Diskon","right":"-{{order.discount|rupiah}}","hideIfZero":true},
    {"type":"kv","left":"Service {{outlet.service_charge_percent}}%","right":"{{order.service_charge|rupiah}}","hideIfZero":true},
    {"type":"kv","left":"PB1 {{outlet.tax_percent}}%","right":"{{order.tax|rupiah}}","hideIfZero":true},
    {"type":"kv","left":"TOTAL","right":"{{order.total|rupiah}}","bold":true,"size":"double"},
    {"type":"payments"},
    {"type":"divider","char":"="},
    {"type":"text","value":"{{receipt.footer_text}}","align":"center"},
    {"type":"qr","value":"{{receipt.digital_url}}","size":6},
    {"type":"feed","lines":3},
    {"type":"cut"}
  ]
}
```

---

## 8. Struktur Repositori Target (Monorepo)

```
posita/
├── apps/
│   ├── api/                    # Laravel 12 (dari kode saat ini)
│   ├── web/                    # Back office Inertia+Vue (dari kode saat ini)
│   ├── pos-mobile/             # Expo — aplikasi kasir
│   └── staff-mobile/           # Expo — aplikasi karyawan (bisa digabung awalnya)
├── packages/
│   ├── core-logic/             # TS: kalkulasi harga, pajak, HPP, diskon
│   ├── api-client/             # TS: klien API bertipe, di-generate dari OpenAPI
│   ├── design-tokens/          # Warna, spacing, tipografi (web + mobile)
│   └── receipt-renderer/       # TS: template struk → ESC/POS & HTML
├── docs/                       # Dokumen ini
└── infra/
    ├── docker/
    └── ci/
```

> **Catatan transisi:** monorepo tidak perlu ada di hari pertama. Fase 1–2
> cukup menambahkan `apps/pos-mobile` di repo yang sama, lalu restrukturisasi
> saat `packages/core-logic` benar-benar dibutuhkan bersama.

---

## 9. Stack Final

| Layer | Pilihan | Alasan singkat |
|---|---|---|
| Backend | **Laravel 12 (PHP 8.3+)** | Investasi eksisting, produktivitas tinggi |
| Database | **PostgreSQL 16** (MySQL 8 didukung) | RLS, JSONB, window function |
| Cache & Queue | **Redis** | Standar, murah, sudah didukung Laravel |
| Auth API | **Laravel Sanctum** (token) | Sederhana, cukup untuk first-party app |
| Storage | **S3-compatible** (MinIO self-host / IDCloudHost / Biznet) | Data residency + murah |
| API contract | **OpenAPI 3.1** (`scramble` atau ditulis manual) | Generate klien TS bertipe |
| Web back office | **Inertia 2 + Vue 3 + TypeScript** | Lanjutkan yang ada |
| Mobile | **React Native + Expo (dev client) + TypeScript** | ADR-002 |
| DB lokal mobile | **expo-sqlite / op-sqlite + Drizzle ORM** | SQL penuh, type-safe |
| State mobile | **Zustand + TanStack Query** | Ringan, cocok offline-first |
| Build mobile | **EAS Build + EAS Update** | Android build tanpa Mac, OTA patch |
| PDF & gambar | **Browsershot (Puppeteer)** untuk menu/desain, DomPDF untuk struk sederhana | Menu butuh render CSS modern |
| Monitoring | **Sentry** (app + API) + **Laravel Pulse** | Error & performa |
| Log | **Structured JSON** → Loki / Better Stack | Bisa ditelusuri |
| CI/CD | **GitHub Actions** | Sudah di GitHub |
| Deploy | **Docker Compose** di VPS Indonesia (Biznet/IDCloudHost/Alibaba Jakarta) | Latensi rendah + kepatuhan data |
| Test backend | **Pest 4** (sudah ada) | Lanjutkan |
| Test mobile | **Jest + React Native Testing Library**, Maestro untuk E2E | Standar |

### Kenapa VPS Indonesia, bukan Vercel/Railway

1. **Latensi.** Kasir menunggu respons di depan pelanggan. Server Singapura
   ±30 ms, server Jakarta ±10 ms. Terasa.
2. **UU PDP.** Data pribadi karyawan (foto absensi, lokasi GPS) lebih aman
   secara hukum jika berada di Indonesia.
3. **Biaya.** VPS 4 vCPU / 8 GB di Indonesia ± Rp400–700rb/bulan, cukup
   untuk ratusan tenant awal. Serverless jadi mahal saat trafik naik.
4. **Kepercayaan pasar.** "Server di Indonesia" adalah poin jual nyata untuk
   pembeli UMKM.

---

## 10. Rencana Migrasi Bertahap (Tanpa Big Bang)

| Fase | Yang dikerjakan | Status web lama |
|---|---|---|
| **0** | Hardening: multi-tenant, RBAC, test, CI | Tetap jalan penuh |
| **1** | Ekstrak `/api/v1` dari service eksisting + OpenAPI | Tetap jalan penuh |
| **2** | Aplikasi kasir Expo (online-only dulu) | Tetap jalan, jadi back-office |
| **3** | Sync engine offline + SQLite lokal | Tetap jalan |
| **4** | Aplikasi staf (absensi), notifikasi, menu designer | Tetap jalan |
| **5** | Optimasi, integrasi aggregator, self-hosted package | Web jadi khusus back-office |

**Tidak ada momen di mana sistem berhenti bekerja.** Web lama tidak pernah
"dimatikan" — perannya bergeser dari "aplikasi utama" menjadi "back office".

---

## Sumber

- [Why Offline-First Architecture Is No Longer Optional for POS Systems — Medium](https://medium.com/@alabeau/why-offline-first-architecture-is-no-longer-optional-for-pos-systems-15fd6edc133b)
- [Offline-First Mobile Architecture 2026 — Askan Technologies](https://www.askantech.com/offline-first-mobile-architecture-apps-without-internet/)
- [Building Offline-First React Native Apps 2026: Expo SQLite + Drizzle ORM — React Native Relay](https://reactnativerelay.com/article/building-offline-first-react-native-apps-2026-expo-sqlite-drizzle-orm-sync-strategies)
- [Offline-First Flutter App With SQLite (2026)](https://gtstu.com/offline-first-flutter-app-sqlite/)
- [Best Offline-First Tech Stack for 2026 — CSS Author](https://cssauthor.com/offline-first-tech-stack/)
- [Mobile App Architecture: React Native Guide for 2026 — Applighter](https://www.applighter.com/blog/mobile-app-architecture)
- [How to Build a Multi-Tenant SaaS Application with Laravel in 2026 — Hamid Kodez](https://www.hamidkodez.com/blog/multi-tenant-saas-laravel)
- [Laravel Multi-Tenancy SaaS Guide (2026 Architecture) — LaraCopilot](https://laracopilot.com/blog/laravel-multi-tenancy-saas-guide/)
- [Multi-Tenant Laravel: Architecture and Tenant Isolation — IGC](https://www.intelligentgraphicandcode.com/development/multi-tenant-laravel)
- [react-native-thermal-pos-printer — GitHub](https://github.com/CoFixer/react-native-thermal-pos-printer)
- [ESCPOS-ThermalPrinter-Android — DantSu](https://github.com/DantSu/ESCPOS-ThermalPrinter-Android)
- [react-native-esc-pos-printer — npm](https://www.npmjs.com/package/react-native-esc-pos-printer)
- [@finan-me/react-native-thermal-printer — npm](https://www.npmjs.com/package/@finan-me/react-native-thermal-printer)
