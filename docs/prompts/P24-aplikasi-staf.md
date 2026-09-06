# P24 — Aplikasi Staf (React Native + Expo)

**Fase:** F5 · **WP:** 5.4 · **Effort:** ± 1 pekan · **Prasyarat:** P11, P12, P15

---

## PERAN
Mobile engineer yang membangun aplikasi untuk karyawan — bukan untuk pemilik.
Penggunanya barista dan pelayan dengan HP pribadi kelas bawah.

## KONTEKS
P15 membangun seluruh backend HR: absensi selfie + GPS, jadwal, izin,
payroll-lite. Tapi **tidak ada aplikasi yang memakainya**. Absensi selfie
mustahil dilakukan dari back office web — butuh kamera dan GPS di HP
karyawan.

## TUJUAN
Aplikasi ringan untuk karyawan: absen, lihat jadwal, ajukan izin, lihat slip
gaji, dan lihat data pribadinya sendiri.

## RUANG LINGKUP
1. Aplikasi Expo terpisah dari aplikasi kasir
2. Login (email/password, lalu PIN untuk buka cepat)
3. Layar consent UU PDP saat pertama login
4. Absen masuk/pulang: selfie + GPS, **≤ 2 ketukan**
5. Lihat jadwal kerja
6. Ajukan izin/cuti + lihat statusnya
7. Riwayat absensi & slip gaji sendiri
8. Halaman "Data Saya" — lihat & minta hapus (UU PDP)
9. Absensi offline lewat outbox (memakai ulang mesin sync P12)

## DI LUAR LINGKUP
- ❌ Fungsi kasir apa pun — karyawan biasa **tidak boleh** bisa menjual dari
  aplikasi ini
- ❌ Chat / pesan internal
- ❌ Approval izin oleh manajer (dilakukan di back office web)
- ❌ Pelacakan lokasi di luar momen absen
- ❌ iOS (Android dulu)

---

## SPESIFIKASI

### 1. Kenapa aplikasi terpisah, bukan modul di aplikasi kasir

Aplikasi kasir dipasang di tablet milik toko. Aplikasi staf dipasang di HP
pribadi karyawan. Menggabungkannya berarti:
- Karyawan memasang aplikasi yang memuat seluruh katalog, harga, dan HPP di
  HP pribadinya
- Permukaan serangan bertambah: satu HP hilang = data toko bocor
- Ukuran aplikasi membengkak untuk pengguna yang hanya butuh absen

Aplikasi terpisah, berbagi `packages/api-client` dan `packages/design-tokens`.

### 2. Alur absen — target 3 detik

```
Buka aplikasi
  ↓  (sudah login, PIN opsional)
┌─────────────────────────────┐
│  Selasa, 8 Sep 2026         │
│  Shift Sore · 14:00–21:00   │
│                             │
│     ┌───────────────┐       │
│     │               │       │
│     │     MASUK     │       │   ← satu tombol besar
│     │               │       │
│     └───────────────┘       │
│                             │
│  📍 Kopi Senja · dalam area │
└─────────────────────────────┘
  ↓ ketuk
Kamera depan terbuka → jepret otomatis 1 detik → selesai
  ↓
┌─────────────────────────────┐
│  ✅ Absen masuk 14:03       │
│     Terlambat 3 menit       │
└─────────────────────────────┘
```

**2 ketukan, ≤ 3 detik.** Tidak ada preview foto panjang, tidak ada
konfirmasi, tidak ada form.

### 3. GPS & geofence

- Lokasi diambil **hanya** saat absen masuk/pulang. Tidak ada pelacakan latar.
- Mode default `warn` (dari P15): di luar radius tetap tercatat, ditandai
  untuk ditinjau manajer.
