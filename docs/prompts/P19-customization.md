# P19 — Engine Kustomisasi & Preset Tipe Bisnis

**Fase:** F5 · **WP:** 5.2 · **Effort:** ± 2 pekan · **Prasyarat:** semua modul

---

## PERAN
Platform engineer yang membuat janji *"full customable"* menjadi nyata dan
bukan slogan.

## KONTEKS
Permintaan pengguna: *"buat project ini full customable supaya benar-benar
fleksibel untuk beberapa kasus toko sekaligus"*.

Bisnis F&B Indonesia sangat heterogen: warkop sederhana, specialty coffee,
kantin mitra, catering box, food truck, resto penuh. Satu UI yang sama untuk
semuanya akan terasa berlebihan bagi yang sederhana dan kurang bagi yang
kompleks.

## TUJUAN
Lima lapis kustomisasi yang memungkinkan satu codebase melayani semua kasus,
tanpa membingungkan pengguna baru.

## RUANG LINGKUP
1. **Lapis 1** — Preset tipe bisnis (8 preset)
2. **Lapis 2** — Feature flag 3 tingkat (paket → tenant → outlet)
3. **Lapis 3** — Pengaturan berjenjang (5 tingkat prioritas)
4. **Lapis 4** — Template: struk, notifikasi, laporan
5. **Lapis 5** — Custom field pada entitas utama
6. Branding / white-label
7. UI onboarding dengan pemilihan preset
8. `SettingsResolver` dengan cache

## DI LUAR LINGKUP
- ❌ Plugin system / kode kustom pengguna
- ❌ Custom workflow engine
- ❌ Custom report builder (v1 cukup ekspor + API)

---

## SPESIFIKASI

Ikuti `docs/20-blueprint-produk.md` bagian **MX — Engine Kustomisasi**.

### Lapis 1 — Preset tipe bisnis

```php
enum BusinessType: string
{
    case CoffeeShop        = 'coffee_shop';
    case CoffeeShopPartner = 'coffee_shop_partner';
    case CanteenPartner    = 'canteen_partner';
    case SimpleWarung      = 'simple_warung';
    case Catering          = 'catering';
    case FullRestaurant    = 'full_restaurant';
    case FoodTruck         = 'food_truck';
    case Custom            = 'custom';

    public function featureFlags(): array;
    public function defaultSettings(): array;
    public function seedData(): array;   // kategori & satuan awal
}
```

Contoh preset **Kantin Mitra**:
```php
[
    'feature_flags' => [
        'partner'        => ['enabled' => true, 'portal_enabled' => true],
        'recipe'         => ['enabled' => false],   // kantin tidak masak sendiri
        'inventory'      => ['enabled' => false],
        'tables'         => ['enabled' => false],
        'hr_attendance'  => ['enabled' => true],
        'notifications_wa' => ['enabled' => true],
    ],
    'settings' => [
        'tax.enabled'                    => false,
        'service_charge.enabled'         => false,
        'partner.settlement_cycle'       => 'weekly',
        'partner.default_share_type'     => 'commission_percent',
        'partner.default_share_value'    => 20,
        'partner.auto_send_daily_report' => true,
    ],
    'seed' => [
        'categories' => ['Makanan Ringan', 'Minuman', 'Makanan Berat'],
    ],
]
```

**Penting:** preset hanya mengatur **nilai awal**. Semuanya tetap bisa
diubah. Mengganti preset **tidak menghapus data** — hanya mengubah flag &
setelan.

### Lapis 2 — Feature flag 3 tingkat

```php
final class FeatureFlagResolver
{
    /**
     * Resolusi: paket langganan (batas atas) → tenant → outlet
     * Fitur mati di paket TIDAK BISA dinyalakan di tenant/outlet.
     */
    public function enabled(string $feature, ?int $outletId = null): bool;
    public function config(string $feature, string $key, mixed $default = null): mixed;
    public function all(?int $outletId = null): array;
}
```

