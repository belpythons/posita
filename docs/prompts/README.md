# Prompt Eksekusi Posita

Prompt siap pakai untuk dieksekusi AI coding agent (Claude Code, Cursor,
Copilot Workspace) atau dipakai manusia sebagai spesifikasi kerja.

## Aturan Main

1. **Selalu muat [`P00-master-context.md`](P00-master-context.md) terlebih
   dahulu** di setiap sesi baru. Prompt lain mengasumsikan konteks itu sudah
   dibaca.
2. **Kerjakan satu prompt per sesi/PR.** Jangan gabungkan. Setiap prompt
   dirancang untuk menghasilkan satu PR yang bisa direview.
3. **Urutan itu penting.** P01 → P02 → dst. Prompt belakangan bergantung
   pada tabel & abstraksi yang dibuat prompt sebelumnya.
4. **Jangan lanjut sebelum Acceptance Criteria hijau.** Setiap prompt punya
   kriteria yang bisa diverifikasi.
5. **Jika prompt bertentangan dengan kode nyata**, kode menang — laporkan
   ketidaksesuaian, jangan paksakan.

## Urutan Eksekusi

| # | Prompt | Fase | WP | Effort |
|---|---|---|---|---|
| P00 | [Master Context](P00-master-context.md) | — | — | Baca dulu |
| P01 | [Multi-Tenancy & Outlet](P01-multi-tenancy.md) | F0 | WP0.1 | 1,5 pekan |
| P02 | [RBAC & Permission](P02-rbac.md) | F0 | WP0.2 | 1 pekan |
| P03 | [Money VO, Test Harness, CI, Cleanup](P03-foundation-quality.md) | F0 | WP0.3–0.7 | 2 pekan |
| P04 | [Catalog: Produk, Varian, Modifier](P04-catalog.md) | F1 | WP1.1 | 2 pekan |
| P05 | [Inventory & Stock Ledger](P05-inventory.md) | F1 | WP1.2 | 2,5 pekan |
| P06 | [Recipe/BOM & HPP](P06-recipe-hpp.md) | F1 | WP1.3 | 1,5 pekan |
| P07 | [Procurement & Restock](P07-procurement.md) | F1 | WP1.4 | 1 pekan |
| P08 | [Sales Core](P08-sales.md) | F1 | WP1.5 | 2,5 pekan |
| P09 | [Cash & Shift](P09-cash-shift.md) | F1 | WP1.6 | 1 pekan |
| P10 | [API v1 & OpenAPI](P10-api-v1.md) | F2 | WP2.1 | 1,5 pekan |
| P11 | [Aplikasi Kasir Expo](P11-mobile-pos.md) | F2 | WP2.2–2.6 | 5 pekan |
| P12 | [Offline Sync Engine](P12-offline-sync.md) | F3 | WP3.1–3.5 | 5 pekan |
| P13 | [Konsinyasi Mitra](P13-konsinyasi.md) | F4 | WP4.1 | 2,5 pekan |
| P14 | [Notifikasi WhatsApp](P14-whatsapp.md) | F4 | WP4.2 | 2 pekan |
| P15 | [HR & Finance](P15-hr-finance.md) | F4 | WP4.3–4.4 | 3 pekan |
| P16 | [Menu Designer & Ekspor](P16-menu-designer.md) | F4 | WP4.5 | 2 pekan |
| P17 | [Laporan Variance / Kebocoran](P17-variance-report.md) | F4 | WP4.6 | 1 pekan |
| P18 | [Payment Gateway & QRIS](P18-payment.md) | F5 | WP5.1 | 2 pekan |
| P19 | [Engine Kustomisasi & Preset](P19-customization.md) | F5 | WP5.2 | 2 pekan |
| P20 | [Ekspor Data & Rilis](P20-release.md) | F5 | WP5.3+ | 1,5 pekan |

## Format Setiap Prompt

```
PERAN        — posisi & keahlian yang diasumsikan
KONTEKS      — apa yang sudah ada, apa yang jadi dasar
TUJUAN       — hasil akhir yang diinginkan
RUANG LINGKUP— yang dikerjakan
DI LUAR LINGKUP — yang JANGAN dikerjakan (mencegah scope creep)
SPESIFIKASI  — skema, aturan bisnis, kontrak
ACCEPTANCE   — kriteria terverifikasi
TESTING      — test yang wajib ada
PERINTAH     — command yang harus lulus
CATATAN      — jebakan yang harus dihindari
```
