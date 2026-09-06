# P18 — Payment Gateway & QRIS

**Fase:** F5 · **WP:** 5.1 · **Effort:** ± 2 pekan · **Prasyarat:** P08, P12

---

## PERAN
Backend engineer yang menangani uang sungguhan. Bug di sini berarti
pelanggan membayar tapi tidak tercatat, atau sebaliknya.

## KONTEKS
Permintaan pengguna: *"opsi pemasangan payment gateway gratisan untuk proses
penjualan e-wallet dan mobile banking"*.

Riset lengkap ada di `docs/riset/03-payment-gateway-qris.md` — **baca dulu.**
Ringkasnya: strategi bertingkat dari QRIS statis (biaya nol) sampai gateway
tanpa setup fee & tanpa biaya bulanan (Midtrans).

## TUJUAN
Sistem pembayaran yang bisa dipakai gratis sejak hari pertama, dan bisa
di-upgrade ke rekonsiliasi otomatis tanpa mengubah kode bisnis.

## RUANG LINGKUP
1. Interface `PaymentProvider` + registry driver
2. Driver `cash`, `manual_qris`, `manual_transfer` (tanpa dependensi luar)
3. Driver `midtrans` (Core API: QRIS, GoPay, ShopeePay, VA semua bank)
4. Tabel `payment_providers`, `payment_intents`, `payment_events`,
   `payment_settlements`
5. Endpoint webhook + verifikasi signature + anti-replay
6. Job rekonsiliasi harian + alert selisih
7. Laporan "Pendapatan Bersih Setelah MDR"
8. UI setting pembayaran per outlet + peringatan kepatuhan
9. Alur fallback offline

## DI LUAR LINGKUP
- ❌ Driver Duitku/Tripay (fase berikutnya — pastikan arsitektur mendukung)
- ❌ Payout otomatis ke rekening merchant
- ❌ Menjadi penyelenggara jasa pembayaran (Posita hanya integrator)

---

## SPESIFIKASI

### Interface

```php
namespace App\Support\Payment\Contracts;

interface PaymentProvider
{
    public function key(): string;
    public function label(): string;
    public function supportedMethods(): array;
    public function requiresCredentials(): bool;
    public function supportsRefund(): bool;

    public function createCharge(PaymentIntent $intent): ChargeResult;
    public function fetchStatus(string $providerRef): PaymentStatus;
    public function parseWebhook(Request $request): WebhookEvent;
    public function verifySignature(Request $request): bool;
    public function refund(string $providerRef, Money $amount): RefundResult;
    public function fetchSettlement(CarbonInterface $date): SettlementReport;
}
```

### Driver

| Driver | Kredensial | Metode | Rekonsiliasi |
|---|---|---|---|
| `cash` | — | Tunai | Manual (shift) |
| `manual_qris` | Upload gambar QRIS | QRIS statis | Manual (konfirmasi kasir) |
| `manual_transfer` | Data rekening | Transfer bank | Manual (upload bukti) |
| `midtrans` | Server key, client key | QRIS, GoPay, ShopeePay, VA, kartu | Otomatis (webhook) |

Konfigurasi **per outlet**, bukan global.

### Aturan implementasi — TIDAK BOLEH DILANGGAR

1. **Idempotency.** Setiap `PaymentIntent` punya `idempotency_key` dari
   klien. Retry tidak boleh membuat charge ganda.
2. **Webhook adalah sumber kebenaran**, bukan respons klien. Order menjadi
   `paid` hanya setelah webhook terverifikasi ATAU `fetchStatus`
   mengonfirmasi.
3. **Verifikasi signature wajib.**
   Midtrans: `sha512(order_id + status_code + gross_amount + server_key)`.
   Signature tidak cocok → tolak + catat sebagai insiden keamanan.
4. **Webhook idempoten.** Provider mengirim ulang. Simpan
   `provider_event_id` dengan unique index; event duplikat → 200 OK tanpa
   efek.
5. **Jangan percaya nominal dari klien.** Hitung ulang total di server dari
   `order_items` sebelum membuat charge.
6. **Rekonsiliasi harian.** Cocokkan transaksi lokal vs settlement report
   provider. Selisih → alert ke owner.
7. **Expiry.** QRIS dinamis kedaluwarsa (default 15 menit). Order kembali ke
   `pending`, bisa dibayar ulang atau ganti metode.
8. **Fallback offline.** Saat internet mati, QRIS dinamis tidak bisa dibuat.
   Kasir otomatis ditawari `manual_qris` atau `cash`. **Wajib dites.**
9. **Kredensial terenkripsi at-rest**, tidak pernah dikembalikan ke klien.

### KEPATUHAN — MDR

> Bank Indonesia **melarang** MDR QRIS dibebankan ke pembeli dalam bentuk
> apapun.

Konsekuensi implementasi:

1. ❌ **JANGAN** buat fitur default "Biaya QRIS Rp X" yang ditambahkan ke
   total belanja pelanggan.
2. ✅ Field `payments.fee_amount` **hanya untuk laporan internal** owner.
3. ✅ Tampilkan peringatan kepatuhan di halaman setting pembayaran.
4. ✅ Laporan "Pendapatan Bersih Setelah MDR" — legal dan sangat berguna.

### Race condition yang wajib ditangani

