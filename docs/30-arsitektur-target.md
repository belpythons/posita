# 30 — Arsitektur Target

Melengkapi [riset/05 — Tech Stack & Re-engineering](riset/05-tech-stack-mobile.md)
yang membahas *pilihan* teknologi. Dokumen ini membahas *bagaimana kode
disusun*, kontrak API, keamanan, deployment, dan operasional.

---

## 1. Layering Backend

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/            ← tipis: validasi → Action → Resource
│   │   └── Web/               ← tipis: validasi → Action → Inertia
│   ├── Requests/              ← FormRequest, satu per operasi
│   ├── Resources/             ← JsonResource, kontrak output API
│   └── Middleware/
├── Actions/                   ← satu kelas = satu use case
│   ├── Sales/CreateOrder.php
│   ├── Sales/VoidOrder.php
│   ├── Inventory/RecordStockMovement.php
│   ├── Recipe/RecalculateRecipeCost.php
│   └── Partner/GenerateSettlement.php
├── Services/                  ← orkestrasi lintas-Action, query kompleks
├── Domain/
│   ├── ValueObjects/          ← Money, Quantity, Percentage, PhoneNumber
│   ├── Enums/                 ← OrderStatus, MovementType, PaymentMethod
│   └── Events/                ← OrderCompleted, StockLevelChanged, ShiftClosed
├── Models/                    ← Eloquent, tanpa logika bisnis berat
├── Support/
│   ├── Payment/               ← kontrak + driver
│   ├── Notification/          ← kontrak + driver
│   ├── Printing/              ← kontrak + renderer ESC/POS
│   └── Export/
├── Policies/                  ← otorisasi
└── Jobs/                      ← pekerjaan antrean
```

### Aturan layering

1. **Controller tidak boleh punya `if` bisnis.** Hanya: validasi
   (FormRequest), otorisasi (Policy), panggil Action, kembalikan Resource.
2. **Action = satu use case, satu method `handle()`.** Bisa dites tanpa
   HTTP. Melempar exception domain, bukan HTTP exception.
3. **Model tidak memanggil Action.** Model hanya relasi, scope, cast,
   accessor.
4. **Event domain untuk efek samping.** `OrderCompleted` memicu potong stok,
   evaluasi notifikasi, update statistik — lewat listener, bukan dipanggil
   berantai di Action.
5. **Query kompleks di Repository atau QueryObject**, bukan di controller.

### Contoh Action

```php
final class CreateOrder
{
    public function __construct(
        private readonly PricingCalculator $pricing,
        private readonly StockLedger $ledger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(CreateOrderData $data, User $actor): Order
    {
        return $this->db->transaction(function () use ($data, $actor) {
            // Idempotency: kembalikan hasil lama jika key sudah ada
            if ($existing = Order::where('idempotency_key', $data->idempotencyKey)->first()) {
                return $existing;
            }

            $priced = $this->pricing->calculate($data->items, $data->outlet, $data->discounts);

            $order = Order::create([
                'id'              => $data->id ?? (string) Ulid::generate(),
                'tenant_id'       => $actor->tenant_id,
                'outlet_id'       => $data->outlet->id,
                'shift_id'        => $data->shiftId,
                'idempotency_key' => $data->idempotencyKey,
                'subtotal'        => $priced->subtotal->minorUnits(),
                'tax_total'       => $priced->tax->minorUnits(),
                'total'           => $priced->total->minorUnits(),
                'cost_total'      => $priced->cost->minorUnits(),
                // ... snapshot lengkap
            ]);

            $order->items()->createMany($priced->itemRows());

            OrderCompleted::dispatch($order);   // → potong stok, notifikasi, statistik

            return $order->fresh(['items.modifiers', 'payments']);
        });
    }
}
```

---

## 2. Value Object `Money`

Salah satu perbaikan paling penting dari audit (K6).

```php
final readonly class Money implements JsonSerializable
{
    /** @param int $minorUnits nilai dalam rupiah utuh (IDR tidak punya sen) */
    private function __construct(
        public int $minorUnits,
        public string $currency = 'IDR',
    ) {}

    public static function rupiah(int|string $amount): self
    {
        return new self((int) round((float) $amount));
    }

    public function plus(self $other): self  { $this->assertSame($other); return new self($this->minorUnits + $other->minorUnits); }
    public function minus(self $other): self { $this->assertSame($other); return new self($this->minorUnits - $other->minorUnits); }

    /** Perkalian dengan pembulatan eksplisit — tidak ada pembulatan implisit. */
    public function times(float $factor, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        return new self($mode->apply($this->minorUnits * $factor));
    }

    public function percent(float $percent, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        return $this->times($percent / 100, $mode);
    }

    /** Bagi rata tanpa kehilangan/menambah rupiah (untuk split bill & bagi hasil). */
    public function allocate(array $ratios): array { /* algoritma largest-remainder */ }

    public function format(): string { return 'Rp ' . number_format($this->minorUnits, 0, ',', '.'); }
    public function jsonSerialize(): array { return ['amount' => $this->minorUnits, 'currency' => $this->currency, 'formatted' => $this->format()]; }
}
```

**Aturan:**
- Rupiah tidak punya sub-satuan praktis → simpan sebagai **integer** rupiah
  utuh di DB (`bigint`), bukan `decimal`.
- `allocate()` wajib dipakai untuk split bill dan bagi hasil mitra —
  memastikan total pecahan persis sama dengan total asal.
- Cast Eloquent `MoneyCast` agar transparan di model.

---

## 3. Kontrak API

### 3.1 Konvensi

```
Base URL      : https://api.posita.id/v1
Auth          : Authorization: Bearer <sanctum-token>
Konteks       : X-Outlet-Id: <uuid>        (wajib untuk endpoint operasional)
Idempotency   : Idempotency-Key: <ulid>    (wajib untuk semua POST/PUT/PATCH mutasi)
Versi klien   : X-Client-Version: pos-mobile/1.4.2
Device        : X-Device-Id: <uuid>
```

### 3.2 Bentuk respons

```jsonc
// Sukses tunggal
{ "data": { ... }, "meta": { "server_time": "2026-09-06T10:22:31+07:00" } }

// Sukses koleksi
{ "data": [ ... ],
  "meta": { "cursor": "eyJ...", "has_more": true, "per_page": 100 } }

// Error — SELALU bentuk ini
{ "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Stok Susu UHT tidak mencukupi",
    "details": { "ingredient_id": "...", "required": 150, "available": 40 },
    "trace_id": "01JBXR..."
} }
```

**Kode error harus stabil & bisa ditangani mesin** — aplikasi mobile
menampilkan pesan berbeda per kode, dan sync engine memutuskan retry vs
tidak berdasarkan kode.

### 3.3 Endpoint utama (v1)

```
POST   /auth/login                       email/PIN → token
POST   /auth/logout
GET    /auth/me                          profil + izin + outlet

