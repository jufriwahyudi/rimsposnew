# Resume Migrasi Aplikasi POS ke Bisnis FnB (Café)

Dokumen ini menjelaskan rancangan modifikasi dan refaktor minor yang perlu dilakukan pada sistem POS Retail agar dapat mendukung operasional bisnis Food & Beverage (FnB/Café) secara optimal, tanpa mengganggu atau mencampuradukkan fitur Retail yang sudah berjalan.

---

## 1. Perubahan Basis Data (Database Schema)

### A. Tabel `product_variants` (Manajemen Produk Non-Stok)
Menambahkan flag untuk membedakan produk yang butuh tracking stok (seperti minuman kaleng, rokok) dengan produk yang diproduksi saat dipesan (seperti kopi seduh, nasi goreng, kentang goreng).

```sql
ALTER TABLE product_variants ADD COLUMN track_stock BOOLEAN DEFAULT TRUE AFTER is_active;
ALTER TABLE product_variants ADD COLUMN cost_price_manual DECIMAL(15, 2) DEFAULT 0.00 AFTER harga_jual;
```

### B. Tabel `sales` (Table Tracking & Open Bill)
Menambahkan kolom untuk melacak nomor meja pelanggan dan mendukung alur bayar belakangan (*open bill*).

```sql
ALTER TABLE sales ADD COLUMN table_number VARCHAR(50) NULL AFTER invoice_number;
-- Ubah tipe status untuk mendukung 'hold' / 'unpaid'
```

### C. Tabel `stores` (Penanda Jenis Bisnis Store)
Menambahkan kolom untuk membedakan jenis usaha masing-masing outlet/toko, guna mencegah tumpang tindih (*overlap*) fitur antara retail dan FnB.

```sql
ALTER TABLE stores ADD COLUMN business_type ENUM('retail', 'fnb') DEFAULT 'retail' AFTER is_active;
```

### D. Tabel `products` (Foto Makanan / Minuman)
Menambahkan kolom foto/gambar pada produk agar kasir/waiter dapat mengenali menu dengan mudah di tampilan grid katalog.

```sql
ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER deskripsi;
```

### E. Tabel `role_master` (Role Type STELLING)
Menambahkan tipe role baru `'STELLING'` ke dalam enum `role_type` untuk digunakan oleh pengguna dari pihak tenant/steling penanggung jawab dapur.

```sql
ALTER TABLE role_master MODIFY COLUMN role_type ENUM('STORE', 'WAREHOUSE', 'ADMIN', 'SUPERADMIN', 'STELLING') NOT NULL;
```

---

## 2. Penyesuaian Model & Relasi

### A. Model `ProductVariant.php`
Menambahkan cast untuk atribut baru dan memperhitungkan stok *infinite* jika `track_stock = false` pada relasi stok.

```php
protected $fillable = [
    // ... kolom lama ...
    'track_stock',
    'cost_price_manual',
];

// Modifikasi getter stok agar bernilai besar jika non-stok
public function getStokStoreAttribute()
{
    if (!$this->track_stock) {
        return 999999; // Anggap tak terbatas untuk tampilan kasir
    }
    return $this->batches()->where('posisi', 'store')->sum('qty_sisa');
}
```

### B. Model `Sale.php`
Menambahkan `table_number` ke dalam `$fillable` array.

### C. Model `Store.php`
Menambahkan `business_type` ke dalam `$fillable` array.

### D. Model `Product.php`
Menambahkan kolom `image` ke `$fillable` dan membuat accessor URL untuk mempermudah akses asset gambar di frontend.
```php
protected $fillable = [
    // ... kolom lama ...
    'image',
];

protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image 
        ? \Storage::url($this->image) 
        : asset('assets/images/default-product.png'); // Gambar default jika kosong
}
```

---

## 3. Logika Controller & Alur Bisnis

### A. Modifikasi `PosController.php`

