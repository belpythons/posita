# 40 — Roadmap Rilis

Rencana eksekusi dari kondisi saat ini (± 20% cakupan produk) menuju rilis
komersial. Enam fase, masing-masing dengan work package, definition of done,
dan kriteria kelulusan.

> **Asumsi estimasi:** 1 developer full-time setara ± 5 hari kerja per
> minggu. Angka "pekan" adalah *effort*, bukan kalender. Dengan 2 developer
> paralel, kalender ± 60% dari total.

---

## Ringkasan Fase

| Fase | Nama | Effort | Output | Bisa dijual? |
|---|---|---|---|---|
| **F0** | Fondasi & Hardening | 4–5 pekan | Basis multi-tenant siap produksi | ❌ |
| **F1** | Domain Inti POS | 8–10 pekan | POS lengkap (web) | 🟡 Beta tertutup |
| **F2** | API & Aplikasi Kasir | 6–7 pekan | Aplikasi Android kasir (online) | 🟡 Beta terbuka |
| **F3** | Offline-First | 4–5 pekan | Kasir jalan tanpa internet | ✅ **v1.0 GA** |
| **F4** | Pembeda | 8–10 pekan | Mitra, WA, HR, Finance, Menu Designer | ✅ v1.5 |
| **F5** | Skala & Ekosistem | 6–8 pekan | Payment, kustomisasi, aggregator | ✅ v2.0 |
| | **Total** | **36–45 pekan** | | |

---

## FASE 0 — Fondasi & Hardening

**Tujuan:** membuat codebase layak menerima fitur produksi. Tanpa ini,
setiap fitur berikutnya menumpuk hutang teknis.

### Work Package

| WP | Isi | Effort |
|---|---|---|
| **WP0.1** | Multi-tenancy: `tenants`, `outlets`, `tenant_id` di semua tabel, `BelongsToTenant` global scope, `EnsureTenantContext` middleware, queue tenant-aware | 1,5 pekan |
| **WP0.2** | RBAC: spatie/laravel-permission scoped tenant, 7 role bawaan, ± 40 permission, Policy per model | 1 pekan |
| **WP0.3** | `Money` VO + `MoneyCast` + `RoundingMode` + `allocate()`; migrasi kolom uang `decimal` → `bigint` | 0,5 pekan |
| **WP0.4** | Test harness: factory semua model, `TenantTestCase`, helper, seeder demo realistis | 0,5 pekan |
| **WP0.5** | CI: GitHub Actions (pint, phpstan L6, pest matrix PostgreSQL+MySQL, coverage gate 70%, composer/npm audit) | 0,5 pekan |
| **WP0.6** | Docker Compose + `make setup` satu perintah + dokumentasi kontribusi | 0,5 pekan |
| **WP0.7** | Bersih-bersih: README produk, hapus komentar NIM, lisensi konsisten, `.env.example` Posita (locale `id`, tz `Asia/Jakarta`), investigasi & hapus file biner `retailer`, konsolidasi komponen UI duplikat | 0,5 pekan |

### Definition of Done
- [ ] Test membuktikan tenant A tidak bisa mengakses data tenant B lewat
      route model binding, filter, laporan, dan ekspor
- [ ] CI hijau di PostgreSQL dan MySQL
- [ ] `make setup` menghasilkan aplikasi berjalan dari repo bersih
- [ ] Tidak ada `float` untuk uang di seluruh codebase
- [ ] phpstan level 6 tanpa baseline error baru
- [ ] Coverage ≥ 70% pada modul yang diubah

### Gate keluar
Demo: buat 2 tenant, 3 outlet, 5 user dengan role berbeda; buktikan isolasi
data dan penegakan izin.

---

## FASE 1 — Domain Inti POS

**Tujuan:** Posita menjadi POS F&B yang sesungguhnya, bukan sistem kantin.

### Work Package

