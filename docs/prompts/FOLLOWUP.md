# FOLLOWUP — Pekerjaan Tertunda & Prompt Lanjutan

Disusun 6 Sep 2026, setelah WP0.1 (P01 Multi-Tenancy) selesai di branch
`feat/tenancy`.

**Status: 1 dari 26 work package selesai.** Sisanya 25.

Dokumen ini berisi prompt siap salin untuk (a) membereskan yang kurang dari
sesi P01, dan (b) melanjutkan ke WP berikutnya. **Kerjakan berurutan** —
F0 dan F1 adalah blocker.

---

## Ringkasan Yang Kurang

| # | Temuan | Dampak | Prompt |
|---|---|---|---|
| 1 | **Baseline test merah** (3 gagal) + `phpunit.xml` menunjuk `tests/Unit` yang tidak ada | Preflight `EXECUTE.md` mensyaratkan baseline hijau. **Setiap sesi berikutnya akan berhenti di langkah 6 preflight.** | **F0** |
| 2 | `feat/tenancy` bertumpu pada `docs/execution-protocol` yang sudah **digantikan** — tidak punya P21–P26, `PROGRESS.md`-nya versi 20 WP | Merge urutan salah akan meregresi dokumentasi dari 26 WP kembali ke 20 | **F1** |
| 3 | PR `feat/tenancy` belum dibuka | Kode P01 belum tereview | **F1** |
| 4 | 7 penyimpangan P01 belum direview — termasuk `users.is_super_admin` (menyentuh area P02) dan perbaikan otorisasi `bulkUpdate*` (di luar lingkup ketat) | Keputusan desain masuk ke `main` tanpa persetujuan | **F2** |
| 5 | PHPStan belum terpasang; verifikasi migrasi PostgreSQL & MySQL belum pernah | Gerbang kualitas dilewati terus | P03 (dijadwalkan) |
| 6 | 25 WP belum dikerjakan | — | **F3** dan seterusnya |

---

## F0 — Perbaiki Baseline Merah (BLOCKER, kerjakan pertama)

> **Kenapa ini didahulukan.** `EXECUTE.md` §3 mensyaratkan
> `php artisan test` hijau sebelum menyentuh kode, dan menginstruksikan
> agent **berhenti** kalau merah. Baseline saat ini merah. Artinya setiap
> sesi berikutnya akan berhenti di preflight sebelum mengerjakan apa pun.
>
> Ini nominalnya lingkup P03, tapi menahannya sampai P03 berarti memblokir
> P02 sampai P26. Tarik maju bagian yang minimum saja — jangan kerjakan
> P03 seluruhnya.

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi, dengan satu
pengecualian: ini bukan work package penuh, melainkan perbaikan baseline
minimum agar preflight bisa hijau.

Masalah:
1. phpunit.xml mendeklarasikan testsuite "Unit" yang menunjuk tests/Unit,
   tapi direktori itu tidak ada. Akibatnya `php artisan test` tanpa
   --testsuite langsung abort.
2. Tiga test Feature gagal dan sudah gagal SEBELUM WP0.1:
   - RegistrationTest menguji route yang sudah dihapus
   - AuthenticationTest mengharap redirect yang sudah diubah di
     bootstrap/app.php
   Verifikasi sendiri ketiganya — jangan percaya deskripsi ini begitu saja.

Yang dikerjakan:
- Buat tests/Unit/.gitkeep, ATAU hapus testsuite "Unit" dari phpunit.xml.
  Pilih yang sesuai rencana P03 (P03 akan menambah test unit, jadi
  membuat direktorinya lebih masuk akal).
- Untuk tiap test yang gagal: tentukan apakah TESTNYA yang usang atau
  KODENYA yang rusak. Perbaiki yang benar, jelaskan alasannya di commit.
  JANGAN skip, disable, atau hapus test untuk membuat hijau.

DI LUAR LINGKUP — jangan dikerjakan:
- Memasang PHPStan (P03)
- Menambah CI (P03)
- Migrasi kolom uang ke bigint (P03)
- Test logika bisnis baru (P03)
- Refactor apa pun di luar tiga test tersebut

Branch: fix/test-baseline, bertumpu pada branch docs terbaru
(claude/dreamy-newton-8fdoap), BUKAN pada docs/execution-protocol.