#### 1. Pemisahan Alur Simpan Pesanan & Pembayaran
Saat ini POS langsung memotong kas dan mencatat transaksi lunas. Untuk FnB, alur dipecah:
- **`saveOrder` (Pesan & Kirim ke Dapur)**: Membuat record `sales` dengan `status = 'hold'` dan `payment_status = 'unpaid'`. Stok barang yang memiliki `track_stock = true` langsung didecrement menggunakan metode `issueFIFOWithBatchLog` untuk mengunci barang.
- **`settlePayment` (Proses Pembayaran)**: Mengambil order meja bersangkutan, menerima input cash/transfer, lalu mengubah status menjadi `status = 'paid'` dan mencatat kas masuk (`CashTransaction`).

#### 2. Bypass FIFO pada Fungsi Pengurang Stok
```php
protected function issueFIFOWithBatchLog(...)
{
    // Cek apakah varian ini butuh tracking stok
    $variant = ProductVariant::find($variantId);
    if ($variant && !$variant->track_stock) {
        return; // Lewati FIFO untuk produk non-stok
    }

    // ... sisa logika FIFO lama tetap berjalan untuk produk retail/stok ...
}
```

### B. Modifikasi `LaporanController.php` (Laporan Keuangan & HPP)
Karena produk non-stok tidak memiliki relasi dengan `sale_item_batches`, logika perhitungan laba/rugi (HPP) perlu mendeteksi fallback ke harga beli/modal manual.

```php
$cost = 0;
foreach ($sale->items as $item) {
    if ($item->batches->isNotEmpty()) {
        foreach ($item->batches as $batch) {
            $cost += ($batch->qty * $batch->cost_price);
        }
    } else {
        // Fallback untuk makanan/minuman non-stok menggunakan modal manual
        $cost += ($item->qty * ($item->variant->cost_price_manual ?? 0));
    }
}
```

---

## 4. Integrasi Layanan (Print & Kitchen Display System / KDS)

Untuk café/FnB, baik **kertas print struk** maupun **KDS digital** akan digunakan secara bersamaan dengan peran masing-masing:

### A. KDS (Kitchen Display System - Layar Digital di Dapur)
KDS dipasang di dapur untuk menggantikan tiket kertas koki:
- Layar KDS memuat antrean pesanan masuk secara real-time (status `hold`).
- Koki dapat menandai status makanan/minuman yang sedang dimasak atau telah selesai dimasak.
- Ketika koki menekan tombol **"Selesai Masak"**, pesanan akan ditandai siap diantar oleh waiter.

### B. Print Struk Checklist (Di Meja Customer)
Sistem POS kasir/waiter akan mencetak lembar struk pesanan khusus (tanpa harga/total rupiah):
- Kertas struk ini ditaruh/ditempel di meja customer.
- Struk ini berfungsi sebagai **checklist manual bagi waiter** untuk menandai (mencentang) pesanan apa saja yang sudah diantarkan ke meja dan mana yang masih ditunggu (pending).

---

## 5. Fitur Manajemen Meja (Pindah Meja & Satukan Bill)

Pada sistem FnB, pesanan aktif (status `hold`) membutuhkan fitur dinamis untuk memfasilitasi kebutuhan pelanggan di area makan:

### A. Fitur Pindah Meja (Change Table)
Jika pelanggan ingin pindah dari meja lama ke meja baru (misal: dari Meja 01 ke Meja 05):
- Sistem kasir memanggil transaksi aktif meja lama.
- Kasir memperbarui kolom `table_number` pada tabel `sales` dari `'01'` menjadi `'05'`.
- Perubahan ini otomatis tersinkronisasi di KDS dapur dan data kasir.

