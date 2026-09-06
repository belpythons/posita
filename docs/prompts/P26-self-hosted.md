# P26 — Paket Self-Hosted

**Fase:** F5 · **WP:** 5.6 · **Effort:** ± 0,5 pekan · **Prasyarat:** P20, P21

---

## PERAN
Release engineer yang mengemas Posita agar bisa dipasang orang lain di server
mereka sendiri, tanpa bantuan Anda.

## KONTEKS
`docs/riset/02-analisis-kompetitor-pos.md` mengidentifikasi vendor lock-in
sebagai keluhan berulang pengguna POS Indonesia — data tidak bisa dibawa saat
pindah vendor. Pilar ketiga Posita ("Punya Kamu, Bentuk Kamu") menjanjikan
opsi self-hosted sebagai bukti janji itu.

Roadmap menandai WP5.6 **boleh ditunda**: nilai pemasarannya tinggi,
permintaan nyatanya rendah. Tapi *keberadaannya* adalah argumen penjualan
walau sedikit yang memakai.

## TUJUAN
Paket instalasi yang bisa dijalankan orang teknis di VPS mereka sendiri dalam
< 30 menit, dengan jalur upgrade dan backup yang jelas.

## RUANG LINGKUP
1. `docker-compose.yml` produksi (app, worker, scheduler, PostgreSQL, Redis,
   Caddy, MinIO)
2. `install.sh` interaktif
3. Validasi lisensi yang **toleran offline** dan **tidak pernah mematikan
   aplikasi**
4. Perintah upgrade + runner migrasi
5. Skrip backup & restore
6. Dokumentasi instalasi, upgrade, backup, troubleshooting
7. Telemetri **opt-in**, mati secara default
8. Ketentuan lisensi (`LICENSE-SELFHOSTED`)

## DI LUAR LINGKUP
- ❌ Helm chart / Kubernetes
- ❌ Instalasi satu klik di panel hosting
- ❌ Dukungan multi-server / high availability
- ❌ Migrasi otomatis dari cloud ke self-hosted (v1 lewat ekspor-impor P20)

---

## SPESIFIKASI

### 1. Struktur paket

```
self-hosted/
├── docker-compose.yml
├── docker-compose.override.example.yml
├── .env.example
├── install.sh
├── upgrade.sh
├── backup.sh
├── restore.sh
├── Caddyfile
└── README.md
```

### 2. `install.sh` — interaktif, bahasa Indonesia

```
$ ./install.sh

Posita Self-Hosted — Instalasi
──────────────────────────────
Memeriksa prasyarat...
  ✅ Docker 24.0.7
  ✅ Docker Compose v2.23
  ✅ Port 80 & 443 tersedia
  ✅ Ruang disk 48 GB

Domain (mis. pos.tokosaya.id): _
Email admin (untuk sertifikat SSL): _
Nama toko: _
Kunci lisensi (kosongkan untuk mode Komunitas): _

Membuat konfigurasi...      ✅
Menarik image...            ✅
Menjalankan migrasi...      ✅
Membuat akun owner...       ✅
Menerbitkan sertifikat...   ✅

Selesai. Buka https://pos.tokosaya.id
Akun: owner@tokosaya.id  ·  Kata sandi ada di ./credentials.txt

Langkah berikutnya:
  1. Ganti kata sandi setelah login pertama
  2. Jadwalkan backup:  ./backup.sh --install-cron
  3. Baca README.md bagian "Upgrade"
```

Skrip harus **idempoten** — dijalankan dua kali tidak merusak instalasi yang
sudah ada.

### 3. Lisensi — aturan yang tidak boleh dilanggar

```php
final class LicenseValidator
{
    // Memeriksa lisensi ke server Posita, maksimal sekali per 24 jam.
    // Hasil di-cache 30 hari.
    public function check(): LicenseStatus;
}
```

| Kondisi | Perilaku |
|---|---|
| Lisensi valid | Semua fitur paket aktif |
| Server lisensi tidak terjangkau | **Pakai hasil cache.** Toleransi 30 hari |
| Cache kedaluwarsa > 30 hari | Turun ke fitur Komunitas + banner peringatan |
| Lisensi kedaluwarsa | Turun ke fitur Komunitas + banner |
| Tanpa lisensi | Mode Komunitas |

> **ATURAN MUTLAK: aplikasi TIDAK PERNAH berhenti berfungsi karena lisensi.**
>
> - Kasir **selalu** bisa berjualan
> - Data **selalu** bisa diekspor penuh
> - Tidak ada penguncian, tidak ada penghapusan, tidak ada mode hanya-baca
>   yang menyandera
>
> Yang dinonaktifkan hanya fitur di atas tingkat Komunitas — dan datanya tetap
> ada, siap hidup lagi saat lisensi diperbarui.

Instalasi yang berhenti melayani pelanggan karena server lisensi Anda sedang
mati adalah kegagalan yang tidak bisa dimaafkan. Konsisten dengan aturan yang
sama di P21 §4.

