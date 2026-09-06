# 01 — Riset Pasar & Perilaku Bisnis F&B Indonesia

Fokus: bisnis makanan & minuman skala UMKM–menengah di Indonesia, dengan
pendalaman pada **coffee shop**, serta model **titip jual / konsinyasi
mitra** yang jadi ciri khas banyak kantin, warung, dan kedai kopi lokal.

---

## 1. Ukuran & Dinamika Pasar

### 1.1 Angka kunci

| Metrik | Nilai | Catatan |
|---|---|---|
| Jumlah titik kedai kopi di Indonesia | ± **461.991** lokasi | Terbanyak di dunia; China ±190rb, AS ±145,6rb |
| Pertumbuhan gerai YoY | ± **12%** | Termasuk warkop tradisional, bukan hanya specialty |
| Komposisi | Warkop tradisional + kedai modern + specialty | Mayoritas adalah usaha mikro/kecil |
| Food cost ideal F&B | **25–35%** dari harga jual | Minuman sering 25–35%, makanan 30–40% |
| Gross margin sehat | **65–75%** | Sisa dipakai gaji, sewa, listrik, laba bersih |

> **Implikasi produk:** pasar sangat besar dan sangat *long-tail*. Mayoritas
> calon pengguna adalah 1 outlet, 2–6 karyawan, omzet Rp15–80 juta/bulan.
> Produk harus bisa dipakai **hari pertama tanpa training**, dan harganya
> harus di bawah threshold "mikir dua kali" (± Rp100rb/bulan/outlet).

### 1.2 Fase pasar: dari ekspansi ke seleksi alam

Industri kopi Indonesia 2026 tidak lagi di fase "buka pasti laku". Tekanan
utama:

- **Kenaikan harga bahan baku** (biji kopi, susu, kemasan) menggerus margin
  yang sudah tipis.
- **Saturasi lokasi** — kompetitor di radius 500 m bisa 5–15 gerai.
- **Perubahan perilaku konsumen** — cepat bosan, sensitif harga, banyak
  promo-hunting, dan pesanan bergeser ke delivery.

**Konsekuensi:** yang bertahan bukan yang penjualannya paling besar, tapi
yang **kontrol biayanya paling rapi**. Ini adalah *value proposition* inti
yang harus dipegang Posita.

---

## 2. Anatomi Kegagalan: Ke Mana Uang Bocor

Riset terhadap penyebab tutupnya kedai kopi di Indonesia menunjukkan pola
berulang. Diurutkan berdasarkan frekuensi & dampak:

### 2.1 Manajemen keuangan buruk (penyebab #1)
- Biaya operasional tidak terkendali.
- Harga jual tidak pernah dihitung ulang saat harga bahan baku naik.
- Tidak ada pemisahan kas pribadi & kas usaha.
- Tidak ada anggaran; keputusan belanja berbasis "perasaan".

### 2.2 Stok tidak tercatat konsisten
- Bahan tiba-tiba habis di jam sibuk → *lost sales* + citra buruk.
- Bahan menumpuk sampai kedaluwarsa → kerugian langsung.
- Pencatatan manual di Excel/kertas/ingatan → laporan keuangan tidak akurat.

### 2.3 Waste yang tidak terukur
Sumber waste khas coffee shop:
| Sumber | Contoh |
|---|---|
| Kalibrasi & dialing-in | Buang 100–300 g biji tiap ganti batch |
| Salah baca resep | Barista baru pakai 22 g padahal standar 18 g |
| Salah bikin / komplain | Remake minuman |
| Susu sisa steaming | Buang 30–80 ml per gelas |
| Kedaluwarsa | Susu, sirup, pastry |
| Overstock | Beli banyak karena "diskon supplier" |

Tanpa pencatatan, waste ini **tidak pernah muncul di laporan** — dia hanya
tampak sebagai "kok untungnya kecil ya".

