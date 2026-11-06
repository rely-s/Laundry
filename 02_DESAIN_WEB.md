# Desain Website Queen Laundry

## Prinsip desain
UI sederhana untuk pemilik laundry, responsif, warna dasar putih, biru tua, aksen turquoise; kontras tinggi, tombol aksi jelas, tabel bisa dicari, format Rupiah dan tanggal Indonesia. **Semua ini usulan desain**, bukan desain yang sudah ada dalam proposal.

## Sitemap
```text
Publik
├── Beranda (opsional: informasi usaha)
└── Lacak Cucian (kode pesanan + token)
Admin
├── Login
├── Dashboard
├── Pelanggan (daftar/tambah/edit/detail)
├── Layanan & Tarif (daftar/tambah/edit)
├── Pesanan (daftar/buat/detail/ubah status/cetak nota)
├── Pembayaran (catat manual, riwayat)
├── Laporan (filter periode, cetak/ekspor)
└── Profil / Ganti Password
```

## Wireframe dashboard admin
```text
┌──────────────────────────────────────────────────────────────────────┐
│ QUEEN LAUNDRY                        Cari pesanan...    Admin ▾      │
├────────────────┬─────────────────────────────────────────────────────┤
│ Dashboard      │ Dashboard                 [ + Pesanan Baru ]       │
│ Pelanggan      │ [Pesanan hari ini] [Diproses] [Siap] [Pendapatan]  │
│ Layanan        │                                                     │
│ Pesanan        │ Grafik pendapatan per hari (Chart.js)               │
│ Pembayaran     │                                                     │
│ Laporan        │ Pesanan terbaru                                     │
│ Pengaturan     │ Kode | Pelanggan | Total | Status | Aksi            │
└────────────────┴─────────────────────────────────────────────────────┘
```

## Wireframe transaksi
```text
┌──────────────────── Buat Pesanan ────────────────────┐
│ Pelanggan [Cari/pilih pelanggan] [+ Baru]            │
│ Tanggal terima [..]  Estimasi selesai [..]           │
│ Layanan [Cuci Setrika ▾]  Jumlah [3.5]  [+ Tambah]   │
│ --------------------------------------------------- │
│ Layanan          Qty     Harga        Subtotal       │
│ Cuci Setrika     3.5     Rp...        Rp...          │
│ Total: Rp...                                       │
│ Catatan [....................................]       │
│ [Batal]                           [Simpan Pesanan]  │
└─────────────────────────────────────────────────────┘
```

## Wireframe tracking pelanggan
```text
┌────────────── Lacak Pesanan Queen Laundry ──────────────┐
│ Kode pesanan [QL-2026-0001]                             │
│ Kode akses   [********]                     [Lacak]     │
│ Status: Sedang dicuci                                   │
│ Diterima ✓ ── Dicuci ● ── Siap diambil ○ ── Diambil ○   │
│ Estimasi selesai: ...                                   │
└─────────────────────────────────────────────────────────┘
```

## Spesifikasi halaman
| Halaman | Elemen utama | Validasi dan perilaku |
|---|---|---|
| Login | username/email, password | pesan umum jika salah, rate limit |
| Dashboard | kartu KPI, grafik, pesanan terbaru | tanggal filter, status akurat |
| Pelanggan | tabel, pencarian, form nama/no HP/alamat opsional | nomor HP boleh tidak unik jika pelanggan berbagi nomor |
| Layanan | nama, satuan kg/item, tarif, aktif | tarif >= 0, nonaktif alih-alih hapus jika dipakai |
| Pesanan | pelanggan, item, qty, harga, total, status | qty > 0; harga diambil dari tarif aktif; hitung ulang server |
| Detail pesanan | nomor nota, item, status, timeline, pembayaran | perubahan status tercatat |
| Pembayaran | nominal, tunai/transfer, tanggal, referensi opsional | jumlah bayar tidak melebihi sisa kecuali aturan refund ada |
| Laporan | rentang tanggal, total penerimaan, rincian pembayaran | periode inklusif; pisahkan pesanan vs penerimaan |
| Tracking | kode pesanan dan token | hanya informasi minimum, hindari enumeration |

## Navigasi dan hak akses
Admin menggunakan sidebar pada desktop dan menu collapse di ponsel. Pelanggan tidak melihat sidebar admin. Semua URL admin dilindungi middleware autentikasi dan otorisasi. Kode pesanan saja **tidak cukup** untuk tracking yang aman; tambahkan token acak atau login pelanggan.

## Alur UX utama
Admin login → cari/buat pelanggan → pilih layanan → input kuantitas → cek total otomatis → simpan → cetak nota → update status → catat pembayaran → lihat laporan. Pelanggan membuka halaman tracking → masukkan kode dan token → melihat status.

## Dokumentasi BAB IV
Ambil screenshot: login, dashboard, pelanggan, layanan, form pesanan, detail/status, pembayaran, laporan, tracking, serta bukti pengujian. Gambar harus dari aplikasi yang benar-benar dijalankan, bukan wireframe ini.
