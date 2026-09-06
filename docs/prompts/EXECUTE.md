# EXECUTE — Prompt Runner Eksekusi Posita

Dokumen ini adalah **prompt yang dijalankan**, bukan bacaan. Salin salah satu
blok di Bagian 1 ke sesi AI coding agent (Claude Code / Cursor / Copilot),
dan agent akan mengeksekusi planning di `docs/` secara terkontrol.

---

## 1. Prompt Siap Salin

### 1A. Mulai / lanjutkan work package berikutnya ← **yang paling sering dipakai**

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Kerjakan work package berikutnya yang belum selesai menurut
docs/prompts/PROGRESS.md. Ikuti seluruh protokol di EXECUTE.md:
preflight, loop eksekusi, gerbang kualitas, lalu commit & push.

Jangan mengerjakan lebih dari satu work package dalam sesi ini.
```

### 1B. Kerjakan work package tertentu

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Work package yang dikerjakan sesi ini: P05 (Inventory & Stock Ledger).
Spesifikasinya ada di docs/prompts/P05-inventory.md.

Verifikasi dulu bahwa prasyaratnya (P01-P04) sudah selesai di
docs/prompts/PROGRESS.md. Kalau belum, hentikan dan beri tahu saya —
jangan dikerjakan di luar urutan.
```

### 1C. Lanjutkan work package yang terputus

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Sesi sebelumnya mengerjakan P05 tapi belum selesai. Baca catatan
"Sedang berjalan" di docs/prompts/PROGRESS.md, periksa apa yang sudah
ada di kode (jangan percaya catatan begitu saja — verifikasi ke kode
dan test), lalu lanjutkan dari titik itu.

Laporkan dulu apa yang kamu temukan sudah selesai sebelum menulis kode baru.
```

### 1D. Review hasil sebuah work package

```
Baca docs/prompts/EXECUTE.md bagian "Gerbang Kualitas".

Review implementasi P05 terhadap spesifikasi di docs/prompts/P05-inventory.md
dan aturan di docs/prompts/P00-master-context.md.

Untuk setiap Acceptance Criteria, nyatakan: TERPENUHI / TIDAK / SEBAGIAN,
dengan bukti (nama test, path file, atau output perintah). Jangan perbaiki
apa pun dulu — laporkan temuannya saja.
```

### 1E. Audit kesesuaian kode dengan blueprint

```
Bandingkan implementasi saat ini dengan docs/20-blueprint-produk.md dan
docs/10-audit-project.md.

Hasilkan: (a) modul yang sudah selesai, (b) yang menyimpang dari blueprint
beserta alasannya jika terlihat disengaja, (c) hutang teknis baru yang
muncul sejak audit awal. Perbarui docs/10-audit-project.md jika ada yang
sudah tidak akurat.

Jangan ubah kode aplikasi.
```

### 1F. Perbaiki CI merah

```
CI merah di branch ini. Reproduksi kegagalannya secara lokal terlebih
dahulu, tentukan akar masalahnya, lalu perbaiki seminimal mungkin.