- Tampilkan status area di layar **sebelum** absen ("dalam area" / "di luar
  area") agar karyawan tahu sebelum menekan, bukan setelah.
- Deteksi mock location → tandai, jangan blokir.
- Izin lokasi ditolak → absen tetap bisa, ditandai `location_unavailable`.
  Memblokir absen karena izin GPS akan membuat karyawan tidak bisa bekerja.

### 4. Offline

Memakai ulang outbox dan mesin sync dari P12:

```
Offline saat absen
  → tersimpan lokal + foto di penyimpanan aplikasi
  → badge "1 absensi menunggu terkirim"
  → terkirim otomatis saat online
  → foto diunggah terpisah setelah recordnya diterima
```

Jam perangkat bisa salah — kirim juga `device_time` dan `uptime_ms`; server
memakai waktu terimanya sebagai pembanding dan menandai selisih > 15 menit.

### 5. Consent UU PDP

Layar wajib saat pertama login, **sebelum** absen pertama:

```
Aplikasi ini akan mengambil:
  📷 Foto selfie saat kamu absen
  📍 Lokasi kamu saat absen masuk & pulang

Untuk apa:
  Memastikan absensi tercatat benar dan gajimu dihitung tepat.

Yang TIDAK kami ambil:
  ✗ Lokasi di luar jam absen
  ✗ Isi HP, kontak, atau aplikasi lain

Foto absensi dihapus otomatis setelah 90 hari.
Kamu bisa melihat & meminta hapus datamu kapan saja di menu "Data Saya".

[ Saya Mengerti & Setuju ]        [ Tidak Setuju ]
```

"Tidak Setuju" harus punya konsekuensi yang jujur: absensi tidak bisa dipakai,
hubungi manajer untuk pencatatan manual. Jangan paksa.

### 6. Halaman "Data Saya"

- Seluruh riwayat absensi miliknya
- Foto yang tersimpan + tanggal hapus otomatisnya
- Slip gaji
- Tombol "Minta Hapus Data Saya" → membuat permintaan ke owner, diproses
  ≤ 30 hari (alur backend-nya sudah ada di P15)

### 7. Keamanan

- Token Sanctum dengan scope terbatas — **tidak boleh** ada izin penjualan
- Timeout sesi 30 hari, PIN untuk buka cepat
- Foto absensi tidak disimpan di galeri publik HP
- Tidak ada data toko (produk, harga, HPP, omzet) yang di-cache di aplikasi ini

---

## ACCEPTANCE CRITERIA

- [ ] Absen masuk selesai ≤ 2 ketukan dan ≤ 3 detik (diukur di perangkat nyata)
- [ ] Status "dalam area / di luar area" tampil **sebelum** menekan tombol
- [ ] Absen di luar geofence tetap tercatat dengan tanda, tidak diblokir
- [ ] Izin lokasi ditolak → absen tetap bisa, ditandai
- [ ] Mock location terdeteksi dan ditandai
- [ ] Absen offline tersimpan dan terkirim otomatis saat online
- [ ] Foto terunggah setelah recordnya diterima server
- [ ] Layar consent muncul sekali, tersimpan dengan timestamp
- [ ] Menolak consent memberi jalan keluar yang jelas, bukan buntu
- [ ] Karyawan hanya melihat datanya sendiri
- [ ] Token aplikasi staf **ditolak** oleh endpoint penjualan
- [ ] Tidak ada data katalog/harga/HPP yang di-cache
- [ ] Berjalan mulus di Android 8, RAM 2 GB
- [ ] Ukuran APK < 30 MB
- [ ] Semua teks bahasa Indonesia

---

## TESTING

```typescript
describe('attendanceStore', () => {
  it('queues attendance to the outbox when offline');
  it('uploads the photo after the record is accepted');
  it('sends device_time alongside the server request');
  it('surfaces geofence status before the user taps');
});

describe('consent', () => {
  it('blocks attendance until consent is given');
  it('offers a manual-recording path when consent is declined');
});
```

```php
// Backend
it('rejects a staff token on sales endpoints');
it('lets an employee read only their own attendance');
it('flags a device_time skew greater than 15 minutes');
```

E2E (Maestro): login → consent → clock in → lihat jadwal → ajukan izin →
clock out → lihat riwayat.

---

## PERINTAH
```bash
cd apps/staff-mobile
npx expo start --dev-client
npm run typecheck && npm run test
eas build --platform android --profile preview
maestro test .maestro/
```

---

## CATATAN

⚠️ **Ini aplikasi untuk karyawan, bukan alat pengawasan.** Setiap fitur yang
terasa seperti mengawasi akan membuat karyawan mencari cara menghindarinya —
dan data absensi jadi tidak berguna. Jangan tambahkan pelacakan latar,
screenshot, atau pemantauan aktivitas, walau secara teknis mudah.

⚠️ **HP karyawan itu kelas bawah dan penuh.** APK harus kecil, hemat memori,
dan tidak menyimpan banyak. Uji di perangkat dengan storage hampir penuh.

⚠️ **Jangan pernah memblokir absensi.** GPS meleset, izin ditolak, internet
mati, kamera rusak — semua itu terjadi. Absensi harus tetap tercatat dengan
penanda, dan manajer yang memutuskan. Karyawan yang tidak bisa absen adalah
konflik ketenagakerjaan yang disebabkan aplikasi Anda.

⚠️ **Pisahkan token dari aplikasi kasir.** Uji secara eksplisit bahwa token
staf ditolak di endpoint penjualan — ini kontrol keamanan, bukan detail.
