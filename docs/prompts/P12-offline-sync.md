# P12 — Offline-First Sync Engine

**Fase:** F3 · **WP:** 3.1–3.5 · **Effort:** ± 5 pekan · **Prasyarat:** P11

---

## PERAN
Engineer yang paham distributed systems. Ini bagian tersulit di seluruh
project, dan gate menuju rilis v1.0.

## KONTEKS
Internet putus di jam sibuk adalah kejadian rutin di kedai kopi Indonesia,
bukan edge case. POS yang berhenti saat internet mati berarti kehilangan
pendapatan langsung — dan pengguna akan kembali ke kalkulator dan buku tulis.

## TUJUAN
Aplikasi kasir bisa berjualan penuh tanpa internet, dan menyinkronkan
kembali tanpa kehilangan atau menduplikasi satu transaksi pun.

## RUANG LINGKUP
1. SQLite lokal (terenkripsi) + Drizzle ORM + skema mirror
2. Sync pull: cursor-based, katalog/resep/setelan/stok
3. Sync push: outbox pattern + idempotency + retry
4. Penanganan penolakan (rejected entries)
5. Penomoran order offline
6. UX offline: indikator status, batasan yang jujur, fallback pembayaran
7. Test suite jaringan yang menyeluruh
8. Endpoint `/api/v1/sync/push`, `/pull`, `/status`

## DI LUAR LINGKUP
- ❌ Sync multi-device dengan editing bersamaan pada entitas yang sama
      (order bersifat append-only → tidak diperlukan)
- ❌ Real-time collaboration
- ❌ Offline untuk back office web

---

## SPESIFIKASI

### Prinsip

1. **Order append-only.** Order selesai tidak pernah diedit; koreksi lewat
   void/refund yang juga append-only. Ini membuat konflik hampir mustahil
   pada data paling kritis.
2. **ID dibuat klien** (ULID) — terurut waktu, unik global.
3. **Idempotency key wajib** pada setiap operasi tulis.
4. **Server otoritatif** untuk data referensi (produk, harga, resep, stok).
5. **Klien otoritatif** untuk data transaksi (order, pembayaran, absensi).

### Skema lokal (SQLite)

```typescript
// Tabel referensi (pull dari server, read-only di klien)
products, product_variants, modifiers, modifier_groups,
categories, recipes, ingredients, stock_levels_snapshot,
outlet_settings, tax_groups, discounts, users_cache

// Tabel transaksi (dibuat lokal, push ke server)
orders, order_items, order_item_modifiers, payments,
shifts, cash_movements, waste_logs, attendances

// Infrastruktur sync
outbox (
  id, entity_type, entity_id, op, payload TEXT,
  idempotency_key, attempts, last_error, last_attempt_at,
  status,          -- 'pending'|'syncing'|'synced'|'rejected'
  created_at
)

sync_state (
  key,             -- 'catalog'|'settings'|'stock'
  cursor, last_synced_at, last_error
)

rejected_entries (
  id, outbox_id, error_code, error_message,
  payload, created_at, resolved_at
)
```

Enkripsi database lokal dengan SQLCipher (kunci di Keystore Android).

### Endpoint push

```
POST /api/v1/sync/push
{
  "device_id": "...",
  "entries": [
    { "idempotency_key": "01JBX...",
      "entity_type": "order",
      "op": "create",
      "payload": {...},
      "client_created_at": "2026-09-06T14:22:31+07:00" },
    ...
  ]
}

Response 200:
{
  "results": [
    { "idempotency_key": "01JBX...", "status": "accepted",
      "server_id": "01JBX...", "server_data": {"order_number": "KS-260906-0042"} },
    { "idempotency_key": "01JBY...", "status": "duplicate",
      "server_id": "01JBY..." },
    { "idempotency_key": "01JBZ...", "status": "rejected",
      "error": {"code": "NO_OPEN_SHIFT", "message": "Shift sudah ditutup"} }
  ],
  "server_time": "..."
}
```