Middleware & UI:
```php
Route::middleware('feature:partner')->group(...);
```
```vue
<FeatureGate feature="recipe">
  <RecipeEditor />
</FeatureGate>
```

Fitur yang mati **tidak muncul di navigasi sama sekali** — bukan disabled,
tapi hilang. Pengguna warung sederhana tidak boleh melihat menu "Resep &
HPP" yang tidak dia butuhkan.

### Lapis 3 — Pengaturan berjenjang

```
Prioritas (yang di bawah menang):
  1. Default sistem      config/posita.php
  2. Preset tipe bisnis  BusinessType::defaultSettings()
  3. Setelan tenant      tenants.settings
  4. Setelan outlet      outlets.settings
  5. Preferensi user     users.preferences  (hanya untuk UI)
```

```php
final class SettingsResolver
{
    public function get(string $key, mixed $default = null, ?int $outletId = null): mixed;
    public function all(?int $outletId = null): array;   // hasil merge, di-cache
    public function set(string $key, mixed $value, SettingScope $scope, ?int $id = null): void;
    public function forget(string $key, SettingScope $scope, ?int $id = null): void;
}
```

Cache hasil merge per outlet, invalidasi saat ada perubahan di tingkat
manapun.

### Kelompok pengaturan (registry)

Setiap setelan terdaftar dengan metadata agar UI bisa di-generate otomatis:

```php
SettingDefinition::make('tax.rate')
    ->group('Pajak & Biaya')
    ->label('Tarif PB1')
    ->description('Pajak restoran daerah. Berbeda tiap kabupaten/kota.')
    ->type('number')
    ->suffix('%')
    ->default(10)
    ->min(0)->max(50)
    ->scope(SettingScope::Outlet)
    ->dependsOn('tax.enabled');
```

Kelompok yang wajib ada: Pajak & Biaya, Struk, Kasir, Shift, Stok, Mitra,
Notifikasi, Tampilan, Keamanan.

### Lapis 4 — Template

**Template struk** (block-based, lihat
`docs/riset/05-tech-stack-mobile.md` §7):

Editor visual sederhana:
- Daftar blok yang bisa di-drag untuk mengurutkan
- Toggle tampil/sembunyi per blok
- Edit teks header & footer
- Upload logo
- Pratinjau dalam bentuk struk 32/48 kolom
- Tombol "Cetak Tes"

**Template notifikasi** — sudah ada di P14.

### Lapis 5 — Custom field

```php
custom_field_definitions
  id, tenant_id, entity_type, key, label, type,
  options json, is_required, default_value,
  show_in_list, show_in_form, sort_order, is_active
```

Entitas yang mendukung: `product`, `customer`, `partner`, `employee`,
`ingredient`, `supplier`.

Nilai disimpan di kolom `custom_fields` JSON pada entitas — sederhana, tanpa
join, cukup untuk kebutuhan UMKM.

UI form & tabel meng-generate field ini otomatis dari definisi.

### Branding / White-label

```json
{
  "app_name": "Kopi Senja POS",
  "logo_path": "brand/logo.png",
  "logo_dark_path": "brand/logo-dark.png",
  "favicon_path": "brand/favicon.png",
  "primary_color": "#C8763C",
  "accent_color": "#2C1810",
  "receipt_header": "KOPI SENJA",
  "receipt_footer": "Terima kasih! IG: @kopisenja",
  "email_from_name": "Kopi Senja",
  "custom_domain": "pos.kopisenja.id"
}
```

Tersedia di paket berbayar; custom domain di paket tertinggi.

### Onboarding

