# P02 — RBAC & Permission

**Fase:** F0 · **WP:** 0.2 · **Effort:** ± 1 pekan · **Prasyarat:** P01 selesai

---

## PERAN
Backend engineer Laravel yang paham bahwa otorisasi POS bukan sekadar
"admin vs staff" — ini adalah kontrol anti-fraud.

## KONTEKS
Saat ini role adalah string di `users.role` (`admin` / `employee`) dengan
`RoleMiddleware` sederhana. Tidak cukup untuk POS: kasir boleh menjual tapi
tidak boleh void; supervisor boleh void tapi tidak boleh mengubah harga;
diskon di atas 20% butuh persetujuan.

## TUJUAN
Sistem RBAC granular, scoped per tenant, dengan role yang bisa dikustom
pengguna.

## RUANG LINGKUP
1. Integrasi `spatie/laravel-permission` dengan scope tenant
2. ± 45 permission terdefinisi, dikelompokkan
3. 7 role bawaan (`is_system`) yang bisa di-clone & dimodifikasi
4. Policy untuk setiap model bisnis
5. UI manajemen role & permission di back office
6. Migrasi `users.role` string → assignment role
7. PIN kasir untuk login cepat

## DI LUAR LINGKUP
- ❌ Fitur bisnis baru
- ❌ Approval workflow multi-step (cukup cek izin dulu)
- ❌ Permission per outlet (v1 cukup per tenant; catat sebagai backlog)

---

## SPESIFIKASI

### 1. Setup spatie/laravel-permission

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Tambahkan `tenant_id` pada tabel `roles` (nullable = role bawaan sistem)
dan `is_system` boolean. Set `Permission::$teams = true` dengan
`team_foreign_key = 'tenant_id'`, atau implementasikan scoping manual —
pilih satu dan dokumentasikan.

### 2. Katalog Permission

```php
// app/Domain/Enums/Permission.php
enum Permission: string
{
    // Sales
    case SalesCreate            = 'sales.create';
    case SalesVoid              = 'sales.void';
    case SalesRefund            = 'sales.refund';
    case SalesDiscountManual    = 'sales.discount.manual';
    case SalesDiscountUnlimited = 'sales.discount.unlimited';
    case SalesPriceOverride     = 'sales.price.override';
    case SalesViewAll           = 'sales.view.all';

    // Shift
    case ShiftOpen              = 'shift.open';
    case ShiftClose             = 'shift.close';
    case ShiftCloseOthers       = 'shift.close.others';
    case ShiftReopen            = 'shift.reopen';
    case CashMovementCreate     = 'cash.movement.create';
    case CashMovementApprove    = 'cash.movement.approve';

    // Catalog
    case CatalogView            = 'catalog.view';
    case CatalogManage          = 'catalog.manage';
    case CatalogPriceManage     = 'catalog.price.manage';

    // Inventory
    case InventoryView          = 'inventory.view';
    case InventoryAdjust        = 'inventory.adjust';
    case InventoryOpname        = 'inventory.opname';
    case InventoryWaste         = 'inventory.waste';
    case InventoryReceive       = 'inventory.receive';
    case InventoryTransfer      = 'inventory.transfer';

    // Recipe
    case RecipeView             = 'recipe.view';
    case RecipeManage           = 'recipe.manage';
    case RecipeCostView         = 'recipe.cost.view';

    // Partner
    case PartnerView            = 'partner.view';
    case PartnerManage          = 'partner.manage';
    case PartnerSettle          = 'partner.settle';

    // HR
    case HrAttendanceOwn        = 'hr.attendance.own';
    case HrAttendanceView       = 'hr.attendance.view';
    case HrAttendanceEdit       = 'hr.attendance.edit';
    case HrEmployeeManage       = 'hr.employee.manage';
    case HrPayrollView          = 'hr.payroll.view';
    case HrPayrollManage        = 'hr.payroll.manage';

    // Finance
    case ExpenseCreate          = 'finance.expense.create';
    case ExpenseApprove         = 'finance.expense.approve';
    case FinanceReportView      = 'finance.report.view';

    // Report
    case ReportViewOwn          = 'report.view.own';
    case ReportViewOutlet       = 'report.view.outlet';
    case ReportViewTenant       = 'report.view.tenant';
    case ReportExport           = 'report.export';
    case DataExportFull         = 'data.export.full';

    // Settings
    case SettingsOutlet         = 'settings.outlet';
    case SettingsTenant         = 'settings.tenant';
    case SettingsNotification   = 'settings.notification';
    case UserManage             = 'user.manage';
    case RoleManage             = 'role.manage';

    public function group(): string { /* 'sales'|'shift'|... */ }
    public function label(): string { /* label bahasa Indonesia */ }
}
```

### 3. Role bawaan

