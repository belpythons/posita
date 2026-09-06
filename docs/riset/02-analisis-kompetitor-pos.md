# 02 — Analisis Kompetitor POS Indonesia & Celah Pasar

Tujuan: memetakan apa yang sudah dikerjakan pemain eksisting, apa yang
**belum** terselesaikan, dan di mana Posita bisa menang.

> **Perlu verifikasi:** harga & fitur berubah cepat. Angka di bawah adalah
> snapshot riset September 2026 dari sumber sekunder. Verifikasi langsung ke
> situs vendor sebelum dipakai di materi penjualan atau perbandingan publik.

---

## 1. Lanskap Kompetitor

### 1.1 Ringkasan harga (indikatif)

| Produk | Tier gratis | Harga berbayar (indikatif) | Positioning |
|---|---|---|---|
| **Majoo** | Trial | Starter Rp249rb • Advance Rp499rb • Prime Rp999rb /bln | All-in-one: kasir + payroll + marketplace |
| **Olsera** | Trial | Basic Rp1.288rb • Premium Rp1.988rb • Pro Rp2.688rb /tahun | Retail multi-channel + akuntansi |
| **Pawoon** | ✅ Free Plan | Basic Rp149rb • Pro Rp299rb /bln | Multi-cabang & franchise |
| **Qasir** | ✅ Ada tier gratis | Berbayar per fitur | UMKM ritel & kuliner, sejak 2015 |
| **Moka POS** (bagian GoTo) | Trial | Menengah–atas | Ekosistem GoTo/GoFood, brand kuat |
| **Kasir Pintar** | ✅ Versi gratis | Pro terjangkau | UMKM entry-level |
| **iSeller** | Trial | Menengah–atas | Integrasi food delivery terlengkap |
| **ESB** | — | Enterprise | Resto menengah–besar, chain |

### 1.2 Peta positioning

```
        Harga tinggi
             │
      ESB ●  │  ● Olsera
             │  ● iSeller
   Majoo ●   │
─────────────┼───────────── Fitur
  Pawoon ●   │  ● Moka
             │
  Qasir ●    │  ○ POSITA (target: fitur menengah-atas, harga bawah)
 Kasir Pintar●
             │
        Harga rendah
```

Strategi Posita: **menempati kuadran kanan-bawah** — fitur setara tier
Rp249rb–499rb kompetitor, dengan harga di bawah Rp100rb, dimungkinkan oleh
arsitektur single-codebase multi-tenant dan biaya akuisisi rendah lewat
komunitas.

---

## 2. Apa yang Sudah Dikerjakan Dengan Baik oleh Pasar

Fitur-fitur ini sudah **komoditas** — harus ada, tapi bukan pembeda:

- Transaksi kasir dasar, keranjang, diskon, cetak struk thermal.
- Manajemen shift kasir: buka/tutup shift, pencatatan float, void, refund,
  laporan akhir shift, tampilan selisih kas otomatis.
- Hak akses berjenjang: pembatalan/ubah harga hanya oleh manajer shift.
- Log aktivitas transaksi untuk telusur selisih kas.
- Laporan penjualan per shift, per metode pembayaran, per produk.
- Mode offline dasar (sebagian vendor).
- QRIS terintegrasi.
- Multi-cabang (di tier menengah ke atas).
- Integrasi GoFood/GrabFood/ShopeeFood dalam satu dashboard, sinkronisasi
  menu satu arah, order masuk langsung ke POS (Klikit, iSeller, Odoo).

---

## 3. Celah yang Belum Terselesaikan (Peluang Posita)

Ini inti dari riset. Diurutkan berdasarkan besarnya peluang.

### 🔴 GAP 1 — Resep/BOM & HPP real-time dikunci di paket mahal

**Kondisi sekarang.** Manajemen resep (BOM) dan pemotongan stok bahan baku
otomatis per penjualan tersedia di POS kelas menengah–atas, tapi umumnya
baru muncul di paket Rp249rb–999rb/bulan atau di produk enterprise. UMKM
dengan omzet Rp20–40 juta/bulan tidak sanggup, padahal justru merekalah
yang paling butuh.

**Akibatnya.** Mayoritas kedai kopi kecil masih menghitung HPP sekali di
awal — di Excel — lalu tidak pernah diperbarui walau harga susu naik 3 kali
dalam setahun.