### 2.4 Menu terlalu banyak varian
Keinginan "menyediakan semuanya" → stok bahan menumpuk, operasional tidak
efisien, barista kewalahan, dan analisis menu jadi mustahil.

### 2.5 Kebocoran kas & fraud internal
- Selisih kas antar shift tidak ditelusuri.
- Void / diskon manual tanpa otorisasi.
- Transaksi tunai tidak diinput.
- Stok "hilang" tanpa jejak.

---

## 3. Perilaku Operasional Harian (Field Reality)

Ini yang harus dimodelkan aplikasi. Alur khas coffee shop kecil–menengah
Indonesia:

```
06.30  Barista datang → absen (sering: grup WA "Hadir bos")
07.00  Buka toko → hitung modal laci (float) → cek stok cepat
07.15  Prep: brewing batch, simple syrup, cek susu, cek es
08.00  Buka penjualan
       ├── Dine-in / takeaway (mayoritas tunai + QRIS)
       ├── Pesanan online GoFood/GrabFood/ShopeeFood (tablet terpisah)
       └── Pre-order box / catering (WA)
14.00  Ganti shift → hitung kas → serah terima (sering: foto struk di WA)
17.00  Restock mendadak (susu habis) → beli ke minimarket → nota kertas
21.30  Tutup → hitung kas fisik → catat setoran
       ├── Sisa stok titipan mitra dihitung & dikembalikan
       └── Rekap ditulis di buku / dikirim foto ke owner via WA
22.00  Owner cek WA, sering baru sadar masalah besoknya
```

### Titik nyeri yang teridentifikasi

| Momen | Masalah nyata | Yang dibutuhkan sistem |
|---|---|---|
| Buka toko | Modal laci sering tidak dicatat | Wajib input opening cash, terkunci |
| Prep | Tidak ada standar takaran | Resep/BOM tersimpan & bisa dilihat barista |
| Jual | Antre saat internet putus | **Offline-first mutlak** |
| Jual | Ada varian & topping (ukuran, gula, es) | Modifier engine |
| Jual | Bayar campuran (tunai + QRIS) | Split payment |
| Restock mendadak | Nota kertas hilang | Input restock cepat + foto nota |
| Ganti shift | Selisih kas tak terjelaskan | Shift report + audit trail per transaksi |
| Tutup | Rekap manual, owner tidak realtime | Auto-recap + **push ke WhatsApp owner** |
| Stok menipis | Baru ketahuan saat habis | **Alert WA otomatis di reorder point** |
| Mitra | Hitung titipan manual, sering ribut | Modul konsinyasi + settlement |

---

## 4. Struktur Biaya Coffee Shop (Model Referensi)

Untuk 1 outlet kecil (±40 kursi, 4 karyawan), proporsi terhadap omzet:

| Pos biaya | % omzet (tipikal) | Catatan |
|---|---|---|
| COGS / HPP bahan | 25–35% | Target sehat: minuman ≤30% |
| Gaji & tunjangan | 20–30% | Sering pos terbesar kedua |
| Sewa tempat | 10–15% | Fixed, paling mematikan saat sepi |
| Utilitas (listrik, air, internet) | 3–6% | Mesin espresso boros listrik |
| Marketing & promo | 3–8% | Termasuk komisi promo aggregator |
| Komisi delivery (GoFood/Grab/Shopee) | 15–25% *dari GMV channel itu* | Bukan dari total omzet |
| Waste & shrinkage | 2–8% | **Paling sering tidak terukur** |
| Lain-lain / maintenance | 2–5% | |
| **Net margin realistis** | **5–15%** | Di bawah 5% = rawan |

> **Insight kunci:** selisih antara bisnis yang untung dan yang tutup sering
> hanya **3–5 poin persen** — persis sebesar waste + shrinkage yang tidak
> pernah dicatat. Di sinilah aplikasi menciptakan nilai yang bisa dirasakan.

### Formula yang harus ada di aplikasi