| WP | Isi | Effort |
|---|---|---|
| **WP1.1** | **Catalog**: kategori, produk, varian, modifier group & modifier, price tier, bundle. UI CRUD + import CSV | 2 pekan |
| **WP1.2** | **Inventory**: unit & konversi, ingredient, stock ledger append-only, stock level cache, batch FIFO, waste log, opname, transfer antar outlet | 2,5 pekan |
| **WP1.3** | **Recipe/BOM**: resep + sub-resep rekursif, waste factor, kalkulasi HPP, rekalkulasi otomatis saat harga bahan berubah, riwayat HPP | 1,5 pekan |
| **WP1.4** | **Procurement**: supplier, restock cepat (4 ketukan), goods receipt, PO opsional, update harga → picu rekalkulasi HPP | 1 pekan |
| **WP1.5** | **Sales**: keranjang, order + item + modifier, snapshot lengkap, diskon (master + manual + otorisasi), tax group, service charge, pembulatan konfigurabel, void, refund, split payment | 2,5 pekan |
| **WP1.6** | **Cash & Shift**: evolusi `ShopSession` → `shifts`, cash movement, denominasi, laporan shift, shift lintas tengah malam | 1 pekan |

### Definition of Done
- [ ] Bisa menjual "Es Kopi Susu, Large, Less Sugar, Extra Shot" dengan harga
      benar dan stok bahan terpotong sesuai resep
- [ ] HPP per item terhitung dan ter-snapshot di setiap `order_item`
- [ ] Membuka shift → jual 20 transaksi → tutup shift → laporan shift benar,
      termasuk selisih kas
- [ ] Mengubah harga beli susu memicu rekalkulasi HPP semua resep terdampak
- [ ] Uji konkurensi: 2 kasir menjual bersamaan tidak menghasilkan stok
      korup
- [ ] Ledger stok bisa direkonstruksi ulang menjadi `stock_levels` yang
      identik

### Gate keluar
Simulasi 1 hari operasi kedai kopi: buka toko, 80 transaksi dengan varian &
modifier, 2 void, 1 refund, restock di tengah hari, 3 pencatatan waste,
tutup shift. Semua angka konsisten dan bisa ditelusuri.

---

## FASE 2 — API & Aplikasi Kasir

**Tujuan:** POS berpindah ke perangkat yang benar.

### Work Package

| WP | Isi | Effort |
|---|---|---|
| **WP2.1** | Ekstraksi `/api/v1`: Sanctum, controller API tipis memanggil Action yang sama, JsonResource, error envelope standar, rate limiting, OpenAPI 3.1 | 1,5 pekan |
| **WP2.2** | Scaffold Expo: TypeScript, navigasi, autentikasi (email + PIN), pemilihan outlet, tema & design token bersama | 1 pekan |
| **WP2.3** | Layar kasir: grid produk, pencarian, kategori, favorit, keranjang, varian & modifier, diskon, split payment, layar pembayaran | 2 pekan |
| **WP2.4** | Print thermal: `PrinterService`, driver Bluetooth ESC/POS, renderer template struk block-based, pairing & uji cetak, kick cash drawer | 1 pekan |
| **WP2.5** | Buka/tutup shift di mobile, input denominasi, ringkasan shift | 0,5 pekan |
| **WP2.6** | EAS Build, internal testing track Play Store, Sentry, crash reporting | 0,5 pekan |

### Definition of Done
- [ ] Transaksi lengkap di tablet Android: pilih produk → modifier →
      pembayaran → cetak struk, < 30 detik
- [ ] Struk tercetak rapi di printer thermal 58 mm dan 80 mm
- [ ] Aplikasi terinstal dari Play Store internal track
- [ ] p95 waktu selesaikan transaksi < 800 ms
- [ ] Berjalan mulus di Android 8, RAM 2 GB

### Gate keluar
Uji lapangan 1 minggu di 1 kedai nyata (dengan sistem lama sebagai cadangan).

---

## FASE 3 — Offline-First

**Tujuan:** internet mati tidak menghentikan penjualan. Ini gate menuju
**v1.0 GA**.

### Work Package

| WP | Isi | Effort |
|---|---|---|
| **WP3.1** | SQLite lokal + Drizzle: skema mirror, migrasi lokal, enkripsi (SQLCipher) | 1 pekan |
| **WP3.2** | Sync pull: cursor-based, `GET /sync/pull`, cache katalog/resep/setelan/stok, sinkronisasi awal & inkremental | 1 pekan |
| **WP3.3** | Sync push: outbox, idempotency key, retry backoff, penanganan penolakan (antrean "perlu perhatian", bukan buang diam-diam) | 1,5 pekan |
| **WP3.4** | UX offline: indikator status sinkronisasi, estimasi stok lokal bertanda, fallback pembayaran ke QRIS statis/tunai, pesan jujur untuk fitur yang tidak tersedia | 0,5 pekan |
| **WP3.5** | Test suite jaringan: mode pesawat, 2G, putus-sambung, konflik, duplikat, jam perangkat salah, storage penuh | 1 pekan |

