# EXECUTE — Protokol Eksekusi Posita

> **Baca file ini di awal setiap sesi kerja, sebelum menyentuh kode apa pun.**
> File ini menjelaskan *cara* bekerja. [`P00-master-context.md`](P00-master-context.md)
> menjelaskan *aturan* kodenya. [`PROGRESS.md`](PROGRESS.md) menjelaskan *apa* yang
> dikerjakan berikutnya.

---

## 1. Aturan Sesi

1. **Satu work package per sesi/PR.** Jangan gabungkan dua WP, sebesar apa pun
   godaannya.
2. **Wajib baca `P00-master-context.md` lebih dulu.** Semua prompt `Pxx`
   mengasumsikan isinya sudah dibaca.
3. **Urutan P01 → P20 mengikat.** Prompt belakangan bergantung pada tabel dan
   abstraksi yang dibuat prompt sebelumnya. Jangan melompat.
4. **Kode nyata menang atas prompt.** Kalau prompt bertentangan dengan kode yang
   ada, kode yang benar. Catat ketidaksesuaiannya di Catatan Penyimpangan
   `PROGRESS.md` — jangan paksakan prompt.
5. **Bagian "DI LUAR LINGKUP" adalah larangan, bukan saran.** Tidak ada
   "sekalian saja".
6. **Jangan klaim sesuatu lulus kalau tidak dijalankan.** Gerbang yang dilewati
   ditulis eksplisit sebagai dilewati, beserta alasannya.

---

## 2. Preflight

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

**Baseline merah bukan tanggung jawab WP ini.** Kalau `php artisan test` sudah
gagal sebelum kamu menyentuh apa pun, laporkan ke user dan tunggu keputusan —
jangan diam-diam memperbaikinya di dalam PR WP ini.

---

## 3. Loop Eksekusi

1. Buka [`PROGRESS.md`](PROGRESS.md), ambil WP pertama berstatus `⬜ Belum`.
2. Baca file prompt `Pxx-*.md` **sampai habis** — terutama bagian
   DI LUAR LINGKUP dan CATATAN & JEBAKAN.
3. Tandai WP itu `🟡 Jalan` di `PROGRESS.md`.
4. Implementasi mengikuti bagian SPESIFIKASI. Setiap kali kamu mengambil
   keputusan yang menyimpang dari prompt, catat di Catatan Penyimpangan
   `PROGRESS.md` — saat itu juga, bukan di akhir.
5. Tulis test sesuai bagian TESTING WAJIB prompt.
6. Jalankan gerbang kualitas (bagian 4).
7. Telusuri ACCEPTANCE CRITERIA prompt satu per satu. Yang tidak bisa
   diverifikasi di lingkungan ini ditulis **belum terverifikasi** — bukan
   dicentang.
8. Tandai `✅ Selesai` di `PROGRESS.md`, isi kolom PR dan Tanggal.
9. Commit & push (bagian 5).

---

## 4. Gerbang Kualitas

| Perintah | Wajib sejak | Status hari ini |
|---|---|---|
| `vendor/bin/pint` | P01 | ✅ terpasang |
| `php artisan test` | P01 | ✅ terpasang (Pest 4, SQLite in-memory) |
| `npm run build` | P01 | ✅ terpasang |
| `php artisan migrate:fresh --seed` (SQLite) | P01 | ✅ |
| `vendor/bin/phpstan analyse` | **P03** | ❌ belum terpasang — lewati sampai P03 |
| `migrate:fresh --seed` di PostgreSQL & MySQL | **P03** | ❌ tidak ada server DB — lewati, laporkan belum terverifikasi |

`P00` mendaftarkan `phpstan` dan verifikasi dua database sebagai wajib. Keduanya
belum bisa dijalankan karena tool dan infrastrukturnya memang belum ada —
pemasangannya adalah lingkup P03. Sampai P03 selesai, keduanya **dilewati dan
dilaporkan**, tidak diklaim lulus.

Meski PostgreSQL & MySQL belum bisa diuji, **tulis migrasi secara portabel**:
tanpa SQL vendor-specific, tanpa `enum` level database, dan reversible
(`down()` yang benar-benar mengembalikan skema).

**Aturan pelaporan:** kalau sebuah gerbang dilewati, alasannya masuk ke deskripsi
PR. Jangan pernah menulis "semua gerbang lulus" kalau ada yang dilewati.

---

## 5. Commit & Push

- **Conventional commits, bahasa Inggris.** Satu commit logis per langkah.
  ```
  feat(tenancy): add tenants and outlets tables
  test(tenancy): cover cross-tenant route model binding
  ```
- **Branch:** `feat/<modul>`, `refactor/<modul>`, atau `docs/<topik>`.
- ```bash
  git push -u origin <branch>
  gh pr create
  ```
- **Badan PR wajib memuat:**
  - WP yang dikerjakan (nomor + judul)
  - ringkasan perubahan
  - gerbang kualitas yang lulus
  - gerbang yang dilewati **beserta alasannya**
  - acceptance criteria yang belum terverifikasi

---

## 6. Kapan Berhenti dan Bertanya

Berhenti dan tanya user, jangan menebak, kalau:

- Prompt bertentangan dengan kode nyata dan tidak jelas mana yang menang.
- Sebuah acceptance criteria butuh infrastruktur yang tidak ada (server DB,
  kredensial, layanan pihak ketiga).
- WP ternyata butuh mengubah sesuatu yang eksplisit ada di daftar
  DI LUAR LINGKUP-nya.
- Preflight gagal karena sebab yang tidak berkaitan dengan WP ini.