```
HPP per porsi        = Σ (qty bahan × harga_satuan_bahan) × (1 + waste_factor)
Food cost %          = HPP per porsi / harga jual × 100
Gross profit         = harga jual - HPP
Theoretical usage    = Σ (qty terjual × resep)
Actual usage         = stok_awal + pembelian - stok_akhir
Variance (shrinkage) = Actual - Theoretical      ← ANGKA EMAS
Variance %           = Variance / Theoretical × 100   (alarm jika > 5%)
Days of cover        = stok_saat_ini / rata2_pemakaian_harian
Reorder point        = rata2_pemakaian_harian × lead_time_hari + safety_stock
```

`Variance` adalah metrik yang hampir tidak pernah muncul di POS UMKM
Indonesia, padahal itu yang menjawab pertanyaan "uang saya lari ke mana".

---

## 5. Model Mitra & Non-Mitra (Konsinyasi / Titip Jual)

Ini pembeda yang diminta dan ternyata **celah nyata di pasar**.

### 5.1 Cara kerja di lapangan

- **Consignor (pengamanat / mitra penitip)** — pemilik produk. Contoh: UMKM
  roti, keripik, kue basah, atau brand kopi lain.
- **Consignee (komisioner / toko)** — outlet yang menjual. Kepemilikan
  barang **tetap di mitra** sampai barang terjual.
- Toko tidak mengeluarkan modal beli; hanya menyediakan tempat & tenaga.
- Bagi hasil bisa berupa: **markup** (toko menaikkan harga dari harga
  mitra), **komisi %** (toko ambil X% dari harga jual), atau **fee flat per
  unit**.
- Barang tidak laku **dikembalikan** ke mitra.

### 5.2 Sumber konflik yang harus dieliminasi sistem

| Konflik | Penyebab | Solusi di aplikasi |
|---|---|---|
| "Barang saya 20, laku 12, kok dibayar 10?" | Tidak ada bukti serah terima | Berita acara digital + tanda tangan/foto |
| Selisih stok titipan | Hitung manual di akhir hari | Rekonsiliasi otomatis: awal − sisa = terjual |
| Pembayaran mitra telat | Tidak ada penjadwalan | Siklus settlement + reminder WA |
| Mitra tidak tahu performanya | Laporan hanya di toko | Portal/laporan mitra (link atau PDF WA) |
| Barang kedaluwarsa dititipkan | Tidak ada tanggal | Batch + expiry per titipan |

### 5.3 Kebutuhan model data

Sistem harus bisa membedakan, di **satu keranjang belanja yang sama**:
- Item **milik sendiri** (non-mitra) → potong stok bahan baku via resep,
  100% pendapatan milik toko.
- Item **titipan mitra** (konsinyasi) → potong stok batch mitra, pendapatan
  dipecah jadi *bagian toko* dan *utang ke mitra*.

Ini berarti setiap `order_item` perlu tahu **sumber kepemilikan** dan
**skema bagi hasilnya** — bukan sekadar produk biasa. Detail skema di
[dokumen 20 — Blueprint Produk](../20-blueprint-produk.md).

---

## 6. Perilaku Pembayaran

| Metode | Perkiraan share (UMKM F&B) | Implikasi |
|---|---|---|
| Tunai | Masih dominan di warkop/kantin, menurun di kedai modern | Cash management & selisih kas tetap krusial |
| QRIS | Tumbuh paling cepat, jadi default kedai modern | Wajib. Idealnya QRIS **dinamis** |
| E-wallet langsung (GoPay/OVO/DANA/ShopeePay) | Sebagian besar sudah lewat QRIS | Cukup lewat QRIS |
| Kartu debit/kredit | Kecil untuk kedai kopi | Opsional |
| Transfer bank / VA | Untuk pre-order & catering box | Perlu untuk modul box order |
| Bayar nanti / kasbon | Umum untuk pelanggan langganan & karyawan | Perlu modul piutang sederhana |