### 4. Mode Komunitas

Gratis, tanpa lisensi. Mencakup fitur setara paket Gratis di
`docs/50-gtm-pricing-legal.md`, plus:
- Outlet tak terbatas (self-hosted, biaya infrastruktur ditanggung sendiri)
- Tanpa batas jumlah user
- Ekspor data penuh

Yang butuh lisensi berbayar: dukungan resmi, branding/white-label penuh,
custom domain terkelola, dan pembaruan prioritas.

### 5. Upgrade

```bash
./upgrade.sh              # backup otomatis → tarik image → migrasi → restart
./upgrade.sh --to v1.4.2  # versi tertentu
./upgrade.sh --check      # cek versi tersedia tanpa mengubah apa pun
```

Wajib:
- **Backup otomatis sebelum migrasi**, tanpa kecuali
- Migrasi kompatibel mundur satu versi minor (aturan zero-downtime dari
  `docs/30-arsitektur-target.md`)
- Gagal migrasi → rollback otomatis ke image sebelumnya + pulihkan backup
- `CHANGELOG.md` menandai rilis yang butuh langkah manual

### 6. Backup & restore

```bash
./backup.sh                          # database + storage → ./backups/
./backup.sh --install-cron           # pasang cron harian 02:00
./backup.sh --to s3://bucket/path    # ke object storage
./restore.sh backups/2026-09-06.tar.gz
./restore.sh --verify backups/...    # uji restore ke container sementara
```

`--verify` penting: memaksa pengguna membuktikan backup-nya bisa dipulihkan,
sesuai aturan di P20 ("backup yang tidak pernah diuji restore bukan backup").

### 7. Telemetri — opt-in

Default **mati**. Jika dinyalakan, hanya kirim: versi, jumlah tenant, jumlah
outlet, dan error yang dianonimkan. **Tidak pernah**: data transaksi, nama
pelanggan, produk, atau angka penjualan.

Tampilkan persis apa yang dikirim di halaman setelan, sebagai JSON mentah.

---

## ACCEPTANCE CRITERIA

- [ ] `install.sh` menghasilkan instalasi berjalan di VPS bersih < 30 menit
- [ ] Menjalankan `install.sh` dua kali tidak merusak instalasi yang ada
- [ ] Prasyarat yang kurang dilaporkan jelas dalam bahasa Indonesia, bukan
      stack trace
- [ ] Sertifikat SSL terbit otomatis lewat Caddy
- [ ] **Server lisensi dimatikan → aplikasi tetap melayani penjualan penuh**
- [ ] **Lisensi kedaluwarsa → turun ke Komunitas, nol data hilang, ekspor
      tetap jalan**
- [ ] `upgrade.sh` membuat backup sebelum migrasi
- [ ] Migrasi gagal → rollback otomatis, instalasi kembali berfungsi
- [ ] `restore.sh --verify` memulihkan ke container sementara dan
      memverifikasi jumlah baris
- [ ] Telemetri mati secara default; isinya bisa dilihat mentah
- [ ] Dokumentasi cukup untuk orang teknis yang belum pernah melihat codebase
- [ ] Uji instalasi dilakukan oleh orang **selain** penulis kodenya

---

## TESTING

```bash
# Uji di VPS bersih (Ubuntu 22.04 & Debian 12)
./install.sh                      # instalasi baru
./install.sh                      # idempoten
./upgrade.sh --to <versi-lama>    # downgrade → rollback
./backup.sh && ./restore.sh --verify backups/*.tar.gz

# Simulasi server lisensi mati
iptables -A OUTPUT -d <license-host> -j DROP
# → verifikasi kasir tetap bisa transaksi & ekspor data
```

```php
it('falls back to cached license status when the server is unreachable');
it('degrades to community tier after 30 days without contact');
it('never blocks sales due to license state');
it('never blocks data export due to license state');
```

---

## CATATAN

⚠️ **Lisensi tidak boleh pernah mematikan aplikasi.** Ini aturan yang sama
dengan P21: produk yang menjual anti-lock-in lalu mengunci instalasi
pelanggannya adalah produk yang berbohong. Degradasi anggun, selalu.

⚠️ **Uji instalasi oleh orang lain.** Penulis kodenya punya asumsi tak sadar
tentang lingkungan. Serahkan `README.md` dan VPS kosong ke orang teknis yang
belum pernah melihat project ini — apa yang macet di situ adalah bug
dokumentasi.

⚠️ **Beban dukungan itu nyata.** Setiap instalasi self-hosted adalah
lingkungan unik yang bisa rusak dengan cara unik. Nyatakan batas dukungan
secara eksplisit di `README.md`: versi OS yang didukung, apa yang termasuk,
dan apa yang tidak.

⚠️ **Nilai WP ini sebagian besar adalah pemasaran.** Sedikit yang akan
memakainya, tapi keberadaannya membuktikan janji "datamu milikmu" itu
sungguhan. Jangan berlebihan membangunnya — cukup, benar, dan
terdokumentasi.