Jangan skip, disable, atau quarantine test untuk membuat CI hijau.
Tunjukkan check yang sama lulus setelah perbaikan, baru push.
```

---

## 2. Peran & Misi

> Bagian ini dan seterusnya ditujukan untuk **agent** yang menjalankan prompt
> di atas.

Kamu adalah engineer yang mengeksekusi rencana produk Posita. Rencananya
sudah matang dan tertulis; tugasmu **bukan** merancang ulang, melainkan
mengimplementasikan dengan disiplin.

Posita adalah POS multi-tenant untuk bisnis F&B Indonesia (fokus coffee
shop) yang menangani penjualan produk sendiri **dan** produk titipan mitra
(konsinyasi) dalam satu sistem.

Tiga pilar yang harus dilayani setiap keputusan teknis:
1. **Profit Guard** — resep/BOM, HPP hidup, waste, laporan kebocoran
2. **Mitra Ready** — konsinyasi kelas satu, bagi hasil, settlement
3. **Punya Kamu, Bentuk Kamu** — ekspor data penuh, API terbuka, kustomisasi

---

## 3. Preflight — Wajib Sebelum Menulis Kode

Jalankan berurutan. Jangan lewati.

| # | Aksi | Kenapa |
|---|---|---|
| 1 | Baca `docs/prompts/P00-master-context.md` | Aturan rekayasa yang mengikat setiap PR |
| 2 | Baca `docs/prompts/PROGRESS.md` | Status terkini & work package berikutnya |
| 3 | Baca file prompt WP yang akan dikerjakan | Spesifikasi lengkap |
| 4 | Baca bagian relevan `docs/20-blueprint-produk.md` | Skema & aturan bisnis rinci |
| 5 | **Verifikasi prasyarat di kode, bukan di catatan** | PROGRESS.md bisa basi |
| 6 | `git status` bersih & branch benar | Hindari mencampur pekerjaan |
| 7 | Jalankan test suite yang ada | Ketahui baseline hijau/merah sebelum mengubah |

**Jika prasyarat belum selesai:** hentikan. Laporkan WP mana yang harus
dikerjakan lebih dulu. Jangan mengerjakan di luar urutan — P01–P20 punya
ketergantungan nyata (mis. tanpa multi-tenancy di P01, seluruh skema
setelahnya salah).

**Jika baseline test sudah merah sebelum kamu menyentuh apa pun:** perbaiki
itu dulu, atau laporkan, sebelum menambah fitur.

---

## 4. Memilih Work Package

```
Urutan wajib: P01 → P02 → P03 → P04 → ... → P20

Fase 0 (P01–P03)  fondasi — blocker semua hal lain
Fase 1 (P04–P09)  domain inti POS
Fase 2 (P10–P11)  API & aplikasi kasir
Fase 3 (P12)      offline-first → gate rilis v1.0
Fase 4 (P13–P17)  pembeda kompetitif
Fase 5 (P18–P20)  skala & ekosistem
```

**Satu work package per sesi.** Beberapa WP besar (P11 ±5 pekan, P12 ±5
pekan) perlu dipecah lintas sesi — itu wajar; pakai prompt 1C untuk
melanjutkan, dan catat titik berhentinya di PROGRESS.md.

---

## 5. Loop Eksekusi

```
┌─ 1. RENCANAKAN ────────────────────────────────────────────────┐
│ Tulis rencana singkat: file apa dibuat/diubah, migrasi apa,     │
│ test apa. Bandingkan dengan "Ruang Lingkup" dan "DI LUAR        │
│ LINGKUP" di prompt WP. Kalau rencanamu melebihi ruang lingkup,  │
│ potong sekarang — bukan nanti.                                  │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 2. MIGRASI & MODEL ───────────────────────────────────────────┐
│ Skema dulu. Reversible. tenant_id + index komposit.             │
│ Soft delete. String + PHP enum, bukan enum DB.                  │
│ Uji `migrate:fresh` lalu `migrate:rollback` sampai bersih.      │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 3. DOMAIN & ACTION ───────────────────────────────────────────┐
│ Value object, enum, event. Lalu Action (satu use case satu      │
│ kelas, method handle()). Logika bisnis HANYA di sini.           │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 4. TEST ──────────────────────────────────────────────────────┐
│ Tulis test dari daftar "TESTING WAJIB" di prompt WP.            │
│ Minimal: happy path + edge case + authorization + isolasi       │
│ tenant. Test adalah spesifikasi — tulis sebelum atau bersamaan  │
│ dengan implementasi, bukan sesudah.                             │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 5. HTTP & UI ─────────────────────────────────────────────────┐
│ FormRequest → Policy → Action → Resource/Inertia.               │
│ Controller tipis: tidak ada `if` bisnis, tidak ada query.       │
│ Teks pengguna bahasa Indonesia lewat lang/id/.                  │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 6. GERBANG KUALITAS ──────────────────────────────────────────┐
│ Bagian 6. Semua harus hijau. Tanpa pengecualian.                │
└────────────────────────────────────────────────────────────────┘
                              ↓
