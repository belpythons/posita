# EXECUTE — Protokol Eksekusi Posita

> **Baca file ini di awal setiap sesi kerja, sebelum menyentuh kode apa pun.**
>
> - File ini menjelaskan **cara** bekerja.
> - [`P00-master-context.md`](P00-master-context.md) menjelaskan **aturan** kodenya.
> - [`PROGRESS.md`](PROGRESS.md) menjelaskan **apa** yang dikerjakan berikutnya.

---

## 1. Prompt Siap Salin

Salin salah satu blok ke sesi AI coding agent (Claude Code / Cursor / Copilot).

### 1A. Mulai / lanjutkan work package berikutnya ← **paling sering dipakai**

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Kerjakan work package berikutnya yang berstatus "Belum" di
docs/prompts/PROGRESS.md. Ikuti seluruh protokol di EXECUTE.md:
preflight, loop eksekusi, gerbang kualitas, lalu commit & push.

Jangan mengerjakan lebih dari satu work package dalam sesi ini.
```

### 1B. Kerjakan work package tertentu

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Work package sesi ini: P05 (Inventory & Stock Ledger).
Spesifikasinya di docs/prompts/P05-inventory.md.

Verifikasi dulu prasyaratnya (P01–P04) sudah selesai menurut PROGRESS.md
DAN terbukti ada di kode. Kalau belum, hentikan dan beri tahu saya —
jangan dikerjakan di luar urutan.
```

### 1C. Lanjutkan work package yang terputus

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Sesi sebelumnya mengerjakan P05 tapi belum selesai. Baca baris berstatus
"Jalan" di docs/prompts/PROGRESS.md, lalu periksa apa yang sebenarnya sudah
ada di kode dan test — jangan percaya catatan begitu saja.

Laporkan dulu temuanmu sebelum menulis kode baru.
```

### 1D. Review hasil sebuah work package

```
Baca docs/prompts/EXECUTE.md bagian "Gerbang Kualitas".

Review implementasi P05 terhadap docs/prompts/P05-inventory.md dan
docs/prompts/P00-master-context.md.

Untuk tiap Acceptance Criteria nyatakan: TERPENUHI / TIDAK / SEBAGIAN /
BELUM TERVERIFIKASI, dengan bukti (nama test, path file, atau output
perintah). Jangan perbaiki apa pun — laporkan temuannya saja.
```

### 1E. Audit kesesuaian kode dengan blueprint

```
Bandingkan implementasi saat ini dengan docs/20-blueprint-produk.md dan
docs/10-audit-project.md.

Hasilkan: (a) modul yang sudah selesai, (b) yang menyimpang dari blueprint
beserta alasannya jika terlihat disengaja, (c) hutang teknis baru sejak
audit awal. Perbarui Baseline Repo di PROGRESS.md kalau sudah tidak akurat.

Jangan ubah kode aplikasi.
```

### 1F. Perbaiki CI merah

```
CI merah di branch ini. Reproduksi kegagalannya secara lokal dulu, tentukan
akar masalahnya, lalu perbaiki seminimal mungkin.