**Aturan penting:** Bank Indonesia melarang MDR QRIS dibebankan ke pembeli
dalam bentuk apapun. Aplikasi **tidak boleh** menyediakan fitur "biaya QRIS
Rp X ditambahkan ke pelanggan" sebagai default — lihat
[dokumen 03](03-payment-gateway-qris.md).

---

## 7. Kewajiban Pajak & Regulasi Operasional

| Item | Ketentuan | Dampak ke aplikasi |
|---|---|---|
| **PB1 / PBJT** (Pajak Barang & Jasa Tertentu — Makanan & Minuman) | Umumnya **10%**, pajak **daerah** (kab/kota), bukan PPN pusat | Tarif harus **konfigurabel per outlet**, muncul di struk |
| Ambang batas omzet | Banyak Pemda menetapkan batas omzet minimum sebelum wajib PB1 | Fitur bisa dimatikan per outlet |
| **Service charge** | 5–10%, ditetapkan restoran sendiri, **bukan pajak**, masuk kas resto | Terpisah dari PB1, konfigurabel, urutan hitung penting |
| Urutan perhitungan | Umumnya: Subtotal → Service Charge → PB1 dihitung atas (Subtotal + SC) | Harus bisa diatur, karena praktik berbeda antar daerah |
| PPh Final UMKM 0,5% | Untuk omzet tertentu | Cukup disediakan laporan omzet bulanan |
| **UU PDP** (Perlindungan Data Pribadi) | Berlaku untuk data karyawan (foto absensi, lokasi) & pelanggan | Consent, retensi, hak hapus — lihat [dokumen 50](../50-gtm-pricing-legal.md) |

> **Perlu verifikasi:** tarif PB1 dan ambang batasnya **berbeda tiap
> kabupaten/kota**. Jangan hardcode 10%. Sediakan sebagai setting outlet
> dengan default 10% dan opsi nonaktif.

---

## 8. Segmentasi Pengguna Target

| Persona | Profil | Kebutuhan utama | Kesediaan bayar |
|---|---|---|---|
| **P1 — Owner kedai kopi tunggal** | 1 outlet, 3–6 staf, omzet 20–60jt/bln | HPP, waste, kontrol shift, laporan WA | Rp50–150rb/bln |
| **P2 — Owner multi-outlet kecil** | 2–5 outlet | Konsolidasi lintas outlet, kontrol jarak jauh, transfer stok | Rp150–500rb/bln |
| **P3 — Pengelola kantin/koperasi mitra** | Banyak mitra titip jual | Konsinyasi, settlement, transparansi ke mitra | Rp50–200rb/bln |
| **P4 — Usaha catering/box** | Pesanan terjadwal | Box order, DP, jadwal pickup, resep porsi besar | Rp50–150rb/bln |
| **P5 — Barista/kasir (user, bukan pembeli)** | Operator harian | Cepat, offline, tidak bikin antre | — (tapi penentu retensi) |

**P1 adalah beachhead market.** P3 adalah pembeda kompetitif (hampir tidak
dilayani kompetitor). P2 adalah jalur monetisasi.

---

## 9. Kesimpulan & Implikasi Produk

1. **Jual "profit protection", bukan "aplikasi kasir".** Pasar kasir sudah
   merah. Pasar "aplikasi yang memberitahu ke mana uang saya bocor" masih
   lapang.
2. **Offline-first bukan fitur, tapi syarat hidup.** Internet putus di jam
   sibuk = kehilangan pendapatan langsung.
3. **WhatsApp adalah kanal notifikasi de facto.** Owner tidak akan membuka
   dashboard tiap hari; mereka membuka WhatsApp tiap 10 menit.
4. **Resep/BOM harus ada di tier gratis atau termurah.** Ini fitur yang
   menyelamatkan bisnis pengguna, dan kompetitor mengunci di paket mahal —
   celah paling tajam untuk direbut.
5. **Konsinyasi mitra adalah blue ocean kecil.** Tidak besar, tapi hampir
   tanpa kompetisi dan sudah jadi keunggulan eksisting Posita.
