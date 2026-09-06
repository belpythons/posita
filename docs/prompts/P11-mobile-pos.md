# P11 — Aplikasi Kasir (React Native + Expo)

**Fase:** F2 · **WP:** 2.2–2.6 · **Effort:** ± 5 pekan · **Prasyarat:** P10

---

## PERAN
Mobile engineer React Native yang paham bahwa aplikasi ini dipakai berdiri,
terburu-buru, dengan tangan basah, di tablet murah, sambil ada 5 orang
antre.

## KONTEKS
API sudah siap (P10). Sekarang POS berpindah ke perangkat yang benar.
Fase ini masih **online-only** — offline ditangani di P12.

## TUJUAN
Aplikasi kasir Android yang cepat, bisa dipakai tanpa training, dan bisa
mencetak struk.

## RUANG LINGKUP
1. Scaffold Expo + TypeScript + navigasi + tema
2. Autentikasi (email/password + PIN switch user)
3. Pemilihan outlet
4. Layar kasir: grid produk, pencarian, kategori, favorit
5. Keranjang: varian, modifier, qty, catatan, diskon item
6. Pembayaran: tunai (dengan quick-cash), QRIS statis, split payment
7. Cetak struk thermal Bluetooth + template block-based
8. Buka/tutup shift + input denominasi
9. Riwayat transaksi + void/refund
10. EAS Build + Play Store internal track + Sentry

## DI LUAR LINGKUP
- ❌ Offline / SQLite lokal (itu P12) — sekarang selalu online
- ❌ Payment gateway dinamis (itu P18)
- ❌ Absensi (itu P15)
- ❌ iOS (Android dulu — 95%+ pasar POS Indonesia)

---

## SPESIFIKASI

### Stack

```json
{
  "expo": "~52",
  "react-native": "0.76+",
  "typescript": "^5",
  "expo-router": "^4",              // file-based routing
  "zustand": "^5",                  // state global
  "@tanstack/react-query": "^5",    // server state & cache
  "nativewind": "^4",               // Tailwind untuk RN
  "react-hook-form": "^7",
  "zod": "^3",                      // validasi, dishare dengan backend schema
  "expo-camera": "*",
  "expo-print": "*",
  "@sentry/react-native": "*"
}
```

Printer: pilih salah satu library ESC/POS Bluetooth yang aktif dipelihara
(lihat `docs/riset/05-tech-stack-mobile.md`). Bungkus di balik interface
sendiri agar bisa diganti.

**Wajib pakai Expo Dev Client**, bukan Expo Go — native module printer tidak
tersedia di Go.

### Struktur

```
apps/pos-mobile/
├── app/                          expo-router
│   ├── (auth)/login.tsx
│   ├── (auth)/pin.tsx
│   ├── (app)/index.tsx           layar kasir
│   ├── (app)/cart.tsx
│   ├── (app)/payment.tsx
│   ├── (app)/history.tsx
│   ├── (app)/shift/open.tsx
│   ├── (app)/shift/close.tsx
│   └── _layout.tsx
├── src/
│   ├── api/                      klien API (dari packages/api-client)
│   ├── stores/                   zustand: auth, cart, shift, settings
│   ├── components/               design system
│   ├── features/
│   │   ├── catalog/
│   │   ├── cart/
│   │   ├── payment/
│   │   ├── printing/
│   │   └── shift/
│   ├── lib/                      utils, format rupiah, tanggal
│   └── theme/                    token dari packages/design-tokens
├── app.json / eas.json
└── package.json
```

### Desain UI — prinsip

1. **Target sentuh minimal 48×48 dp.** Tangan basah, terburu-buru.
2. **Kontras tinggi.** Kedai bisa terang benderang atau remang.
3. **Tidak ada gestur tersembunyi.** Semua aksi punya tombol terlihat.
4. **Konfirmasi hanya untuk aksi destruktif.** Menambah item tidak perlu
   konfirmasi; void perlu.
5. **Umpan balik langsung.** Setiap ketukan menghasilkan respons visual
   < 100 ms, bahkan jika prosesnya lebih lama.
6. **Angka besar.** Total transaksi harus terbaca dari 1 meter.

### Layar kasir — layout

```
┌──────────────────────────────────────────────────────────────┐
│ Kopi Senja · Sore · Rina        🟢 Tersinkron    [≡]         │
├────────────────────────────────────┬─────────────────────────┤
│ [🔍 Cari produk...]                │  KERANJANG          (3) │
│                                    │                         │
│ [Semua][Kopi][Non-Kopi][Makanan]   │  Es Kopi Susu       ×2  │
│                                    │  Large, Less Sugar      │
│ ┌────────┐┌────────┐┌────────┐     │           Rp 54.000  🗑  │
│ │Es Kopi ││Ameri-  ││Cappu-  │     │                         │
│ │Susu    ││cano    ││ccino   │     │  Croissant          ×1  │
│ │22.000  ││18.000  ││25.000  │     │           Rp 25.000  🗑  │
│ └────────┘└────────┘└────────┘     │  ─────────────────────  │
│ ┌────────┐┌────────┐┌────────┐     │  Subtotal   Rp  79.000  │
│ │Latte   ││Croiss- ││Kopi    │     │  Diskon     Rp       0  │
│ │24.000  ││ant     ││Tubruk  │     │  PB1 10%    Rp   7.900  │
│ └────────┘│25.000  ││12.000  │     │  ─────────────────────  │
│           └────────┘└────────┘     │  TOTAL      Rp  86.900  │
│                                    │                         │
│                                    │  [ + Diskon ]           │
│                                    │  [    BAYAR    ]        │
└────────────────────────────────────┴─────────────────────────┘
```