Jangan skip, disable, atau quarantine test untuk membuat CI hijau.
Tunjukkan check yang sama lulus setelah perbaikan, baru push.
```

---

## 2. Aturan Sesi

1. **Satu work package per sesi/PR.** Jangan gabungkan dua WP, sebesar apa pun
   godaannya.
2. **Wajib baca `P00-master-context.md` lebih dulu.** Semua prompt `Pxx`
   mengasumsikan isinya sudah dibaca.
3. **Urutan P01 → P20 mengikat.** Prompt belakangan bergantung pada tabel dan
   abstraksi yang dibuat prompt sebelumnya. Jangan melompat.
4. **Kode nyata menang atas prompt.** Kalau prompt bertentangan dengan kode
   yang ada, kode yang benar. Catat ketidaksesuaiannya di Catatan Penyimpangan
   `PROGRESS.md` — jangan paksakan prompt.
5. **Bagian "DI LUAR LINGKUP" adalah larangan, bukan saran.** Tidak ada
   "sekalian saja".
6. **Jangan klaim sesuatu lulus kalau tidak dijalankan.** Gerbang yang
   dilewati ditulis eksplisit sebagai dilewati, beserta alasannya.
7. **PROGRESS.md bisa basi.** Ia catatan, bukan kebenaran. Verifikasi prasyarat
   ke kode dan test sebelum melanjutkan.

---

## 3. Preflight

Jalankan berurutan. Berhenti kalau ada yang gagal.

```bash
git status                                          # wajib bersih
composer install
npm install
cp .env.example .env && php artisan key:generate    # hanya jika .env belum ada
php artisan migrate --seed
php artisan test                                    # baseline harus hijau
git switch -c feat/<slug-wp>
```

Lalu, sebelum menulis kode:

| # | Aksi | Kenapa |
|---|---|---|
| 1 | Baca `P00-master-context.md` | Aturan rekayasa yang mengikat setiap PR |
| 2 | Baca `PROGRESS.md` — Baseline Repo & tabel status | Kondisi nyata & WP berikutnya |
| 3 | Baca file prompt WP **sampai habis** | Terutama DI LUAR LINGKUP dan CATATAN & JEBAKAN |
| 4 | Baca bagian relevan `docs/20-blueprint-produk.md` | Skema & aturan bisnis rinci |
| 5 | **Verifikasi prasyarat di kode, bukan di catatan** | PROGRESS.md bisa basi |

**Baseline merah bukan tanggung jawab WP ini.** Kalau `php artisan test` sudah
gagal sebelum kamu menyentuh apa pun, laporkan ke user dan tunggu keputusan —
jangan diam-diam memperbaikinya di dalam PR WP ini.

**Kalau prasyarat belum selesai:** hentikan. Laporkan WP mana yang harus lebih
dulu. P01–P20 punya ketergantungan nyata — tanpa multi-tenancy di P01, seluruh
skema sesudahnya salah.

---

## 4. Loop Eksekusi

1. Buka [`PROGRESS.md`](PROGRESS.md), ambil WP pertama berstatus `⬜ Belum`.
2. Baca prompt `Pxx-*.md` sampai habis.
3. Tandai WP itu `🟡 Jalan` di `PROGRESS.md`.
4. **Rencanakan:** tulis file apa dibuat/diubah, migrasi apa, test apa.
   Bandingkan dengan Ruang Lingkup dan DI LUAR LINGKUP. Kalau rencanamu
   melebihi lingkup, potong **sekarang**, bukan nanti.
5. **Implementasi**, dengan urutan lapisan:

   ```
   Migrasi & Model     skema dulu; reversible; tenant_id + index komposit;
                       soft delete; string + PHP enum, bukan enum DB
          ↓
   Domain & Action     value object, enum, event, lalu Action
                       (satu use case satu kelas, method handle()).
                       Logika bisnis HANYA di sini.
          ↓
   Test                dari daftar TESTING WAJIB prompt. Test adalah
                       spesifikasi — tulis sebelum atau bersamaan
                       implementasi, bukan sesudah.
          ↓
   HTTP & UI           FormRequest → Policy → Action → Resource/Inertia.
                       Controller tipis: tidak ada `if` bisnis, tidak ada
                       query. Teks pengguna bahasa Indonesia lewat lang/id/.
   ```

6. Setiap kali mengambil keputusan yang menyimpang dari prompt, catat di
   Catatan Penyimpangan `PROGRESS.md` — **saat itu juga**, bukan di akhir.
7. Jalankan gerbang kualitas (bagian 5).
8. Telusuri ACCEPTANCE CRITERIA prompt satu per satu. Yang tidak bisa
   diverifikasi di lingkungan ini ditulis **belum terverifikasi** — bukan
   dicentang.
9. Tandai `✅ Selesai` di `PROGRESS.md`, isi kolom PR dan Tanggal.
10. Commit & push (bagian 6), lalu laporkan (bagian 7).

---

## 5. Gerbang Kualitas

| Perintah | Wajib sejak | Status hari ini |
|---|---|---|
| `vendor/bin/pint` | P01 | ✅ terpasang |
| `php artisan test` | P01 | ✅ terpasang (Pest 4, SQLite in-memory) |
| `npm run build` | P01 | ✅ terpasang |
| `php artisan migrate:fresh --seed` (SQLite) | P01 | ✅ |
| `vendor/bin/phpstan analyse` | **P03** | ❌ belum terpasang — lewati sampai P03 |
| `migrate:fresh --seed` di PostgreSQL & MySQL | **P03** | ❌ tidak ada server DB — lewati, laporkan belum terverifikasi |

`P00` mendaftarkan `phpstan` dan verifikasi dua database sebagai wajib.
Keduanya belum bisa dijalankan karena tool dan infrastrukturnya memang belum
ada — pemasangannya adalah lingkup P03. Sampai P03 selesai, keduanya
**dilewati dan dilaporkan**, tidak diklaim lulus.

Meski PostgreSQL & MySQL belum bisa diuji, **tulis migrasi secara portabel**:
tanpa SQL vendor-specific, tanpa `enum` level database, dan reversible
(`down()` yang benar-benar mengembalikan skema).

Ditambah verifikasi manual:

- [ ] Setiap Acceptance Criteria terpenuhi, dan kamu bisa menunjuk buktinya
      (nama test / path file / output perintah)
- [ ] Setiap item TESTING WAJIB ada testnya
- [ ] Ada test isolasi tenant untuk model baru
- [ ] Tidak ada teks pengguna di-hardcode di PHP/Vue
- [ ] Tidak ada `float` untuk uang
- [ ] Tidak ada mutasi stok di luar `StockLedger` (jika WP menyentuh stok)
- [ ] Tidak ada rahasia/kredensial masuk repo
- [ ] Tidak ada file di luar Ruang Lingkup WP yang berubah

**Aturan pelaporan:** kalau sebuah gerbang dilewati, alasannya masuk ke
deskripsi PR. Jangan pernah menulis "semua gerbang lulus" kalau ada yang
dilewati.

**Aturan test gagal:** jangan pernah skip, disable, atau quarantine test agar
hijau. Kalau test lama gagal karena perubahanmu, itu artinya perubahanmu
merusak sesuatu — atau test itu memang perlu diperbarui karena perilakunya
sengaja berubah. Putuskan mana yang benar dan jelaskan di commit message.

---

## 6. Commit & Push

- **Conventional commits, bahasa Inggris.** Satu commit logis per langkah.
  ```
  feat(tenancy): add tenants and outlets tables
  test(tenancy): cover cross-tenant route model binding
  ```
- Sebutkan kode WP-nya di badan commit (mis. "Implements P01") agar bisa
  ditelusuri.
- Jangan sebutkan nama/ID model AI di commit, PR, atau komentar kode.
- **Branch:** `feat/<modul>`, `refactor/<modul>`, atau `docs/<topik>`.

```bash
git push -u origin <branch>
```

PR dibuat lewat UI GitHub (`gh` tidak selalu tersedia di lingkungan kerja).
**Badan PR wajib memuat:**
- WP yang dikerjakan (nomor + judul)
- ringkasan perubahan
- gerbang kualitas yang lulus
- gerbang yang dilewati **beserta alasannya**
- acceptance criteria yang belum terverifikasi

---

## 7. Laporan Akhir Sesi

Jujur. Pekerjaan setengah jadi yang dilaporkan selesai jauh lebih merusak
daripada pekerjaan yang jujur belum selesai.

```markdown
## Hasil Sesi — P05 (Inventory & Stock Ledger)

