# P15 — HR (Absensi) & Finance (Pengeluaran)

**Fase:** F4 · **WP:** 4.3–4.4 · **Effort:** ± 3 pekan · **Prasyarat:** P09, P14

---

## PERAN
Engineer yang menangani **data pribadi karyawan** — area dengan konsekuensi
hukum (UU PDP) dan sosial nyata.

## KONTEKS
Permintaan pengguna: *"pencatatan absensi karyawan dan pendapatan serta
pengeluaran toko yang terdata lengkap dan transparansi"*. Dua modul ini
melengkapi gambaran keuangan: tanpa pengeluaran, laporan laba tidak berarti.

## TUJUAN
Absensi yang bisa dipercaya tanpa menjadi alat pengawasan berlebihan, dan
pencatatan keuangan yang cukup lengkap untuk menghasilkan laba rugi
sederhana.

## RUANG LINGKUP

### Bagian A — HR
1. `employees` (terpisah dari `users` — tidak semua karyawan punya akun)
2. `work_schedules` — jadwal shift
3. `attendances` — clock in/out dengan selfie + GPS
4. `leave_requests` — izin & cuti
5. `payroll_periods` + `payroll_items` — payroll-lite + slip PDF
6. Kepatuhan UU PDP: consent, retensi, hak akses & hapus

### Bagian B — Finance
7. `expense_categories` + `expenses`
8. `recurring_expenses` — sewa, gaji, langganan
9. `other_incomes`
10. Laporan: arus kas, laba rugi sederhana, realisasi vs anggaran,
    break-even

## DI LUAR LINGKUP
- ❌ Perhitungan PPh 21 & BPJS (kompleks, berisiko salah — fase berikutnya)
- ❌ Integrasi bank / auto-payment
- ❌ Akuntansi double-entry penuh
- ❌ Manajemen aset & depresiasi

---

## SPESIFIKASI — BAGIAN A: HR

### Skema
Ikuti `docs/20-blueprint-produk.md` bagian **M10 — HR**.

### Absensi

```
Alur clock-in (di aplikasi staf / mobile):
  1. Buka aplikasi → tombol besar [ MASUK ]
  2. Kamera depan terbuka → ambil selfie (otomatis, tanpa preview panjang)
  3. GPS diambil di latar
  4. Simpan → tampilkan konfirmasi + jam masuk
  → Total: 3 detik, 2 ketukan
```

**Validasi:**
```php
$withinGeofence = $this->distanceMeters($lat, $lng, $outlet) <= $outlet->geofence_radius_m;
$late = $clockInAt->gt($schedule->start_time->addMinutes($template->grace_period_minutes));

// Geofence bersifat PERINGATAN, bukan blokir (default)
// setting: hr_attendance.geofence_mode = 'warn' | 'block' | 'off'
```

**Alasan geofence tidak memblokir secara default:** GPS di tablet murah
sering meleset 50–200 m. Memblokir absensi membuat karyawan tidak bisa
bekerja dan menciptakan konflik. Tandai untuk ditinjau manajer — itu cukup.

**Deteksi kecurangan:**
- Mock location (Android `isFromMockProvider`)
- Akurasi GPS buruk (> 100 m) → tandai
- Device berbeda dari biasanya → tandai
- Foto identik dengan sebelumnya (hash) → tandai

Semua **ditandai untuk ditinjau**, bukan diblokir otomatis.

### Kepatuhan UU PDP — WAJIB

| Kewajiban | Implementasi |
|---|---|
| Consent | Layar persetujuan saat karyawan pertama login, menjelaskan data apa yang diambil dan untuk apa. Tersimpan dengan timestamp |
| Minimalisasi | GPS **hanya** saat clock in/out, tidak tracking terus-menerus. Jangan simpan riwayat lokasi |
| Retensi | Foto absensi terhapus otomatis setelah N hari (default 90, konfigurabel). Job terjadwal |
| Hak akses | Karyawan bisa melihat seluruh data absensinya |
| Hak hapus | Permintaan penghapusan diproses; data agregat (jumlah hari kerja) dipertahankan, data pribadi (foto, koordinat) dihapus |
| Keamanan | Foto terenkripsi at-rest, URL bertanda-tangan berumur pendek, akses hanya manajer |
| Transparansi | Halaman "Data Saya" di aplikasi staf |

