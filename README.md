```markdown
# 🏪 Aplikasi POS Konsinyasi UMKM (Web Apps)

Aplikasi manajemen point-of-sales sederhana yang dirancang khusus untuk toko retail yang menerima titipan barang (konsinyasi) dari berbagai UMKM. Aplikasi ini fokus pada pencatatan **Stok Masuk (Pagi)** dan **Stok Keluar (Malam)**, serta perhitungan otomatis keuntungan dan setoran mitra.

---

## 🚀 Tech Stack

Project ini dibangun menggunakan arsitektur **Monolith Modern** dengan teknologi berikut:

* **Backend Framework:** Laravel 12
* **Frontend Framework:** Vue.js 3 (via Inertia.js)
* **Admin Panel:** FilamentPHP v3 (Super Admin Dashboard)
* **Audit Trail:** Spatie Activitylog
* **Styling:** Tailwind CSS
* **Database:** MySQL (Eloquent ORM)
* **Authentication:** Laravel Breeze (Customized)

---

## 🏗️ Architecture & Design Patterns

We adhere to strict SOLID principles and Separation of Concerns. Please follow these patterns when contributing:

### 1. Action Pattern (`app/Actions`)
Business logic should NOT reside in Controllers. We use **Action Classes** for single-responsibility operations.
* **Usage**: Controllers should inject and `execute()` actions.
* **Example**: `StartDailyShopAction`, `CloseDailyShopAction`.

### 2. ViewModel Pattern (`app/ViewModels`)
Data preparation for views (especially Inertia/Vue) is handled by **ViewModels**.
* **Purpose**: Keeps Controllers clean and ensures view data is typed and testable.
* **Example**: `PosDashboardViewModel` prepares data for the Dashboard.

### 3. Observer Pattern (`app/Observers`)
Side-effects such as **Audit Logging** are handled by Model Observers to keep core logic pure.
* **Implementation**: `DailyConsignmentObserver` automatically logs "Shop Opened/Closed" events using `spatie/laravel-activitylog`.

### 🗂️ New Directory Structure

```tree
app/
├── Actions/          # Business Logic (Single Responsibility)
│   └── Consignment/
├── ViewModels/       # View Data Preparation
└── Observers/        # Model Events (Audit Logs)
```

---

## 👥 Tim Pengembang & Pembagian Tugas (Feature Based)

| Nama | Role | Fokus Pengerjaan |
| :--- | :--- | :--- |
| 👨‍💻 **Belva** | **Lead / Core System** | Setup Project, Database Schema, Keamanan (Auth/Roles), Filament Admin Panel, & Audit Log. |
| 👨‍💻 **Rivaldi** | **Feature Dev: Buka Kedai** | Mengembangkan fitur input stok pagi (`OpenShop`), kalkulasi Markup Harga, dan validasi stok awal. |
| 👨‍💻 **Amar** | **Feature Dev: Tutup Kedai** | Mengembangkan fitur stok opname malam (`CloseShop`), kalkulasi Profit/Revenue, dan penanganan retur barang. |
| 👩‍💻 **Nurita** | **Frontend Specialist** | UI/UX Design, Atomic Components (`ConsignmentCard`, `Button`), Styling Tailwind, & Mobile Responsiveness. |

---

## 🔒 Keamanan & Audit Trail (Privacy First)

Sistem ini menerapkan standar keamanan ketat untuk integritas data:

1.  **Role-Based Access Control (RBAC):**
    * **Super Admin:** Akses penuh ke Panel Filament (`/admin`) untuk manajemen User, Mitra, dan Laporan.
    * **Employee:** Akses khusus ke POS Dashboard (`/dashboard`) untuk input transaksi harian.
    * *Note:* Registrasi publik dinonaktifkan. Akun baru hanya bisa dibuat oleh Super Admin.
2.  **Audit Trail (Log Aktivitas):**
    * Mencatat setiap perubahan data (Create, Update, Delete) pada User, Partner, dan Transaksi.
    * Mencatat riwayat Login pengguna.
    * Log bersifat **Read-Only** dan bisa dipantau via menu "Audit Logs" di Panel Admin.

---

## 🛠️ Cara Install & Setup (Local Development)

Ikuti langkah-langkah ini untuk menjalankan project di komputer Anda.

### 1. Prasyarat
Pastikan sudah terinstall:
* PHP >= 8.4
* Composer
* Node.js & NPM
* MySQL (XAMPP/Laragon)

### 2. Clone Repository
```bash
git clone [https://github.com/belpythons/posita.git](https://github.com/belpythons/posita.git)
cd posita

```

### 3. Install Dependencies

Install library PHP dan JavaScript.

```bash
composer install
npm install

```

### 4. Setup Environment

Duplikat file `.env.example` dan ubah menjadi `.env`.

```bash
cp .env.example .env

```

Buka file `.env` dan sesuaikan koneksi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=retailer  # Pastikan buat database ini di phpMyAdmin
DB_USERNAME=root
DB_PASSWORD=

```

### 5. Generate Key & Migrasi

Penting: Migrasi juga akan membuat tabel untuk `activity_log`.

```bash
php artisan key:generate
php artisan migrate

```

### 6. Buat Akun Super Admin

Jalankan perintah ini untuk membuat user yang bisa akses panel `/admin`.

```bash
php artisan make:filament-user
# Ikuti instruksi di terminal (Nama, Email, Password)

```

### 7. Jalankan Aplikasi

Buka dua terminal terpisah:

**Terminal 1 (Backend Server):**

```bash
php artisan serve

```

**Terminal 2 (Frontend Compiler):**

```bash
npm run dev

```

Akses Aplikasi:

* **Login:** `http://localhost:8000/login`
* *Super Admin* -> Redirect ke `/admin`
* *Employee* -> Redirect ke `/dashboard`



---

## 📂 Struktur Folder Penting (Zona Kerja Tim)

Agar tidak terjadi *conflict* saat push code, perhatikan area kerja masing-masing:

* **Belva (Core & Admin):**
* `app/Models/*`
* `database/migrations/*`
* `app/Filament/*`
* `routes/auth.php`


* **Rivaldi (Fitur Buka Kedai):**
* `app/Http/Controllers/PosController.php` (Method: `storeOpen`)
* `resources/js/Pages/Pos/OpenShop.vue`


* **Amar (Fitur Tutup Kedai):**
* `app/Http/Controllers/PosController.php` (Method: `updateClose`)
* `resources/js/Pages/Pos/CloseShop.vue`
* `resources/js/Pages/Pos/Partials/CloseShopItem.vue`


* **Nurita (UI Components):**
* `resources/js/Components/*`
* `resources/js/Layouts/EmployeeLayout.vue`



---

## 📝 Alur Penggunaan Aplikasi (User Flow)

1. **Setup Awal (Super Admin):**
* Login ke `/admin`.
* Menu **Users**: Buat akun untuk Karyawan (Role: Employee).
* Menu **Partners**: Tambah data Mitra tetap.


2. **Buka Kedai - Input Pagi (Karyawan - Rivaldi):**
* Login menggunakan akun Employee.
* Pilih menu **"Buka Kedai"**.
* Pilih Mitra & Input Barang (Nama, Stok, Modal).
* Sistem menghitung Harga Jual otomatis.


3. **Tutup Kedai - Input Malam (Karyawan - Amar):**
* Pilih menu **"Tutup Kedai"**.
* Input **Sisa Stok (Fisik)**.
* Simpan. Sistem menghitung Profit & Revenue.


4. **Monitoring (Super Admin - Belva):**
* Cek Dashboard `/admin` untuk laporan keuangan.
* Cek menu **Audit Logs** untuk memantau aktivitas karyawan.



---

## ⚠️ Catatan Penting

* **Filament Version:** Project ini menggunakan Filament **v3**.
* **Laravel Version:** Laravel 12.
* **Security:** Password user baru di-hash otomatis. Reset password bisa dilakukan oleh Admin.

---