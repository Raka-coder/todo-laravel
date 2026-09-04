# Laravel Todo CRUD & Cloud Infrastructure

Aplikasi manajemen tugas (**Todo App**) modern dan reaktif yang dibangun di atas **Laravel 12**, **Livewire 4**, dan **Flux UI**. Proyek ini dirancang sebagai studi kasus komprehensif untuk pembelajaran **Cloud Computing** dan **DevOps**, dilengkapi dengan konfigurasi orkestrasi **Docker Multi-Container** (Nginx, PHP-FPM, MySQL 8.0) yang siap dideploy dalam 1 command pada lingkungan server virtualisasi **Proxmox VE** (KVM VM / LXC).

---

## 📋 Daftar Isi
1. [Fitur Utama](#-fitur-utama)
2. [Tech Stack](#-tech-stack)
3. [Prasyarat Sistem](#-prasyarat-sistem)
4. [Akun Demo](#-akun-demo)
5. [Panduan Menjalankan Aplikasi](#-panduan-menjalankan-aplikasi)
   - [Opsi A: Menjalankan Secara Lokal (Development)](#opsi-a-menjalankan-secara-lokal-development)
   - [Opsi B: Menjalankan dengan Docker (1 Command)](#opsi-b-menjalankan-dengan-docker-1-command)
6. [Arsitektur Sistem & Data Flow](#-arsitektur-sistem--data-flow)
   - [Struktur Direktori Proyek](#struktur-direktori-proyek)
   - [Arsitektur Container Docker](#arsitektur-container-docker)
   - [Database Schema](#database-schema)
7. [Variabel Lingkungan (Environment Variables)](#-variabel-lingkungan-environment-variables)
8. [Testing & Quality Assurance](#-testing--quality-assurance)
9. [Panduan Deployment Proxmox VE](#-panduan-deployment-proxmox-ve)
10. [Daftar Perintah (Available Scripts)](#-daftar-perintah-available-scripts)
11. [Troubleshooting & Solusi Masalah](#-troubleshooting--solusi-masalah)

---

## 🌟 Fitur Utama

- **Reaktif Tanpa Reload (Livewire 4 Single-File Components)**: Pembaruan UI instan dengan performa tinggi.
- **Desain UI Elegan (Flux UI & Tailwind CSS v4)**: Komponen input modern, modal, badge prioritas, dan statistik kartu.
- **CRUD Todo Lengkap**:
  - **Create**: Tambah tugas dengan Judul, Deskripsi/Catatan detail, Tingkat Prioritas (*Low*, *Medium*, *High*), dan Batas Waktu (*Due Date*).
  - **Read**: Ringkasan statistik tugas (*Total, Tertunda, Selesai*), filter tab status, filter level prioritas, dan pencarian instan (debounced).
  - **Update**: Edit tugas secara langsung melalui modal interaktif Flux UI.
  - **Toggle Status**: Satu klik checkbox untuk menandai tugas selesai / belum selesai.
  - **Delete**: Dialog modal konfirmasi untuk mencegah penghapusan yang tidak disengaja.
  - **Overdue Indicator**: Penanda otomatis berwarna merah jika batas waktu tugas telah terlewat.
- **User Scoping & Multi-Tenancy**: Data Todo terisolasi secara ketat per user yang terotentikasi.
- **Autentikasi Aman (Laravel Fortify)**: Login, Register, Password Reset, Email Verification, Two-Factor Authentication (2FA), dan Passkeys (WebAuthn).
- **Docker Multi-Stage Build**: Image produksi PHP-FPM yang ramping dengan pra-kompilasi asset Vite melalui Node.js 22 alpine.
- **Persistent Data Volume**: Data database MySQL tersimpan aman pada Docker Named Volume (`db_data`) dan tidak hilang saat container direstart/rebuild.

---

## 🛠️ Tech Stack

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 12.x | PHP 8.3+ Framework |
| **Reactivity Layer** | Livewire 4.x (SFC) | Single-File Component architecture |
| **UI Components** | Livewire Flux 2.x | Official component library for Livewire |
| **CSS & Bundler** | Tailwind CSS v4 + Vite 8.x | Modern utility-first CSS framework |
| **Authentication** | Laravel Fortify | Headless 2FA, Passkeys & Session auth |
| **Database** | SQLite (Dev) / MySQL 8.0 (Docker/Prod) | Relational Database |
| **Web Server** | Nginx Alpine | Reverse Proxy & FastCGI handler |
| **Application Server** | PHP-FPM 8.3 Alpine | Multi-stage containerized PHP |
| **Testing Suite** | Pest PHP 5.x & PHPUnit | Automated Feature & Unit testing |
| **Code Style** | Laravel Pint | Automated PHP code formatter |

---

## 💻 Prasyarat Sistem

### Untuk Menjalankan Lokal:
- **PHP**: `^8.3` (dengan ekstensi: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `intl`)
- **Composer**: `^2.x`
- **Node.js**: `^20.x` atau `^22.x` & **NPM**: `^10.x`
- **SQLite3** atau **MySQL**

### Untuk Menjalankan via Docker / Proxmox:
- **Docker Engine**: `^24.0+`
- **Docker Compose**: `v2.x` (`docker compose`)

---

## 🔑 Akun Demo

Aplikasi menyediakan seeder khusus untuk membuat akun demo siap pakai beserta contoh daftar tugas (*Todos*) berbagai status dan prioritas:

- **URL Login:** `http://localhost/login` (atau `http://<IP_SERVER>/login`)
- **Email:** `demo@example.com`
- **Password:** `password`

*(Anda juga dapat mendaftarkan akun baru secara mandiri melalui menu **Register**).*

---

## 🚀 Panduan Menjalankan Aplikasi

### Opsi A: Menjalankan Secara Lokal (Development)

1. **Clone repository dan masuk ke direktori:**
   ```bash
   git clone <repository_url> todo-crud
   cd todo-crud
   ```

2. **Jalankan script setup otomatis:**
   ```bash
   composer run setup
   ```
   *Perintah ini akan menginstall dependensi composer, membuat `.env`, generate `APP_KEY`, menjalankan migrasi SQLite, menginstall npm packages, dan membuild asset frontend.*

3. **Jalankan Seeder untuk Akun Demo:**
   ```bash
   php artisan db:seed
   ```

4. **Jalankan Development Server:**
   ```bash
   composer run dev
   ```
   *(Atau jalankan `php artisan dev` yang akan mengaktifkan server PHP dan Vite secara bersamaan).*

5. Buka di browser: **[http://localhost:8000](http://localhost:8000)**

---

### Opsi B: Menjalankan dengan Docker (1 Command)

Sangat direkomendasikan untuk testing cloud computing, VM Proxmox, atau environment production.

1. **Salin file template environment Docker:**
   ```bash
   cp .env.docker.example .env
   ```

2. **Generate `APP_KEY` dan atur konfigurasi `.env`:**
   ```bash
   # Generate key acak 32-byte
   echo "base64:$(openssl rand -base64 32)"
   ```
   Buka file `.env` dan masukkan key di atas pada `APP_KEY=base64:...` serta sesuaikan `APP_URL`:
   ```dotenv
   APP_NAME="Todo Cloud App"
   APP_ENV=production
   APP_KEY=base64:PASTE_HASIL_GENERATE_KEY_DI_SINI
   APP_DEBUG=false
   APP_URL=http://localhost

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=todo_app
   DB_USERNAME=todo_user
   DB_PASSWORD=secret123
   DB_ROOT_PASSWORD=root_secret123
   ```

3. **Jalankan Docker Compose (1 Command):**
   ```bash
   docker compose up -d --build
   ```
   Proses ini akan:
   - Meng-compile frontend assets dengan Node.js 22 alpine.
   - Membangun container PHP-FPM 8.3 alpine dan mengoptimalkan autoloader.
   - Menjalankan container database MySQL 8.0 dengan healthcheck otomatis.
   - Menjalankan migrasi database otomatis secara aman saat startup (`entrypoint.sh`).
   - Mengaktifkan Nginx web server di port 80.

4. **Isi Database dengan Data Akun Demo:**
   ```bash
   docker compose exec app php artisan db:seed --force
   ```

5. **Akses Aplikasi:**
   Buka browser di **[http://localhost](http://localhost)** (atau IP server Proxmox Anda).

---

## 🏗️ Arsitektur Sistem & Data Flow

### Struktur Direktori Proyek

```
todo-crud/
├── app/
│   ├── Models/
│   │   ├── Todo.php                  # Eloquent Model Todo (fillable, casts, user relation)
│   │   └── User.php                  # Model User dengan relasi hasMany Todo
│   └── Providers/                    # Service Providers
├── database/
│   ├── factories/
│   │   └── TodoFactory.php           # Factory model Todo untuk automated testing
│   ├── migrations/
│   │   └── 2026_09_04_134248_create_todos_table.php # Skema tabel todos
│   └── seeders/
│       └── DatabaseSeeder.php        # Seeder akun demo (demo@example.com) & sampel tasks
├── docker/
│   ├── entrypoint.sh                 # Container startup script (auto migrate & cache)
│   ├── nginx/
│   │   └── default.conf              # Nginx server block & FastCGI configuration
│   └── php/
│       ├── local.ini                 # Pengaturan memory_limit & upload size PHP
│       └── opcache.ini               # Konfigurasi akselerasi Zend Opcache
├── resources/
│   └── views/
│       ├── dashboard.blade.php       # Tampilan utama Dashboard (memuat komponen Todo)
│       ├── components/               # Komponen UI Blade umum
│       ├── flux/                     # Kumpulan komponen Flux UI
│       └── pages/
│           └── todo/
│               └── ⚡todo-manager.blade.php # Livewire 4 Single-File Component (SFC)
├── tests/
│   ├── Feature/
│   │   ├── TodoModelTest.php         # Test unit/feature Model Todo & relasi User
│   │   ├── TodoLivewireTest.php      # Test reaktivitas Livewire Todo CRUD & security
│   │   └── DatabaseSeederTest.php    # Test integritas seeder demo
├── Dockerfile                        # Multi-Stage Build (Node.js 22 + PHP-FPM 8.3)
├── docker-compose.yml                # Orkestrasi Nginx + PHP-FPM + MySQL
├── DEPLOYMENT.md                     # Panduan operasional container
└── PROXMOX_GUIDE.md                  # Panduan lengkap Proxmox VE, VM, SSH, & Hardening
```

### Arsitektur Container Docker

```
  [ Client / Web Browser ]
             │
             ▼ Port 80
 ┌─────────────────────────────────────────────────────────┐
 │                   Nginx (Alpine)                        │
 │  - Serves static compiled assets directly from /public  │
 │  - Reverse proxy FastCGI requests                       │
 └───────────────────────────┬─────────────────────────────┘
                             │ FastCGI (Port 9000)
 ┌───────────────────────────▼─────────────────────────────┐
 │                PHP-FPM 8.3 Application                  │
 │  - Built via Multi-Stage Dockerfile (Node.js -> PHP)    │
 │  - Runs Laravel 12 + Livewire 4 SFC                     │
 │  - Non-destructive auto-migration via entrypoint.sh     │
 └───────────────────────────┬─────────────────────────────┘
                             │ MySQL TCP (Port 3306)
 ┌───────────────────────────▼─────────────────────────────┐
 │                  MySQL 8.0 Database                     │
 │  - Volume: db_data (Persistent storage)                 │
 │  - Auto-healthchecked before app initialization         │
 └─────────────────────────────────────────────────────────┘
```

### Database Schema

```
users
├── id (bigint, PK)
├── name (string)
├── email (string, unique)
├── email_verified_at (timestamp, nullable)
├── password (string, hashed)
├── remember_token (string, nullable)
├── two_factor_secret (text, nullable)
├── two_factor_recovery_codes (text, nullable)
├── two_factor_confirmed_at (timestamp, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)

todos
├── id (bigint, PK)
├── user_id (bigint, FK -> users.id, cascadeOnDelete)
├── title (string)
├── description (text, nullable)
├── is_completed (boolean, default: false)
├── priority (enum: ['low', 'medium', 'high'], default: 'medium')
├── due_date (date, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

---

## ⚙️ Variabel Lingkungan (Environment Variables)

| Variabel | Deskripsi | Default / Rekomendasi |
| :--- | :--- | :--- |
| `APP_NAME` | Nama aplikasi | `"Todo Cloud App"` |
| `APP_ENV` | Environment aplikasi (`local`, `production`) | `production` (di Docker) |
| `APP_KEY` | Kunci enkripsi 32-byte Laravel | Dihasilkan via `key:generate` |
| `APP_DEBUG` | Debug mode (`true` saat dev, `false` di prod) | `false` |
| `APP_URL` | URL absolut host aplikasi | `http://localhost` / `http://<IP_SERVER>` |
| `DB_CONNECTION` | Driver database | `sqlite` (lokal) / `mysql` (Docker) |
| `DB_HOST` | Hostname database server | `db` (nama service Docker) |
| `DB_PORT` | Port database server | `3306` |
| `DB_DATABASE` | Nama database MySQL | `todo_app` |
| `DB_USERNAME` | Username MySQL | `todo_user` |
| `DB_PASSWORD` | Password user MySQL | Password kuat |
| `DB_ROOT_PASSWORD` | Password root MySQL | Password kuat |
| `SESSION_DRIVER` | Driver penyimpanan sesi user | `database` |
| `CACHE_STORE` | Driver cache Laravel | `database` |

---

## 🧪 Testing & Quality Assurance

Proyek ini menerapkan prinsip **Test-Driven Development (TDD)** dan memiliki cakupan pengujian penuh menggunakan **Pest PHP**:

```bash
# Menjalankan seluruh test suite
php artisan test --compact
```

### Skenario Test yang Dicakup:
- ✅ **Model & Relations**: Memastikan model `Todo` berelasi `belongsTo` ke `User`, casting boolean dan date berfungsi, dan mass-assignment terlindungi.
- ✅ **Dashboard Integration**: Memastikan user login dapat melihat dashboard beserta komponen Todo miliknya.
- ✅ **Create Todo**: Validasi input judul, deskripsi, prioritas, dan tanggal jatuh tempo.
- ✅ **Toggle Completion**: Perubahan status selesai/aktif secara reaktif.
- ✅ **Edit & Update**: Pembaruan judul dan data tugas melalui Livewire state.
- ✅ **Delete Todo**: Penghapusan data tugas.
- ✅ **User Isolation & Authorization**: Memastikan user tidak dapat melihat, mengubah, atau menghapus Todo milik user lain (`403 Forbidden`).
- ✅ **Database Seeder Integrity**: Memastikan demo seeder berjalan idempoten dan menginisialisasi user demo dengan aman.

### Format Kode (Laravel Pint):
```bash
vendor/bin/pint --format agent
```

---

## ☁️ Panduan Deployment Proxmox VE

Untuk panduan mendalam langkah-demi-langkah setup Proxmox VE dari awal hingga siap produksi, silakan baca dokumentasi terpisah kami di:

👉 **[PROXMOX_GUIDE.md](./PROXMOX_GUIDE.md)**

Ringkasan tahapan Proxmox:
1. **Post-Install Script**: Konfigurasi repository `pve-no-subscription` dan mematikan subscription nag.
2. **SSH Key Windows**: Pembuatan kunci `Ed25519` di PowerShell dan konfigurasi alias `~/.ssh/config`.
3. **Ubuntu VM Creation**: Pembuatan VM Cloud-Init menggunakan Proxmox Community Helper Script.
4. **Hardening**: Menonaktifkan password login SSH (`PasswordAuthentication no`) dan mengaktifkan firewall UFW.
5. **Docker Execution**: Menjalankan `docker compose up -d --build` dan `php artisan db:seed --force`.

---

## 📜 Daftar Perintah (Available Scripts)

| Perintah | Deskripsi |
| :--- | :--- |
| `composer run setup` | Menyiapkan dependensi, env, database sqlite, dan build frontend secara otomatis |
| `composer run dev` | Menjalankan server development (PHP Artisan serve + Vite) |
| `composer run test` | Menjalankan lint check, static analysis PHPStan, dan Pest test suite |
| `composer run lint` | Memformat kode PHP sesuai standar PSR/Laravel Pint |
| `npm run build` | Meng-compile asset frontend CSS & JS dengan Vite untuk produksi |
| `docker compose up -d --build` | Membangun dan menjalankan seluruh stack container di background |
| `docker compose ps` | Memeriksa status kesehatan (*health status*) seluruh container |
| `docker compose logs -f` | Memantau log real-time dari seluruh container |
| `docker compose down` | Menghentikan dan membersihkan container (volume data tetap aman) |
| `docker compose exec app php artisan db:seed --force` | Menjalankan seeder akun demo di dalam container aplikasi |

---

## 🔧 Troubleshooting & Solusi Masalah

### 1. Masalah: "Vite manifest not found" saat membuka aplikasi
- **Penyebab**: Asset frontend belum dikompilasi atau cache manifest belum dibuat.
- **Solusi**:
  ```bash
  npm run build
  ```
  *(Jika menggunakan Docker, pastikan proses build selesai tanpa error).*

### 2. Masalah: Database connection refused di Docker
- **Penyebab**: Container PHP mencoba menyambung ke MySQL sebelum MySQL selesai inisialisasi awal.
- **Solusi**: `docker-compose.yml` telah dilengkapi dengan `condition: service_healthy` pada database MySQL. Tunggu beberapa detik hingga healthcheck MySQL berstatus `healthy`.

### 3. Masalah: Izin folder storage / cache di Linux/Docker
- **Solusi**: Pastikan permission folder storage dimiliki oleh `www-data`:
  ```bash
  docker compose exec app chown -R www-data:www-data storage bootstrap/cache
  docker compose exec app chmod -R 775 storage bootstrap/cache
  ```

### 4. Masalah: Permission Denied saat login SSH ke Ubuntu VM
- **Penyebab**: Public key SSH belum terinjeksi di Cloud-Init atau permission file `id_ed25519` terlalu terbuka.
- **Solusi**:
  - Di Proxmox Web GUI: Periksa tab **Cloud-Init** pada VM dan pastikan string Public Key Anda sudah ditempel, lalu klik **Regenerate Image** dan restart VM.
  - Di Windows PowerShell: Pastikan Anda menggunakan private key yang cocok: `ssh -i ~/.ssh/id_ed25519 ubuntu@<IP_VM>`.

---

## 📄 Lisensi

Proyek ini didistribusikan di bawah lisensi [MIT License](https://opensource.org/licenses/MIT).