**Peluang Posita.** Jadikan **resep + HPP + pemotongan stok otomatis sebagai
fitur inti di tier terendah**. Ini adalah "unfair offering" paling tajam.

---

### 🔴 GAP 2 — Tidak ada yang menghitung *variance* (aktual vs teoretis)

**Kondisi sekarang.** POS melaporkan *apa yang terjual*. Sistem inventory
melaporkan *apa yang tersisa*. Hampir tidak ada yang menyandingkan keduanya
dan berkata: *"Bulan ini kamu seharusnya pakai 12 kg biji, tapi ternyata
habis 14,3 kg. Ada 2,3 kg (Rp460.000) yang tidak jadi penjualan."*

**Peluang Posita.** Laporan **Profit Leak / Variance** sebagai fitur
signature. Ini adalah satu-satunya laporan yang membuat owner berkata "wah,
ini yang selama ini saya cari".

---

### 🔴 GAP 3 — Konsinyasi / titip jual mitra tidak ditangani sebagai kelas satu

**Kondisi sekarang.** Praktik titip jual sangat umum di kantin, warung,
koperasi, dan kedai kopi yang menjual pastry/keripik dari UMKM lain. Tapi
POS mainstream memodelkannya sebagai "produk biasa" — tidak ada konsep
kepemilikan barang, batch titipan, bagi hasil, retur, atau settlement.
Akibatnya pengelolaannya kembali ke WhatsApp dan buku tulis, dan sering
memicu konflik dengan mitra.

**Peluang Posita.** Modul konsinyasi penuh: batch titipan, berita acara
digital, rekonsiliasi otomatis, skema bagi hasil (markup / komisi % / fee
flat), siklus settlement, dan laporan mitra yang dikirim otomatis via
WhatsApp. **Ini sudah jadi DNA project Posita** dan harus diperdalam, bukan
dibuang.

---

### 🟠 GAP 4 — Notifikasi proaktif ke WhatsApp masih jarang & dangkal

**Kondisi sekarang.** Sebagian besar POS mengandalkan owner membuka
dashboard. Yang punya notifikasi biasanya terbatas pada rekap harian via
email atau push notification di aplikasi yang jarang dibuka.

**Peluang Posita.** *Alert engine* berbasis aturan yang mengirim ke
WhatsApp: stok menipis di reorder point, shift ditutup dengan selisih kas di
atas ambang, penjualan turun drastis vs rata-rata, mitra perlu dibayar,
box order jatuh tempo besok, food cost melewati target. Lihat
[dokumen 04](04-whatsapp-notifikasi.md).

---

### 🟠 GAP 5 — Vendor lock-in & ekspor data yang dipersulit

**Kondisi sekarang.** Keluhan berulang pengguna POS Indonesia: saat ingin
pindah vendor, data produk, riwayat penjualan, dan data pelanggan tidak bisa
dibawa. Ini menciptakan ketidakpercayaan struktural terhadap kategori.

**Peluang Posita.** Jadikan **"Data kamu, milik kamu"** sebagai janji merek
yang diiklankan: ekspor penuh kapan saja (CSV, XLSX, JSON, dan dump SQL),
API terbuka, dan opsi self-hosted untuk pelanggan yang mau. Ini biaya
engineering kecil tapi diferensiasi pemasaran besar.

---

### 🟠 GAP 6 — Ekspor & desain menu untuk kebutuhan marketing

**Kondisi sekarang.** POS menyimpan daftar menu, tapi tidak membantu owner
**memakai** daftar itu: cetak menu, buat menu QR, buat konten Instagram,
atau update papan harga. Owner harus pindah ke Canva dan menyalin ulang
manual — lalu harga di menu cetak dan di POS jadi berbeda.

**Peluang Posita.** *Menu Designer* dengan template yang bisa dikustom user
(warna, font, layout, foto, urutan seksi) dan ekspor multi-format:
PDF A4/A5, tent card, X-banner, PNG 1080×1080 (feed IG), 1080×1920 (story),
plus halaman menu digital QR. **Satu sumber kebenaran harga.**

---

### 🟡 GAP 7 — Kustomisasi terbatas & kaku

**Kondisi sekarang.** POS mainstream memaksa alur kerja tertentu. Warkop,
kedai specialty, kantin sekolah, dan catering box punya kebutuhan berbeda,
tapi mendapat UI yang sama.