**Aturan:**
- Batch maksimal 100 entri per request.
- Setiap entri diproses independen — satu gagal tidak membatalkan yang lain.
- `duplicate` diperlakukan sama dengan `accepted` oleh klien.
- `rejected` → masuk `rejected_entries`, **tampilkan ke kasir**. Jangan
  pernah buang diam-diam.
- Urutan penting: shift harus di-push sebelum order yang merujuknya. Klien
  mengurutkan outbox berdasarkan dependensi.

### Endpoint pull

```
GET /api/v1/sync/pull?cursor=<opaque>&outlet_id=1&types=products,recipes

Response:
{
  "changes": {
    "products":   [...],
    "recipes":    [...],
    "stock_levels": [...]
  },
  "deletions": [ {"type":"product", "id": 42}, ... ],
  "next_cursor": "eyJ1cGRhdGVkX2F0IjoiMjAyNi0wOS0wNlQxMDoyMjozMSIsImlkIjo0Mn0=",
  "has_more": true,
  "server_time": "..."
}
```

Cursor = base64 dari `{updated_at, id}` terakhir. Setiap tabel yang
disinkron wajib punya `updated_at` dan soft delete.

### Resolusi konflik

| Data | Aturan |
|---|---|
| Order & payment | Append-only → tidak ada konflik. Duplikat dicegah idempotency key |
| Stock level | **Server otoritatif.** Klien menampilkan estimasi lokal dengan label "perkiraan" |
| Harga & resep | **Server otoritatif.** Order offline memakai *snapshot harga saat transaksi* |
| Shift | Klien otoritatif untuk input kasir; server memvalidasi & menghitung |
| Absensi | Klien otoritatif; server memvalidasi geofence & waktu |

### Penomoran order offline

```
Online:  KS-260906-0042           (dari server)
Offline: KS-260906-D3-0007        (prefix device + urutan lokal)

Saat sync:
  → server memberi nomor final
  → simpan keduanya: order_number (final) & local_order_number
  → struk yang sudah dicetak memakai nomor lokal → tetap bisa dicocokkan
```

### UX offline — indikator wajib

```
🟢 Tersinkron                  semua terkirim
🟡 3 transaksi menunggu        online, sedang mengirim
🔴 Offline · 12 tersimpan      tidak ada koneksi
⚠️ 1 transaksi perlu perhatian ada yang ditolak server
```

Indikator ini **selalu terlihat** di header, bisa diketuk untuk detail.

### Batasan offline yang harus dikomunikasikan jujur

| Fitur | Saat offline |
|---|---|
| Jual & cetak struk | ✅ Berfungsi penuh |
| QRIS dinamis | ❌ → tawarkan QRIS statis atau tunai |
| Stok real-time | 🟡 Estimasi lokal, diberi label |
| Lihat data outlet lain | ❌ Pesan jelas |
| Laporan lintas shift | ❌ Pesan jelas |
| Kirim WhatsApp | 🟡 Diantre, terkirim saat online |
| Tambah produk baru | ❌ Perlu online |

**Jangan gagal diam-diam.** Setiap batasan ditampilkan dengan kalimat yang
dimengerti kasir, bukan error teknis.

### Strategi sync

```
Pull:
  - Saat aplikasi dibuka
  - Setiap 15 menit saat idle
  - Saat pull-to-refresh manual
  - Setelah push berhasil (untuk mendapat nomor order final)

Push:
  - Segera setelah transaksi selesai (jika online)
  - Setiap 30 detik jika ada outbox pending
  - Saat koneksi pulih (listener NetInfo)
  - Saat aplikasi kembali ke foreground
  - Retry: exponential backoff 1s, 2s, 4s, 8s, 16s, 30s, lalu tiap 60s
```

---

## ACCEPTANCE CRITERIA

