# Roadmap Implementasi BAB IV dan Rencana Pengujian

## Rekomendasi struktur BAB IV
4.1 Hasil analisis kebutuhan (aktor, kebutuhan fungsional/nonfungsional, batasan)

4.2 Perancangan sistem (arsitektur, use case, activity diagram, class diagram, ERD, rancangan antarmuka)

4.3 Implementasi sistem (lingkungan perangkat keras/lunak, database, login, pelanggan, layanan, transaksi, status, pembayaran, laporan, tracking; screenshot hasil nyata)

4.4 Pengujian sistem (tabel Black Box, hasil aktual, UAT dan analisis)

4.5 Pembahasan hasil (kecocokan tujuan proposal, manfaat, kendala, keterbatasan)

**Catatan:** struktur akhir BAB IV mengikuti pedoman kampus dan persetujuan pembimbing.

## Urutan pengerjaan praktis
| Sprint | Pekerjaan | Bukti selesai |
|---|---|---|
| 0 | Konfirmasi tarif, alur status, pembayaran, tracking, dan skema akses | kebutuhan disetujui |
| 1 | Buat proyek PHP, database, migrasi, seeder, autentikasi admin | login dan schema berjalan |
| 2 | CRUD pelanggan dan layanan | form + validasi + tabel |
| 3 | Pesanan dan item; kalkulasi server-side; cetak nota | transaksi tersimpan konsisten |
| 4 | Update status, histori, tracking token | status pelanggan benar dan privat |
| 5 | Pembayaran manual dan laporan periode | hasil cocok dengan data uji |
| 6 | Dashboard, responsivitas, backup, keamanan | UI siap demonstrasi |
| 7 | Black Box, UAT, perbaikan, screenshot BAB IV | hasil uji terdokumentasi |

## Contoh kasus Black Box (rencana, bukan hasil)
| ID | Skenario | Ekspektasi |
|---|---|---|
| BB01 | Admin login benar | dashboard terbuka |
| BB02 | Password salah | ditolak tanpa bocor detail |
| BB03 | Pelanggan tanpa nama | validasi gagal |
| BB04 | Layanan harga negatif | ditolak |
| BB05 | Pesanan 2.5 kg × Rp8.000 | subtotal Rp20.000 |
| BB06 | Pesanan tanpa item | ditolak |
| BB07 | Status pesanan berubah | status dan histori tersimpan |
| BB08 | Tracking token salah | data tidak ditampilkan |
| BB09 | Pembayaran melebihi tagihan | ditolak |
| BB10 | Laporan 1–31 Oktober | hanya pembayaran periode itu |
| BB11 | Pelanggan membuka URL admin | akses ditolak |
| BB12 | Tarif berubah setelah transaksi | total transaksi lama tetap |

## Rencana UAT
Responden utama: pemilik Queen Laundry (Admin); pelanggan sebagai responden fitur tracking. Gunakan skala 1–5 untuk kemudahan, kejelasan, akurasi biaya, kecepatan pencarian, kemudahan tracking, dan laporan. Catat jumlah responden, pertanyaan, skor, persentase, dan komentar; jangan mengarang hasil sebelum pengujian. Rumus persentase: `total_skor_aktual / (jumlah_responden × jumlah_pertanyaan × skor_maksimum) × 100%`. Ambang interpretasi harus didefinisikan sebelum uji.

## Risiko dan mitigasi
- Salah definisi pendapatan → sepakati berbasis pembayaran atau nilai order sebelum coding.
- Kebocoran tracking → token acak + hash, rate limit, tampilan data minimum.
- Harga historis berubah → snapshot harga pada order_items.
- Data hilang → backup dan uji pemulihan.
- Scope creep → patuhi batasan BAB I; fitur tambahan harus beralasan dan disetujui.

## Checklist siap demonstrasi
- [ ] Semua fitur wajib berjalan pada data dummy
- [ ] Admin tidak bisa diakses tanpa login
- [ ] Perhitungan total dan laporan cocok perhitungan manual
- [ ] Tracking tidak membuka pesanan orang lain
- [ ] Pengujian Black Box punya hasil aktual dan screenshot
- [ ] UAT dilakukan dengan responden nyata
- [ ] ERD sama dengan database yang diimplementasikan
- [ ] BAB IV mencantumkan lingkungan, langkah, hasil, dan keterbatasan
