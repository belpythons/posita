# PROGRESS — Status Eksekusi Posita

> **Untuk agent:** perbarui file ini di akhir setiap sesi, **sebelum**
> commit. Jangan pernah menandai selesai kalau Acceptance Criteria belum
> semuanya terpenuhi — tandai `🟡 Sebagian` dan catat sisanya.
>
> **Jangan percaya file ini secara buta.** Ini catatan manusia/agent yang
> bisa basi. Selalu verifikasi ke kode dan test sebelum melanjutkan.

**Terakhir diperbarui:** 6 September 2026 — belum ada eksekusi
**Sedang berjalan:** —
**Berikutnya:** **P01 — Multi-Tenancy & Outlet**

---

## Legenda

| Simbol | Arti |
|---|---|
| ⬜ | Belum mulai |
| 🟡 | Sedang berjalan / sebagian selesai |
| ✅ | Selesai, semua Acceptance Criteria terpenuhi & terverifikasi |
| ⛔ | Terblokir (lihat catatan) |
| ⏸️ | Ditunda atas keputusan pemilik produk |

---

## Fase 0 — Fondasi & Hardening

> Gate keluar: demo 2 tenant, 3 outlet, 5 user beda role; buktikan isolasi
> data dan penegakan izin.

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P01 | Multi-Tenancy & Outlet | ⬜ | — | 1,5 pekan | **Blocker semua WP lain** |
| P02 | RBAC & Permission | ⬜ | P01 | 1 pekan | |
| P03 | Money VO, Test Harness, CI, Cleanup | ⬜ | P01, P02 | 2 pekan | Termasuk hapus NIM & setup CI |

## Fase 1 — Domain Inti POS

> Gate keluar: simulasi 1 hari operasi kedai (80 transaksi, void, refund,
> restock, waste, tutup shift) dengan semua angka konsisten & bisa ditelusuri.

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P04 | Catalog: Produk, Varian, Modifier | ⬜ | P03 | 2 pekan | |
| P05 | Inventory & Stock Ledger | ⬜ | P04 | 2,5 pekan | Paling kritis untuk integritas data |
| P06 | Recipe/BOM & HPP | ⬜ | P04, P05 | 1,5 pekan | **Fitur signature** |
| P07 | Procurement & Restock | ⬜ | P05, P06 | 1 pekan | Kecepatan input = fiturnya |
| P08 | Sales Core | ⬜ | P04–P07 | 2,5 pekan | Jantung transaksi |
| P09 | Cash & Shift | ⬜ | P08 | 1 pekan | |

## Fase 2 — API & Aplikasi Kasir

> Gate keluar: uji lapangan 1 minggu di 1 kedai nyata dengan sistem lama
> sebagai cadangan.

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P10 | API v1 & OpenAPI | ⬜ | P01–P09 | 1,5 pekan | |
| P11 | Aplikasi Kasir (RN + Expo) | ⬜ | P10 | 5 pekan | Perlu dipecah lintas sesi |

## Fase 3 — Offline-First

> Gate keluar — **RILIS v1.0**: uji lapangan 2 minggu di 3 kedai (termasuk
> 1 lokasi internet buruk), nol kehilangan transaksi.

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P12 | Offline Sync Engine | ⬜ | P11 | 5 pekan | Bagian tersulit. Perlu dipecah lintas sesi |

## Fase 4 — Pembeda Kompetitif

> Gate keluar — **RILIS v1.5**

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P13 | Konsinyasi Mitra | ⬜ | P08, P12 | 2,5 pekan | Blue ocean — hampir tanpa kompetitor |
| P14 | Notifikasi WhatsApp | ⬜ | P05, P09, P13 | 2 pekan | |
| P15 | HR (Absensi) & Finance | ⬜ | P09, P14 | 3 pekan | Sentuh UU PDP — hati-hati |
| P16 | Menu Designer & Ekspor | ⬜ | P04 | 2 pekan | Butuh Chrome di server (Browsershot) |
| P17 | Laporan Variance / Kebocoran | ⬜ | P05, P06 | 1 pekan | **Fitur signature** |

## Fase 5 — Skala & Ekosistem

> Gate keluar — **RILIS v2.0**

| WP | Prompt | Status | Prasyarat | Effort | Catatan |
|---|---|:---:|---|---|---|
| P18 | Payment Gateway & QRIS | ⬜ | P08, P12 | 2 pekan | Uang sungguhan — coverage ~100% |
| P19 | Engine Kustomisasi & Preset | ⬜ | semua modul | 2 pekan | |
| P20 | Ekspor Data & Rilis | ⬜ | semua | 1,5 pekan | Termasuk uji restore backup |

---

## Ringkasan

| | Jumlah | Effort |
|---|---|---|
| ✅ Selesai | 0 / 20 | 0 pekan |
| 🟡 Berjalan | 0 | — |
| ⬜ Belum mulai | 20 | 36–45 pekan |

---

## Catatan Sesi

> Tambahkan entri baru di **atas**. Ringkas saja — detail ada di commit & PR.

### (belum ada sesi eksekusi)

Dokumentasi perencanaan selesai dan ter-merge (PR #25). Eksekusi belum
dimulai. WP pertama: **P01 — Multi-Tenancy & Outlet**.

---

## Keputusan Arsitektur Selama Eksekusi

> Catat keputusan yang **menyimpang** dari dokumen perencanaan, beserta
> alasannya. Kalau menyimpangnya signifikan, perbarui juga dokumen aslinya
> agar tidak ada dua sumber kebenaran.

| Tanggal | WP | Keputusan | Alasan | Dokumen diperbarui |
|---|---|---|---|---|
| — | — | — | — | — |

---

## Hutang Teknis yang Sengaja Ditunda

> Hal yang disadari tapi sengaja tidak dikerjakan sekarang. Kalau daftar ini
> tumbuh lebih cepat dari yang diselesaikan, itu sinyal untuk berhenti
> menambah fitur.

| Ditemukan di WP | Isu | Dampak | Rencana |
|---|---|---|---|
| — | — | — | — |

---

## Item yang Perlu Verifikasi Pemilik Produk

Dibawa dari fase riset — **belum terverifikasi**, jangan dipakai di materi
komersial sebelum dicek ke sumber primer.

| # | Item | Sumber yang harus dicek | Dibutuhkan sebelum |
|---|---|---|---|
| 1 | Ambang MDR QRIS 0% per 1 Okt 2026 untuk kategori UKE/UME/UBE | Dokumen resmi Bank Indonesia atau PJP yang dipakai | P18 — kalkulator biaya in-app |
| 2 | Harga & fitur kompetitor (Majoo, Olsera, Pawoon, dll) | Situs resmi masing-masing vendor | Materi pemasaran & perbandingan publik |
| 3 | Tarif PB1/PBJT & ambang omzet per kabupaten/kota | Perda / Bapenda daerah target | P08 — default tarif pajak |
| 4 | Status lisensi Bank Indonesia payment gateway yang dipilih | Daftar PJP berizin BI | P18 — sebelum integrasi |
| 5 | Lisensi resmi project (README bilang unlicense, composer.json bilang MIT) | Keputusan pemilik produk | P03 — cleanup |