- [ ] Matikan Wi-Fi → jual 50 transaksi → nyalakan → **semua** tersinkron,
      **tanpa duplikat**, **tanpa kehilangan**
- [ ] Push entri yang sama 3× menghasilkan 1 order di server
- [ ] Aplikasi bisa dibuka dan berjualan setelah restart perangkat tanpa
      jaringan sama sekali
- [ ] Entri yang ditolak server muncul di UI dengan alasan yang bisa
      ditindaklanjuti
- [ ] Nomor order lokal dan final keduanya tersimpan dan bisa dicocokkan
- [ ] Waktu selesaikan transaksi offline < 200 ms
- [ ] Sinkronisasi awal katalog 500 produk < 10 detik
- [ ] Push 100 order tertunda < 5 detik
- [ ] Jaringan putus di tengah push tidak menyebabkan duplikat
- [ ] Jam perangkat yang salah tidak merusak urutan (pakai ULID + server_time)
- [ ] Database lokal terenkripsi (verifikasi dengan membuka file mentah)
- [ ] Storage penuh ditangani dengan pesan yang jelas, bukan crash

---

## TESTING WAJIB

### Backend
```php
it('processes each push entry independently');
it('returns duplicate for a repeated idempotency key');
it('rejects an order referencing a closed shift');
it('assigns the final order number on sync');
it('returns changes after the cursor');
it('includes deletions in the pull response');
it('paginates pull results');
it('scopes sync to the outlet and tenant');
```

### Mobile
```typescript
describe('syncEngine', () => {
  it('queues writes to the outbox when offline');
  it('pushes in dependency order: shift before orders');
  it('retries with exponential backoff');
  it('marks entries synced on accepted and duplicate');
  it('moves rejected entries to the attention queue');
  it('does not duplicate when the network drops mid-push');
  it('resumes pull from the stored cursor');
});
```

### Skenario integrasi (wajib dijalankan manual sebelum rilis)
```
1. Airplane mode → 50 transaksi → online → verifikasi 50 order di server
2. Jaringan putus-sambung tiap 10 detik selama 30 transaksi
3. Jaringan 2G lambat (throttle 50 kbps) selama 20 transaksi
4. Kill aplikasi di tengah push → restart → verifikasi tidak ada duplikat
5. Dua device di outlet sama menjual bersamaan → verifikasi tidak ada
   nomor order bentrok
6. Shift ditutup di device A saat device B masih offline dengan transaksi
   di shift itu → verifikasi penanganan penolakan
7. Storage perangkat penuh → verifikasi pesan error, bukan crash
8. Jam perangkat dimundurkan 2 jam → verifikasi urutan tetap benar
```

---

## PERINTAH
```bash
php artisan test --filter=Sync
cd apps/pos-mobile && npm run test
maestro test .maestro/offline/
```

---

## CATATAN

⚠️ **Ini bagian tersulit di seluruh project.** Alokasikan waktu ekstra.
Jangan potong test.

⚠️ **Jangan pernah membuang data klien secara diam-diam.** Jika server
menolak, tampilkan ke kasir. Transaksi yang hilang tanpa jejak akan
menghancurkan kepercayaan secara permanen.

⚠️ **Uji dengan mematikan jaringan sungguhan**, bukan mock. Perilaku
NetInfo, timeout DNS, dan koneksi setengah-mati sulit disimulasikan.

⚠️ **Batasi ukuran database lokal.** Simpan riwayat transaksi lokal maksimal
30 hari; sisanya hanya di server. Tablet murah punya storage terbatas.

⚠️ **Jangan pakai timestamp perangkat untuk urutan.** Jam tablet murah
sering salah. Pakai ULID (terurut monoton lokal) + `server_time` untuk
rekonsiliasi.

⚠️ **Pertimbangkan PowerSync/ElectricSQL** jika sync buatan sendiri
memakan > 6 pekan. Trade-off: dependensi & biaya berlangganan vs waktu
engineering. Dokumentasikan keputusan jika beralih.
