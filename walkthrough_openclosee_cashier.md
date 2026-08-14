# Walkthrough: Fitur Open/Close Cashier & Rekonsiliasi Kas Kasir

Fitur **Open Cashier, Close Cashier & Rekonsiliasi Kas Kasir** telah berhasil diimplementasikan dengan dukungan konfigurasi aktif/non-aktif per masing-masing store/outlet.

---

## 1. Perubahan & Fitur yang Diimplementasikan

### A. Konfigurasi Toko (Store Configuration)
- **Database**: Menambahkan kolom `enable_cash_register` (boolean) di tabel `stores`.
- **UI Manage Toko**: Menambahkan switch toggle *"Wajib Buka/Tutup Kasir (Shift & Rekonsiliasi Kas)"* pada form tambah dan edit toko (`resources/views/stores/index.blade.php`).
- **Store Table Badge**: Menampilkan badge ungu `Buka/Tutup Kasir: Wajib` pada daftar toko jika fitur diaktifkan.

### B. Struktur Data Shift Kasir
- **Database `cash_registers`**:
  - Menyimpan data `opening_cash`, `total_cash_sales`, `total_non_cash_sales`, `total_refund_cash`, `total_cash_in`, `total_cash_out`, `expected_cash`, `actual_cash`, `cash_difference`, `denominations`, `notes`, dan status `open`/`closed`.
  - Menghubungkan relasi foreign key `cash_register_id` ke tabel `sales` dan `cash_transactions`.
- **Model & Service**:
  - `App\Models\CashRegister`: Model sesi shift kasir dengan relasi ke Store, User, Sales, dan CashTransactions.
  - `App\Services\CashRegisterService`: Menangani pembukaan shift, kalkulasi real-time expected cash, pencatatan petty cash movement, rekonsiliasi kas fisik, dan penutupan kasir.

### C. Alur POS & Rekonsiliasi Kas
- **PosController**:
  - Validasi saat kasir checkout (baik Web maupun Mobile API): jika toko mewajibkan shift kasir dan kasir belum buka shift, transaksi dicegah dan kasir diminta melakukan Buka Kasir terlebih dahulu.
  - Setiap penjualan (`Sale`) dan transaksi kas (`CashTransaction`) otomatis terhubung ke `cash_register_id` sesi aktif.
- **Tampilan POS (`resources/views/pos/index.blade.php`)**:
  - **Status Bar Kasir**: Indikator status kasir (Buka/Tutup), nama kasir, waktu buka, modal kas awal, tombol Kas Masuk, Kas Keluar, dan Tutup Kasir.
  - **Modal Buka Kasir (Open Cashier)**: Input modal kas awal (opening cash float) dan catatan pembukaan shift. Otomatis muncul saat membuka POS jika belum buka shift.
  - **Modal Kas Masuk / Kas Keluar (Petty Cash)**: Untuk mencatat pengeluaran kecil kasir (beli es batu, galon, dsb) atau uang kas masuk tambahan.
  - **Modal Tutup Kasir & Rekonsiliasi**:
    - Menampilkan ringkasan sistem: Modal Awal, Penjualan Tunai (+), Kas Masuk (+), Kas Keluar (-), Refund Tunai (-), dan Total Kas Seharusnya di Laci (Expected Cash).
    - Input kas fisik aktual di laci.
    - Kalkulator Pecahan Uang (lembaran 100k, 50k, 20k, 10k, 5k, 2k, 1k, koin) yang otomatis menjumlahkan kas fisik.
    - Indikator Selisih Kas real-time (Pas / Lebih / Kurang).
    - Catatan/alasan selisih kas.
    - Konfirmasi tutup kasir & tombol cetak struk rekapitulasi shift.

### D. Laporan & Struk Thermal
- **Struk Thermal Tutup Kasir (`resources/views/cash_registers/receipt.blade.php`)**: Format struk 58mm / 80mm untuk printer thermal berisi rincian penutupan shift dan selisih kas.
- **Halaman Laporan Shift Kasir (`resources/views/cash_registers/index.blade.php`)**: DataTables riwayat shift dengan filter tanggal dan status sesi.
- **Halaman Detail Shift Kasir (`resources/views/cash_registers/show.blade.php`)**: Rincian lengkap rekonsiliasi, daftar penjualan di shift tersebut, dan daftar petty cash movement.

### E. Mobile API Endpoints (`routes/api.php`)
- `GET /api/pos/cash-register/status`: Status sesi kasir aktif.
- `POST /api/pos/cash-register/open`: Buka kasir & catat kas awal.
- `GET /api/pos/cash-register/summary`: Ringkasan sebelum penutupan kasir.
- `POST /api/pos/cash-register/movement`: Catat kas masuk / keluar kecil.
- `POST /api/pos/cash-register/close`: Tutup kasir & rekonsiliasi kas fisik.

---

## 2. Cara Menguji (Verification Steps)

1. **Aktifkan Fitur pada Toko**:
   - Buka menu **Pengaturan > Manage Toko**.
   - Klik tombol Edit pada salah satu toko.
   - Aktifkan switch **"Wajib Buka/Tutup Kasir (Shift & Rekonsiliasi Kas)"**, lalu simpan.
2. **Buka POS**:
   - Masuk ke menu **POS**.
   - Sistem akan langsung menampilkan modal **Buka Kasir (Open Cashier)**.
   - Masukkan Modal Kas Awal (misal: `100.000`), lalu klik **Buka Sesi Kasir**.
3. **Lakukan Transaksi & Kas Movement**:
   - Lakukan transaksi penjualan tunai (misal: Rp 50.000).
   - Klik tombol **Kas Keluar** di bar kasir atas, catat pengeluaran Rp 10.000 (misal: Beli Galon).
4. **Tutup Kasir & Rekonsiliasi**:
   - Klik tombol **Tutup Kasir & Rekonsiliasi**.
   - Sistem menghitung: `Modal Awal (100.000) + Penjualan Tunai (50.000) - Kas Keluar (10.000) = Expected Cash (140.000)`.
   - Input Uang Fisik Aktual di laci:
     - Jika diinput `140.000` -> Status Pas (Selisih Rp 0).
     - Jika diinput `135.000` -> Status Kurang (-Rp 5.000), wajib isi catatan.
   - Klik **Tutup Kasir & Selesaikan Rekonsiliasi**.
   - Klik opsi **Cetak Struk Penutupan** untuk mencetak struk thermal.
5. **Cek Laporan**:
   - Buka menu **Laporan > Laporan Shift Kasir** untuk melihat riwayat audit shift dan klik detail.