**Peluang Posita.** Engine kustomisasi: preset tipe bisnis, feature flag per
outlet, custom field, template struk block-based, urutan hitung pajak yang
bisa diatur, role & permission yang bisa dirakit sendiri.

---

### 🟡 GAP 8 — Offline yang setengah hati

**Kondisi sekarang.** Banyak yang mengklaim "mode offline" tapi sebenarnya
hanya cache read-only, atau kehilangan transaksi saat sinkronisasi gagal.

**Peluang Posita.** Offline-first sungguhan: database lokal SQLite, outbox
dengan idempotency key, sync dua arah dengan resolusi konflik yang
terdefinisi, dan indikator status sinkronisasi yang jujur ke kasir.

---

## 4. Matriks Perbandingan Fitur

Legenda: ✅ ada & baik • 🟡 ada tapi terbatas/di paket mahal • ❌ tidak ada
• 🎯 target Posita

| Fitur | Moka | Majoo | Olsera | Pawoon | Qasir | **Posita (target)** |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Kasir dasar & struk | ✅ | ✅ | ✅ | ✅ | ✅ | 🎯 ✅ |
| Varian & modifier | ✅ | ✅ | ✅ | 🟡 | 🟡 | 🎯 ✅ |
| Shift & selisih kas | ✅ | ✅ | ✅ | ✅ | 🟡 | 🎯 ✅ |
| Offline penuh (write) | 🟡 | 🟡 | 🟡 | 🟡 | 🟡 | 🎯 ✅ |
| **Resep/BOM + potong stok** | 🟡 | 🟡 | 🟡 | ❌ | ❌ | 🎯 ✅ **tier gratis** |
| **HPP real-time per item** | 🟡 | 🟡 | 🟡 | ❌ | ❌ | 🎯 ✅ |
| **Variance aktual vs teoretis** | ❌ | ❌ | 🟡 | ❌ | ❌ | 🎯 ✅ **signature** |
| Waste logging bersebab | ❌ | 🟡 | 🟡 | ❌ | ❌ | 🎯 ✅ |
| Stock opname terjadwal | 🟡 | ✅ | ✅ | 🟡 | ❌ | 🎯 ✅ |
| PO & supplier | 🟡 | ✅ | ✅ | ❌ | ❌ | 🎯 ✅ |
| **Konsinyasi mitra** | ❌ | ❌ | ❌ | ❌ | ❌ | 🎯 ✅ **blue ocean** |
| **Settlement & laporan mitra** | ❌ | ❌ | ❌ | ❌ | ❌ | 🎯 ✅ |
| Absensi karyawan | ❌ | ✅ | 🟡 | ❌ | ❌ | 🎯 ✅ |
| Payroll | ❌ | ✅ | 🟡 | ❌ | ❌ | 🎯 🟡 (lite) |
| Pencatatan pengeluaran | 🟡 | ✅ | ✅ | 🟡 | 🟡 | 🎯 ✅ |
| **Alert WhatsApp otomatis** | ❌ | 🟡 | ❌ | ❌ | ❌ | 🎯 ✅ |
| **Menu designer & ekspor** | ❌ | ❌ | ❌ | ❌ | ❌ | 🎯 ✅ |
| Menu digital QR | 🟡 | ✅ | ✅ | 🟡 | 🟡 | 🎯 ✅ |
| Integrasi GoFood/Grab/Shopee | ✅ | ✅ | ✅ | 🟡 | 🟡 | 🎯 🟡 (Fase 5) |
| Multi-outlet | ✅ | ✅ | ✅ | ✅ | 🟡 | 🎯 ✅ |
| **Ekspor data penuh** | 🟡 | 🟡 | 🟡 | 🟡 | 🟡 | 🎯 ✅ **janji merek** |
| API terbuka | 🟡 | 🟡 | ✅ | ❌ | ❌ | 🎯 ✅ |
| Self-hosted | ❌ | ❌ | ❌ | ❌ | ❌ | 🎯 ✅ (opsi) |

---

## 5. Tiga Pilar Diferensiasi Posita

Semua keputusan produk selanjutnya harus bisa ditelusuri ke salah satu dari
tiga pilar ini. Kalau tidak bisa, kemungkinan besar itu fitur yang bisa
ditunda.

### Pilar 1 — **Profit Guard**
> *"POS lain memberitahu kamu berapa yang terjual. Posita memberitahu ke
> mana untungmu bocor."*

Resep/BOM, HPP hidup, waste log, variance report, alert food cost. Fitur
inti, tersedia sejak tier gratis.

