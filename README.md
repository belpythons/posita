# POSITA - Point of Sales for UMKM Retailers

**POSITA** adalah aplikasi Kasir (Point of Sales) modern yang dirancang khusus untuk membantu UMKM retail mengelola penjualan, stok konsinyasi, dan pelaporan harian. Aplikasi ini dibangun dengan arsitektur **Modern Monolith** yang memisahkan logika operasional kasir (Frontend) dan manajemen pusat (Admin Panel).

## 🛠️ Tech Stack

Project ini dibangun menggunakan teknologi terkini di ekosistem Laravel:

* **Backend Framework:** Laravel 12
* **Frontend Framework:** Vue.js 3 (via Inertia.js)
* **Admin Panel:** FilamentPHP v3 (Super Admin Dashboard)
* **Database:** MySQL
* **Styling:** Tailwind CSS 4.0
* **Audit Trail:** Spatie Activitylog
* **Authentication:** Laravel Breeze (Customized with Role-based Guards)

---

## 🏗️ Architecture & Design Patterns

Untuk menjaga kode tetap bersih, mudah di-maintain, dan *scalable*, project ini menerapkan beberapa **Design Patterns** dan prinsip **Domain-Driven Design (DDD)** ringan:

### 1. Action Pattern (Business Logic)
Kami memindahkan logika bisnis yang kompleks dari Controller ke dalam **Action Classes**. Hal ini memastikan prinsip *DRY (Don't Repeat Yourself)*, di mana logika yang sama bisa dipanggil baik dari controller Vue maupun Filament.

* **Lokasi:** `app/Actions/`
* **Contoh:**
    * `StartDailyShopAction`: Menangani validasi dan pembukaan sesi toko harian.
    * `CloseDailyShopAction`: Menghitung varian kas, rekap penjualan, dan menutup sesi.

### 2. ViewModel Pattern (Data Presentation)
Untuk menghindari "Fat Controller", kami menggunakan **ViewModel** untuk mempersiapkan data yang akan dikirim ke tampilan (Inertia/Vue). ViewModel membungkus data dari berbagai model menjadi satu objek yang rapi.

* **Lokasi:** `app/ViewModels/`
* **Contoh:** `PosDashboardViewModel` menyiapkan data mitra, sesi aktif, dan statistik harian untuk dashboard kasir.

### 3. Observer Pattern (Audit & Side Effects)
Pencatatan log aktivitas (Audit Trail) dilakukan secara otomatis menggunakan **Observer Pattern**. Controller tidak perlu tahu tentang proses logging.

* **Lokasi:** `app/Observers/`
* **Contoh:** `DailyConsignmentObserver` otomatis mencatat log ke tabel `activity_log` setiap kali toko dibuka atau ditutup.

### 4. Role-Based Access Control (Separated Auth)
Sistem login dipisahkan secara ketat untuk keamanan:
* **Super Admin:** Hanya bisa login via `/admin` (Filament Panel).
* **Retailer (Kasir):** Hanya bisa login via `/login` (Inertia UI) dan akan di-redirect jika mencoba akses admin.

### 📂 Struktur Folder Penting
```text
app/
├── Actions/          <-- Logika Bisnis (Buka/Tutup Toko)
├── Filament/         <-- Admin Panel Resources
├── Http/
│   ├── Controllers/  <-- Controller Tipis (Hanya memanggil Action)
│   └── Middleware/   <-- Proteksi Role
├── Models/
├── Observers/        <-- Otomasi Activity Log
└── ViewModels/       <-- Penyiapan Data View

```

---

## 👥 Tim & Pembagian Tugas (Jobdesk)

Pengembangan fitur dibagi berdasarkan modul spesifik untuk efisiensi kerja:

### 👨‍✈️ Belva (Team Lead & System Architect)

* **Tanggung Jawab:** Integrasi sistem, keamanan, dan manajemen role.
* **Implementasi:**
* Refactoring kode menerapkan Design Patterns (Action, ViewModel, Observer).
* Membuat proteksi Middleware & Login Redirect (Admin vs Retailer).
* Implementasi `spatie/activitylog` pada Filament (`ActivityLogResource`).
* Setup Server & Deployment.



### 👨‍💻 Rivaldi (Fitur "Buka Kedai")

* **Tanggung Jawab:** Alur pembukaan toko dan input stok awal.
* **Implementasi:**
* Backend: Logic `StartDailyShopAction`.
* Frontend: Halaman `Pos/OpenShop.vue`.
* Fitur: Input modal awal, pemilihan mitra, dan kalkulasi harga jual otomatis (Markup logic).



### 👨‍💻 Amar (Fitur "Tutup Kedai")

* **Tanggung Jawab:** Rekapitulasi harian dan penutupan buku.
* **Implementasi:**
* Backend: Logic `CloseDailyShopAction`.
* Frontend: Halaman `Pos/CloseShop.vue`.
* Fitur: Input uang aktual, kalkulasi selisih (variance), dan ringkasan penjualan harian.



### 👩‍💻 Nurita (UI/UX & Theming)

* **Tanggung Jawab:** Antarmuka pengguna dan pengalaman visual.
* **Implementasi:**
* Styling Global: Menentukan palet warna (Biru/Orange) dan Typography.
* Komponen: Membuat `ToastNotification.vue`, Card Layout, dan responsivitas mobile.
* Frontend: Memastikan transisi antar halaman (Inertia) berjalan mulus.



---

## 🚀 Panduan Instalasi (Installation Guide)

Ikuti langkah-langkah berikut untuk menjalankan project di local environment Anda:

### Prasyarat

* PHP >= 8.4
* Composer
* Node.js & NPM
* MySQL

### Langkah 1: Clone Repository

```bash
git clone [https://github.com/username/posita.git](https://github.com/username/posita.git)
cd posita

```

### Langkah 2: Install Dependencies

Install paket PHP dan JavaScript:

```bash
composer install
npm install

```

### Langkah 3: Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env

```

Buka file `.env` dan sesuaikan konfigurasi database Anda:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=retailer
DB_USERNAME=root
DB_PASSWORD=

```

### Langkah 4: Generate Key & Migrate Database

Generate application key dan jalankan migrasi database beserta seeder (data dummy):

```bash
php artisan key:generate
php artisan migrate:fresh --seed

```

*> **PENTING:** Perintah `--seed` wajib dijalankan agar Anda memiliki akun Super Admin dan Retailer untuk login.*

### Langkah 5: Jalankan Aplikasi

Anda perlu menjalankan dua terminal terpisah:

**Terminal 1 (Vite Development Server):**

```bash
npm run dev

```

**Terminal 2 (Laravel Server):**

```bash
php artisan serve

```

Akses aplikasi di: `http://localhost:8000`

---

## 🔐 Akun Demo (Credentials)

Gunakan akun berikut untuk pengujian sistem (dibuat oleh Seeder):

### 1. Super Admin (Akses Filament Panel)

* **URL:** `http://localhost:8000/admin`
* **Email:** `admin@posita.test`
* **Password:** `password`
* *Fitur:* Manajemen User, Master Data Partner, Monitoring Activity Log.

### 2. Retailer / Kasir (Akses POS Dashboard)

* **URL:** `http://localhost:8000/login`
* **Email:** `retailer@posita.test`
* **Password:** `password`
* *Fitur:* Buka Toko, Transaksi, Tutup Toko.

---