Selesai kalau: `php artisan test` (tanpa flag) jalan sampai habis dan
seluruhnya hijau. Laporkan test mana yang diperbaiki dan kenapa.
```

---

## F1 — Rebase `feat/tenancy` & Buka PR

> **Masalahnya.** `feat/tenancy` bercabang dari `docs/execution-protocol`
> (commit `8bc652e`). Branch itu sudah **digantikan**: isinya sudah
> di-merge ke `claude/dreamy-newton-8fdoap`, yang kemudian menambahkan
> P21–P26 dan memperbarui `EXECUTE.md` ke 26 WP.
>
> `feat/tenancy` juga menyunting `docs/prompts/PROGRESS.md` — versi 20 WP.
> Kalau ia merge setelah branch docs, tabel status akan mundur dari 26 WP
> ke 20 WP, dan enam prompt hilang dari daftar.

```
Ada masalah urutan branch yang harus dibereskan sebelum PR dibuka.

Fakta terverifikasi:
- origin/feat/tenancy (5649208) bercabang dari origin/docs/execution-protocol
  (8bc652e), bukan dari branch docs terbaru.
- origin/claude/dreamy-newton-8fdoap (9f08a14) sudah memuat SELURUH isi
  docs/execution-protocol lewat merge, PLUS enam prompt baru (P21-P26) dan
  EXECUTE.md yang diperbarui untuk 26 work package.
- feat/tenancy menyunting docs/prompts/PROGRESS.md versi lama (20 WP), yang
  akan meregresi dokumentasi kalau di-merge belakangan.

Yang dikerjakan:
1. Merge origin/claude/dreamy-newton-8fdoap ke feat/tenancy (JANGAN rebase —
   branch ini sudah di-push, rebase akan merusak checkout orang lain).
2. Selesaikan konflik pada docs/prompts/PROGRESS.md dengan MENGGABUNG:
   - pertahankan tabel 26 WP, ledger utang teknis, dan bagian "Perlu
     Verifikasi Pemilik Produk" dari branch docs
   - pertahankan status WP0.1 = Selesai, kolom PR/Tanggal, dan tujuh
     Catatan Penyimpangan dari feat/tenancy
   Hasil akhir: 26 baris WP, WP0.1 ✅, tujuh penyimpangan tercatat.
3. Verifikasi: `grep -cE '^\| WP[0-9]' docs/prompts/PROGRESS.md` = 26,
   dan keenam file P21-P26 ada di docs/prompts/.
4. Jalankan gerbang kualitas sesuai EXECUTE.md §6. Laporkan apa adanya —
   PHPStan dan verifikasi PostgreSQL/MySQL DILEWATI, bukan lulus.
5. Push, lalu buka PR dari feat/tenancy ke main. Pakai badan PR yang sudah
   disiapkan di scratchpad (PR-feat-tenancy.md), tambahkan bagian:
   "Gerbang yang dilewati beserta alasannya" dan "Acceptance criteria yang
   belum terverifikasi".

Kalau gh tidak terpasang, cetak badan PR-nya dan beri saya link
pull/new/feat/tenancy.
```

---

## F2 — Review P01 Sebelum Merge

> Tujuh penyimpangan dicatat, dua di antaranya melewati batas lingkup P01.
> Review ini memutuskan mana yang boleh masuk `main`.

```
Review branch feat/tenancy terhadap docs/prompts/P01-multi-tenancy.md dan
docs/prompts/P00-master-context.md.

Fokus pada tujuh Catatan Penyimpangan di docs/prompts/PROGRESS.md, terutama:

1. users.is_super_admin — kolom baru. P02 (RBAC) akan mengganti users.role
   dengan spatie/laravel-permission. Apakah kolom ini akan bentrok atau jadi
   duplikat konsep saat P02? Kalau ya, apa jalan keluarnya: pertahankan,
   atau tunda ke P02?

2. Perbaikan lubang otorisasi ConsignmentService::bulkUpdate* — ini
   perbaikan keamanan nyata, tapi di luar lingkup ketat P01. Verifikasi
   perbaikannya benar dan ada testnya. Kalau benar, pertahankan dan catat
   sebagai pengecualian yang disetujui.