### Payroll-lite

```
Perhitungan:
  Bulanan: gaji_pokok × (hari_hadir / hari_kerja_seharusnya)
  Harian:  tarif_harian × hari_hadir
  Jam:     tarif_per_jam × total_jam_kerja

  + lembur = jam_lembur × tarif_per_jam × pengali_lembur
  + tunjangan (dikonfigurasi per karyawan)
  + bonus (input manual)
  − potongan (input manual: kasbon, denda, dll)
  = total dibayarkan
```

Hasilkan slip gaji PDF. **Jangan** klaim perhitungan sesuai regulasi
ketenagakerjaan — posisikan sebagai alat bantu pencatatan, dengan disclaimer.

---

## SPESIFIKASI — BAGIAN B: Finance

### Kategori pengeluaran bawaan

```
Operasional
├── Sewa Tempat
├── Listrik & Air
├── Internet & Telepon
├── Gaji & Tunjangan
├── Perawatan Alat
├── Kebersihan
├── Marketing & Promosi
├── Transportasi
└── Lain-lain

COGS (otomatis dari goods receipt)
├── Bahan Baku
└── Kemasan

Capex
├── Peralatan
└── Renovasi
```

Pengguna bisa menambah/mengubah kategori.

### Pencatatan pengeluaran cepat

```
[ + Pengeluaran ]
  Kategori:   [ Listrik & Air ▾ ]
  Jumlah:     [ 850.000 ]
  Keterangan: [ Token listrik September ]
  Dibayar dari: [ Kas Laci ▾ ]
  📷 Foto nota (opsional)
  [ SIMPAN ]
```

Jika dibayar dari kas laci → otomatis membuat `cash_movement` bertipe
`cash_out` di shift aktif. Ini menjaga rekonsiliasi kas tetap benar.

### Biaya berulang

```php
// recurring_expenses: sewa, gaji, langganan
// Scheduler harian:
//   - cari yang next_due_date <= hari ini
//   - jika auto_create → buat expense draft
//   - jika tidak → kirim reminder (event, WA di P14)
//   - majukan next_due_date
```

### Laporan laba rugi sederhana

```
LABA RUGI — September 2026 · Kopi Senja

PENDAPATAN
  Penjualan kotor                    Rp 42.150.000
  Diskon                             Rp    850.000 −
  Refund                             Rp    120.000 −
  ────────────────────────────────────────────────
  Pendapatan bersih                  Rp 41.180.000

HARGA POKOK PENJUALAN
  Bahan baku terpakai                Rp 11.940.000
  Kemasan                            Rp  1.850.000
  Waste tercatat                     Rp    420.000
  ────────────────────────────────────────────────
  Total HPP                          Rp 14.210.000  (34,5%)

  LABA KOTOR                         Rp 26.970.000  (65,5%)

BEBAN OPERASIONAL
  Gaji & tunjangan                   Rp 11.200.000
  Sewa tempat                        Rp  5.000.000
  Listrik & air                      Rp  1.850.000
  Internet                           Rp    350.000
  Marketing                          Rp    800.000
  Perawatan                          Rp    450.000
  Lain-lain                          Rp    320.000
  ────────────────────────────────────────────────
  Total beban                        Rp 19.970.000  (48,5%)

  LABA BERSIH                        Rp  7.000.000  (17,0%)

⚠️ Kebocoran stok tidak tercatat     Rp    417.800
   (lihat Laporan Kebocoran)
```