### B. Fitur Satukan Bill (Merge Bill)
Jika dua meja atau lebih ingin menyatukan tagihan mereka menjadi satu pembayaran (misal: Meja 01 gabung dengan Meja 02):
1. **Pilih Transaksi**: Kasir memilih transaksi utama (misal: Bill Meja 01) dan transaksi yang akan digabungkan (Bill Meja 02).
2. **Transfer Item**: Sistem memindahkan semua item menu (`sale_items`) dari Bill Meja 02 ke `sale_id` milik Bill Meja 01.
3. **Kalkulasi Ulang**: Menghitung kembali total harga (`subtotal`, `discount_total`, `grand_total`) pada Bill Meja 01.
4. **Hapus/Hapus Sementara Bill Kosong**: Menghapus atau menandai status Bill Meja 02 sebagai `void` / `merged` agar tidak lagi muncul sebagai tagihan ganda.
5. **Cetak Ulang Checklist**: Kasir mencetak ulang lembar checklist meja gabungan untuk ditaruh di meja pelanggan (opsional).

---

## 6. Manajemen Multi-Tenant (Food Court / Multi-Stall)

Jika dalam satu toko/café terdapat beberapa penyedia makanan (tenant/steling) berbeda yang beroperasi bersama di bawah satu kasir terpusat, pengelolaannya dapat dirancang sebagai berikut:

### A. Skema Database & Relasi Produk
Setiap produk harus diasosiasikan dengan tenant pemiliknya. Kita bisa menambahkan tabel `tenants` atau menggunakan tabel `vendors` yang sudah kita buat sebelumnya:
- **Tabel `tenants`**: Menyimpan data nama tenant, persentase bagi hasil/komisi, dan penanggung jawab.
- **Tabel `products`**: Ditambahkan kolom `tenant_id` (foreign key ke `tenants`). Dengan begitu, kasir tahu produk mana milik tenant mana.

### B. Pemisahan Order ke Dapur (Routing Order & Akses Tenant)
Ketika pelanggan memesan 2 Kopi (Tenant A) dan 1 Roti Bakar (Tenant B) dalam satu nota/meja yang sama:
1. **Kitchen Display System (KDS - Versi Web Responsif)**: 
   - KDS dikembangkan berbasis web dan dioptimalkan tampilannya agar responsif untuk layar **tablet** atau **mobile** yang ditaruh di dapur masing-masing tenant.
   - Hak akses login koki/tenant didasarkan pada user dengan tipe role **`STELLING`** di tabel `role_master`.
   - Setiap user ber-role `STELLING` dikaitkan dengan `tenant_id` tertentu. Begitu login, KDS secara otomatis menyaring dan hanya menampilkan daftar pesanan item menu yang dijual oleh tenant tersebut (dapur Tenant A hanya melihat Kopi, dapur Tenant B hanya melihat Roti Bakar).
2. **Printer Dapur**: 
   - Sistem POS akan memecah (*split*) data cetak berdasarkan `tenant_id` dan mengirimkan perintah cetak ke printer masing-masing tenant (Printer Tenant A dan Printer Tenant B secara terpisah).
3. **Checklist Waiter (Meja Customer)**:
   - Tetap mencetak 1 struk utuh berisi seluruh pesanan (Kopi + Roti Bakar) untuk ditaruh di meja pelanggan agar waiter mudah mencentang semua sajian.

### C. Pembagian Pendapatan & Laporan Keuangan
Pembayaran dari pelanggan tetap diterima secara terpusat oleh kasir toko utama. Pada akhir periode (harian/mingguan), sistem akan menyajikan laporan pembagian hasil:
- **Laporan Penjualan Per Tenant**: Menampilkan omset kotor masing-masing tenant berdasarkan item yang terjual.
- **Kalkulasi Bagi Hasil (Revenue Split)**: Sistem otomatis memotong persentase komisi pengelola toko (misal: bagi hasil 15% untuk toko, 85% untuk tenant).
- **Laporan Piutang Tenant**: Mencatat kewajiban toko untuk mentransfer uang penjualan bersih ke masing-masing tenant pada masa *settlement*.

---

## 7. Pencegahan Overlapping Fitur (Retail vs FnB)

Untuk memastikan kode program tetap rapi, efisien, dan tidak membingungkan pengguna kasir retail biasa, kita membedakan alur kerja secara dinamis menggunakan nilai `business_type` di tabel `stores` (melalui session `session('business_type')`):