┌─ 7. SERAH TERIMA ──────────────────────────────────────────────┐
│ Perbarui PROGRESS.md → commit → push → laporkan (Bagian 9).     │
└────────────────────────────────────────────────────────────────┘
```

---

## 6. Gerbang Kualitas

Semua wajib lulus sebelum commit. Kalau ada yang gagal, **perbaiki** —
jangan dilaporkan sebagai "catatan untuk nanti".

```bash
vendor/bin/pint                       # format
vendor/bin/phpstan analyse            # level 6, tanpa error baru
php artisan test                      # seluruh suite, bukan hanya filter WP ini
npm run build                         # frontend build
```

Ditambah verifikasi manual:

- [ ] Setiap **Acceptance Criteria** di prompt WP terpenuhi, dan kamu bisa
      menunjuk buktinya (nama test / path file / output perintah)
- [ ] Setiap item di **TESTING WAJIB** ada testnya
- [ ] Migrasi reversible: `migrate:fresh` → `migrate:rollback` bersih
- [ ] Ada test isolasi tenant untuk model baru
- [ ] Tidak ada teks pengguna yang di-hardcode di PHP/Vue
- [ ] Tidak ada `float` untuk uang
- [ ] Tidak ada mutasi stok di luar `StockLedger` (jika WP menyentuh stok)
- [ ] Tidak ada rahasia/kredensial masuk repo
- [ ] Tidak ada file di luar **Ruang Lingkup** WP yang berubah

**Aturan tentang test yang gagal:** jangan pernah skip, disable, atau
quarantine test agar hijau. Kalau ada test lama yang gagal karena
perubahanmu, itu artinya perubahanmu merusak sesuatu — atau test itu memang
perlu diperbarui karena perilakunya sengaja berubah. Putuskan mana yang
benar dan jelaskan alasannya di commit message.

---

## 7. Aturan Keras

Aturan lengkap ada di `docs/prompts/P00-master-context.md` §"Aturan
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

## 8. Commit, Branch & PR

```bash
# Branch: satu WP satu branch
git checkout -b feat/p05-inventory-ledger

# Commit: conventional commits, bahasa Inggris, jelaskan KENAPA
git commit -m "feat(inventory): add append-only stock ledger

Introduce stock_movements as the single source of truth for stock,
with stock_levels as a rebuildable cache. Every mutation goes through
StockLedger; direct writes to stock_levels are no longer possible.

Implements P05 (WP1.2)."
```

**Aturan commit:**
- Bahasa Inggris untuk commit message; bahasa Indonesia hanya untuk teks
  yang dilihat pengguna aplikasi
- Satu commit logis per perubahan koheren — jangan satu commit raksasa
- Jangan sebutkan nama/ID model AI di commit, PR, atau komentar kode
- Sebutkan kode WP-nya (mis. "Implements P05") agar bisa ditelusuri

**PR:** buat hanya jika diminta. Isi body dengan: WP yang dikerjakan,
ringkasan perubahan, cara mengujinya, dan Acceptance Criteria yang
terpenuhi.

---

## 9. Format Laporan Akhir Sesi

Akhiri setiap sesi dengan laporan ini. Jujur — pekerjaan setengah jadi yang
dilaporkan selesai jauh lebih merusak daripada pekerjaan yang jujur belum
selesai.

```markdown
## Hasil Sesi — P05 (Inventory & Stock Ledger)

**Status:** Selesai / Sebagian / Terblokir

### Yang dikerjakan
- 8 migrasi: units, ingredients, stock_movements, stock_levels, ...
- StockLedger service sebagai satu-satunya pintu mutasi stok
- Command posita:stock:rebuild & posita:stock:verify
- 34 test (Unit 21, Feature 13)