GET    /outlets                          outlet yang bisa diakses user
GET    /outlets/{id}/settings            setelan efektif (sudah dimerge)

GET    /catalog/sync                     katalog penuh untuk cache lokal
GET    /products                         daftar berpaginasi
GET    /products/{id}

POST   /orders                           buat order (idempoten)
GET    /orders                           riwayat
GET    /orders/{id}
POST   /orders/{id}/void
POST   /orders/{id}/refund

POST   /payments                         buat payment intent
GET    /payments/{id}/status
POST   /webhooks/payment/{driver}        callback provider (tanpa auth, verifikasi signature)

POST   /shifts/open
POST   /shifts/{id}/close
GET    /shifts/{id}/report
POST   /shifts/{id}/cash-movements

GET    /inventory/levels
POST   /inventory/movements              penyesuaian manual
POST   /inventory/waste
POST   /inventory/receipts               restock cepat
POST   /inventory/stock-takes

GET    /partners
POST   /partners/{id}/consignments       terima titipan
POST   /consignments/{id}/reconcile
POST   /partners/{id}/settlements

POST   /attendance/clock-in              multipart: foto + lat/lng
POST   /attendance/clock-out
GET    /attendance/me

POST   /sync/push                        batch outbox
GET    /sync/pull?cursor=...             perubahan sejak cursor
GET    /sync/status                      cek versi & kesehatan sinkronisasi
```

### 3.4 Versioning

- Versi mayor di path (`/v1`). Breaking change → `/v2`, `/v1` didukung
  minimal 12 bulan.
- Penambahan field bersifat non-breaking; klien harus toleran terhadap field
  tak dikenal.
- `X-Client-Version` dipakai untuk memaksa upgrade jika klien terlalu lama
  (respons `426 Upgrade Required` dengan link Play Store).

---

## 4. Keamanan

| Area | Kontrol |
|---|---|
| Auth API | Sanctum personal access token, kedaluwarsa 30 hari, refresh saat aktif |
| PIN kasir | Hash bcrypt, throttle 5 percobaan/menit per device, lockout 15 menit |
| Otorisasi | Policy per model + gate izin granular; **tes wajib** untuk setiap endpoint |
| Isolasi tenant | Global scope + middleware + (PostgreSQL) Row Level Security sebagai lapis kedua |
| Kredensial pihak ketiga | Terenkripsi at-rest (`encrypted` cast), tidak pernah dikembalikan ke klien |
| Webhook | Verifikasi signature wajib; tolak & catat yang gagal; anti-replay via `provider_event_id` |
| Rate limit | Login 5/menit · API umum 120/menit/user · ekspor 5/jam · webhook 600/menit |
| Upload file | Validasi MIME nyata (bukan ekstensi), maks 10 MB, simpan di luar webroot, nama diacak |
| Foto absensi | Terenkripsi at-rest, retensi 90 hari (konfigurabel), akses hanya manajer |
| Audit log | Semua operasi sensitif: void, diskon manual, ubah harga, buka ulang shift, ubah izin, ekspor data |
| Transport | TLS 1.3 wajib, HSTS, certificate pinning di aplikasi mobile |
| Rahasia | Tidak pernah di repo; `.env` + secret manager; rotasi terjadwal |
| Backup | Terenkripsi, harian, retensi 30 hari, **uji restore bulanan** |
| Dependensi | Dependabot + `composer audit` + `npm audit` di CI |

### Ancaman spesifik POS

| Ancaman | Mitigasi |
|---|---|
| Kasir menghapus transaksi tunai | Order immutable; void tercatat + butuh alasan + alert jika melewati ambang |
| Diskon manual berlebihan | Batas persen per role; di atas batas butuh persetujuan supervisor |
| Buka ulang shift untuk menutupi selisih | Butuh izin khusus + audit log + alert ke owner |
| Perangkat hilang berisi data offline | SQLite terenkripsi (SQLCipher), remote wipe via revoke token, timeout auto-logout |
| Manipulasi absensi (titip absen) | Foto selfie + GPS + device fingerprint + deteksi mock location |
| Sinkronisasi dipalsukan | Semua tulis butuh token valid; `tenant_id` & `outlet_id` dari token, bukan payload |

---

## 5. Performa

| Target | Nilai |
|---|---|
| Waktu tambah item ke keranjang | < 50 ms (lokal, tanpa jaringan) |
| Waktu selesaikan transaksi (offline) | < 200 ms |
| Waktu selesaikan transaksi (online) | < 800 ms p95 |
| Cetak struk | < 2 detik |
| Sinkronisasi awal katalog (500 produk) | < 10 detik |
| Push 100 order tertunda | < 5 detik |
| Muat dashboard back office | < 1,5 detik p95 |
| Ekspor laporan 30 hari | < 15 detik (async + notifikasi) |

### Strategi

- **Baca dari cache lokal dulu** di aplikasi mobile. Jaringan hanya untuk
  sinkronisasi latar.
- **Tabel agregat** (`daily_stats` yang sudah ada, diperluas) untuk laporan
  — jangan agregasi tabel order mentah untuk dashboard.
- **Index komposit** `(tenant_id, outlet_id, occurred_at)` di
  `stock_movements` dan `(tenant_id, outlet_id, completed_at)` di `orders`.
- **Partisi** `stock_movements` dan `orders` per bulan saat volume tinggi
  (PostgreSQL declarative partitioning).
- **Queue untuk semua yang lambat**: PDF, ekspor, notifikasi, rekalkulasi
  HPP massal.
- **Cache setelan efektif** per outlet (invalidasi saat berubah).

---

## 6. Deployment

### 6.1 Topologi produksi (tahap awal, ratusan tenant)

```
┌──────────── VPS Indonesia (Jakarta) ────────────┐
│  Docker Compose                                 │
│  ┌───────────┐  ┌───────────┐  ┌─────────────┐  │
│  │  Caddy    │→ │ PHP-FPM   │  │  Horizon    │  │
│  │  (TLS)    │  │  app ×2   │  │  worker ×2  │  │
│  └───────────┘  └───────────┘  └─────────────┘  │
│  ┌───────────┐  ┌───────────┐  ┌─────────────┐  │
│  │PostgreSQL │  │  Redis    │  │  Scheduler  │  │
│  │    16     │  │           │  │   (cron)    │  │
│  └───────────┘  └───────────┘  └─────────────┘  │
│  ┌────────────────────────────────────────────┐ │
│  │  MinIO (S3-compatible) — foto, PDF, ekspor │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
        │                              │
        ▼                              ▼
  Backup terenkripsi           Sentry · Uptime monitor
  (object storage terpisah)