### A. Tampilan POS Kasir Dinamis (Flutter POS App UI)
Karena aplikasi kasir (frontend POS) didevelop menggunakan **Flutter**, pemisahan fitur UI diatur secara dinamis di sisi Flutter berdasarkan konfigurasi jenis bisnis toko (`business_type`) yang diterima dari API saat memilih toko (*select store*):

- **Retail Mode (`business_type = 'retail'`)**:
  - Menggunakan UI retail yang sudah selesai dikembangkan (alur transaksi cepat langsung menuju form pembayaran, tanpa informasi meja).
- **FnB / Café Mode (`business_type = 'fnb'`)**:
  - Flutter POS UI akan memunculkan komponen tambahan:
    - Panel pemilihan/tata letak nomor meja (`table_number`).
    - Tombol "Pesan / Hold Order" untuk mengirim pesanan ke dapur tanpa harus langsung membayar (*open bill*).
    - Halaman daftar tagihan meja aktif (*active bill lists*) untuk memudahkan kasir memanggil kembali pesanan meja tertentu.
    - Tombol aksi tambahan untuk "Pindah Meja" dan "Satukan Bill" di layar kelola meja.

### B. Validasi Stok di Backend
- **Toko Retail**:
  - Sistem wajib memvalidasi stok untuk semua item sebelum bayar. Jika stok tidak cukup, transaksi langsung diblokir di kasir.
- **Café / FnB**:
  - Untuk item yang `track_stock = false` (makanan/minuman olahan), kasir melewatkan pengecekan stok fisik di toko agar transaksi tetap bisa berjalan meskipun stok tercatat nol di database.

### C. Alur Jurnal Akuntansi & Kas Masuk
- **Toko Retail**:
  - Jurnal transaksi kas masuk langsung terbentuk seketika saat kasir menekan tombol bayar (karena langsung lunas).
- **Café / FnB**:
  - Jurnal transaksi piutang/penjualan draft (jika dibutuhkan) baru dicatat ketika statusnya `hold`. Pencatatan kas masuk asli (`CashTransaction`) hanya akan dideklarasikan saat tagihan dilunasi (*settle payment*).

---

## 8. Panduan Modifikasi Source Code Flutter (`rimspos_mobile`)

Untuk mendukung fitur FnB/Café pada aplikasi mobile Flutter Anda, berikut adalah panduan modifikasi berkas-berkas Dart:

### A. Modifikasi Layer Model (`lib/models/`)

#### 1. [store.dart](file:///d:/learnflutter/rimspos_mobile/lib/models/store.dart)
Tambahkan properti `businessType` untuk mendeteksi apakah toko aktif bermode retail atau fnb.
```dart
class Store {
  final int id;
  final String name;
  final String printerType;
  final String businessType; // Tambah properti ini ('retail' | 'fnb')

  const Store({
    required this.id,
    required this.name,
    this.printerType = '80mm',
    required this.businessType,
  });

  factory Store.fromJson(Map<String, dynamic> json) => Store(
        id: json['id'] as int,
        name: json['name'] as String,
        printerType: json['printer_type'] as String? ?? '80mm',
        businessType: json['business_type'] as String? ?? 'retail', // Fallback retail
      );
}
```

#### 2. [transaction.dart](file:///d:/learnflutter/rimspos_mobile/lib/models/transaction.dart)
Tambahkan kolom `tableNumber` ke dalam model `Transaction` dan `TransactionDetail`.
```dart
// Di kelas Transaction & TransactionDetail
final String? tableNumber;

// Tambahkan tableNumber pada constructor dan dari json parsing:
tableNumber: json['table_number'] as String?,
```

