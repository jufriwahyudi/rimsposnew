# Dokumentasi Modul Manajemen Bahan Baku & Resep F&B (Ekstensi POS)

Modul ini memisahkan entitas **Bahan Baku (Raw Material)** dengan **Menu Jual (Finished Good)** serta mengotomatiskan pemotongan stok bahan baku di Toko/Outlet berdasarkan konfigurasi Resep (Bill of Materials / BOM) saat kasir melakukan transaksi checkout.

---

## 1. Daftar Menu & Route Aplikasi

Semua menu baru berada di bawah menu utama **Stok** pada dashboard admin.

| Nama Menu | Route Name | URL Path | Controller | Deskripsi Fungsi |
| :--- | :--- | :--- | :--- | :--- |
| **Bahan Baku** | `ingredients.index` | `/ingredients` | `IngredientController` | CRUD master bahan baku, satuan dasar (base unit), HPP estimasi, dan konversi satuan pembelian supplier. |
| **Resep Menu** | `recipes.index` | `/recipes` | `RecipeController` | Konfigurasi BOM untuk menu jualan. Menghubungkan produk jadi dengan bahan baku beserta takarannya. |
| **Stok Bahan Baku** | `ingredient-stocks.index` | `/ingredient-stocks` | `IngredientStockController` | Panel monitoring saldo stok di Gudang & Toko, stock-in pembelian, transfer stok gudang ke toko, dan opname/wastage. |

### Detail Route Tambahan (API & Aksi):
* **Simpan Konversi Satuan:** `POST /ingredients/{ingredient}/conversions` (Route: `ingredients.conversions.store`)
* **Hapus Konversi Satuan:** `DELETE /ingredients/conversions/{conversion}` (Route: `ingredients.conversions.destroy`)
* **Halaman Konfigurasi Resep:** `GET /recipes/{product}/manage` (Route: `recipes.manage`)
* **Simpan Resep:** `POST /recipes/{product}/save` (Route: `recipes.save`)
* **Input Stock In (Grosir):** `POST /ingredient-stocks/stock-in` (Route: `ingredient-stocks.stock-in`)
* **Form & Simpan Transfer Stok:** `GET /ingredient-stocks/transfer` & `POST /ingredient-stocks/transfer` (Route: `ingredient-stocks.transfer.store`)
* **Form & Simpan Opname Stok:** `GET /ingredient-stocks/adjust` & `POST /ingredient-stocks/adjust` (Route: `ingredient-stocks.adjust.store`)
* **Halaman Laporan Aktivitas Stok:** `GET /ingredient-stocks/report` (Route: `ingredient-stocks.report`)

---

## 2. Struktur Skema Database (standardized)

Semua primary key (`id`) dan foreign key referencing di modul ini telah distandarkan menggunakan tipe data auto-incrementing integer (`bigint` / `unsignedBigInteger`) agar konsisten dengan tabel bawaan database POS.

### A. Tabel: `units`
Menyimpan master data satuan pengukuran.
```sql
CREATE TABLE units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- e.g., 'Pack', 'Piece', 'Kilogram'
    symbol VARCHAR(10) NOT NULL, -- e.g., 'Pack', 'Pcs', 'Kg'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### B. Tabel: `ingredients`
Menyimpan master data bahan baku mentah.
```sql
CREATE TABLE ingredients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES stores(id) ON DELETE CASCADE,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    base_unit_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES units(id) ON DELETE RESTRICT,
    cost_per_unit DECIMAL(12, 2) DEFAULT 0.00, -- Estimasi HPP per base unit
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### C. Tabel: `ingredient_unit_conversions`
Menghubungkan unit pembelian/supplier (grosir) ke unit dasar (base unit), mendukung standar takaran berbeda per supplier dengan kolom `code`.
```sql
CREATE TABLE ingredient_unit_conversions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ingredient_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES ingredients(id) ON DELETE CASCADE,
    purchase_unit_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES units(id) ON DELETE RESTRICT,
    code VARCHAR(50) NOT NULL, -- e.g., 'Pack9', 'Pack10' (membedakan jenis takaran untuk unit 'Pack' yang sama)
    conversion_factor DECIMAL(10, 4) NOT NULL, -- e.g., 9.0000 (1 Pack = 9 Pcs)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(ingredient_id, purchase_unit_id, code)
);
```

### D. Tabel: `inventory_stocks`
Menyimpan saldo kuantitas stok bahan baku riil per lokasi.
```sql
CREATE TABLE inventory_stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ingredient_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES ingredients(id) ON DELETE CASCADE,
    location_type VARCHAR(20) NOT NULL, -- 'WAREHOUSE' atau 'STORE'
    location_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES stores(id) ON DELETE CASCADE,
    quantity DECIMAL(12, 4) NOT NULL DEFAULT 0.0000, -- disimpan dalam satuan dasar (base_unit)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(ingredient_id, location_type, location_id)
);
```

