# P16 — Menu Designer & Ekspor

**Fase:** F4 · **WP:** 4.5 · **Effort:** ± 2 pekan · **Prasyarat:** P04

---

## PERAN
Full-stack engineer dengan kepekaan desain. Output modul ini akan dicetak
dan dipajang di toko pengguna — kualitas visualnya mewakili merek mereka.

## KONTEKS
Permintaan pengguna: *"export menu dengan kustomisasi design menyesuaikan
kemauan user untuk dipakai dalam bisnisnya"*.

Riset menunjukkan **tidak ada POS Indonesia yang menyediakan ini.** Owner
harus menyalin ulang menu ke Canva secara manual — dan akibatnya harga di
menu cetak sering berbeda dengan harga di kasir.

## TUJUAN
Menu designer yang mengubah katalog (satu sumber kebenaran) menjadi materi
cetak dan digital berkualitas, dengan desain yang dikontrol pengguna.

## RUANG LINGKUP
1. `menu_designs` + `menu_design_sections` + `menu_exports` + `digital_menus`
2. 6 template awal: Classic, Modern, Minimal, Chalkboard, Bold, Elegant
3. Editor: warna, tipografi, layout, logo, badge, urutan seksi
4. Pratinjau langsung (side-by-side)
5. Ekspor 9 format via Browsershot
6. Menu QR digital dengan link permanen + QR bermerek
7. Deteksi "menu perlu diperbarui" saat harga berubah
8. Ekspor CSV/XLSX untuk unggah ke GoFood/Grab/Shopee

## DI LUAR LINGKUP
- ❌ Editor drag-drop bebas (terlalu kompleks; template + kustomisasi cukup)
- ❌ Upload font kustom (v1 cukup Google Fonts)
- ❌ Pemesanan lewat menu QR (v1 read-only)
- ❌ Sinkronisasi otomatis ke aggregator (itu P20/F5)

---

## SPESIFIKASI

### Skema & config
Ikuti `docs/20-blueprint-produk.md` bagian **M13 — Menu Designer**,
termasuk struktur `config` JSON lengkap.

### Template awal

| Template | Karakter | Cocok untuk |
|---|---|---|
| **Classic** | Serif, dua kolom, garis tipis, krem | Kedai kopi tradisional |
| **Modern** | Sans-serif, grid, foto besar, kontras tinggi | Specialty coffee |
| **Minimal** | Banyak ruang kosong, monokrom, tanpa foto | Slow bar, minimalis |
| **Chalkboard** | Tekstur papan tulis, font tulisan tangan | Kedai kasual |
| **Bold** | Warna kuat, tipografi besar | Food truck, kedai anak muda |
| **Elegant** | Emas/hitam, serif tinggi, ornamen halus | Cafe premium |

Setiap template = komponen Blade/Vue yang menerima `config` + data menu.

### Editor

```
┌─────────────────────┬───────────────────────────────┐
│ PENGATURAN          │  PRATINJAU                    │
│                     │  ┌─────────────────────────┐  │
│ ▼ Template          │  │                         │  │
│   [Classic ▾]       │  │      KOPI SENJA         │  │
│                     │  │   Sejak 2021 · Slow Bar │  │
│ ▼ Warna             │  │  ─────────────────────  │  │
│   Latar    ██ #FAF7 │  │  KOPI SUSU              │  │
│   Teks     ██ #2C18 │  │  Es Kopi Susu     22K   │  │
│   Aksen    ██ #C876 │  │  Kopi Susu Panas  20K   │  │
│                     │  │  Kopi Aren        24K   │  │
│ ▼ Tipografi         │  │                         │  │
│   Judul  [Playfair] │  │  MANUAL BREW            │  │
│   Isi    [Inter   ] │  │  V60 Single Origin 32K  │  │
│   Ukuran [ 14 ]     │  │  Japanese Iced     35K  │  │
│                     │  │                         │  │
│ ▼ Tata Letak        │  │  MAKANAN                │  │
│   Kolom     [ 2 ▾ ] │  │  Croissant         25K  │  │
│   Harga  [Kanan ▾]  │  │  Pisang Goreng     18K  │  │
│   Format [ 22K   ▾] │  │                         │  │
│   Foto   [Kecil  ▾] │  │  @kopisenja  📍 Jl...   │  │
│                     │  │       [QR Menu]         │  │
│ ▼ Konten            │  └─────────────────────────┘  │
│   ☑ Deskripsi       │                               │
│   ☐ Kalori          │  [🖨 A4] [📱 Story] [🔗 QR]   │
│   ☑ Badge           │                               │
│   ☑ Sembunyikan     │                               │
│     yang habis      │                               │
│                     │                               │
│ ▼ Seksi             │                               │
│   ⋮⋮ Kopi Susu      │                               │
│   ⋮⋮ Manual Brew    │                               │
│   ⋮⋮ Makanan        │                               │
│   [+ Tambah seksi]  │                               │
└─────────────────────┴───────────────────────────────┘
```

Pratinjau harus update **langsung** (debounce 300 ms), tanpa reload.

### Format ekspor