#### 3. [product_variant.dart](file:///d:/learnflutter/rimspos_mobile/lib/models/product_variant.dart)
Tambahkan field `imageUrl` untuk memuat gambar produk di tampilan grid katalog POS.
```dart
class ProductVariant {
  final int id;
  final int productId;
  final String sku;
  final String name;
  final String? variant;
  final double price;
  final int stok;
  final String? imageUrl; // Tambah properti ini

  const ProductVariant({
    required this.id,
    required this.productId,
    required this.sku,
    required this.name,
    this.variant,
    required this.price,
    required this.stok,
    this.imageUrl,
  });

  factory ProductVariant.fromJson(Map<String, dynamic> json) => ProductVariant(
        id: json['id'] as int,
        productId: json['product_id'] as int,
        sku: json['sku'] as String,
        name: json['name'] as String,
        variant: json['variant'] as String?,
        price: (json['price'] as num).toDouble(),
        stok: (json['stok'] as num).toInt(),
        imageUrl: json['image_url'] as String?, // Ambil url image dari API
      );
}
```

---

### B. Modifikasi Layar Kasir POS (`lib/screens/pos/`)

#### 1. [pos_screen.dart](file:///d:/learnflutter/rimspos_mobile/lib/screens/pos/pos_screen.dart)
- **Tampilan Grid Katalog Produk (FnB Mode)**:
  - Pada mode Retail, halaman POS berfokus pada pencarian manual/scan barcode.
  - Pada mode FnB, ubah tampilan utama POS menjadi **Grid Katalog Produk** berdasar kategori (contoh: Kopi, Makanan Berat, Cemilan). Semua produk langsung terpampang berupa kartu/kotak di layar, sehingga kasir/waiter cukup mengetuk (*tap*) gambar/nama produk untuk memasukkannya ke keranjang secara instan tanpa perlu mengetik atau memindai barcode.
- **Selektor Meja**: Di bagian atas AppBar kasir, tampilkan tombol "Pilih Meja" jika `store.businessType == 'fnb'`.
- **Aksi Bottom Bar**: Ganti label tombol utama dari "Checkout" menjadi "Simpan Order / Hold" jika belum bayar, untuk menyimpan order meja sementara.
- **Manajemen Meja Aktif**: Tambahkan dialog/tombol pintas untuk mengakses fitur **Pindah Meja** (panggil API `/api/pos/sales/{id}/table`) dan **Satukan Bill**.

#### 2. [checkout_sheet.dart](file:///d:/learnflutter/rimspos_mobile/lib/screens/pos/checkout_sheet.dart)
- Kirim data `table_number` di dalam payload parameter `cartPayload` saat memanggil `PosService.checkout()`.
- Sesuaikan skema agar dapat menerima parameter opsional `existingSaleId`. Jika kita membuka transaksi meja aktif yang berstatus `hold`, tombol bayar akan menembak API pelunasan tagihan (`/api/pos/sales/{id}/pay`) alih-alih membuat transaksi baru (`checkout`).

---

### C. Modifikasi Layar Riwayat Penjualan (`lib/screens/transactions/`)

#### 1. [transaction_list_screen.dart](file:///d:/learnflutter/rimspos_mobile/lib/screens/transactions/transaction_list_screen.dart)
- Tambahkan tab/kategori baru di bagian atas: **"Tagihan Aktif (Hold)"** di samping tab "Riwayat Penjualan".
- Tab "Tagihan Aktif" akan menampilkan semua meja yang sedang memesan makanan namun belum membayar, sehingga waiter/kasir dapat memantau meja mana saja yang terisi.

#### 2. [transaction_detail_screen.dart](file:///d:/learnflutter/rimspos_mobile/lib/screens/transactions/transaction_detail_screen.dart)
- Tampilkan informasi **Nomor Meja** di samping nomor invoice jika tagihan memiliki `tableNumber`.
- Jika status transaksi adalah `'hold'`, munculkan tombol aksi baru di bagian bawah detail transaksi:
  - **"Pelunasan / Bayar Tagihan"**: Membuka `CheckoutSheet` secara langsung untuk melunasi nota tersebut.
  - **"Cetak Struk Checklist"**: Memanggil printer bluetooth/RawBT untuk mencetak daftar menu checklist waiter.
