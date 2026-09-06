# Ringkasan Perubahan — Paket Perencanaan & Eksekusi Posita

Catatan tertulis atas seluruh dokumen perencanaan yang dihasilkan, sebagai
pengganti deskripsi pull request. **Tidak ada PR yang dibuat** — perubahan ada
di branch `claude/dreamy-newton-8fdoap`, dan keputusan menggabungkannya ke
`main` ada di tangan pemilik repo.

**Tanggal:** 6 September 2026
**Sifat perubahan:** dokumentasi saja — **nol file kode aplikasi berubah**

---

## 1. Apa yang Ada Sekarang

41 file markdown, ± 11.900 baris, di `docs/`.

```
docs/
├── README.md                       indeks & navigasi
├── RINGKASAN-PERUBAHAN.md          file ini
│
├── riset/                          5 dokumen riset
│   ├── 01-pasar-fnb-indonesia.md       pasar, struktur biaya, kegagalan, konsinyasi, pajak
│   ├── 02-analisis-kompetitor-pos.md   8 kompetitor, matriks fitur, 8 celah pasar
│   ├── 03-payment-gateway-qris.md      opsi gratis, MDR 2026, driver abstraction
│   ├── 04-whatsapp-notifikasi.md       official vs gateway lokal, katalog 25+ alert
│   └── 05-tech-stack-mobile.md         evaluasi stack + 6 ADR
│
├── 10-audit-project.md             kondisi codebase, hutang teknis, gap analysis
├── 20-blueprint-produk.md          14 modul, ± 85 tabel, aturan bisnis, engine kustomisasi
├── 30-arsitektur-target.md         layering, kontrak API, keamanan, deployment
├── 40-roadmap-rilis.md             6 fase, 26 work package, KPI, mitigasi risiko
├── 50-gtm-pricing-legal.md         positioning, paket harga, kepatuhan UU PDP & PB1
│
└── prompts/
    ├── EXECUTE.md                  protokol eksekusi + 6 prompt siap salin
    ├── PROGRESS.md                 baseline repo, status 26 WP, ledger utang teknis
    ├── README.md                   indeks & urutan eksekusi
    ├── P00-master-context.md       aturan rekayasa yang mengikat setiap PR
    └── P01…P26                     26 spesifikasi work package
```

---

## 2. Riwayat Commit

Empat commit di atas `main` (`3353f7a`):

| Commit | Isi |
|---|---|
| `50d392b` | Prompt runner (`EXECUTE.md`) + papan status (`PROGRESS.md`) versi pertama |
| `8bc652e` | Versi paralel dari sesi lain: baseline repo terverifikasi + ledger utang teknis |
| `902a2e7` | **Merge** kedua versi — bukan membuang salah satu |
| `d6e3c9f` | Enam prompt baru P21–P26 menutup lubang cakupan |

Dokumen riset & perencanaan (`riset/`, `10`–`50`, `P00`–`P20`) sudah lebih dulu
masuk `main` lewat commit `787a877`.

### Catatan tentang merge `902a2e7`

Dua sesi secara terpisah menulis `EXECUTE.md` dan `PROGRESS.md` dari parent
yang sama, menghasilkan dua versi bersaing. Keduanya digabung, bukan dipilih
salah satu:

**Diambil dari `docs/execution-protocol`:**
- Baseline repo terverifikasi lewat perintah nyata (grep `tenant` → nol hit,
  hitungan migrasi/model/service, inventaris test & factory, versi tooling)
- Tiga titik raw query yang lolos global scope, dan 6 route model binding yang
  harus diamankan P01
- Ledger utang teknis, tiap baris dipetakan ke WP penebusnya
- Perintah preflight konkret
- **Tabel gerbang kualitas dengan status hari ini** — ini memperbaiki cacat di
  versi satunya, yang mencantumkan `phpstan analyse` sebagai gerbang wajib
  padahal PHPStan belum terpasang sampai P03

