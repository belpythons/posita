# P14 — Notifikasi WhatsApp

**Fase:** F4 · **WP:** 4.2 · **Effort:** ± 2 pekan · **Prasyarat:** P05, P09, P13

---

## PERAN
Backend engineer yang membangun sistem yang akan mengirim pesan ke telepon
pribadi orang. Spam = uninstall.

## KONTEKS
Pemilik kedai tidak membuka dashboard; mereka membuka WhatsApp puluhan kali
sehari. Serah terima shift dan laporan kas sudah terjadi di WhatsApp dalam
bentuk foto buku tulis. Posita hanya perlu menggantikannya dengan pesan
terstruktur otomatis.

## TUJUAN
Alert engine berbasis aturan yang mengirim informasi operasional penting ke
WhatsApp, dengan kontrol penuh di tangan pengguna.

## RUANG LINGKUP
1. Interface `WhatsAppDriver` + registry
2. Driver: `log` (dev), `fonnte`, `wablas`, `wa_cloud`
3. Tabel: `notification_channels`, `notification_rules`,
   `notification_templates`, `notification_logs`
4. `NotificationEngine`: rule evaluation, dedup, quiet hours, rate limit,
   batching
5. Katalog 25+ alert (lihat `docs/riset/04-whatsapp-notifikasi.md`)
6. Editor template dengan pratinjau
7. Magic link pendek bertanda-tangan
8. UI pengaturan + riwayat + kill switch
9. Kuota per channel

## DI LUAR LINGKUP
- ❌ Chatbot / auto-reply
- ❌ Broadcast marketing ke pelanggan
- ❌ Inbox WhatsApp terintegrasi

---

## SPESIFIKASI

Ikuti `docs/riset/04-whatsapp-notifikasi.md` untuk katalog alert, contoh
pesan, skema tabel, dan aturan engine — dokumen tersebut adalah spesifikasi
utama modul ini.

### Interface driver

```php
namespace App\Support\Notification\Contracts;

interface WhatsAppDriver
{
    public function key(): string;
    public function send(WhatsAppMessage $message): SendResult;
    public function sendBulk(array $messages): array;
    public function checkStatus(string $providerMessageId): DeliveryStatus;
    public function validateCredentials(): bool;
    public function quotaRemaining(): ?int;
}

final readonly class WhatsAppMessage
{
    public string $to;              // dinormalisasi ke 62xxx
    public string $body;
    public ?string $templateName;   // untuk WA Cloud API
    public array $templateParams;
    public ?string $mediaUrl;
    public NotificationPriority $priority;
}
```

### Pipeline

```
Domain Event (StockLevelChanged, ShiftClosed, SettlementDue, ...)
  → RuleEvaluator        ada rule aktif untuk event ini?
  → ConditionMatcher     kondisi terpenuhi? (threshold, dll)
  → DedupGuard           sudah dikirim dalam window cooldown?
  → RecipientResolver    role → nomor telepon
  → TemplateRenderer     render body dengan data
  → QuietHoursGate       di luar jam? jadwalkan, kecuali kritis
  → BatchAggregator      gabungkan alert sejenis dalam window
  → SendWhatsAppJob      queued, retry 3× exponential backoff
  → Driver::send()
  → notification_logs
```

### Aturan yang tidak boleh dilanggar

1. **Deduplikasi wajib.** `dedup_key = hash(event_code + entity_id + window)`.
   Default cooldown 6 jam per bahan untuk alert stok.
2. **Quiet hours.** Default 22:00–06:00 WIB. Alert non-kritis diantre.
3. **Batching.** 5 bahan menipis = **1** pesan, bukan 5.
4. **Rate limit per channel.** Untuk gateway unofficial, jeda acak 3–10
   detik antar pesan (mengurangi risiko ban).
5. **Semua lewat queue.** Tidak ada pengiriman WA di request cycle.
6. **Kill switch** global per tenant dan per rule. Wajib ada.
7. **Template bisa diedit pengguna** dengan daftar variabel + pratinjau.
8. **Kegagalan tidak boleh senyap.** Gagal 3× → catat + tampilkan lonceng
   in-app.
9. **Nomor penerima harus opt-in tercatat.**
10. **Kuota harian per channel** dengan peringatan saat mendekati batas.

### Contoh rule konfigurasi

```json
{
  "event_code": "STOCK_CRITICAL",
  "is_enabled": true,
  "channel_id": 1,
  "conditions": {
    "days_of_cover_below": 1,
    "min_ingredient_count": 1
  },
  "recipients": [
    {"type": "role", "value": "owner"},
    {"type": "role", "value": "manager"},
    {"type": "phone", "value": "628123456789", "label": "Pak Budi (supplier)"}
  ],
  "schedule": {"mode": "immediate"},
  "quiet_hours": {"from": "22:00", "to": "06:00", "timezone": "Asia/Jakarta"},
  "cooldown_minutes": 360,
  "batch_window_minutes": 15,
  "priority": "critical"
}
```