```
Langkah 1: "Bisnis kamu jenis apa?"
  [☕ Coffee Shop]  [🏪 Kantin/Titip Jual]  [🍱 Catering/Box]
  [🥤 Warung Sederhana]  [🍽 Resto Lengkap]  [🚚 Food Truck]
  [⚙️ Atur Sendiri]

Langkah 2: "Nama & lokasi toko"

Langkah 3: "Fitur apa yang kamu butuhkan?"
  ☑ Kelola stok bahan baku       (bisa diubah nanti)
  ☑ Resep & hitung HPP
  ☐ Barang titipan mitra
  ☑ Absensi karyawan
  ☐ Pesanan box/catering

Langkah 4: "Tambah 5 produk pertamamu"  (quick-add)

Langkah 5: "Siap jualan!"  → langsung ke layar kasir
```

Target: **daftar sampai transaksi pertama < 15 menit.**

---

## ACCEPTANCE CRITERIA

- [ ] 8 preset tersedia dan menghasilkan konfigurasi yang berbeda & masuk akal
- [ ] Memilih preset "Warung Sederhana" menyembunyikan menu Resep, Stok,
      Mitra dari navigasi sepenuhnya
- [ ] Mengganti preset tidak menghapus data yang sudah ada
- [ ] Fitur yang mati di paket langganan tidak bisa dinyalakan di
      tenant/outlet
- [ ] `SettingsResolver` mengembalikan nilai sesuai prioritas berjenjang
- [ ] Mengubah setelan di tingkat outlet meng-override tenant
- [ ] Cache setelan ter-invalidasi saat ada perubahan di tingkat manapun
- [ ] UI setelan ter-generate otomatis dari registry
- [ ] Template struk yang diedit menghasilkan cetakan sesuai
- [ ] Custom field muncul di form dan tabel entitas terkait
- [ ] Branding mengubah tampilan aplikasi & struk
- [ ] Onboarding selesai < 15 menit sampai transaksi pertama (diukur dengan
      pengguna nyata)

---

## TESTING WAJIB

```php
// tests/Unit/Customization/SettingsResolverTest.php
it('resolves from system defaults');
it('overrides system defaults with the business preset');
it('overrides the preset with tenant settings');
it('overrides tenant settings with outlet settings');
it('invalidates the cache when any level changes');

// tests/Unit/Customization/FeatureFlagResolverTest.php
it('respects the subscription plan ceiling');
it('cannot enable a feature disabled by the plan');
it('resolves outlet-level overrides');

// tests/Feature/Customization/PresetTest.php
it('applies feature flags and settings for each preset')->with('all_presets');
it('seeds initial categories');
it('does not delete data when switching preset');

// tests/Feature/Customization/CustomFieldTest.php
it('renders custom fields on the entity form');
it('validates required custom fields');
it('stores values in the custom_fields json column');

// tests/Feature/Customization/ReceiptTemplateTest.php
it('renders a custom receipt template to ESC/POS commands');
it('hides blocks that are toggled off');
```

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Customization
php artisan posita:preset:apply --tenant=1 --preset=canteen_partner --dry-run
```

---

## CATATAN

⚠️ **Kustomisasi tanpa default yang baik akan melumpuhkan pengguna.**
Pengguna baru tidak boleh diminta mengonfigurasi apapun. Preset harus
menghasilkan sistem yang langsung bisa dipakai.

⚠️ **Fitur yang mati harus HILANG, bukan disabled.** Menu abu-abu yang tidak
bisa diklik membuat pengguna merasa produknya tidak lengkap.

⚠️ **Cache setelan sangat penting.** `SettingsResolver::get()` akan dipanggil
puluhan kali per request. Tanpa cache, ini akan jadi bottleneck.

⚠️ **Jangan buat setelan untuk semuanya.** Setiap setelan adalah keputusan
yang dibebankan ke pengguna. Kalau ada satu jawaban yang benar untuk 95%
kasus, jadikan itu default dan jangan tawarkan opsinya.

⚠️ **Custom field jangan sampai jadi database kedua.** Batasi jumlahnya
(maks 10 per entitas) dan jangan izinkan relasi antar custom field.