6. **Kustomisasi = daya tahan.** Bisnis F&B Indonesia sangat heterogen
   (warkop, specialty, kantin, catering, food truck). Engine preset +
   feature flag memungkinkan satu codebase melayani semuanya.

---

## Sumber

- [Jumlah coffee shop Indonesia tertinggi di dunia — CNA Indonesia](https://www.cna.id/indonesia/indonesia-nomor-satu-coffee-shop-warung-kopi-warkop-kedai-kopi-461991-lokasi-43116)
- [Peluang Usaha Kedai Kopi 2026 — Semeru Ingredients](https://semeruingredients.id/pages/artikel?slug=peluang-usaha-kedai-kopi-2026-pasar-besar-modal-fleksibel-persaingan-ketat)
- [Tren Industri Kopi 2026 — Semeru Ingredients](https://semeruingredients.id/tren-industri-kopi-2026-dari-saturasi-pasar-hingga-inovasi-rasa-yang-makin-personal)
- [Industri Kopi Indonesia di Persimpangan Krusial — Tenjo Bumi Kopi](https://tenjobumikopi.com/industri-kopi-indonesia-di-persimpangan-krusial/)
- [Penyebab Banyaknya Coffee Shop yang Gagal dan Tutup — Luden.id](https://luden.id/penyebab-coffee-shop-yang-tutup/)
- [Kesalahan Fatal Pemilik Kedai Kopi Pemula — Mallkopi](https://mallkopi.com/kesalahan-pemilik-kedai-kopi-pemula/)
- [Manajemen Stok Digital untuk Resto & Coffee Shop — ReBill POS](https://rebill-pos.com/blog/manajemen-stok-digital-resto-coffee-shop-2026)
- [Solusi 5 Kemalasan Operasional Pemilik Coffee Shop — Three Folks](https://www.three-folks.com/scoop/solusi-5-kemalasan-operasional-pemilik-coffee-shop)
- [HPP Ideal untuk Cafe dan Restoran — Guslan Putra](https://guslan.com/blog/hpp-ideal-kafe-restoran/)
- [Cara Menghitung HPP Minuman Cafe — AltaF&B](https://altafnb.com/cara-hitung-hpp-minuman-cafe/)
- [Cara Menghitung Food Cost (HPP Makanan) — Jurnal.id](https://www.jurnal.id/id/blog/contoh-cara-menghitung-hpp-makanan-juga-food-cost-adalah-berikut/)
- [Pentingnya Food Cost dalam Bisnis Minuman — Delifru](https://delifru.co.id/pentingnya-food-cost-dalam-bisnis-minuman/)
- [Pengertian Sistem Konsinyasi dan Contoh Pencatatan Akuntansinya — Bee.id](https://www.bee.id/blog/sistem-konsinyasi/)
- [Cara Mencatat Konsinyasi (Titip Jual) — Kledo](https://kledo.com/cara-mencatat-konsinyasi-titip-jual/)
- [Bisnis UMKM Dengan Sistem Titip Jual — BukaOutlet](https://bukaoutlet.com/article/bisnis-umkm-dengan-sistem-titip-jual--solusi-untuk-pemula-yang-belum-punya-tempat)
- [Pajak Restoran dan Tarifnya — Mekari](https://mekari.com/blog/pajak-restoran/)
- [PB1 dalam PBJT: Cara Hitung Pajak Restoran — FlazzTax](https://flazztax.com/2025/02/25/pb1-dalam-pbjt-bagaimana-cara-hitung-pajak-restoran/)
- [Perbedaan Service Charge dan Service Tax Restoran — ESB](https://www.esb.id/id/inspirasi/perbedaan-service-charge-dan-service-tax-restoran)
- [Serba-Serbi Pajak Restoran — Bapenda Jakarta](https://bapenda.jakarta.go.id/artikel/serbaserbi-pajak-restoran)
