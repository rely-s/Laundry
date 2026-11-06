# Analisis Kebutuhan Sistem Queen Laundry

## Dasar dan batasan
Sumber: Proposal Skripsi Denia (2026), terutama BAB I §1.2–1.4, BAB II (Waterfall, UML, Black Box, UAT), BAB III §3.3. Ini merupakan **rancangan implementasi**, bukan bukti bahwa program sudah dibuat. Kebutuhan tambahan diberi label *rekomendasi*.

**Masalah yang ditemukan:** pencatatan pelanggan dan transaksi masih menggunakan buku; pencarian riwayat sulit; laporan pendapatan lambat dan berisiko salah hitung.

**Wajib sesuai proposal:** CRUD pelanggan; transaksi dan kalkulasi biaya otomatis; pembaruan status pesanan; tracking pelanggan; laporan pendapatan periodik; Admin (pemilik) dan Pelanggan; PHP + MySQL; Waterfall. **Di luar lingkup:** penggajian, stok deterjen/pewangi, pajak, payment gateway, WhatsApp otomatis.

## Matriks kebutuhan fungsional
| ID | Fungsi | Aktor | Kriteria penerimaan |
|---|---|---|---|
| F01 | Login/logout admin | Admin | Kredensial valid masuk; tidak valid ditolak |
| F02 | CRUD dan pencarian pelanggan | Admin | Nama dan kontak tersimpan, dapat dicari |
| F03 | CRUD paket layanan dan tarif | Admin | Tarif per kg/per item tersimpan dan dapat dinonaktifkan; *rekomendasi untuk mendukung kalkulasi* |
| F04 | Buat pesanan laundry | Admin | Kode unik, pelanggan, tanggal, rincian, total tersimpan |
| F05 | Hitung biaya otomatis | Sistem | Jumlah × harga saat transaksi; total konsisten |
| F06 | Update status laundry | Admin | Transisi sah dan waktu perubahan tersimpan |
| F07 | Tracking pesanan | Pelanggan | Hanya data pesanan sendiri / kode+token rahasia; tidak membuka data pelanggan lain |
| F08 | Catat pembayaran tunai/transfer manual | Admin | Nominal, metode, waktu dan status bayar tersimpan; *rekomendasi agar pendapatan valid* |
| F09 | Laporan pendapatan per tanggal/periode | Admin | Rekap berdasarkan pembayaran yang diterima, bukan sekadar nilai pesanan |
| F10 | Cetak nota dan ekspor laporan | Admin | Dokumen memuat rincian dan total; *rekomendasi* |
| F11 | Dashboard ringkas | Admin | Total pesanan, dalam proses, selesai, pendapatan terbayar |

## Nonfungsional
- Password admin di-hash, session aman, CSRF protection, validasi server-side, otorisasi per role.
- Semua uang menggunakan DECIMAL(12,2), bukan FLOAT; tanggal disimpan konsisten; backup database berkala.
- Tampilan responsif mobile dan desktop; form dapat digunakan tanpa keahlian teknis.
- Gunakan HTTPS jika dipublikasikan; jangan expose nomor telepon/alamat melalui halaman tracking publik.
- Performa sasaran awal: pencarian dan simpan normal di bawah 3 detik pada lingkungan uji; ini **target rancangan**, bukan hasil pengujian.

## Perangkat dan teknologi
**Tercantum BAB III:** laptop Lenovo Core i5 gen 8 RAM 16 GB, internet, printer, XAMPP, VS Code, Chrome, phpMyAdmin, draw.io, Word/Excel. **Teknologi Implementasi:** PHP Native (PDO), MySQL / MariaDB (XAMPP), HTML5 + Bootstrap 5, Chart.js, JavaScript murni. Menggunakan arsitektur modular terstruktur (pemisahan config database, includes helper autentikasi/CSRF/finansial, layout templates, dan antarmuka modul) dengan disiplin keamanan standar industri: PDO prepared statements (anti-SQL Injection), validasi token CSRF pada semua form POST, sanitasi XSS htmlspecialchars, regenerasi ID sesi, dan password hashing bcrypt.

## Aturan bisnis yang perlu disepakati
1. Status usulan: diterima → dicuci → disetrika → siap_diambil → diambil; bila layanan tanpa setrika, boleh langsung siap_diambil. Batalkan hanya dengan alasan dan jejak audit.
2. Harga layanan disalin ke `order_items.unit_price` saat pesanan dibuat; perubahan tarif tidak mengubah transaksi lama.
3. Transaksi bisa memiliki beberapa layanan. Total = SUM(qty × unit_price), diskon default nol (opsional).
4. Pembayaran parsial diperbolehkan dalam model; jika Queen Laundry hanya lunas sekali, sederhanakan UI.
5. Pendapatan periode = SUM pembayaran berhasil pada tanggal pembayaran. Laporan pesanan dibuat terpisah dari laporan kas.
6. Pelanggan hanya tracking; riwayat transaksi pada BAB I manfaat disebut tetapi batasan hanya mengizinkan tracking: perlu persetujuan pembimbing sebelum menambahkan menu riwayat.

## Pertanyaan validasi lapangan
- Jenis layanan nyata dan satuannya (kg, item), tarif, pembulatan berat minimum?
- Apakah pembayaran di muka, saat pengambilan, atau cicilan?
- Apakah pelanggan login, atau tracking memakai kode + token? (rekomendasi: kode + token untuk lingkup minimal)
- Apakah satu nota bisa berisi beberapa jenis layanan?
- Definisi pendapatan yang disetujui pemilik (kas diterima atau nilai transaksi)?
- Apakah perlu status antarproses terpisah untuk layanan cuci saja?

## Tahapan Waterfall
Analisis (validasi aturan) → Desain (UI, UML, ERD) → Implementasi (modul bertahap) → Pengujian (Black Box dan UAT) → Pemeliharaan. Bekukan kebutuhan setelah persetujuan untuk konsistensi metodologi.