```

**Spesifikasi awal:** 4 vCPU / 8 GB RAM / 160 GB SSD NVMe.
Perkiraan biaya Rp400–700rb/bulan. Cukup untuk ± 300–500 tenant aktif.

**Jalur scale-up:** pisahkan PostgreSQL ke instance sendiri → tambah app
node di belakang load balancer → read replica untuk laporan.

### 6.2 Environment

| Env | Tujuan | Data |
|---|---|---|
| `local` | Pengembangan | Seed dummy |
| `testing` | CI | Ephemeral, di-drop tiap run |
| `staging` | Uji terima & demo sales | Anonimisasi dari produksi |
| `production` | Nyata | — |

### 6.3 Pipeline CI/CD

```
Push / PR
  ├── Lint      : pint --test · eslint · prettier --check
  ├── Static    : phpstan level 6 · vue-tsc
  ├── Test      : pest (PostgreSQL + MySQL matrix) · jest
  ├── Coverage  : gate ≥70% pada modul kritis
  ├── Security  : composer audit · npm audit
  └── Build     : vite build · (EAS build di tag)

Merge ke main
  ├── Deploy staging otomatis
  └── Smoke test

Tag v*.*.*
  ├── Deploy production (zero-downtime: migrate → build → switch)
  ├── EAS Build (Android AAB) → internal track Play Store
  └── Sentry release + source map