Bahasa harus dimengerti pemilik warung, bukan bahasa akuntan.

### Break-even

```
Biaya tetap bulanan  = sewa + gaji + langganan
Margin kontribusi %  = (pendapatan − HPP) / pendapatan
Omzet BEP            = biaya tetap / margin kontribusi %
Omzet BEP harian     = omzet BEP / hari operasi
```

Tampilkan sebagai: *"Kamu perlu jual minimal Rp920.000/hari untuk balik
modal. Rata-rata bulan ini: Rp1.405.000/hari ✅"*

---

## ACCEPTANCE CRITERIA

### HR
- [ ] Clock-in dengan selfie + GPS selesai dalam < 5 detik, 2 ketukan
- [ ] Absen di luar geofence tercatat dengan flag, tidak diblokir (mode
      default)
- [ ] Mock location terdeteksi dan ditandai
- [ ] Layar consent muncul sekali dan tersimpan dengan timestamp
- [ ] Foto absensi terhapus otomatis setelah masa retensi
- [ ] Karyawan bisa melihat & meminta hapus datanya sendiri
- [ ] Keterlambatan terhitung sesuai jadwal + grace period
- [ ] Slip gaji PDF tergenerate dengan angka benar
- [ ] Karyawan tidak bisa melihat data absensi karyawan lain

### Finance
- [ ] Pencatatan pengeluaran ≤ 4 ketukan
- [ ] Pengeluaran dari kas laci membuat `cash_movement` yang benar
- [ ] Biaya berulang tergenerate otomatis sesuai jadwal
- [ ] Laba rugi menampilkan angka yang konsisten dengan penjualan & stok
- [ ] HPP di laporan cocok dengan Σ `order_items.total_cost`
- [ ] Break-even terhitung benar
- [ ] Foto nota tersimpan dan bisa dilihat

---

## TESTING WAJIB

```php
// HR
it('records attendance with photo and coordinates');
it('flags attendance outside the geofence without blocking');
it('blocks attendance outside the geofence when configured to block');
it('detects mock location');
it('computes late minutes against schedule and grace period');
it('deletes attendance photos after the retention period');
it('lets an employee view only their own attendance');
it('records consent with a timestamp');
it('anonymizes personal data on a deletion request while keeping aggregates');
it('computes payroll for monthly, daily and hourly employees');

// Finance
it('creates a cash movement when paid from the drawer');
it('generates recurring expenses on schedule');
it('computes profit and loss consistent with sales and stock data');
it('matches report COGS with the sum of order item costs');
it('computes the break-even point');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter="Hr|Finance|Attendance|Expense"
php artisan posita:attendance:purge-photos --dry-run
```

---

## CATATAN

⚠️ **Foto selfie dan lokasi adalah data pribadi.** Perlakukan dengan
serius: enkripsi, retensi, consent, hak hapus. Ini bukan formalitas — UU PDP
punya sanksi nyata.

⚠️ **Jangan bangun alat pengawasan.** Absensi seharusnya membantu karyawan
dibayar dengan benar, bukan mengawasi mereka. Jangan simpan riwayat lokasi
di luar momen clock in/out. Jangan tambahkan screenshot atau tracking
aktivitas.

⚠️ **Geofence yang memblokir akan menyebabkan konflik nyata.** Bayangkan
karyawan tidak bisa absen karena GPS meleset, lalu dianggap bolos. Default
harus `warn`.

⚠️ **Pengeluaran yang tidak dicatat membuat laporan laba rugi berbohong.**
Buat pencatatan secepat mungkin, dan ingatkan lewat rekap harian jika tidak
ada pengeluaran tercatat dalam seminggu (mustahil dalam bisnis nyata).

⚠️ **Payroll adalah area sensitif.** Sertakan disclaimer bahwa perhitungan
adalah alat bantu, dan pengguna bertanggung jawab atas kepatuhan
ketenagakerjaan.