| Role | Permission |
|---|---|
| `owner` | Semua |
| `manager` | Semua kecuali `settings.tenant`, `role.manage`, `data.export.full` |
| `supervisor` | Sales lengkap, shift lengkap kecuali reopen, inventory, recipe view+cost, report outlet |
| `cashier` | `sales.create`, `sales.discount.manual`, `shift.open`, `shift.close`, `cash.movement.create`, `catalog.view`, `report.view.own`, `hr.attendance.own` |
| `barista` | `catalog.view`, `recipe.view`, `inventory.waste`, `inventory.view`, `hr.attendance.own` |
| `staff` | `hr.attendance.own`, `catalog.view` |
| `partner` | Akses portal terbatas (ditangani guard terpisah di P13) |

Seeder membuat role ini dengan `tenant_id = null` dan `is_system = true`.
Saat tenant dibuat, role di-clone ke tenant tersebut agar bisa dimodifikasi.

### 4. Batas nilai (bukan sekadar boolean)

Beberapa izin butuh batas numerik. Simpan di `roles.limits` (JSON):

```json
{
  "discount_max_percent": 20,
  "discount_max_amount": 50000,
  "void_max_amount": 100000,
  "cash_out_max_amount": 200000
}
```

Buat gate `sales.discount.within_limit` yang memeriksa nilai, bukan hanya
keberadaan izin.

### 5. PIN kasir

```php
// migration users
$table->string('pin')->nullable();          // hashed
$table->timestamp('pin_set_at')->nullable();
```

- PIN 4–6 digit, di-hash dengan bcrypt.
- Endpoint `POST /api/v1/auth/pin` untuk switch user cepat dalam satu device.
- Throttle: 5 percobaan per menit per device, lockout 15 menit.
- PIN hanya berlaku untuk user yang sudah pernah login penuh di device
  tersebut (device terdaftar).

### 6. Policy

Buat policy untuk setiap model bisnis. Pola:

```php
final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::ReportViewOwn, Permission::SalesViewAll]);
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->can(Permission::SalesViewAll)) return true;
        return $order->created_by === $user->id;
    }

    public function void(User $user, Order $order): bool
    {
        if (! $user->can(Permission::SalesVoid)) return false;
        if ($order->isCompleted() && $order->completed_at->diffInHours() > 24) return false;
        return true;
    }
}
```

### 7. UI Back Office

Halaman `Admin/Roles/Index.vue` dan `Admin/Roles/Edit.vue`:
- Daftar role dengan jumlah user
- Editor izin dikelompokkan per modul dengan checkbox
- Input batas nilai
- Tombol "Duplikat role ini"
- Role sistem: tampilkan tapi tidak bisa diedit (hanya di-clone)

---

## ACCEPTANCE CRITERIA

- [ ] Migrasi mengonversi `users.role` string menjadi assignment role tanpa
      kehilangan akses
- [ ] Role dari tenant A tidak terlihat oleh tenant B
- [ ] Kasir tanpa `sales.void` mendapat 403 saat mencoba void
- [ ] Kasir dengan `discount_max_percent: 20` ditolak saat memberi diskon 25%
- [ ] Owner bisa membuat role kustom dan menugaskannya
- [ ] Role sistem tidak bisa dihapus atau diedit, tapi bisa di-clone
- [ ] Login PIN berfungsi dan throttled dengan benar
- [ ] Setiap endpoint API punya otorisasi (tidak ada yang polos)

---

## TESTING WAJIB

```php
it('denies void without the permission');
it('denies discount above the role limit');
it('allows discount within the role limit');
it('scopes roles to the tenant');
it('prevents assigning a role from another tenant');
it('clones system roles into a new tenant');
it('throttles PIN attempts');
it('locks out after repeated PIN failures');
it('enforces authorization on every API endpoint');   // test tabel-driven
```

Test tabel-driven untuk endpoint:
```php
dataset('protected_endpoints', [
    ['POST', '/api/v1/orders',            Permission::SalesCreate],
    ['POST', '/api/v1/orders/{id}/void',  Permission::SalesVoid],
    // ... semua endpoint
]);
```

---

## PERINTAH

```bash
vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test
php artisan permission:show      # verifikasi matriks role-permission
```

---

## CATATAN

⚠️ **Hapus `RoleMiddleware` lama** setelah migrasi selesai, jangan
dibiarkan menumpuk.

⚠️ **Cache permission spatie harus di-flush per tenant** saat role berubah:
`app(PermissionRegistrar::class)->forgetCachedPermissions()`.

⚠️ **PIN bukan pengganti password.** PIN hanya untuk switch user pada device
yang sudah terautentikasi penuh. Jangan izinkan login awal hanya dengan PIN.

⚠️ Simpan **alasan** setiap void dan diskon manual, beserta siapa yang
menyetujui. Ini bukti anti-fraud yang akan dipakai laporan nanti.