### Definition of Done
- [ ] Matikan Wi-Fi → jual 50 transaksi → nyalakan → semua tersinkron tanpa
      duplikat dan tanpa kehilangan
- [ ] Push yang sama dikirim 3× hanya menghasilkan 1 order
- [ ] Order yang ditolak server muncul di UI dengan alasan yang bisa
      ditindaklanjuti
- [ ] Aplikasi bisa dibuka dan berjualan setelah restart perangkat tanpa
      jaringan
- [ ] Waktu selesaikan transaksi offline < 200 ms

### Gate keluar — **RILIS v1.0**
Uji lapangan 2 minggu di 3 kedai berbeda, termasuk 1 lokasi dengan internet
buruk. Nol kehilangan transaksi.

---

## FASE 4 — Pembeda

**Tujuan:** membangun tiga pilar diferensiasi.

### Work Package

| WP | Isi | Effort |
|---|---|---|
| **WP4.1** | **Konsinyasi lanjutan**: batch + expiry, serah terima berfoto & tanda tangan, skema bagi hasil (markup/komisi/flat/bertingkat), rekonsiliasi harian, retur, settlement + PDF, portal mitra | 2,5 pekan |
| **WP4.2** | **Notifikasi WhatsApp**: engine (rules, dedup, quiet hours, rate limit, batching), driver Fonnte/Wablas/Cloud API, katalog 25+ alert, editor template, riwayat, kill switch | 2 pekan |
| **WP4.3** | **HR**: karyawan, jadwal, absensi selfie + geofence, izin/cuti, payroll-lite + slip PDF, kepatuhan UU PDP (consent, retensi, hak hapus) | 2 pekan |
| **WP4.4** | **Finance**: kategori & pencatatan pengeluaran, biaya berulang, pendapatan lain, arus kas, laba rugi sederhana, realisasi vs anggaran | 1 pekan |
| **WP4.5** | **Menu Designer**: model desain, 6 template awal, editor (warna/font/layout/logo/badge/seksi), pratinjau langsung, ekspor 9 format via Browsershot, Menu QR dengan link permanen | 2 pekan |
| **WP4.6** | **Laporan Variance / Kebocoran**: perhitungan teoretis vs aktual, laporan per bahan, dugaan penyebab, tren, alert | 1 pekan |

### Definition of Done
- [ ] Alur mitra lengkap: terima titipan → jual → rekonsiliasi → retur →
      settlement → mitra terima laporan WA
- [ ] Alert stok menipis terkirim ke WhatsApp, ter-dedup, menghormati jam
      tenang
- [ ] Karyawan absen dengan selfie + GPS; manajer melihat rekap; slip gaji
      tergenerate
- [ ] Menu diekspor sebagai PDF A4 siap cetak dan PNG Instagram dengan
      desain yang diatur pengguna
- [ ] Laporan variance menampilkan nilai kebocoran dalam rupiah

### Gate keluar — **RILIS v1.5**

---

## FASE 5 — Skala & Ekosistem

| WP | Isi | Effort |
|---|---|---|
| **WP5.1** | **Payment gateway**: `PaymentProvider` abstraction, driver `cash`/`manual_qris`/`manual_transfer`/`midtrans`, webhook + verifikasi signature, rekonsiliasi harian, laporan pendapatan bersih setelah MDR | 2 pekan |
| **WP5.2** | **Engine kustomisasi**: feature flag 3 lapis, 8 preset tipe bisnis, setelan berjenjang, custom field, editor template struk, branding/white-label | 2 pekan |
| **WP5.3** | **Ekspor data penuh**: arsip ZIP semua tabel (CSV+JSON+media), penjadwalan, API terbuka + dokumentasi publik | 0,5 pekan |
| **WP5.4** | **Aplikasi staf** (atau modul dalam aplikasi kasir): absensi, jadwal, slip gaji, pengajuan izin | 1 pekan |
| **WP5.5** | **Integrasi aggregator**: GoFood/GrabFood/ShopeeFood — order masuk ke POS, sinkronisasi menu, rekonsiliasi komisi | 2,5 pekan |
| **WP5.6** | **Paket self-hosted**: Docker image, installer, dokumentasi, lisensi | 0,5 pekan |

