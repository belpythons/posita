POSITA - Point of Sales & Inventory SystemAplikasi Kasir dan Manajemen Kedai berbasis web dengan arsitektur Laravel Inertia Vue dan Service Layer Pattern.🚀 Teknologi UtamaBackend: Laravel 11Frontend: Vue 3 + Inertia.jsStyling: Tailwind CSSDatabase: MySQL📂 Struktur Folder & ArsitekturProject ini menggunakan Service Layer Pattern untuk memisahkan logika bisnis dari Controller.1. Controllers (app/Http/Controllers)Hanya bertugas menerima request, memanggil Service, dan mengembalikan response (View).Admin/*: Controller khusus halaman Admin (Pemilik).Pos/*: Controller khusus halaman Kasir/Karyawan.2. Services (app/Services)Tempat semua logika bisnis ("Otak" aplikasi).ShopSessionService: Logika buka/tutup toko & hitung selisih uang.ConsignmentService: Logika input barang titipan & hitung bagi hasil.BoxOrderService: Logika pemesanan nasi kotak & upload bukti bayar.AdminDataService: CRUD master data (Partner, Template, User).3. Frontend Pages (resources/js/Pages)Admin/: Halaman-halaman dashboard admin.Pos/: Halaman-halaman operasional karyawan.Pos/Box/: Fitur khusus pemesanan box.🛠 Panduan Instalasi (Untuk Developer)Clone Repositorygit clone <repo_url>
cd posita

Install Backend Dependenciescomposer install

Setup Environmentcp .env.example .env
# Sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD di file .env
php artisan key:generate

Migrasi Database (Fresh Install)php artisan migrate:fresh --seed
# Ini akan membuat tabel users, partners, shop_sessions, daily_consignments, box_orders, dll.

Install Frontend Dependenciesnpm install
npm run build

Jalankan Server# Terminal 1
php artisan serve

# Terminal 2 (Untuk Hot Reload saat development)
npm run dev

👥 Pembagian Tugas (Job Desk)| Role | Developer | Fitur Utama || Admin & Core | Belva | Dashboard Admin, Master Data Partner, User Management, Database Schema. || POS System | Rivaldi | Buka Kedai (Input Stok), Tutup Kedai (Rekonsiliasi Kas), Laporan Harian. || Box Order | Amar | Katalog Paket Box, Form Pemesanan, Upload Bukti Bayar. || UI/UX | Nurita | Responsive Design (Mobile First), Layouting, Komponen Vue (Card, Modal, Badge). |⚠️ Aturan DevelopmentJangan Pakai Filament: Hapus folder app/Filament jika masih ada. Kita menggunakan Custom Vue Admin.Service Layer: Jangan taruh logika kompleks di Controller. Pindahkan ke app/Services.Mobile First: Pastikan tampilan karyawan (Pos/*) rapi saat dibuka di HP.