### E. Tabel: `product_recipes` (BOM)
Menghubungkan Menu Jual (`products`) dengan Bahan Baku (`ingredients`).
```sql
CREATE TABLE product_recipes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES products(id) ON DELETE CASCADE,
    ingredient_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES ingredients(id) ON DELETE CASCADE,
    quantity_required DECIMAL(10, 4) NOT NULL, -- Jumlah base unit yang dibutuhkan per 1 porsi
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### F. Tabel: `ingredient_stock_movements` (Audit Trail)
Mencatat riwayat keluar-masuk stok bahan baku untuk transparansi audit.
```sql
CREATE TABLE ingredient_stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ingredient_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES ingredients(id) ON DELETE CASCADE,
    location_type VARCHAR(20) NOT NULL, -- 'WAREHOUSE' atau 'STORE'
    location_id BIGINT UNSIGNED NOT NULL FOREIGN KEY REFERENCES stores(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL, -- 'PURCHASE', 'TRANSFER_IN', 'TRANSFER_OUT', 'SALE', 'WASTAGE', 'ADJUSTMENT'
    quantity_change DECIMAL(12, 4) NOT NULL, -- Nilai (+/-) dalam satuan dasar (base_unit)
    reference_id VARCHAR(100) NULL, -- ID Transaksi / PO / Transfer
    notes TEXT NULL,
    tanggal DATETIME NOT NULL, -- Tanggal Transaksi (mendukung backdate)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 3. Logika Alur Kerja & Eksekusi Bisnis

Semua kalkulasi stok dikelola secara terpusat oleh class service **`App\Services\IngredientInventoryService`**:

### A. Pembelian / Stock In (Supplier -> Gudang)
1. Owner menginput penerimaan bahan baku di Gudang, memilih bahan baku, satuan pembelian (misal: **Pack**), jumlah pembelian (misal: **2 Pack**), dan **Tanggal Pembelian** (mendukung pengisian *backdate* mundur).
2. Sistem memeriksa konversi di `ingredient_unit_conversions` berdasarkan satuan pembelian yang dipilih.
   * **Akomodasi Standar Ganda/Multi-Supplier:** Owner dapat memilih `Pack9` atau `Pack10` pada modal pembelian (Stock In) sesuai dengan fisik yang dikirim oleh supplier.
   * Jika memilih `Pack9` (dengan rasio konversi **9.0**), sistem mengonversi:
     $$\text{base\_qty} = 2 \times 9.0 = 18.0 \text{ Pcs}$$
   * Jika memilih `Pack10` (dengan rasio konversi **10.0**), sistem mengonversi:
     $$\text{base\_qty} = 2 \times 10.0 = 20.0 \text{ Pcs}$$
3. Saldo kuantitas di `inventory_stocks` (lokasi: `WAREHOUSE`) ditambahkan sesuai nilai konversi dasar tersebut.
4. Log movement dibuat dengan tipe `PURCHASE` senilai kuantitas dasar hasil konversi dengan kolom `tanggal` diisi sesuai pilihan user.

### B. Transfer Stok (Gudang -> Toko/Outlet)
1. Owner memindahkan stok bahan baku dari Gudang ke Toko sebelum operasional jualan dimulai.
2. Sistem memvalidasi saldo stok di `inventory_stocks` (lokasi: `WAREHOUSE`). Jika stok tidak mencukupi dari jumlah transfer yang diminta, sistem membatalkan transfer dan melempar pesan *validation error*.
3. Jika cukup, saldo stok Gudang dikurangi dan saldo stok Toko (lokasi: `STORE`) ditambah dalam database transaction.
4. Log movement dibuat untuk masing-masing lokasi: `TRANSFER_OUT` (-) di Gudang dan `TRANSFER_IN` (+) di Toko.

### C. Penjualan POS (Auto-Deduction via Recipe)
1. Ketika kasir (di aplikasi Web POS desktop atau Mobile App) melakukan checkout transaksi lunas (`paid`), sistem mengiterasi item keranjang belanja.
2. Untuk setiap item, sistem memeriksa nilai `products.product_type`.
3. **Jika tipe produk adalah `RECIPE`:**
   * Sistem mengambil semua baris resep bahan baku di `product_recipes` untuk produk tersebut.
   * Untuk setiap bahan baku, sistem mengalikan kuantitas resep dengan kuantitas porsi yang dibeli:
     $$\text{total\_deduct} = \text{recipe.quantity\_required} \times \text{item.qty}$$
   * Sistem mengurangi stok bahan baku di Toko (`inventory_stocks` lokasi `STORE`, `location_id` sesuai store transaksi) sebanyak `total_deduct`.
   * Log movement dibuat dengan tipe `SALE` senilai `-total_deduct`.
4. **Jika tipe produk adalah `SINGLE` (default):**
   * Sistem menggunakan alur *fallback* lama dengan memanggil metode `issueFIFOWithBatchLog` untuk memotong stok produk jadi.

### D. Pembatalan Transaksi (Void)
1. Ketika transaksi dibatalkan (void), sistem memeriksa jika terdapat item dengan tipe produk `RECIPE`.
2. Jika iya, sistem memanggil fungsi `restoreRecipeStock()` untuk menambahkan kembali bahan baku ke stok Toko sebanyak jumlah porsi yang dibatalkan dikali takaran resep.
3. Log movement dicatat dengan tipe `ADJUSTMENT` senilai `+total_restore`.

### E. Opname / Penyesuaian Stok & Wastage
1. Owner menginput hasil perhitungan fisik bahan baku di lapangan pada lokasi tertentu (Gudang atau Toko).
2. Sistem menghitung selisih kuantitas:
   $$\text{selisih} = \text{actual\_quantity} - \text{current\_system\_quantity}$$
3. Saldo stok diupdate langsung ke nilai `actual_quantity`.
4. **Deteksi Wastage:** Jika catatan alasan (notes) mengandung kata kunci seperti *"rusak"*, *"busuk"*, *"gosong"*, atau *"hilang"*, tipe movement dicatat sebagai **WASTAGE**. Jika tidak, dicatat sebagai **ADJUSTMENT**.