3. unique:users,email dibiarkan global, bukan per-tenant. Ini keputusan
   yang mengikat: dua tenant tidak akan pernah bisa punya user dengan email
   sama. Apakah itu bisa diterima untuk model bisnis di
   docs/50-gtm-pricing-legal.md (satu orang bisa punya beberapa toko)?

4. Tujuh index dari 2025_12_25_000002_add_performance_indexes.php tidak
   disentuh. Apakah index itu masih efektif setelah tenant_id ditambahkan,
   atau justru sudah tidak optimal karena tidak menyertakan tenant_id?

Untuk tiap Acceptance Criteria di P01, nyatakan TERPENUHI / TIDAK /
SEBAGIAN / BELUM TERVERIFIKASI dengan bukti (nama test, path file, output
perintah).

Periksa juga hal yang sering lolos:
- Adakah DB::table() / DB::select() / query builder mentah yang masih lolos
  global scope? (grep seluruh app/)
- Adakah withoutTenantScope() di luar console command?
- Apakah tenant_id bisa di-mass-assign dari request di suatu tempat?
- Apakah konteks tenant dibersihkan setelah job gagal, bukan hanya setelah
  job sukses?

JANGAN perbaiki apa pun. Laporkan temuannya saja, diurutkan dari yang
paling berisiko.
```

---

## F3 — Lanjut ke WP Berikutnya (P02 RBAC)

> Jalankan setelah F0 hijau dan F1 selesai.

```
Baca docs/prompts/EXECUTE.md dan jalankan sebagai instruksi.

Kerjakan work package berikutnya yang berstatus "Belum" di
docs/prompts/PROGRESS.md — seharusnya WP0.2 (P02 RBAC & Permission).

Prasyarat: P01 sudah selesai DAN sudah di-merge ke main. Verifikasi di
kode, bukan di catatan — pastikan tabel tenants/outlets ada, trait
BelongsToTenant terpasang, dan test isolasi tenant hijau.

Catatan khusus dari sesi P01 yang berdampak ke P02:
- Kolom users.is_super_admin ditambahkan di P01. P02 harus memutuskan
  apakah konsep itu diserap ke sistem role, atau tetap sebagai kolom
  terpisah. Catat keputusannya di Catatan Penyimpangan.
- users.role masih enum level database. P02 wajib menggantinya dengan
  string + PHP enum (aturan P00 #13, dan ledger utang teknis mencatat ini
  sebagai lingkup P02).

Ikuti seluruh protokol: preflight, loop eksekusi, gerbang kualitas,
commit & push. Jangan mengerjakan lebih dari satu work package.
```

---

## F4 — Setelah P02: Urutan Selanjutnya

Sesudah P02, jalankan **F3 lagi** — prompt itu generik dan otomatis
mengambil WP berikutnya dari `PROGRESS.md`. Urutannya:

```
F0 → F1 → F2 → P02 → P03 → P04 → P05 → P06 → P07 → P08 → P09 → P22
   → P10 → P11 → P12 → P13 → P14 → P15 → P16 → P17 → P23
   → P18 → P19 → P20 → P24 → P25 → P26 → P21
```

Ingat: **nomor file bukan urutan kerja.** P22 dikerjakan di Fase 1, P21
paling akhir. Lihat `EXECUTE.md` §4.

**P03 akan menutup utang yang menumpuk** — PHPStan, CI, migrasi uang ke
`bigint`, test logika bisnis, `pint.json`, file `retailer`. Setelah P03,
gerbang kualitas bisa dijalankan penuh dan tidak ada lagi yang "dilewati".

---

## Catatan Untuk Sesi Berikutnya

1. **Selalu bercabang dari branch docs terbaru**, bukan dari
   `docs/execution-protocol` (sudah digantikan) — kesalahan ini sudah
   terjadi sekali dan butuh merge tambahan untuk diperbaiki.
2. **Verifikasi ke kode, jangan percaya `PROGRESS.md` begitu saja.** File
   itu catatan, bukan kebenaran.
3. **Gerbang yang dilewati ditulis sebagai dilewati**, jangan pernah
   diklaim lulus. Sesi P01 melakukan ini dengan benar — pertahankan.
4. **Baseline merah bukan tanggung jawab WP berjalan** — kecuali F0, yang
   memang khusus dibuat untuk membereskannya.