**Diambil dari `claude/dreamy-newton-8fdoap`:**
- Enam blok prompt siap salin
- Loop eksekusi berlapis (migrasi → domain → test → HTTP/UI)
- Tabel aturan keras, template laporan akhir sesi, anti-pattern, peta dokumen
- Gate keluar per fase, prasyarat & effort per WP, item yang perlu verifikasi
  pemilik produk

---

## 3. Hasil Audit Cakupan

Pemeriksaan `docs/40-roadmap-rilis.md` terhadap prompt yang ada menemukan enam
lubang: pekerjaan yang diasumsikan ada oleh rencana, tapi tidak dibangun prompt
mana pun. Keenamnya ditutup di `d6e3c9f`.

| Prompt | WP | Fase | Effort | Lubang yang ditutup |
|---|---|---|---|---|
| P21 Billing & Subscription | 5.7 | F5 | 2 pekan | Model harga dijual di `50-gtm`, P19 menegakkan batas paket, P01 membuat `tenants.plan` — tapi tidak ada yang menerbitkan tagihan atau menerima pembayaran |
| P22 Customer & Piutang | 1.7 | F1 | 1 pekan | `orders.customer_id` & `customer_credits` dirujuk sejak M5/M6, entitasnya tidak pernah dibuat |
| P23 Meja, Dine-in & KDS | 4.7 | F4 | 2 pekan | Blueprint mendefinisikan `tables`, preset "Resto Lengkap" menyalakan `tables` & `kds`, tidak ada yang membangunnya |
| P24 Aplikasi Staf | 5.4 | F5 | 1 pekan | P15 membangun absensi selfie + GPS tanpa klien yang bisa memotret |
| P25 Integrasi Aggregator | 5.5 | F5 | 2,5 pekan | Ada di roadmap, tidak ada prompt |
| P26 Paket Self-Hosted | 5.6 | F5 | 0,5 pekan | Ada di roadmap, tidak ada prompt |

### Keputusan desain yang diambil di keenamnya

- **P21 memakai ulang driver pembayaran P18**, tidak membangun tumpukan kedua.
- **Gagal bayar tidak pernah menyandera data** — turun ke paket Gratis dengan
  data utuh, ekspor penuh tetap tersedia tanpa syarat. Aturan yang sama
  diterapkan pada validasi lisensi di P26. Produk yang menjual anti-lock-in
  lalu mengunci data penunggak adalah produk yang berbohong.
- **P22 mengunci aturan akuntansi kasbon**: penjualan kasbon adalah pendapatan
  tapi **bukan** kas di laci; pelunasan menambah kas di shift saat pelunasan,
  tanpa membuat order baru. Kalau ini salah, rekonsiliasi shift meleset setiap
  hari.
- **P23 menurunkan status meja dari order terbuka**, tidak menyimpannya sebagai
  kolom — kolom status selalu melenceng dalam hitungan hari. KDS memakai
  polling, bukan WebSocket, karena jaringan kedai tidak stabil.
- **P24 memisahkan aplikasi staf dari aplikasi kasir** supaya HP pribadi
  karyawan tidak pernah menyimpan katalog, harga, atau HPP.
- **P25 diberi gerbang bisnis di depan**: akses API GoFood/Grab/Shopee butuh
  kemitraan resmi, bukan pendaftaran mandiri. Prompt dimulai dari driver
  `manual_csv` yang tidak butuh kemitraan apa pun dan tetap memberi laporan
  komisi & margin per channel.

---

## 4. Dampak ke Roadmap

| | Sebelum | Sesudah |
|---|---|---|
| Work package | 20 | **26** |
| Fase 1 | 8–10 pekan | 9–11 pekan |
| Fase 4 | 8–10 pekan | 10–12 pekan |
| Fase 5 | 6–8 pekan | 8–10 pekan |
| **Total** | **36–45 pekan** | **45–54 pekan** |

- **Billing (WP5.7)** masuk daftar *tidak boleh dipotong* — tanpa ini tidak ada
  cara menagih siapa pun.
- **Meja/Dine-in/KDS (WP4.7)** masuk daftar *boleh ditunda* — tidak dibutuhkan
  coffee shop. Kalau ditunda, klaim `tables` & `kds` **wajib dihapus** dari
  preset "Resto Lengkap" supaya tidak menjanjikan fitur yang tidak ada.