### Gate keluar — **RILIS v2.0**

---

## Peta Rilis

```
       F0        F1              F2         F3        F4              F5
    ┌──────┬──────────────┬───────────┬─────────┬──────────────┬─────────────┐
    │ 4-5w │    8-10w     │   6-7w    │  4-5w   │    8-10w     │    6-8w     │
    └──────┴──────────────┴───────────┴─────────┴──────────────┴─────────────┘
                          ▲            ▲         ▲              ▲             ▲
                       Beta        Beta       v1.0 GA        v1.5         v2.0
                     tertutup     terbuka   (offline)    (pembeda)   (ekosistem)

    v1.0  — POS lengkap, offline, mobile. Bisa dijual.
    v1.5  — Mitra, WA, HR, Finance, Menu Designer. Diferensiasi lengkap.
    v2.0  — Payment, kustomisasi penuh, aggregator, self-hosted.
```

---

## KPI per Fase

| Fase | KPI teknis | KPI produk |
|---|---|---|
| F0 | Coverage ≥70%, CI < 8 menit, 0 kebocoran tenant | — |
| F1 | 0 inkonsistensi ledger dalam uji simulasi | Cakupan fitur ≥ 60% |
| F2 | p95 transaksi < 800 ms, crash-free ≥ 99,5% | 1 kedai uji lapangan |
| F3 | 0 kehilangan transaksi, sync sukses ≥ 99,9% | 3 kedai, 2 minggu |
| F4 | Kirim WA sukses ≥ 95% | 10 kedai berbayar |
| F5 | Webhook sukses ≥ 99,5% | 50 kedai berbayar |

---

## Risiko & Mitigasi

| Risiko | Dampak | Kemungkinan | Mitigasi |
|---|---|---|---|
| Scope creep — mengejar fitur kompetitor | Rilis molor berbulan | **Tinggi** | Kunci scope per fase. Fitur baru masuk backlog fase berikutnya, tanpa kecuali |
| Sync offline lebih sulit dari perkiraan | F3 molor 2–4 pekan | Sedang | Rancang order sebagai append-only sejak F1 → konflik hampir mustahil |
| Akun WA gateway di-ban | Notifikasi mati | Sedang | Driver abstraction + fallback Cloud API + tenant pakai nomor sendiri |
| Migrasi `decimal` → `bigint` merusak data | Kritis | Rendah | Lakukan di F0 saat data masih dummy; script migrasi + verifikasi |
| Perangkat Android low-end tidak kuat | Retensi buruk | Sedang | Uji di Android 8 / RAM 2 GB sejak F2, bukan di akhir |
| Tim kecil, bus factor 1 | Semua | **Tinggi** | Dokumentasi arsitektur (dokumen ini), test sebagai spesifikasi, code review wajib |
| Regulasi QRIS/PB1 berubah | Perhitungan salah | Sedang | Semua tarif konfigurabel, tidak ada hardcode |
| Kompetitor menurunkan harga | Margin tertekan | Sedang | Struktur biaya rendah + diferensiasi non-harga (mitra, variance) |

---

## Prioritisasi Jika Waktu Terbatas

Jika harus memotong, urutan yang **boleh** ditunda:

1. ⏸️ WP5.5 Integrasi aggregator — kompleks, butuh partnership, bisa
   ditangani manual dulu
2. ⏸️ WP5.6 Self-hosted — nilai pemasaran tinggi, permintaan nyata rendah
3. ⏸️ WP4.3 Payroll (absensi tetap ada, payroll ditunda)
4. ⏸️ WP1.4 PO formal (restock cepat tetap ada)
5. ⏸️ Bundle & price tier lanjutan di WP1.1

Yang **tidak boleh** dipotong (ini produknya):
- Multi-tenancy (F0)
- Recipe/BOM + HPP (WP1.3)
- Sales core (WP1.5)
- Offline-first (F3)
- Konsinyasi (WP4.1)
- Notifikasi WA stok (WP4.2)
- Laporan variance (WP4.6)
