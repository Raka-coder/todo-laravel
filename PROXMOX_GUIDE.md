# Panduan Lengkap: Setup Proxmox VE, Ubuntu VM, SSH Key Windows & Deployment Docker Todo App

Panduan ini berisi langkah-langkah *best practice* dari awal setup Proxmox VE, pembuatan Ubuntu Server VM menggunakan Helper Script, konfigurasi autentikasi SSH Key dari Windows, pengerasan keamanan (hardening), hingga menjalankan aplikasi Todo CRUD berbasis Docker multi-container.

---

## Daftar Isi
1. [Fase 1: Optimasi Awal Proxmox VE (Post-Install)](#1-fase-1-optimasi-awal-proxmox-ve-post-install)
2. [Fase 2: Pembuatan SSH Key di Windows](#2-fase-2-pembuatan-ssh-key-di-windows)
3. [Fase 3: Pembuatan Ubuntu VM di Proxmox](#3-fase-3-pembuatan-ubuntu-vm-di-proxmox)
4. [Fase 4: Koneksi SSH & Hardening Server Ubuntu](#4-fase-4-koneksi-ssh--hardening-server-ubuntu)
5. [Fase 5: Setup Docker & Deployment Todo App](#5-fase-5-setup-docker--deployment-todo-app)
6. [Fase 6: Backup & Maintenance](#6-fase-6-backup--maintenance)

---

## 1. Fase 1: Optimasi Awal Proxmox VE (Post-Install)

Setelah Proxmox VE selesai diinstal, buka **Shell Proxmox** melalui Web Console (`https://<ip-proxmox>:8006`) atau SSH ke root Proxmox.

### A. Jalankan Post-Install Helper Script
Eksekusi script komunitas terverifikasi untuk mengonfigurasi repository gratis dan mematikan notifikasi subscription berbayar:

```bash
bash -c "$(wget -qLO - https://github.com/community-scripts/ProxmoxVE/raw/main/misc/post-pve-install.sh)"
```

**Opsi yang dipilih pada wizard:**
- *Disable PVE Enterprise Repository?* -> **Yes**
- *Enable PVE No-Subscription Repository?* -> **Yes**
- *Correct Ceph Repositories?* -> **Yes**
- *Disable Subscription Nag?* -> **Yes**
- *Update Debian Package lists and upgrade system?* -> **Yes**

---

## 2. Fase 2: Pembuatan SSH Key di Windows

Gunakan **Windows PowerShell** (atau Windows Terminal) di komputer lokal Anda.

### A. Generate SSH Key Pair (Ed25519)
```powershell
ssh-keygen -t ed25519 -C "admin-proxmox-todo"
```
- Tekan `Enter` untuk menyimpan di default path (`C:\Users\<Username>\.ssh\id_ed25519`).
- Masukkan passphrase jika ingin proteksi ekstra (atau tekan `Enter` dua kali untuk tanpa passphrase).

### B. Salin String Public Key
Tampilkan isi public key di PowerShell:
```powershell
Get-Content ~\.ssh\id_ed25519.pub
```
Contoh output:
```text
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIH... admin-proxmox-todo
```
*(Salin teks ini lengkap, karena akan ditempelkan ke konfigurasi Cloud-Init VM)*.

### C. Buat Konfigurasi Shortcut SSH di Windows
Buka atau buat file konfigurasi SSH client:
```powershell
notepad ~\.ssh\config
```
Tambahkan entri berikut:
```ssh-config
# Shortcut ke Host Proxmox VE
Host pve
    HostName 192.168.1.100
    User root
    IdentityFile ~/.ssh/id_ed25519

# Shortcut ke Ubuntu VM (Aplikasi Todo)
Host todo-server
    HostName 192.168.1.110
    User ubuntu
    IdentityFile ~/.ssh/id_ed25519
```
*(Sesuaikan IP `HostName` dengan subnet jaringan Anda)*.

---

## 3. Fase 3: Pembuatan Ubuntu VM di Proxmox

Untuk menjalankan Docker dengan isolasi penuh dan modul kernel lengkap, gunakan **Ubuntu Server KVM Virtual Machine** dengan fitur Cloud-Init.

### A. Eksekusi Script Pembuat Ubuntu VM
Di **Shell Proxmox**, jalankan script berikut:
```bash
bash -c "$(wget -qLO - https://github.com/community-scripts/ProxmoxVE/raw/main/vm/ubuntu-vm.sh)"
```

### B. Rekomendasi Parameter Konfigurasi VM:
- **VM ID**: `100` (atau biarkan default)
- **OS Release**: `Ubuntu 24.04 (Noble)` atau `Ubuntu 22.04 (Jammy)`
- **CPU Cores**: `2 vCPU` (cukup untuk build container & runtime Laravel)
- **RAM**: `2048 MB (2 GB)`
- **Disk Size**: `20 GB` hingga `32 GB`
- **Storage Target**: `local-lvm` atau `local-zfs`
- **QEMU Guest Agent**: **Yes**

### C. Konfigurasi Cloud-Init Sebelum Menyalakan VM
1. Pada Proxmox Web GUI, pilih VM yang baru dibuat -> Pilih tab **Cloud-Init**.
2. **User**: `ubuntu`
3. **Password**: Buat password yang kuat (sebagai cadangan).
4. **SSH Public Key**: Tempelkan isi public key dari Windows (`ssh-ed25519 AAAAC3...`).
5. **IP Config (net0)**:
   - Pilih **Static** -> IPv4/CIDR: `192.168.1.110/24`, Gateway: `192.168.1.1` (atau gunakan DHCP jika router Anda sudah mereservasi IP).
6. Klik **Regenerate Image**.
7. Klik tombol **Start** pada VM.

---

## 4. Fase 4: Koneksi SSH & Hardening Server Ubuntu

### A. Hubungkan ke Server dari Windows
Buka Windows PowerShell dan jalankan:
```powershell
ssh todo-server
```
*(Atau jika tanpa file config: `ssh -i ~\.ssh\id_ed25519 ubuntu@192.168.1.110`)*.

### B. Update & Upgrade Paket OS
```bash
sudo apt update && sudo apt upgrade -y
```

### C. Hardening SSH (Nonaktifkan Password Login)
Pastikan server hanya dapat diakses melalui SSH Key:
```bash
sudo nano /etc/ssh/sshd_config.d/50-cloud-init.conf
```
*(Jika file di atas tidak ada, edit `/etc/ssh/sshd_config`)*.

Pastikan baris berikut diset:
```text
PasswordAuthentication no
PermitRootLogin prohibit-password
PubkeyAuthentication yes
```
Simpan (`Ctrl+O`, `Enter`, `Ctrl+X`), lalu restart SSH service:
```bash
sudo systemctl restart ssh
```

### D. Setup Firewall UFW
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp comment 'SSH'
sudo ufw allow 80/tcp comment 'HTTP Nginx'
sudo ufw allow 443/tcp comment 'HTTPS'
sudo ufw enable
```

---

## 5. Fase 5: Setup Docker & Deployment Todo App

### A. Install Docker Engine Resmi di Ubuntu VM
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Tambahkan user ubuntu ke grup docker (agar tidak perlu sudo saat docker)
sudo usermod -aG docker $USER
newgrp docker
```
Verifikasi Docker:
```bash
docker --version
docker compose version
```

### B. Clone Proyek & Siapkan Environment
```bash
git clone <URL_REPOSITORY_ANDA> todo-crud
cd todo-crud

cp .env.docker.example .env
```

Generate `APP_KEY` dan edit file `.env`:
```bash
# Generate key 32-byte acak
echo "base64:$(openssl rand -base64 32)"

nano .env
```
Sesuaikan nilai konfigurasi berikut pada `.env`:
```dotenv
APP_NAME="Todo Cloud App"
APP_ENV=production
APP_KEY=base64:PASTE_HASIL_GENERATE_KEY_DI_SINI
APP_DEBUG=false
APP_URL=http://192.168.1.110

DB_DATABASE=todo_app
DB_USERNAME=todo_user
DB_PASSWORD=GantiDenganPasswordAman123!
DB_ROOT_PASSWORD=GantiDenganRootPasswordAman123!
```

---

### C. Jalankan Container (1 Command)
```bash
docker compose up -d --build
```

Container akan otomatis:
1. Mem-build asset Vite frontend dengan Node.js 22.
2. Mem-build image PHP-FPM 8.3 dengan ekstensi MySQL, Opcache, bcmath, dll.
3. Menjalankan database MySQL 8.0 dengan volume persistent `db_data`.
4. Menjalankan migrasi database otomatis secara aman via entrypoint (tanpa drop data).
5. Menyalakan Nginx web server di port 80.

---

### D. Jalankan Demo Seeder (1 Akun Demo)
Isi database dengan 1 akun demo beserta sampel data Todo:
```bash
docker compose exec app php artisan db:seed --force
```

#### 🔑 Kredensial Akun Demo:
- **URL Akses:** `http://192.168.1.110/login`
- **Email:** `demo@example.com`
- **Password:** `password`

*(Tersedia 6 data tugas contoh berbagai status & prioritas. Anda juga dapat mendaftarkan akun baru melalui tombol Register).*

---

### E. Akses Aplikasi di Browser
Buka browser di komputer Windows Anda:
```
http://192.168.1.110
```

---

## 6. Fase 6: Backup & Maintenance

1. **Jadwal Snapshot Backup di Proxmox**:
   - Web GUI Proxmox -> **Datacenter** -> **Backup** -> **Add**.
   - Pilih Node & VM ID `100`.
   - Jadwalkan (misal harian jam 02:00 pagi).
   - Mode: *Snapshot*, Kompresi: *ZSTD*.
2. **Log Rotation Docker**:
   - Buat `/etc/docker/daemon.json` untuk mencegah log container membesar tanpa batas:
   ```json
   {
     "log-driver": "json-file",
     "log-opts": {
       "max-size": "10m",
       "max-file": "3"
     }
   }
   ```
   - Restart daemon: `sudo systemctl restart docker`.