- Tiga baris baru di ledger utang teknis mencatat *forward reference*: tempat
  di mana P01, P19, dan blueprint merujuk modul yang baru dibangun belakangan.

---

## 5. Verifikasi yang Dijalankan

| Pemeriksaan | Hasil |
|---|---|
| Seluruh tautan relatif `.md` di `docs/` | ✅ valid, nol broken link |
| Jumlah baris WP di `PROGRESS.md` | ✅ 26, urutan konsisten dengan `README.md` |
| Setiap WP di roadmap punya prompt | ✅ sisanya tercakup rentang (`WP0.3–0.7`, `WP2.2–2.6`, `WP3.1–3.5`, `WP4.3–4.4`) |
| Sisa marker konflik merge | ✅ bersih |
| File kode aplikasi berubah | ✅ nol |

**Gerbang `pint` / `test` / `build` tidak dijalankan** — nol file kode berubah,
dan `vendor/` serta `node_modules/` belum di-install di lingkungan ini. Sesuai
aturan pelaporan di `EXECUTE.md` §5, gerbang yang dilewati ditulis eksplisit,
bukan diklaim lulus.

---

## 6. Status & Langkah Berikutnya

**Eksekusi kode belum dimulai.** Seluruh 26 WP berstatus ⬜ Belum.

Work package berikutnya: **WP0.1 — [P01 Multi-Tenancy & Outlet](prompts/P01-multi-tenancy.md)**.
Ini blocker untuk semua WP lain; tanpa multi-tenancy, seluruh skema sesudahnya
salah.

Untuk memulai, salin blok ini ke sesi AI coding agent:

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Kerjakan work package berikutnya yang berstatus "Belum" di
docs/prompts/PROGRESS.md. Ikuti seluruh protokol di EXECUTE.md:
preflight, loop eksekusi, gerbang kualitas, lalu commit & push.

Jangan mengerjakan lebih dari satu work package dalam sesi ini.
```

Lima varian prompt lain (kerjakan WP tertentu, lanjutkan yang terputus, review,
audit, perbaiki CI) ada di [`prompts/EXECUTE.md`](prompts/EXECUTE.md) §1.

---

## 7. Menggabungkan Tanpa Pull Request

Branch `claude/dreamy-newton-8fdoap` sudah di-push ke origin. Kalau ingin
menggabungkannya ke `main` tanpa membuka PR:

```bash
git checkout main
git pull origin main
git merge claude/dreamy-newton-8fdoap
git push origin main
```

Branch `docs/execution-protocol` sudah sepenuhnya terkandung di dalamnya
(lewat merge `902a2e7`), jadi setelah itu bisa dihapus:

```bash
git push origin --delete docs/execution-protocol
```

---

## 8. Perlu Verifikasi Pemilik Produk

Dibawa dari fase riset — **belum terverifikasi**. Jangan dipakai di materi
komersial atau di-hardcode sebelum dicek ke sumber primer. Daftar ini juga ada
di [`prompts/PROGRESS.md`](prompts/PROGRESS.md).

| # | Item | Sumber yang harus dicek | Dibutuhkan sebelum |
|---|---|---|---|
| 1 | Ambang MDR QRIS 0% per 1 Okt 2026 untuk kategori UKE/UME/UBE | Dokumen resmi Bank Indonesia atau PJP yang dipakai | P18 |
| 2 | Harga & fitur kompetitor (Majoo, Olsera, Pawoon, dll) | Situs resmi masing-masing vendor | Materi pemasaran |
| 3 | Tarif PB1/PBJT & ambang omzet per kabupaten/kota | Perda / Bapenda daerah target | P08 |
| 4 | Status lisensi Bank Indonesia payment gateway yang dipilih | Daftar PJP berizin BI | P18 |
| 5 | Lisensi resmi project (README bilang unlicense, `composer.json` bilang MIT) | Keputusan pemilik produk | P03 |
