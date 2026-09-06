# Posita — Dokumen Riset & Blueprint Rilis

Kumpulan dokumen riset pasar, audit teknis, blueprint produk, dan rencana
re-engineering untuk membawa **Posita** dari project kuliah menjadi produk
POS siap rilis untuk bisnis Food & Drink Indonesia (fokus: coffee shop),
dengan dukungan penuh untuk model **penjualan mitra (konsinyasi)** dan
**non-mitra (produk sendiri)**.

> Tanggal riset: September 2026. Semua angka pasar, tarif, dan harga
> kompetitor **wajib diverifikasi ulang** sebelum dipakai di materi
> komersial — lihat catatan "Perlu Verifikasi" di tiap dokumen.

---

## Peta Dokumen

### A. Riset (`docs/riset/`)

| # | Dokumen | Isi |
|---|---------|-----|
| 01 | [Pasar & Perilaku Bisnis F&B Indonesia](riset/01-pasar-fnb-indonesia.md) | Ukuran pasar kedai kopi, struktur biaya, perilaku operasional harian, penyebab kegagalan, siklus kas, model mitra/konsinyasi |
| 02 | [Analisis Kompetitor POS](riset/02-analisis-kompetitor-pos.md) | Moka, Majoo, Olsera, Qasir, Pawoon, iSeller, ESB, Kasir Pintar — fitur, harga, celah pasar yang belum terisi |
| 03 | [Payment Gateway & QRIS](riset/03-payment-gateway-qris.md) | Opsi gratis/murah, MDR 2026, arsitektur driver, rekonsiliasi otomatis |
| 04 | [Notifikasi WhatsApp](riset/04-whatsapp-notifikasi.md) | Official Cloud API vs gateway lokal, biaya, risiko ban, desain alert engine |
| 05 | [Tech Stack & Re-engineering Mobile](riset/05-tech-stack-mobile.md) | React Native vs Flutter vs PWA, offline-first, sync engine, print thermal, keputusan arsitektur (ADR) |

### B. Perencanaan (`docs/`)

| # | Dokumen | Isi |
|---|---------|-----|
| 10 | [Audit Kondisi Project Saat Ini](10-audit-project.md) | Apa yang sudah ada, hutang teknis, gap terhadap target produk |
| 20 | [Blueprint Produk & Domain Model](20-blueprint-produk.md) | 14 modul, ERD lengkap, aturan bisnis, engine kustomisasi |
| 30 | [Arsitektur Target](30-arsitektur-target.md) | Topologi sistem, API-first, sync protocol, keamanan, deployment |
| 40 | [Roadmap Rilis](40-roadmap-rilis.md) | 6 fase, work package, definition of done, KPI, estimasi effort |
| 50 | [GTM, Pricing & Kepatuhan](50-gtm-pricing-legal.md) | Model harga, positioning, PB1/PBJT, UU PDP, checklist legal |

### C. Ringkasan Perubahan

[RINGKASAN-PERUBAHAN.md](RINGKASAN-PERUBAHAN.md) — catatan tertulis atas
seluruh dokumen yang dihasilkan: riwayat commit, hasil audit cakupan, dampak
ke roadmap, verifikasi yang dijalankan, dan langkah berikutnya.

### D. Prompt Eksekusi (`docs/prompts/`)

Prompt siap pakai untuk dieksekusi AI coding agent (Claude Code / Cursor /
Copilot).

| File | Fungsi |
|---|---|
| [prompts/EXECUTE.md](prompts/EXECUTE.md) | **Mulai dari sini** — prompt runner + protokol eksekusi |
| [prompts/PROGRESS.md](prompts/PROGRESS.md) | Papan status work package |
| [prompts/FOLLOWUP.md](prompts/FOLLOWUP.md) | Pekerjaan tertunda & prompt lanjutan |
| [prompts/README.md](prompts/README.md) | Indeks & urutan P01–P20 |
| [prompts/P00-master-context.md](prompts/P00-master-context.md) | Aturan rekayasa yang mengikat |

---

## Ringkasan Eksekutif (TL;DR)

**Temuan pasar.** Indonesia punya ~462.000 titik kedai kopi — terbanyak di
dunia, tumbuh ~12% YoY — tapi industrinya sedang masuk fase seleksi alam:
kenaikan harga bahan baku, saturasi, dan margin tipis. Penyebab tutup paling
sering **bukan** karena penjualan sepi, melainkan **kebocoran profit yang
tidak terlihat**: waste bahan baku, HPP tidak dihitung ulang, stok tidak
tercatat, dan selisih kas antar shift.

**Celah kompetitor.** POS besar (Moka, Majoo, Olsera) kuat di transaksi,
tapi: (a) resep/BOM dan HPP real-time umumnya dikunci di paket mahal
(Rp249rb–999rb/bulan), (b) hampir tidak ada yang menangani **konsinyasi /
titip jual mitra** sebagai warga kelas satu, (c) ekspor data dibatasi
(vendor lock-in adalah keluhan berulang), (d) alerting proaktif ke WhatsApp
masih jarang.

**Posisi Posita.** Tiga pembeda yang bisa dimenangkan:
1. **Profit-guard, bukan sekadar mesin kasir** — HPP per gelas hidup, waste
   tracking, variance stok aktual vs teoretis, alert WA otomatis.
2. **Mitra & non-mitra dalam satu sistem** — konsinyasi, bagi hasil, dan
   settlement mitra terintegrasi dengan kasir yang sama.
3. **Anti lock-in & full customable** — ekspor penuh (CSV/JSON/SQL), engine
   konfigurasi per outlet, preset tipe bisnis, desain struk & menu bisa
   dikustom user.

**Keputusan teknis utama.** *Jangan* rewrite dari nol. Pertahankan Laravel
12 sebagai core, ubah jadi API-first, lalu bangun aplikasi kasir
**React Native + Expo** yang offline-first (SQLite + outbox sync). Back
office tetap web (Inertia + Vue 3). Detail & alasan: [ADR di dokumen 05](riset/05-tech-stack-mobile.md).

**Payment gateway.** Rekomendasi bertingkat: mulai dari **QRIS statis
merchant** (biaya nol, konfirmasi manual) → **Midtrans Core API** (tanpa
setup fee & tanpa biaya bulanan, bayar per transaksi) untuk QRIS dinamis
dengan rekonsiliasi otomatis. Dibungkus driver abstraction agar tenant
bebas pilih. Detail: [dokumen 03](riset/03-payment-gateway-qris.md).