**Status:** Selesai / Sebagian / Terblokir

### Yang dikerjakan
- 8 migrasi: units, ingredients, stock_movements, stock_levels, ...
- StockLedger sebagai satu-satunya pintu mutasi stok
- 34 test (Unit 21, Feature 13)

### Acceptance Criteria
- [x] Pembelian 5 kg → saldo 5.000 g   → StockLedgerTest::it_records_a_movement
- [x] Rebuild identik dengan ledger    → StockLedgerTest::it_rebuilds_levels
- [ ] Uji konkurensi 50 paralel        → BELUM TERVERIFIKASI, butuh PostgreSQL

### Gerbang kualitas
pint ✅ · test ✅ 247 lulus · build ✅
phpstan ⏭️ dilewati (belum terpasang, lingkup P03)
PostgreSQL/MySQL ⏭️ dilewati (tidak ada server DB)

### Keputusan menyimpang dari prompt
- Kuantitas disimpan decimal(15,4), bukan integer satuan terkecil.
  Alasan: presisi cukup untuk gram/ml, lebih mudah dibaca saat debug.
  Sudah dicatat di PROGRESS.md → Catatan Penyimpangan.

### Untuk sesi berikutnya
- Selesaikan uji konkurensi (butuh PostgreSQL)
- Lanjut ke P06 (Recipe/BOM & HPP)