### Magic link

```php
// Setiap alert menyertakan deep link pendek
Route::get('/r/{token}', ShortLinkController::class)->name('shortlink');

// Token = signed, berisi: target_route, params, expires_at (24 jam default)
// Membuka link → auto-login sebagai user terkait (jika masih valid)
// Untuk mitra: berlaku 30 hari
```

### UI Pengaturan

```
Admin/Notifications/
├── Index.vue          daftar alert dengan toggle, dikelompokkan per kategori
├── Rule.vue           edit satu rule: kondisi, penerima, jadwal, template
├── Channels.vue       kelola koneksi WhatsApp (driver + kredensial + tes)
├── Templates.vue      editor template dengan variabel + pratinjau
└── Logs.vue           riwayat: terkirim/gagal/ditahan, filter, kirim ulang
```

**Editor template** menampilkan daftar variabel yang tersedia untuk event
tersebut, dan tombol "Kirim Tes ke Nomor Saya".

---

## ACCEPTANCE CRITERIA

- [ ] Stok turun di bawah reorder point → alert terkirim ke WhatsApp owner
- [ ] 5 bahan menipis bersamaan → **1** pesan berisi 5 bahan
- [ ] Alert yang sama dalam window cooldown → tidak dikirim ulang, tercatat
      sebagai `suppressed`
- [ ] Alert non-kritis jam 2 pagi → diantre, terkirim jam 6 pagi
- [ ] Alert kritis → terkirim langsung walau di quiet hours
- [ ] Tutup shift → laporan lengkap terkirim ke owner
- [ ] Selisih kas di atas ambang → alert terpisah terkirim
- [ ] Settlement mitra jatuh tempo → alert ke owner
- [ ] Ganti driver dari Fonnte ke Wablas tanpa mengubah kode bisnis
- [ ] Kill switch mematikan semua notifikasi seketika
- [ ] Template yang diedit pengguna ter-render dengan benar
- [ ] Nomor 08xx, +62xx, dan 62xx semuanya ternormalisasi
- [ ] Kegagalan pengiriman tercatat dan muncul di UI
- [ ] Kuota harian tercapai → berhenti kirim + peringatan

---

## TESTING WAJIB

```php
// tests/Unit/Notification/NotificationEngineTest.php
it('suppresses duplicates within the cooldown window');
it('batches multiple low-stock alerts into one message');
it('queues non-critical alerts during quiet hours');
it('sends critical alerts during quiet hours');
it('resolves role recipients to phone numbers');
it('renders templates with the correct variables');
it('respects the per-channel daily quota');

// tests/Unit/Notification/PhoneNormalizationTest.php
it('normalizes 08xx to 62xx');
it('normalizes +62xx to 62xx');
it('normalizes 62xx unchanged');
it('rejects invalid numbers');

// tests/Feature/Notification/DriverTest.php
it('sends via the log driver in testing');
it('swaps drivers without changing business code');
it('retries on transient failure');
it('records failures in the log after 3 attempts');

// tests/Feature/Notification/AlertCatalogTest.php
it('dispatches STOCK_LOW at the reorder point')->with('all_alert_codes');
```

Gunakan driver `log` di seluruh test — **jangan pernah** memanggil API
sungguhan di CI.

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Notification
php artisan posita:notification:test --event=STOCK_LOW --to=628123456789
```

---

## CATATAN

⚠️ **Spam = uninstall.** Lebih baik terlalu sedikit notifikasi daripada
terlalu banyak. Default: hanya 6 alert aktif (stok kritis, tutup shift,
selisih kas, rekap harian, settlement jatuh tempo, box order H-1). Sisanya
mati, pengguna bisa mengaktifkan sendiri.

⚠️ **Gateway unofficial berisiko banned.** Wajib: jeda acak antar pesan,
batas laju, dan variasi pesan. Sediakan fallback ke driver lain saat gagal
berulang.

⚠️ **Biarkan tenant memakai kredensial gateway mereka sendiri.** Ini
memindahkan biaya & risiko ban ke mereka, dan sesuai dengan janji
kustomisasi. Sediakan juga channel bawaan Posita untuk yang tidak mau ribet
(dengan kuota terbatas).

⚠️ **Isi pesan sama pentingnya dengan sistemnya.** Pesan harus bisa
ditindaklanjuti: bukan "stok menipis", tapi "Susu tinggal 2,1 L, cukup 0,4
hari, perkiraan belanja Rp385.000, [catat restock]".
