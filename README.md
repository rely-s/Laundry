# Sistem Informasi Manajemen Queen Laundry Berbasis Web (PHP Native PDO)

Aplikasi web sistem informasi manajemen operasional dan keuangan untuk **Queen Laundry** Desa Simpang Belui Kabupaten Kerinci, dikembangkan berdasarkan proposal skripsi Denia (2026) dengan metodologi Waterfall.

---

## 🚀 Fitur Utama

### 1. Panel Publik
- **Beranda & Profil Usaha:** Menampilkan informasi layanan, keunggulan, dan daftar tarif terkini.
- **Lacak Status Cucian Mandiri (`/tracking.php`):** Pelanggan dapat memantau progres cucian secara real-time (*Diterima → Sedang Dicuci → Sedang Disetrika → Siap Diambil → Sudah Diambil*) cukup dengan memasukkan **Nama Pelanggan** (tanpa perlu nomor nota/token).

### 2. Panel Administrator (Kasir & Pemilik)
- **Dashboard Ringkasan KPI:** Kartu statistik pesanan harian, cucian dalam proses, siap diambil, dan total kas masuk bulan berjalan, dilengkapi grafik analitik Chart.js.
- **Manajemen Data Pelanggan:** CRUD pelanggan dengan pencarian instan nama dan nomor kontak WhatsApp.
- **Manajemen Paket Layanan & Tarif:** Pengaturan tarif per kilogram (kg) dan per satuan item dengan fitur nonaktifkan tarif (mencegah rusaknya data transaksi lama).
- **Transaksi Pesanan Multi-Item:** Form pesanan interaktif dengan kalkulasi otomatis subtotal dan total biaya, kode pesanan unik `QL-YYYYMM-XXXX`, serta **snapshot harga satuan** pada saat transaksi dibuat.
- **Cetak Nota Laundry:** Format struk siap cetak (printer thermal 58/80mm maupun A4).
- **Pembaruan Status & Jejak Audit:** Transisi status terpantau dengan pencatatan otomatis waktu dan petugas yang memperbarui status.
- **Pencatatan Kas Pembayaran:** Mendukung pembayaran tunai dan transfer manual, dengan proteksi larangan pembayaran melebihi sisa tagihan (*overpayment check*).
- **Laporan Keuangan Kas Masuk:** Filter periode tanggal, rekap pendapatan harian berbasis pembayaran yang sah, format siap cetak, dan ekspor data ke file CSV.

---

## 🛠️ Teknologi & Arsitektur

- **Backend:** PHP 8.x Native murni (tanpa framework) dengan **PDO**
- **Basis Data:** MySQL / MariaDB (XAMPP) dengan arsitektur InnoDB 7 tabel berelasi
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Chart.js, Vanilla JavaScript
- **Keamanan:**
  - PDO Prepared Statements (Anti-SQL Injection)
  - CSRF Token Protection pada seluruh form POST
  - Sanitasi XSS `htmlspecialchars()`
  - Password Hash menggunakan `bcrypt` (`password_hash`)
  - Session Fixation Prevention (`session_regenerate_id`)

---

## 📁 Struktur Direktori Proyek

```text
├── admin/                  # Modul backoffice administrator
│   ├── customers/          # CRUD data pelanggan
│   ├── orders/             # Transaksi, detail, dan cetak nota
│   ├── payments/           # Pencatatan kas pembayaran
│   ├── reports/            # Laporan keuangan dan ekspor CSV
│   ├── services/           # CRUD paket layanan dan tarif
│   ├── index.php           # Dashboard KPI & grafik Chart.js
│   └── profile.php         # Ganti password administrator
├── config/                 # Konfigurasi database & PDO singleton
├── database/               # Skema DDL InnoDB & seeder data awal
├── includes/               # Helper autentikasi, CSRF, finansial, format rupiah
├── templates/              # Header, sidebar, dan footer antarmuka
├── tests/                  # Suite pengujian otomatis Black Box (BB01-BB12)
├── index.php               # Halaman publik beranda
├── login.php               # Login admin dengan proteksi brute-force
├── logout.php              # Logout aman
├── tracking.php            # Pelacakan status cucian pelanggan
├── start_mysql.bat         # Skrip satu-klik MariaDB XAMPP
└── start_server.bat        # Skrip satu-klik web server PHP
```

---

## 💻 Panduan Instalasi & Menjalankan Proyek

### Prasyarat
- PHP 8.x
- XAMPP (MySQL / MariaDB)

---

### Cara 1: Menjalankan Manual Melalui Terminal VS Code (Direkomendasikan)

Buka terminal di VS Code dengan menekan tombol **`Ctrl + ` `** (backtick) atau melalui menu **Terminal > New Terminal**:

#### Langkah 1: Jalankan Database MariaDB (Terminal 1)
Ketik perintah berikut pada terminal:
```powershell
.\start_mysql.bat
```
> *Catatan: Biarkan tab terminal ini tetap terbuka (jangan ditutup).*

#### Langkah 2: Jalankan Web Server PHP (Terminal 2)
1. Buka tab terminal baru di VS Code dengan mengklik tombol **`+`** (*New Terminal*) di pojok kanan panel terminal.
2. Jalankan server lokal PHP:
```powershell
php -S 127.0.0.1:8000
```
Jika berhasil, terminal akan memunculkan status:
`[Development Server (http://127.0.0.1:8000) started]`

#### Langkah 3: Buka Aplikasi di Browser
Buka browser (Google Chrome / Edge) dan akses:
- **Halaman Utama Publik:** `http://127.0.0.1:8000`
- **Panel Login Admin:** `http://127.0.0.1:8000/login.php`
- **Pelacakan Status Cucian:** `http://127.0.0.1:8000/tracking.php`

#### Cara Menghentikan Server
Bila sudah selesai menggunakan aplikasi, klik pada masing-masing tab terminal lalu tekan tombol **`Ctrl + C`**.

---

### Cara 2: Menjalankan Cepat Menggunakan File Batch
1. **Jalankan Database:** Klik ganda file `start_mysql.bat` di File Explorer.
2. **Jalankan Server:** Klik ganda file `start_server.bat` di File Explorer.
3. Buka browser: `http://127.0.0.1:8000`

---

### Inisialisasi Database (Jika Baru Pertama Kali Setup):
```bash
php database/migrate.php
php database/seed.php
```

---

## 🔑 Kredensial Akun Default

- **URL Login Admin:** `http://127.0.0.1:8000/login.php`
- **Email:** `admin@queenlaundry.test`
- **Password:** `admin123`

---

## 🧪 Pengujian Black Box (BAB IV Skripsi)

Sistem telah dilengkapi dengan suite pengujian otomatis mandiri yang menguji 12 kasus Black Box (BB01–BB12):
```bash
php tests/blackbox_test.php
```
**Hasil Pengujian:** 12 dari 12 Kasus Uji Berhasil (100% PASS).

---

## 📄 Lisensi & Hak Cipta
Dikembangkan untuk keperluan Skripsi Program Studi Sistem Informasi / Teknik Informatika - Denia (2026).