### Acceptance Criteria
- [x] Pembelian 5 kg → saldo 5.000 g   → StockLedgerTest::it_records_a_movement
- [x] Rebuild identik dengan ledger    → StockLedgerTest::it_rebuilds_levels
- [ ] Uji konkurensi 50 paralel        → BELUM, butuh PostgreSQL di lokal

### Gerbang kualitas
pint ✅ · phpstan ✅ · test ✅ 247 lulus · build ✅ · coverage 78%

### Keputusan yang diambil
- Kuantitas disimpan decimal(15,4), bukan integer satuan terkecil.
  Alasan: presisi cukup untuk gram/ml, dan lebih mudah dibaca saat debug.
  Sudah dicatat di docs/20-blueprint-produk.md.

### Menyimpang dari rencana
- Tidak ada.  /  ATAU: jelaskan apa & kenapa.

### Untuk sesi berikutnya
- Selesaikan uji konkurensi (butuh PostgreSQL, SQLite tidak punya
  SELECT FOR UPDATE)
- Lanjut ke P06 (Recipe/BOM & HPP)

### Butuh keputusan Anda
- Tidak ada.  /  ATAU: pertanyaan spesifik dengan opsi & rekomendasi.
```

---

## 10. Kapan Berhenti dan Bertanya

**Berhenti, tanya dulu** kalau:

- Spesifikasi WP bertentangan dengan kode yang ada, dan tidak jelas mana
  yang benar
- Menyelesaikannya butuh perubahan skema di luar ruang lingkup WP
- Ada keputusan arsitektur yang tidak tercakup ADR di
  `docs/riset/05-tech-stack-mobile.md`
- Butuh kredensial, akun pihak ketiga, atau layanan berbayar
- Perubahan akan merusak data pengguna yang sudah ada
- Estimasi effort meleset > 2× dari yang tertulis di roadmap

**Jangan berhenti, kerjakan saja** kalau:

- Butuh memilih nama variabel, struktur file, atau detail implementasi
- Ada bug kecil di kode lama yang menghalangi WP ini (perbaiki, sebutkan
  di laporan)
- Spesifikasi tidak menyebutkan sebuah edge case tapi jawabannya jelas dari
  aturan di P00

**Kalau harus bertanya:** kerjakan dulu semua yang tidak bergantung pada
jawaban itu, baru ajukan pertanyaannya. Jangan berhenti total dengan tangan
kosong.

---

## 11. Anti-Pattern

❌ Mengerjakan lebih dari satu WP dalam satu sesi
❌ Menambah fitur di luar ruang lingkup ("sekalian saja")
❌ Melewati test karena "nanti saja"
❌ Skip/disable test agar CI hijau
❌ Melaporkan selesai padahal Acceptance Criteria belum semua terpenuhi
❌ Mengubah `docs/` untuk mencocokkan implementasi yang salah
❌ Mengerjakan WP di luar urutan tanpa memverifikasi prasyarat
❌ Merancang ulang keputusan yang sudah ada ADR-nya tanpa alasan baru
❌ Logika bisnis di controller atau di komponen Vue
❌ Commit raksasa yang mencampur banyak perubahan tak berkaitan

---

## 12. Peta Dokumen

| Butuh tahu | Baca |
|---|---|
| Aturan rekayasa yang mengikat | `docs/prompts/P00-master-context.md` |
| Status & WP berikutnya | `docs/prompts/PROGRESS.md` |
| Spesifikasi satu WP | `docs/prompts/P<nn>-*.md` |
| Skema & aturan bisnis rinci | `docs/20-blueprint-produk.md` |
| Layering, API, keamanan | `docs/30-arsitektur-target.md` |
| Kondisi awal & hutang teknis | `docs/10-audit-project.md` |
| Kenapa stack-nya begini (ADR) | `docs/riset/05-tech-stack-mobile.md` |
| Urutan fase & estimasi | `docs/40-roadmap-rilis.md` |
| Konteks pasar & alasan fitur ada | `docs/riset/01-pasar-fnb-indonesia.md` |
| Celah kompetitor yang dikejar | `docs/riset/02-analisis-kompetitor-pos.md` |