### Butuh keputusan Anda
- Tidak ada.  /  ATAU: pertanyaan spesifik dengan opsi & rekomendasi.
```

---

## 8. Kapan Berhenti dan Bertanya

**Berhenti, tanya dulu** kalau:

- Prompt bertentangan dengan kode nyata dan tidak jelas mana yang menang
- Acceptance criteria butuh infrastruktur yang tidak ada (server DB,
  kredensial, layanan pihak ketiga)
- WP ternyata butuh mengubah sesuatu yang eksplisit ada di DI LUAR LINGKUP-nya
- Preflight gagal karena sebab yang tidak berkaitan dengan WP ini
- Perubahan akan merusak data pengguna yang sudah ada
- Estimasi effort meleset > 2× dari roadmap

**Jangan berhenti, kerjakan saja** kalau:

- Butuh memilih nama variabel, struktur file, atau detail implementasi
- Ada bug kecil di kode lama yang menghalangi WP ini (perbaiki, sebutkan di
  laporan)
- Spesifikasi tidak menyebut sebuah edge case tapi jawabannya jelas dari P00

**Kalau harus bertanya:** kerjakan dulu semua yang tidak bergantung pada
jawaban itu, baru ajukan pertanyaannya. Jangan berhenti total dengan tangan
kosong.

---

## 9. Aturan Keras

Lengkapnya di [`P00-master-context.md`](P00-master-context.md) §"Aturan
Rekayasa". Yang paling sering dilanggar dan paling mahal akibatnya:

| # | Aturan | Kenapa mahal kalau dilanggar |
|---|---|---|
| 1 | `tenant_id` selalu diisi server-side, tidak pernah dari request | Kebocoran data antar tenant = kegagalan katastrofik |
| 2 | Semua uang lewat `Money` VO, disimpan `bigint` | Selisih rupiah berulang → sengketa dengan mitra |
| 3 | Semua mutasi stok lewat `stock_movements` | Ledger tidak bisa direkonstruksi = angka tidak bisa dipercaya |
| 4 | Snapshot harga & HPP di `order_items` | Laporan historis berubah = kepercayaan hilang |
| 5 | Jangan pernah percaya total dari klien | Aplikasi mobile bisa dimodifikasi |
| 6 | Idempotency key pada setiap tulis dari klien | Transaksi ganda saat sinkronisasi |
| 7 | Order selesai bersifat immutable | Audit trail rusak, fraud tidak terdeteksi |
| 8 | Integrasi eksternal di balik interface + driver | Terkunci pada satu vendor |

---

## 10. Anti-Pattern

❌ Mengerjakan lebih dari satu WP dalam satu sesi
❌ Menambah fitur di luar ruang lingkup ("sekalian saja")
❌ Melewati test karena "nanti saja"
❌ Skip/disable test agar CI hijau
❌ Menulis "semua gerbang lulus" padahal ada yang dilewati
❌ Mencentang acceptance criteria yang belum diverifikasi
❌ Mengubah `docs/` untuk mencocokkan implementasi yang salah
❌ Mengerjakan WP di luar urutan tanpa memverifikasi prasyarat
❌ Merancang ulang keputusan yang sudah ada ADR-nya tanpa alasan baru
❌ Logika bisnis di controller atau komponen Vue
❌ Commit raksasa yang mencampur perubahan tak berkaitan

---

## 11. Peta Dokumen

| Butuh tahu | Baca |
|---|---|
| Aturan rekayasa yang mengikat | [`P00-master-context.md`](P00-master-context.md) |
| Status, baseline repo, utang teknis | [`PROGRESS.md`](PROGRESS.md) |
| Spesifikasi satu WP | `P<nn>-*.md` |
| Skema & aturan bisnis rinci | [`../20-blueprint-produk.md`](../20-blueprint-produk.md) |
| Layering, API, keamanan | [`../30-arsitektur-target.md`](../30-arsitektur-target.md) |
| Kondisi awal & hutang teknis | [`../10-audit-project.md`](../10-audit-project.md) |
| Kenapa stack-nya begini (ADR) | [`../riset/05-tech-stack-mobile.md`](../riset/05-tech-stack-mobile.md) |
| Urutan fase & estimasi | [`../40-roadmap-rilis.md`](../40-roadmap-rilis.md) |
| Konteks pasar & alasan fitur ada | [`../riset/01-pasar-fnb-indonesia.md`](../riset/01-pasar-fnb-indonesia.md) |
| Celah kompetitor yang dikejar | [`../riset/02-analisis-kompetitor-pos.md`](../riset/02-analisis-kompetitor-pos.md) |