| Format | Ukuran | DPI | Kegunaan |
|---|---|---|---|
| PDF A4 | 210×297 mm | 300 | Menu meja, cetak percetakan |
| PDF A5 | 148×210 mm | 300 | Menu genggam |
| PDF Tent Card | 100×150 mm | 300 | Kartu meja lipat |
| PDF X-Banner | 60×160 cm | 150 | Banner depan toko |
| PNG Feed IG | 1080×1080 | — | Konten Instagram |
| PNG Story | 1080×1920 | — | Story IG / status WA |
| PNG Papan Harga | 1920×1080 | — | Layar TV di toko |
| Web (Menu QR) | Responsif | — | Menu digital |
| CSV / XLSX | — | — | Unggah ke GoFood/Grab/Shopee |

### Implementasi render

```php
namespace App\Services\MenuDesign;

final class MenuRenderer
{
    public function renderHtml(MenuDesign $design): string;

    /** Render ke PDF via Browsershot (Puppeteer). */
    public function renderPdf(MenuDesign $design, ExportFormat $format): string;

    /** Render ke PNG via Browsershot screenshot. */
    public function renderImage(MenuDesign $design, ExportFormat $format): string;
}

// Job antrean — render bisa 3-10 detik
final class GenerateMenuExport implements ShouldQueue
{
    public function handle(): void
    {
        $path = $this->renderer->render(...);
        // simpan ke S3, buat menu_exports record
        // notifikasi in-app "Menu siap diunduh"
    }
}
```

**Browsershot** dipilih karena mendukung CSS modern, Google Fonts, dan
menghasilkan PDF berkualitas cetak. DomPDF tidak cukup untuk ini.

### Menu QR digital

```
Route publik: /menu/{slug}
  - Tanpa auth
  - Responsif, cepat (< 1,5 detik di 3G)
  - Menampilkan menu sesuai desain yang dipilih
  - Opsi sembunyikan item yang habis (real-time)
  - Analytics: view count, item paling dilihat

QR bermerek:
  - Warna sesuai palette desain
  - Logo di tengah
  - Ekspor PNG (1000×1000) & SVG (untuk cetak)
  - Frame dengan teks "Scan Menu Kami"
```

### Deteksi perlu diperbarui

```php
// Saat harga produk berubah:
//   → tandai semua menu_designs yang memuat produk itu
//     dengan needs_regeneration = true
//   → tampilkan badge di UI: "3 desain menu perlu diperbarui"
//   → tombol "Perbarui Semua" → regenerate batch
//   → Menu QR terbarui otomatis (dirender saat diakses, tidak perlu regenerate)
```

---

## ACCEPTANCE CRITERIA

- [ ] 6 template tersedia dan menghasilkan output yang secara visual berbeda
      dan rapi
- [ ] Mengubah warna/font/layout langsung terlihat di pratinjau (< 500 ms)
- [ ] Ekspor PDF A4 berkualitas cetak (300 DPI, font ter-embed, warna sRGB)
- [ ] Ekspor PNG 1080×1080 tajam dan siap unggah ke Instagram
- [ ] Menu QR bisa diakses publik, responsif, load < 1,5 detik di 3G
- [ ] QR code bermerek (warna + logo) ter-generate dalam PNG dan SVG
- [ ] Mengubah harga produk menandai desain sebagai "perlu diperbarui"
- [ ] Regenerasi batch berfungsi untuk semua desain sekaligus
- [ ] Ekspor CSV berformat sesuai kebutuhan unggah GoFood
- [ ] Item habis stok bisa disembunyikan otomatis di menu QR
- [ ] Render 50 produk selesai < 10 detik

---

## TESTING WAJIB

```php
it('renders each template without error')->with('all_templates');
it('applies custom colors from the config');
it('applies custom fonts from the config');
it('respects the column layout setting');
it('formats prices as 22K when configured');
it('hides out-of-stock items when configured');
it('generates a valid PDF with embedded fonts');
it('generates a PNG of the exact requested dimensions');
it('marks designs as needing regeneration when a price changes');
it('serves the public menu without authentication');
it('scopes the public menu to the correct tenant');
it('exports CSV in the aggregator upload format');
```

Simpan snapshot render sebagai baseline untuk visual regression test.

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=MenuDesign
php artisan posita:menu:render --design=1 --format=a4 --output=/tmp/test.pdf
npm run build
```

---

## CATATAN

⚠️ **Kualitas visual adalah fiturnya.** Menu ini akan dipajang di toko
pengguna. Template yang terlihat murahan akan membuat mereka kembali ke
Canva — dan kehilangan alasan utama fitur ini ada.

⚠️ **Browsershot butuh Chrome di server.** Sertakan di Docker image, atau
gunakan container terpisah. Dokumentasikan kebutuhan resource (Chrome
headless boros memori — batasi konkurensi job render).

⚠️ **Google Fonts harus di-embed di PDF**, bukan di-link. PDF yang dibuka di
komputer percetakan tidak punya akses internet.

⚠️ **Satu sumber kebenaran adalah nilai intinya.** Pastikan pesan ini jelas
di UI: "Harga di menu ini otomatis mengikuti harga di kasir. Tidak akan
beda lagi."

⚠️ **Batasi jumlah ekspor** untuk mencegah penyalahgunaan resource:
10/jam di tier gratis, 60/jam di berbayar.