```

**Aturan migrasi zero-downtime:** migrasi harus kompatibel mundur satu
versi. Tambah kolom dulu (nullable) → deploy kode yang menulis keduanya →
backfill → deploy kode yang membaca kolom baru → hapus kolom lama di rilis
berikutnya.

---

## 7. Observability

| Sinyal | Alat | Yang dipantau |
|---|---|---|
| Error backend | Sentry | Exception, dikelompokkan per tenant |
| Error mobile | Sentry RN | Crash, ANR, error JS, versi perangkat |
| Performa | Laravel Pulse | Query lambat, job lambat, request lambat |
| Uptime | Better Stack / Uptime Kuma | Health check tiap menit |
| Log | JSON terstruktur → Loki | Bisa ditelusuri per `trace_id` |
| Metrik bisnis | Dashboard internal | Tenant aktif, transaksi/hari, kegagalan sync |

### Alert internal (ke tim, bukan pengguna)

- Tingkat kegagalan sync > 1% dalam 15 menit
- Antrean job tertunda > 1.000
- Kegagalan webhook payment > 5 dalam 10 menit
- Kegagalan pengiriman WhatsApp > 20%
- Error rate API > 2%
- Ruang disk < 20%
- Backup gagal

---

## 8. Kesiapan Operasional (Checklist Rilis)

- [ ] Backup otomatis harian + **uji restore terbukti berhasil**
- [ ] Runbook insiden (siapa dihubungi, langkah apa)
- [ ] Health check endpoint (`/up` dengan cek DB, Redis, storage)
- [ ] Maintenance mode dengan halaman berbahasa Indonesia
- [ ] Rate limit terpasang di semua endpoint publik
- [ ] Kebijakan retensi data terimplementasi (foto absensi, log)
- [ ] Alur ekspor & hapus data pengguna (kepatuhan UU PDP)
- [ ] Status page publik
- [ ] Dokumentasi API terpublikasi
- [ ] Rencana rollback teruji
- [ ] Uji beban: 100 transaksi/menit berkelanjutan
- [ ] Uji jaringan: mode pesawat, jaringan lambat 2G, putus-sambung
- [ ] Uji perangkat: Android 8 low-end (2 GB RAM), tablet 10 inci