```
Skenario: webhook tiba SEBELUM respons createCharge tersimpan

  t0  Klien → POST /payments
  t1  Server → Midtrans charge()
  t2  Midtrans → webhook (pelanggan bayar sangat cepat)
  t3  Server (worker webhook) → cari payment_intent → BELUM ADA
  t4  Server (request asli) → simpan payment_intent

Solusi:
  - Simpan payment_intent dengan status 'pending' SEBELUM memanggil provider
  - Webhook yang tidak menemukan intent → simpan ke payment_events dengan
    status 'orphaned', proses ulang lewat job retry (backoff 5s, 30s, 2m)
  - Job rekonsiliasi menangkap orphan yang tersisa
```

### Alur fallback offline

```
Kasir pilih QRIS → API tidak reachable (timeout 3 detik)
  ├─► Deteksi offline
  ├─► Dialog: "Internet mati. Pakai QRIS tempel atau tunai?"
  ├─► Pilih QRIS tempel → tampilkan gambar QRIS statis tersimpan lokal
  ├─► Kasir konfirmasi manual + input nominal + (opsional) foto bukti
  ├─► Order tersimpan lokal, status paid, verification='manual'
  └─► Saat online: masuk antrean rekonsiliasi, muncul di laporan tutup shift
      sebagai "N transaksi perlu diverifikasi"
```

### Laporan pendapatan bersih

```
PENDAPATAN BERSIH — September 2026

Metode          Transaksi      Kotor        MDR      Bersih
Tunai                 412  12.400.000          0  12.400.000
QRIS                  689  24.150.000    169.050  23.980.950
VA BCA                 34   3.200.000    136.000   3.064.000
GoPay                  89   2.400.000     16.800   2.383.200
─────────────────────────────────────────────────────────────
TOTAL               1.224  42.150.000    321.850  41.828.150

Biaya pembayaran 0,76% dari omzet.
Setara Rp321.850/bulan.
```

---

## ACCEPTANCE CRITERIA

- [ ] Driver `manual_qris` bisa dipakai tanpa kredensial apapun (gratis)
- [ ] Upload gambar QRIS statis per outlet, tampil di layar kasir
- [ ] Konfirmasi manual mewajibkan nominal + referensi
- [ ] Driver `midtrans` menghasilkan QRIS dinamis di sandbox
- [ ] Webhook dengan signature valid mengubah order jadi `paid`
- [ ] Webhook dengan signature tidak valid ditolak + tercatat
- [ ] Webhook duplikat diproses sekali (200 OK, tanpa efek ganda)
- [ ] Webhook yang tiba sebelum intent tersimpan tetap terproses (orphan
      handling)
- [ ] QRIS kedaluwarsa mengembalikan order ke `pending`
- [ ] Refund parsial berfungsi (jika didukung provider)
- [ ] Offline → fallback ke QRIS statis/tunai berfungsi (uji dengan
      mematikan jaringan)
- [ ] Job rekonsiliasi harian mencocokkan transaksi & melaporkan selisih
- [ ] Laporan pendapatan bersih setelah MDR akurat
- [ ] Kredensial terenkripsi dan tidak pernah muncul di response API
- [ ] Peringatan kepatuhan MDR tampil di halaman setting
- [ ] Ganti driver tanpa mengubah kode bisnis

---

## TESTING WAJIB

```php
// tests/Feature/Payment/WebhookTest.php
it('accepts a webhook with a valid signature');
it('rejects a webhook with an invalid signature');
it('logs invalid signatures as a security incident');
it('processes a duplicate webhook only once');
it('handles a webhook arriving before the intent is stored');
it('retries orphaned webhooks');

// tests/Feature/Payment/ChargeTest.php
it('creates a payment intent before calling the provider');
it('is idempotent for the same key');
it('recalculates the amount server-side');
it('expires an unpaid QRIS after the timeout');
it('reverts the order to pending on expiry');

// tests/Feature/Payment/ManualQrisTest.php
it('requires an amount and reference on manual confirmation');
it('flags the payment as manually verified');
it('lists manual payments in the shift report for review');

// tests/Feature/Payment/ReconciliationTest.php
it('matches local transactions against the provider settlement');
it('reports unmatched transactions');
it('computes net revenue after MDR');

// tests/Feature/Payment/DriverSwapTest.php
it('swaps providers without changing business code');
```

**Wajib:** semua test memakai provider `fake`. Jangan pernah memanggil API
Midtrans sungguhan di CI.

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Payment
php artisan posita:payment:reconcile --date=2026-09-05 --dry-run
```

---

## CATATAN

⚠️ **Uang sungguhan.** Test coverage di modul ini harus mendekati 100%.

⚠️ **Webhook harus merespons cepat** (< 3 detik) atau provider menganggap
gagal dan mengirim ulang. Simpan payload, kembalikan 200, proses di queue.

⚠️ **Verifikasi status lisensi provider.** Payment gateway harus terdaftar
dan diawasi Bank Indonesia. Cek sebelum mengintegrasikan.

⚠️ **Posisikan Posita sebagai integrator, bukan penyelenggara pembayaran.**
Pastikan dokumen legal & materi pemasaran tidak menimbulkan kesan
sebaliknya — ini punya konsekuensi regulasi.

⚠️ **Tarif MDR berubah.** Simpan sebagai konfigurasi, bukan hardcode.
Kebijakan BI per 1 Oktober 2026 memperluas MDR 0% — verifikasi ke sumber
primer sebelum menampilkan kalkulator biaya ke pengguna.

⚠️ **Uji fallback offline dengan mematikan jaringan sungguhan.** Ini alur
yang paling sering rusak dan paling jarang dites.