### Layar pembayaran — tunai

```
TOTAL          Rp 86.900

Uang diterima
┌───────────────────────────┐
│      Rp 100.000           │
└───────────────────────────┘

[ Pas ] [50rb] [100rb] [150rb] [200rb]    ← quick cash
[ 1 ][ 2 ][ 3 ]
[ 4 ][ 5 ][ 6 ]
[ 7 ][ 8 ][ 9 ]
[ 000 ][ 0 ][ ⌫ ]

KEMBALIAN      Rp 13.100

[ + Metode Lain ]   [ SELESAIKAN ]
```

Tombol quick-cash harus **cerdas**: tampilkan pecahan yang masuk akal untuk
total tersebut (untuk Rp86.900 → Pas, 90rb, 100rb, 150rb, 200rb).

### Printing

```typescript
interface PrinterService {
  scan(): Promise<PrinterDevice[]>;
  connect(device: PrinterDevice): Promise<void>;
  disconnect(): Promise<void>;
  isConnected(): boolean;
  print(commands: EscPosCommand[]): Promise<void>;
  openCashDrawer(): Promise<void>;
  testPrint(): Promise<void>;
}

// Template block-based → ESC/POS
// Lihat docs/riset/05-tech-stack-mobile.md §7 untuk format template
const commands = renderReceipt(receiptTemplate, {
  order, outlet, cashier, payments
});
await printer.print(commands);
```

**Wajib:**
- Dukung lebar 58 mm (32 karakter) dan 80 mm (48 karakter).
- Pairing printer tersimpan; reconnect otomatis saat aplikasi dibuka.
- Tombol "Cetak Ulang" di riwayat transaksi.
- Jika printer tidak terhubung, transaksi **tetap selesai** — tampilkan
  opsi cetak ulang nanti. Jangan blokir penjualan karena printer.

### Performa

| Interaksi | Target |
|---|---|
| Tambah item ke keranjang | < 50 ms |
| Buka layar pembayaran | < 100 ms |
| Selesaikan transaksi (online) | < 800 ms p95 |
| Cetak struk | < 2 detik |
| Cold start aplikasi | < 3 detik |

Optimasi wajib:
- `FlashList` untuk grid produk (bukan `FlatList`).
- Gambar produk di-cache dan di-resize di server.
- Katalog di-fetch sekali dan di-cache dengan React Query.
- Hindari re-render seluruh grid saat keranjang berubah (memoization).

---

## ACCEPTANCE CRITERIA

- [ ] Transaksi lengkap (pilih produk → varian → modifier → bayar → cetak)
      selesai dalam < 30 detik oleh pengguna baru tanpa training
- [ ] Berjalan mulus di Android 8, RAM 2 GB, tablet 10 inci
- [ ] Struk tercetak rapi di printer thermal 58 mm dan 80 mm
- [ ] Kembalian terhitung benar, termasuk untuk split payment
- [ ] Buka & tutup shift dengan input denominasi berfungsi
- [ ] Void transaksi dengan alasan berfungsi dan tercatat
- [ ] Aplikasi terinstal dari Play Store internal track
- [ ] Sentry menerima crash report
- [ ] p95 waktu selesaikan transaksi < 800 ms
- [ ] Transaksi tetap bisa diselesaikan saat printer terputus
- [ ] Semua teks dalam bahasa Indonesia

---

## TESTING

```typescript
// Unit
describe('cartStore', () => {
  it('adds an item with variant and modifiers');
  it('merges identical line items');
  it('keeps separate lines for different modifiers');
  it('calculates the cart total matching the server');
});

describe('receiptRenderer', () => {
  it('renders a 32-column receipt');
  it('renders a 48-column receipt');
  it('truncates long product names correctly');
  it('aligns prices to the right edge');
});

describe('quickCash', () => {
  it('suggests sensible denominations for a given total');
});

// E2E (Maestro)
- flow: login → open shift → sell 3 items → pay cash → print → close shift
- flow: sell → void with reason → verify history
- flow: printer disconnected → complete sale → reprint later
```

---

## PERINTAH
```bash
cd apps/pos-mobile
npx expo start --dev-client
npm run typecheck
npm run test
eas build --platform android --profile preview
maestro test .maestro/
```

---

## CATATAN

⚠️ **Uji di perangkat nyata sejak hari pertama, bukan hanya emulator.**
Emulator menyembunyikan masalah performa yang akan membunuh produk di
tablet Rp1,5 juta.

⚠️ **Bluetooth di Android itu rewel.** Izin berubah antar versi Android
(BLUETOOTH_SCAN & BLUETOOTH_CONNECT di Android 12+). Uji di Android 8, 10,
12, dan 14.

⚠️ **Jangan blokir penjualan karena apapun.** Printer mati, jaringan lambat,
gambar gagal load — transaksi harus tetap bisa diselesaikan. Ini prinsip
yang tidak bisa ditawar.

⚠️ **Siapkan struktur untuk offline sejak sekarang.** Walau P12 belum
dikerjakan, tulis akses data lewat lapisan repository agar nanti tinggal
menukar implementasinya dari "API" ke "SQLite + sync".

⚠️ **Nomor order dari server saja untuk sekarang.** Logika penomoran offline
ditangani di P12.
