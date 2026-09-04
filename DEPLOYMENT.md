# Panduan Deployment Cloud Computing (Proxmox & Docker)

Panduan ini menjelaskan cara menjalankan dan mendeploy aplikasi Laravel Todo CRUD menggunakan arsitektur multi-container Docker di lingkungan server / Proxmox VE (Virtual Machine atau LXC Container).

---

## 1. Arsitektur Infrastruktur

Aplikasi ini menggunakan 3 service container terpisah yang terhubung dalam 1 bridge network:

1. **Web Server (`webserver`)**: Nginx Alpine sebagai reverse proxy di port `80` (HTTP).
2. **Application Server (`app`)**: PHP-FPM 8.3 Alpine yang dibangun menggunakan *Multi-Stage Dockerfile* (meng-compile frontend assets Vite via Node.js 22 alpine terlebih dahulu, lalu menyalinnya ke image PHP yang ramping).
3. **Database Server (`db`)**: MySQL 8.0 dengan named persistent volume `db_data` (data tidak hilang saat container restart).

```
  [ Client / Browser ]
           │
           ▼ Port 80
 ┌───────────────────────────────────────────────┐
 │               Nginx Webserver                 │
 └───────────────────────┬───────────────────────┘
                         │ FastCGI (Port 9000)
 ┌───────────────────────▼───────────────────────┐
 │             PHP-FPM 8.3 (Laravel)             │
 └───────────────────────┬───────────────────────┘
                         │ MySQL TCP (Port 3306)
 ┌───────────────────────▼───────────────────────┐
 │            MySQL 8.0 Database                 │
 │            (Volume: db_data)                  │
 └───────────────────────────────────────────────┘
```

---

## 2. Prasyarat Server (Proxmox VM / LXC)

Pastikan VM atau LXC Container di Proxmox sudah terinstall:
- Docker Engine (`>= 24.0`)
- Docker Compose v2 (`docker compose`)
- Git

> **Catatan Proxmox LXC**: Jika menggunakan LXC Container di Proxmox, pastikan opsi **Nesting** dan **Keyctl** diaktifkan pada pengaturan *Options / Features* LXC.

---

## 3. Menjalankan Aplikasi dalam 1 Command

### Langkah 1: Clone Repository & Masuk ke Direktori
```bash
git clone <repository_url> todo-crud
cd todo-crud
```

### Langkah 2: Buat File Environment & App Key
Copy file template environment docker:
```bash
cp .env.docker.example .env
```

Generate `APP_KEY` unik:
```bash
# Opsi 1: Menggunakan command PHP lokal jika terinstall
php artisan key:generate --show

# Opsi 2: Menggunakan string base64 acak 32-byte
echo "base64:$(openssl rand -base64 32)"
```
Salin key tersebut ke dalam file `.env` pada variabel `APP_KEY=base64:...`

Sesuaikan konfigurasi lainnya pada `.env`:
- `APP_URL=http://<IP_SERVER_PROXMOX>` (contoh: `http://192.168.1.110`)
- `DB_PASSWORD` & `DB_ROOT_PASSWORD` (ganti dengan password yang aman)

---

### Langkah 3: Jalankan Container (1 Command)
```bash
docker compose up -d --build
```

Command ini akan secara otomatis:
1. Membangun image frontend (Node.js 22 meng-compile asset Vite).
2. Membangun image PHP-FPM 8.3 dengan dependensi composer `--no-dev` & ekstensi PHP lengkap.
3. Menjalankan container database MySQL 8.0 dan menunggu healthcheck ready.
4. Menjalankan migrasi database otomatis secara aman melalui `entrypoint.sh` (tanpa drop data).
5. Menyalakan Nginx web server di port 80.

---

### Langkah 4: Jalankan Demo Seeder (1 Akun Demo)
Untuk mengisi akun demo beserta contoh data Todo, jalankan perintah seeder di dalam container:

```bash
docker compose exec app php artisan db:seed --force
```

#### 🔑 Kredensial Akun Demo:
- **URL Login:** `http://<IP_SERVER_PROXMOX>/login`
- **Email:** `demo@example.com`
- **Password:** `password`

> *Catatan:* Akun demo ini langsung dilengkapi dengan 6 data Todo contoh mencakup berbagai status (Selesai/Tertunda), batas waktu (due date), dan level prioritas (High, Medium, Low). Anda juga bisa membuat akun baru secara mandiri melalui menu **Register**.

---

## 4. Perintah Operasional & Maintenance

### Melihat Status Container
```bash
docker compose ps
```

### Melihat Log Aplikasi
```bash
# Log seluruh container
docker compose logs -f

# Log container PHP Laravel
docker compose logs -f app

# Log container Nginx
docker compose logs -f webserver

# Log container Database MySQL
docker compose logs -f db
```

### Menjalankan Perintah Artisan di Container
```bash
docker compose exec app php artisan <command>

# Contoh:
docker compose exec app php artisan route:list
docker compose exec app php artisan config:clear
```

### Menghentikan Container
```bash
docker compose down
```
*(Data database tetap aman tersimpan di Docker Named Volume `db_data`)*

---

## 5. Fitur Aplikasi Todo CRUD

- **Create**: Tambah tugas baru dengan judul, deskripsi, prioritas (Low, Medium, High), dan tanggal jatuh tempo (due date).
- **Read**: Tampilan ringkasan statistik (Total, Tertunda, Selesai), pencarian cepat real-time, dan filter status/prioritas.
- **Update**: Edit tugas langsung melalui modal Flux UI.
- **Toggle Status**: Satu klik untuk menandai selesai / belum selesai.
- **Delete**: Hapus tugas dengan dialog konfirmasi aman.
- **User Scoping**: Setiap user memiliki akses data tugas yang terisolasi sepenuhnya.
