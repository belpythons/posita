# P20 — Ekspor Data, Kesiapan Operasional & Rilis

**Fase:** F5 · **WP:** 5.3+ · **Effort:** ± 1,5 pekan · **Prasyarat:** semua

---

## PERAN
Release engineer yang bertanggung jawab agar produk ini bisa dipercaya
menjalankan bisnis orang lain.

## KONTEKS
Semua fitur sudah ada. Yang belum: janji anti-lock-in yang bisa dibuktikan,
dan kesiapan operasional untuk melayani pelanggan berbayar.

## TUJUAN
Produk siap rilis komersial: data pengguna bisa dibawa pulang kapan saja,
sistem bisa dipulihkan saat bencana, dan tim tahu apa yang harus dilakukan
saat ada masalah.

## RUANG LINGKUP
1. Ekspor data penuh (arsip ZIP: CSV + JSON + media)
2. API terbuka + dokumentasi publik
3. Hak subjek data (UU PDP): akses, koreksi, hapus, portabilitas
4. Backup otomatis + **uji restore terbukti**
5. Observability: Sentry, Pulse, uptime, log terstruktur
6. Health check + maintenance mode berbahasa Indonesia
7. Runbook insiden
8. Uji beban & uji jaringan
9. Dokumen legal + halaman kebijakan
10. Paket self-hosted (opsional)

## DI LUAR LINGKUP
- ❌ Fitur bisnis baru
- ❌ Integrasi aggregator (WP5.5, terpisah)

---

## SPESIFIKASI

### 1. Ekspor data penuh — janji merek

```
Pengaturan → Data Saya → [ Unduh Semua Data ]

Menghasilkan ZIP:
  posita-export-{tenant-slug}-{tanggal}.zip
  ├── README.txt                (penjelasan isi, bahasa Indonesia)
  ├── csv/
  │   ├── products.csv
  │   ├── orders.csv
  │   ├── order_items.csv
  │   ├── ingredients.csv
  │   ├── stock_movements.csv
  │   ├── recipes.csv
  │   ├── partners.csv
  │   ├── consignment_batches.csv
  │   ├── employees.csv
  │   ├── attendances.csv
  │   ├── expenses.csv
  │   └── ... (semua tabel)
  ├── json/
  │   └── (sama, format JSON dengan relasi utuh)
  └── media/
      ├── products/
      ├── receipts/
      └── menu-designs/
```

**Aturan:**
- Tersedia di **semua paket, termasuk gratis**. Ini janji merek.
- Dijalankan sebagai job antrean; notifikasi saat siap.
- Link unduh bertanda-tangan, berlaku 7 hari.
- Rate limit: 1 ekspor penuh per 24 jam per tenant.
- `README.txt` menjelaskan setiap file dalam bahasa Indonesia.
- Data pribadi karyawan disertakan hanya jika pemohon punya izin
  `hr.employee.manage`.

### 2. Hak subjek data (UU PDP)

```
POST /api/v1/privacy/export-request     ekspor data pribadi saya
POST /api/v1/privacy/deletion-request   hapus data pribadi saya
GET  /api/v1/privacy/my-data            lihat data pribadi saya
```

Untuk karyawan:
- Melihat seluruh data absensi & payroll miliknya
- Meminta penghapusan → data pribadi (foto, koordinat) dihapus, data
  agregat (jumlah hari kerja) dipertahankan untuk kebutuhan pembukuan
- Permintaan diproses dalam ≤ 30 hari, dengan konfirmasi tertulis

### 3. Backup & Disaster Recovery

```yaml
Backup:
  frekuensi:  harian, 02:00 WIB
  isi:        database (pg_dump terkompresi) + storage (rsync inkremental)
  enkripsi:   AES-256, kunci disimpan terpisah dari backup
  lokasi:     object storage terpisah dari server aplikasi
  retensi:    harian 30 hari, mingguan 12 minggu, bulanan 12 bulan
  verifikasi: checksum setiap backup

Uji restore:
  frekuensi:  bulanan
  prosedur:   restore ke environment terpisah, jalankan smoke test,
              verifikasi jumlah baris & integritas
  dokumentasi: catat waktu restore aktual (untuk RTO)

Target:
  RPO (kehilangan data maksimal):  24 jam
  RTO (waktu pemulihan maksimal):  4 jam
```

**Backup yang tidak pernah diuji restore-nya bukan backup.** Jadwalkan
uji bulanan dan catat hasilnya.

### 4. Observability

| Sinyal | Alat | Yang dipantau |
|---|---|---|
| Error backend | Sentry | Exception, dikelompokkan per tenant |
| Error mobile | Sentry RN | Crash, ANR, error JS, versi perangkat |
| Performa | Laravel Pulse | Query lambat, job lambat, request lambat |
| Uptime | Uptime Kuma / Better Stack | Health check tiap menit |
| Log | JSON terstruktur → Loki | Bisa ditelusuri per `trace_id` |
| Metrik bisnis | Dashboard internal | Tenant aktif, transaksi/hari, kegagalan sync |

**Alert internal (ke tim):**
- Tingkat kegagalan sync > 1% dalam 15 menit
- Antrean job tertunda > 1.000
- Kegagalan webhook payment > 5 dalam 10 menit
- Kegagalan kirim WhatsApp > 20%
- Error rate API > 2%
- Ruang disk < 20%
- **Backup gagal**

### 5. Health check

