# 50 — Go-To-Market, Pricing & Kepatuhan

---

## 1. Positioning

### Pernyataan posisi

> Untuk **pemilik coffee shop dan usaha F&B kecil di Indonesia** yang
> kehilangan untung tanpa tahu penyebabnya, **Posita** adalah aplikasi kasir
> yang **menghitung HPP hidup, melacak setiap bahan, dan memberitahu lewat
> WhatsApp saat ada yang bocor** — berbeda dari POS lain yang hanya mencatat
> penjualan dan mengunci fitur pengendalian biaya di paket mahal.

### Tagline kandidat

- **"Kasir yang jaga untungmu."**
- "Bukan cuma catat jualan — tahu ke mana untungmu pergi."
- "POS untuk kopi, mitra, dan semua yang di antaranya."

### Tiga janji

| Janji | Bukti |
|---|---|
| **Untungmu terjaga** | HPP hidup, laporan kebocoran, alert food cost |
| **Mitramu tenang** | Konsinyasi transparan, settlement otomatis, laporan ke mitra |
| **Datamu milikmu** | Ekspor penuh kapan saja, API terbuka, opsi self-hosted |

---

## 2. Model Harga

### 2.1 Prinsip

1. **Tier gratis harus benar-benar berguna**, bukan umpan. Warung kecil bisa
   memakainya selamanya.
2. **Harga per outlet, bukan per user.** Menambah karyawan tidak menghukum.
3. **Fitur pengendalian biaya (HPP, resep) ada di tier gratis.** Ini
   keputusan strategis: kompetitor mengunci di Rp249rb+; kita membukanya.
4. **Tidak ada biaya per transaksi.** Sukses pengguna tidak dihukum.
5. **Ekspor data di semua tier**, termasuk gratis. Anti lock-in adalah janji
   merek, bukan fitur berbayar.

### 2.2 Paket

| | **Gratis** | **Pro** | **Bisnis** |
|---|---|---|---|
| **Harga** | Rp0 selamanya | **Rp79.000**/outlet/bln | **Rp179.000**/outlet/bln |
| | | Rp790.000/tahun (hemat 2 bln) | Rp1.790.000/tahun |
| Outlet | 1 | 1 (tambah outlet = tambah biaya) | Multi-outlet |
| User | 3 | Tidak terbatas | Tidak terbatas |
| Transaksi | Tidak terbatas | Tidak terbatas | Tidak terbatas |
| Riwayat data | 3 bulan | 24 bulan | Tidak terbatas |
| **Kasir & offline** | ✅ | ✅ | ✅ |
| Varian & modifier | ✅ | ✅ | ✅ |
| Shift & selisih kas | ✅ | ✅ | ✅ |
| Cetak struk thermal | ✅ | ✅ | ✅ |
| **Resep & HPP** | ✅ (maks 30 produk) | ✅ Tidak terbatas | ✅ |
| **Stok bahan baku** | ✅ (maks 50 bahan) | ✅ Tidak terbatas | ✅ |
| Restock & waste log | ✅ | ✅ | ✅ |
| **Laporan kebocoran** | 🔒 | ✅ | ✅ |
| Opname & batch/expiry | 🔒 | ✅ | ✅ |
| PO & supplier | 🔒 | ✅ | ✅ |
| **Konsinyasi mitra** | ✅ (maks 3 mitra) | ✅ Tidak terbatas | ✅ + portal mitra |
| Settlement otomatis | 🔒 | ✅ | ✅ |
| **Notifikasi WhatsApp** | 30 pesan/bln | 1.000 pesan/bln | Tidak terbatas* |
| Absensi karyawan | 🔒 | ✅ | ✅ |
| Payroll-lite | 🔒 | 🔒 | ✅ |
| Pengeluaran & arus kas | ✅ | ✅ | ✅ |
| Laba rugi | 🔒 | ✅ | ✅ |
| **Menu Designer** | 2 template | Semua template | Semua + custom |
| Menu QR | ✅ | ✅ | ✅ + domain sendiri |
| Payment gateway | Manual QRIS | + Midtrans | + semua driver |
| **Ekspor data penuh** | ✅ | ✅ | ✅ |
| API terbuka | 🔒 | ✅ (rate terbatas) | ✅ |
| Branding/white-label | 🔒 | Logo & warna | Penuh + custom domain |
| Preorder/catering | ✅ (10/bln) | ✅ | ✅ |
| Integrasi aggregator | 🔒 | 🔒 | ✅ |
| Dukungan | Komunitas | WhatsApp (jam kerja) | Prioritas + onboarding |

\* Wajar; pakai kredensial gateway sendiri untuk volume sangat besar.

### 2.3 Perbandingan harga dengan pasar

| Produk | Paket menengah | Posita Pro |
|---|---|---|
| Majoo Starter | Rp249.000/bln | **Rp79.000** (−68%) |
| Majoo Advance | Rp499.000/bln | **Rp179.000** (−64%) |
| Pawoon Pro | Rp299.000/bln | **Rp79.000** (−74%) |
| Olsera Basic | ± Rp107.000/bln (tahunan) | **Rp66.000** (tahunan) |