### Pilar 2 — **Mitra Ready**
> *"Satu kasir untuk barang sendiri dan barang titipan."*

Konsinyasi kelas satu, bagi hasil fleksibel, settlement, transparansi ke
mitra. Tidak ada kompetitor langsung.

### Pilar 3 — **Punya Kamu, Bentuk Kamu**
> *"Datamu bisa dibawa pulang kapan saja. Aplikasinya bisa dibentuk sesuai
> tokomu."*

Ekspor penuh, API terbuka, opsi self-hosted, engine kustomisasi, preset
tipe bisnis, desain struk & menu milik user.

---

## 6. Risiko Kompetitif & Mitigasi

| Risiko | Kemungkinan | Mitigasi |
|---|---|---|
| Kompetitor besar menurunkan harga tier resep/BOM | Sedang | Menang di kedalaman (variance, waste cause) & konsinyasi, bukan sekadar keberadaan fitur |
| Moka/GoTo menekan lewat bundling GoFood | Tinggi | Jangan lawan di distribusi; menang di segmen yang tidak dilayani GoTo (kantin mitra, catering) |
| Vendor menyalin fitur konsinyasi | Rendah–sedang | Pasar terlalu niche untuk prioritas mereka; bangun keunggulan lewat komunitas mitra |
| Perang harga sampai gratis | Sedang | Struktur biaya rendah (self-serve, tanpa sales force) memungkinkan bertahan lebih lama |
| Ketergantungan pada satu gateway WA/payment | Tinggi | Driver abstraction sejak awal — bisa ganti provider tanpa ubah kode bisnis |

---

## Sumber

- [Perbandingan 7 Sistem POS Terpopuler di Indonesia 2026 — Kasir Pintar](https://kasirpintar.co.id/solusi/detail/perbandingan-7-sistem-pos-terpopuler-di-indonesia-pada-tahun-2023)
- [10 Aplikasi POS Kasir Terbaik di Indonesia 2026 — MAS Software](https://www.mas-software.com/blog/aplikasi-pos-terbaik)
- [Perbandingan Aplikasi Kasir: Fitur dan Harga 2026 — ReBill POS](https://rebill-pos.com/blog/perbandingan-aplikasi-kasir-fitur-harga-terbaik)
- [8 Aplikasi Kasir (POS) Terbaik untuk UKM 2026 — FounderPlus](https://founderplus.id/blog/aplikasi-kasir-pos-ukm-terbaik/)
- [Aplikasi Kasir untuk Restoran & Kafe: Fitur Wajib — Kasir Pintar](https://kasirpintar.co.id/solusi/detail/aplikasi-kasir-untuk-restoran-kafe-fitur-wajib-dan-rekomendasinya)
- [Aplikasi Kasir Restoran: Fitur yang Wajib Ada di 2026 — Xetup](https://xetup.id/id/blog/aplikasi-kasir-restoran-fitur-yang-wajib-ada)
- [15 Fitur Aplikasi Kasir yang Wajib Dimiliki — HashMicro](https://www.hashmicro.com/id/blog/fitur-aplikasi-kasir-paling-diminati/)
- [Masalah Umum yang Sering Terjadi Pada Aplikasi Kasir — Youtap](https://www.pos.youtap.id/blog/masalah-umum-aplikasi-kasir)
- [Sebelum Langganan Aplikasi Kasir, Pahami Dulu Hal Ini — FolioPOS](https://www.foliopos.com/blog/detail/sebelum-langganan-aplikasi-kasir-pahami-dulu-hal-ini)
- [Vendor Lock-In: Risiko Ketergantungan pada Satu Vendor — Indodax Academy](https://indodax.com/academy/vendor-lock-in/)
- [Recipe / Bill of Materials (BoM) — Orocube POS Guide](https://guide.orocube.com/recipe/)
- [Coffee Shop Inventory Management Software — Toast](https://pos.toasttab.com/blog/on-the-line/coffee-shop-inventory)
- [Integrasi GoFood untuk Restoran Indonesia — Klikit](https://klikit.io/id/learn/gofood-integration-indonesia)
- [Integrasi POS dengan Food Delivery — iSeller](https://www.isellercommerce.com/food-delivery)
- [Integrasi GrabFood & GoFood di Odoo](https://www.odoo.com/blog/business-hacks-1/odoo-integration-grabfood-gofood-2297)