```php
GET /up

{
  "status": "ok",              // ok | degraded | down
  "checks": {
    "database":  {"status":"ok","latency_ms":3},
    "redis":     {"status":"ok","latency_ms":1},
    "storage":   {"status":"ok"},
    "queue":     {"status":"ok","pending":12},
    "scheduler": {"status":"ok","last_run":"2026-09-06T10:20:00+07:00"}
  },
  "version": "1.0.0",
  "server_time": "2026-09-06T10:22:31+07:00"
}
```

Maintenance mode dengan halaman berbahasa Indonesia yang menjelaskan
perkiraan waktu selesai.

### 6. Runbook Insiden

Buat `docs/runbook/` berisi:

| Dokumen | Isi |
|---|---|
| `incident-response.md` | Klasifikasi severity, siapa dihubungi, alur eskalasi |
| `database-restore.md` | Langkah restore backup, verifikasi |
| `sync-failures.md` | Diagnosa & perbaikan kegagalan sinkronisasi massal |
| `payment-issues.md` | Webhook tidak masuk, selisih rekonsiliasi |
| `whatsapp-banned.md` | Nomor gateway di-ban, cara switch driver |
| `rollback.md` | Prosedur rollback deploy |
| `data-breach.md` | Prosedur notifikasi kebocoran data (3×24 jam per UU PDP) |

### 7. Uji sebelum rilis

```bash
# Uji beban
k6 run tests/load/pos-transactions.js
  Target: 100 transaksi/menit berkelanjutan selama 30 menit
          p95 < 800 ms, error rate < 0,1%

# Uji jaringan (manual, di perangkat nyata)
  - Mode pesawat: 50 transaksi → online → verifikasi
  - Jaringan 2G (throttle 50 kbps): 20 transaksi
  - Putus-sambung tiap 10 detik: 30 transaksi
  - Kill app di tengah sync → restart → verifikasi tanpa duplikat

# Uji perangkat
  - Android 8, RAM 2 GB
  - Android 10, RAM 3 GB
  - Android 13, tablet 10 inci
  - Printer thermal 58 mm dan 80 mm, minimal 3 merek berbeda

# Uji pemulihan
  - Restore backup ke environment bersih
  - Verifikasi jumlah baris semua tabel
  - Jalankan smoke test
  - Catat waktu total
```

### 8. Dokumen legal & halaman publik

- [ ] Syarat & Ketentuan Layanan (bahasa Indonesia)
- [ ] Kebijakan Privasi (sesuai UU PDP)
- [ ] Perjanjian Pemrosesan Data
- [ ] Kebijakan Pengembalian Dana
- [ ] SLA untuk paket Bisnis
- [ ] Halaman status publik
- [ ] Dokumentasi API publik
- [ ] Basis pengetahuan / panduan pengguna

### 9. Paket self-hosted (opsional)

```
docker-compose.yml         konfigurasi produksi
install.sh                 installer interaktif
docs/self-hosted/          panduan instalasi, upgrade, backup
LICENSE-SELFHOSTED         ketentuan lisensi
```

---

## ACCEPTANCE CRITERIA

- [ ] Ekspor penuh menghasilkan ZIP yang bisa dibuka dan dipahami tanpa
      bantuan
- [ ] Ekspor tersedia di paket gratis
- [ ] Data yang diekspor lengkap (verifikasi: jumlah baris CSV = jumlah baris
      DB untuk tenant tersebut)
- [ ] Karyawan bisa melihat & meminta hapus data pribadinya
- [ ] Backup harian berjalan otomatis dan terenkripsi
- [ ] **Restore dari backup berhasil diuji** dan waktunya tercatat
- [ ] Health check mengembalikan status semua komponen
- [ ] Sentry menerima error dari backend dan mobile
- [ ] Semua alert internal terkonfigurasi dan diuji (trigger manual)
- [ ] Runbook lengkap dan bisa diikuti orang yang tidak menulis kodenya
- [ ] Uji beban lulus: 100 tx/menit, p95 < 800 ms, error < 0,1%
- [ ] Semua uji jaringan lulus di perangkat nyata
- [ ] Struk tercetak benar di 3 merek printer berbeda
- [ ] Dokumen legal tersedia dan tertaut di aplikasi
- [ ] Rollback deploy teruji

---

## PERINTAH
```bash
# Ekspor
php artisan posita:export:full --tenant=1

# Backup & restore
php artisan posita:backup:run
php artisan posita:backup:verify
php artisan posita:backup:restore --file=... --target=staging

# Health
curl https://api.posita.id/up | jq

# Beban
k6 run tests/load/pos-transactions.js
```

---

## CATATAN

⚠️ **Backup yang tidak pernah diuji restore bukan backup.** Uji bulanan,
catat hasilnya, dan pastikan orang lain (bukan penulis kodenya) bisa
melakukannya dengan mengikuti runbook.

⚠️ **Ekspor data adalah janji merek, bukan fitur.** Jangan persulit, jangan
batasi ke paket berbayar, jangan hilangkan kolom. Ini yang membedakan
Posita dari kompetitor yang mengunci data.

⚠️ **Halaman kesalahan berbahasa Indonesia.** Pengguna yang melihat
"500 Internal Server Error" akan panik. Tulis: "Ada gangguan di sistem
kami. Transaksimu aman tersimpan. Kami sedang memperbaiki."

⚠️ **Uji di perangkat sungguhan, jaringan sungguhan.** Emulator dan jaringan
kantor menyembunyikan masalah yang akan muncul di kedai dengan Wi-Fi buruk
dan tablet Rp1,5 juta.

⚠️ **Rilis bertahap.** Jangan buka pendaftaran umum di hari pertama. Mulai
dari design partner, lalu undangan terbatas, baru terbuka.