**Kenapa bisa lebih murah:** tanpa tim sales lapangan, self-serve onboarding,
satu codebase multi-tenant, infrastruktur VPS bukan cloud premium, dan
akuisisi lewat komunitas bukan iklan berbayar.

### 2.4 Add-on

| Add-on | Harga |
|---|---|
| Outlet tambahan (paket Pro) | Rp59.000/outlet/bln |
| Kuota WhatsApp tambahan 1.000 pesan | Rp25.000 |
| Onboarding & migrasi data terpandu | Rp500.000 sekali |
| Desain menu kustom oleh tim | Rp350.000 sekali |
| Self-hosted license (tahunan) | Rp3.500.000/tahun |
| Pelatihan tim on-site (Jabodetabek) | Rp1.500.000/sesi |

---

## 3. Strategi Masuk Pasar

### Fase 1 — Beachhead (bulan 1–3, target 20 kedai)

**Target:** coffee shop 1 outlet di 1 kota (mulai dari kota domisili tim).

**Taktik:**
- **Design partner program**: 10 kedai dapat Pro gratis 12 bulan, imbalannya
  feedback mingguan dan izin jadi studi kasus.
- Kunjungan langsung — pasar ini masih dimenangkan dengan hubungan personal.
- Bantu migrasi data mereka **secara gratis** (ini penghalang terbesar
  pindah vendor).

**Metrik sukses:** 10 kedai memakai harian selama 4 minggu berturut-turut.

### Fase 2 — Komunitas (bulan 4–8, target 100 kedai)

- **Konten edukasi**, bukan iklan produk:
  - "Cara Hitung HPP Kopi yang Benar (+ Template Gratis)"
  - "5 Sumber Kebocoran di Coffee Shop yang Tidak Kelihatan"
  - "Panduan Titip Jual: Kontrak & Pembagian yang Adil"
  - Kalkulator HPP gratis di web (lead magnet, tanpa login)
- Hadir di komunitas: grup Facebook/WA pemilik coffee shop, forum barista,
  komunitas UMKM daerah.
- Kemitraan dengan **supplier bahan baku & roastery** — mereka punya akses
  ke ratusan kedai dan insentif agar pelanggannya tidak bangkrut.
- Program referral: pengguna yang mereferensikan dapat 1 bulan gratis;
  yang direferensikan dapat diskon 50% bulan pertama.

### Fase 3 — Skala (bulan 9+, target 500+ kedai)

- Self-serve onboarding penuh — daftar sampai transaksi pertama < 15 menit.
- Reseller/agensi lokal dengan white-label.
- Perluasan segmen: kantin sekolah/kampus, koperasi, food court (semua
  memiliki kebutuhan konsinyasi).
- Ekspansi kota.

### Kanal akuisisi (prioritas)

| Kanal | Biaya | Prioritas |
|---|---|---|
| Kunjungan langsung & rujukan | Waktu | 🔴 Tertinggi (fase 1) |
| Komunitas & grup WA/FB | Waktu | 🔴 Tertinggi |
| Konten SEO (kalkulator HPP, panduan) | Waktu | 🟠 Tinggi (fase 2) |
| Kemitraan supplier/roastery | Bagi hasil | 🟠 Tinggi |
| TikTok/Instagram edukatif | Waktu | 🟡 Sedang |
| Iklan berbayar | Uang | ⚪ Terakhir — jangan sebelum retensi terbukti |

### Metrik yang diawasi

| Metrik | Target sehat |
|---|---|
| Waktu daftar → transaksi pertama | < 30 menit |
| Aktivasi (≥20 transaksi minggu pertama) | > 60% |
| Retensi 30 hari | > 70% |
| Konversi gratis → berbayar | > 15% |
| Churn bulanan berbayar | < 5% |
| Adopsi fitur resep | > 50% pengguna aktif |
| NPS | > 40 |

**Metrik utara (north star):** *jumlah kedai yang membuka laporan kebocoran
minimal sekali per minggu.* Ini pengguna yang mendapat nilai nyata dan tidak
akan pergi.

---

## 4. Kepatuhan Hukum

### 4.1 UU PDP (UU No. 27/2022 Perlindungan Data Pribadi)

Posita memproses data pribadi: karyawan (foto selfie, lokasi GPS, gaji),
pelanggan (nama, nomor telepon), dan mitra.

| Kewajiban | Implementasi |
|---|---|
| **Dasar pemrosesan** | Persetujuan eksplisit saat karyawan pertama login; kontrak untuk data mitra |
| **Transparansi** | Kebijakan privasi berbahasa Indonesia yang bisa dibaca orang awam |
| **Minimalisasi data** | Jangan kumpulkan yang tidak dipakai. GPS hanya saat clock-in/out, bukan tracking terus-menerus |
| **Retensi** | Foto absensi terhapus otomatis (default 90 hari, konfigurabel). Data transaksi sesuai kewajiban pajak |
| **Hak subjek data** | Akses, koreksi, penghapusan, portabilitas — implementasikan sebagai fitur, bukan proses manual |
| **Keamanan** | Enkripsi at-rest & in-transit, kontrol akses, audit log |
| **Notifikasi kebocoran** | Prosedur pemberitahuan dalam 3×24 jam ke subjek data & otoritas |
| **Pemroses data** | Perjanjian dengan pihak ketiga (gateway WA, payment gateway, hosting) |

