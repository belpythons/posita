# POSITA - Point of Sales & Consignment Management System

---

## 1. 📋 Overview Project

**Posita** adalah sistem aplikasi Point of Sale (POS) berbasis web yang dirancang khusus untuk manajemen usaha dengan model konsinyasi (titip jual) dan penyewaan box (box rental). 

Aplikasi ini memudahkan:
- Pengelolaan sesi kasir (buka/tutup toko)
- Pelacakan stok mitra (partner) dengan sistem konsinyasi
- Manajemen penyewaan ruang display (box order)
- Laporan dan analitik penjualan

---

## 2. � Tech Stack Requirement

Project ini dibangun menggunakan teknologi modern:

| Kategori | Teknologi | Versi |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel | 11.x |
| **Frontend Framework** | Vue.js (Composition API) | 3.x |
| **Routing/Glue** | Inertia.js | - |
| **Styling** | Tailwind CSS | 3.x |
| **Database** | MySQL / MariaDB | - |
| **Build Tool** | Vite | - |
| **Package Manager** | Composer, NPM | - |

### Prasyarat Sistem
- PHP >= 8.4
- Composer
- Node.js & NPM
- MySQL / MariaDB

---

## 3. 📦 Step by Step Installation

### Langkah 1: Clone Repository
```bash
git clone https://github.com/username/posita.git
cd posita
```

### Langkah 2: Install Dependencies
Install paket backend dan frontend:
```bash
composer install
npm install
```

### Langkah 3: Konfigurasi Environment
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan konfigurasi database:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=posita_db
DB_USERNAME=root
DB_PASSWORD=
```

### Langkah 4: Generate App Key
```bash
php artisan key:generate
```

### Langkah 5: Migrasi dan Seeder
Jalankan migrasi database dan isi dengan data dummy untuk demo:
```bash
php artisan migrate:fresh --seed
```

### Langkah 6: Jalankan Aplikasi
Buka dua terminal terpisah:

*Terminal 1 (Backend Server):*
```bash
php artisan serve
```

*Terminal 2 (Frontend Hot-Reload):*
```bash
npm run dev
```

### Langkah 7: Akses Aplikasi
Buka browser dan kunjungi: `http://localhost:8000`

### Demo Account
| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@posita.com` | `password` |
| **Kasir (Staff)** | `kasir1@posita.com` | `password` |

---

## 4. 👥 Task Distribution

### Rivaldi — Open Shop Feature
List everything related to the **Open Shop** feature:
- `app/Http/Controllers/Pos/ShopSessionController.php` (method: open, store)
- `app/Services/ShopSessionService.php` (logic untuk membuka sesi toko)
- `app/Actions/StartDailyShopAction.php`
- `app/Models/ShopSession.php`
- `resources/js/Pages/Pos/OpenShop.vue`
- `database/migrations/*_create_shop_sessions_table.php`

---

### Amar — Close Shop Feature
List everything related to the **Close Shop** feature:
- `app/Http/Controllers/Pos/ShopSessionController.php` (method: close, updateClose)
- `app/Services/ShopSessionService.php` (logic untuk menutup sesi toko)
- `app/Actions/CloseDailyShopAction.php`
- `app/Models/ShopSession.php` (status update logic)
- `resources/js/Pages/Pos/CloseShop.vue`
- Logic kalkulasi revenue, profit, dan cash discrepancy

---

### Nurita — Box Order Feature
List everything related to the **Box Order** feature:
- `app/Http/Controllers/Pos/BoxOrderController.php`
- `app/Services/BoxOrderService.php`
- `app/Models/BoxOrder.php`
- `app/Models/BoxOrderItem.php`
- `app/Models/BoxTemplate.php`
- `resources/js/Pages/Pos/Box/Index.vue`
- `resources/js/Pages/Pos/Box/Create.vue`
- `database/migrations/*_create_box_orders_table.php`
- `database/migrations/*_create_box_order_items_table.php`
- `database/migrations/*_create_box_templates_table.php`

---

### Belva — Remaining Project Implementation
Implement the remaining projects:
- **Admin Dashboard:** `app/Http/Controllers/Admin/DashboardController.php`, `app/Services/DashboardService.php`
- **Partner Management:** `app/Http/Controllers/Admin/PartnerController.php`, `app/Models/Partner.php`
- **Consignment System:** `app/Http/Controllers/Pos/ConsignmentController.php`, `app/Services/ConsignmentService.php`, `app/Models/DailyConsignment.php`
- **User Management:** `app/Models/User.php`, authentication logic
- **Reporting:** `app/Services/ReportService.php`
- **Database:** Optimisasi migrasi, seeder, dan struktur database
- **UI Layout:** `resources/js/Layouts/*`, `resources/js/Components/*`

---

*© 2024/2025 Posita Development Team.*