**Keputusan penting:** simpan data di **server Indonesia**. Selain aspek
hukum, ini poin jual nyata.

### 4.2 Pajak & Fiskal

| Item | Implementasi |
|---|---|
| **PB1/PBJT** (pajak restoran, umumnya 10%) | Konfigurabel per outlet (tarif, aktif/nonaktif, basis hitung). **Jangan hardcode** — tarif & ambang berbeda tiap kab/kota |
| Service charge | Terpisah dari pajak, konfigurabel, opsi kena pajak atau tidak |
| Tampilan struk | Rincian: subtotal, diskon, service charge, PB1, total |
| Laporan pajak | Rekap omzet kena pajak per bulan untuk pelaporan ke Bapenda |
| PPh Final UMKM | Laporan omzet bulanan/tahunan |
| PPN | Posita sebagai penyedia jasa (SaaS) wajib PPN atas biaya langganan bila memenuhi ketentuan |

> **Wajib disertakan:** disclaimer bahwa Posita adalah alat bantu pencatatan,
> bukan pengganti konsultan pajak. Pengguna bertanggung jawab atas kewajiban
> perpajakannya.

### 4.3 Pembayaran

- Patuhi larangan Bank Indonesia: **MDR QRIS tidak boleh dibebankan ke
  pembeli**. Jangan sediakan fitur surcharge QRIS sebagai default.
- Payment gateway harus **terdaftar & diawasi Bank Indonesia**. Verifikasi
  status lisensi setiap provider sebelum diintegrasikan.
- Posita **bukan** penyelenggara jasa pembayaran — hanya integrator. Pastikan
  dokumen legal & materi pemasaran tidak menimbulkan kesan sebaliknya.

### 4.4 Ketenagakerjaan

Fitur absensi & payroll menyentuh area sensitif:
- Data upah dilindungi kerahasiaannya.
- Perhitungan lembur mengacu ketentuan ketenagakerjaan; sediakan sebagai
  konfigurasi, jangan asumsikan satu aturan.
- **Jangan** klaim "sesuai regulasi ketenagakerjaan" tanpa review hukum.
  Posisikan sebagai alat pencatatan.

### 4.5 Dokumen legal yang harus siap sebelum rilis

- [ ] Syarat & Ketentuan Layanan (bahasa Indonesia)
- [ ] Kebijakan Privasi (sesuai UU PDP)
- [ ] Perjanjian Pemrosesan Data (untuk pengguna bisnis)
- [ ] Kebijakan Pengembalian Dana
- [ ] SLA (untuk paket Bisnis)
- [ ] Perjanjian dengan vendor pihak ketiga
- [ ] Badan hukum (PT/CV) untuk menerima pembayaran langganan
- [ ] Merek dagang "Posita" (opsional, tapi dianjurkan sebelum promosi luas)

---

## 5. Model Pendukung (Support)

| Tier | Kanal | SLA respons |
|---|---|---|
| Gratis | Basis pengetahuan + komunitas WA | Best effort |
| Pro | WhatsApp jam kerja (09.00–18.00 WIB) | < 4 jam kerja |
| Bisnis | WhatsApp prioritas + panggilan onboarding | < 1 jam kerja |

**Investasi yang menurunkan beban support:**
- Onboarding dalam aplikasi dengan checklist ("Tambah 5 produk pertamamu")
- Video pendek berbahasa Indonesia untuk tiap fitur inti
- Pesan error yang menjelaskan **apa yang harus dilakukan**, bukan kode
- Data contoh yang bisa dihapus sekali klik
- Impor CSV dengan template & validasi yang jelas

---

## 6. Proyeksi Finansial Sederhana

**Asumsi konservatif, tahun pertama setelah v1.0:**

| Bulan | Pengguna gratis | Berbayar | MRR |
|---|---|---|---|
| 3 | 30 | 5 | Rp395.000 |
| 6 | 120 | 25 | Rp2.150.000 |
| 9 | 300 | 65 | Rp5.720.000 |
| 12 | 600 | 140 | Rp12.600.000 |

**Biaya operasional bulanan pada bulan 12:**

| Pos | Biaya |
|---|---|
| VPS + backup | Rp900.000 |
| Domain, email, Sentry | Rp300.000 |
| Gateway WhatsApp | Rp200.000 |
| Play Store, tools | Rp150.000 |
| **Total infrastruktur** | **± Rp1.550.000** |

Titik impas infrastruktur tercapai sekitar **20 pelanggan berbayar**. Biaya
terbesar adalah waktu tim, bukan infrastruktur — model ini bisa bertahan
lama dengan tim kecil.

> **Catatan:** proyeksi ini adalah skenario dasar untuk perencanaan
> operasional, bukan janji. Variabel terbesar adalah tingkat konversi
> gratis→berbayar, yang sangat bergantung pada seberapa nyata nilai laporan
> kebocoran dirasakan pengguna.
