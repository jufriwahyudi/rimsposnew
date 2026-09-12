# Implementation Plan: Fitur Toko Baju & Apotik dengan Sistem Modular Addon Store

Dokumen ini merancang arsitektur dan implementasi teknis untuk memenuhi kebutuhan klien baru (**Toko Baju & Apotik**) dengan menyederhanakan `business_type` menjadi **3 jenis inti** saja:
1. **`retail`**: Retail umum (Toko Baju, Grosir Sembako, Elektronik, Swalayan, ATK, Toko Bangunan).
2. **`fnb`**: Makanan & Minuman (Cafe, Restoran, Bakery).
3. **`pharmacy`**: Apotik & Toko Obat Farmasi.

Fitur-fitur spesifik dikontrol fleksibel melalui **Sistem Addon Store** (mirip dengan addon `self_service`, `kds`, dan `multi_printer` pada FnB):
- **`addon_sales_person`**: Pencatatan Pramuniaga / SPG closing penjualan (Toko Baju / Retail Elektronik / Fashion).
- **`addon_multi_unit`**: Multi-Satuan Dinamis Bertingkat (misal: *Pcs $\to$ Lusin $\to$ Dus* untuk Retail/Grosir; *Tablet $\to$ Strip $\to$ Box* untuk Apotik). **Dapat diaktifkan baik di Retail maupun Pharmacy!**
- **`addon_fefo`**: Pencatatan Batch, Expired Date & Pengeluaran Stok FEFO (*First Expired, First Out*) untuk Apotik, Frozen Food, atau Kosmetik.
- **`addon_concoction`**: Penjualan Obat Racikan & Resep Dokter (Puyer/Kapsul/Signa) untuk Apotik.

---

## User Review Required

> [!IMPORTANT]
> **1. Multi-Satuan (`addon_multi_unit`) Tersedia Penuh untuk Retail & Pharmacy:**
> * Toko dengan tipe **`retail`** bebas mencentang `addon_multi_unit`. Ini sangat berguna untuk:
>   * Toko Grosir / Sembako: *Pcs $\to$ Renteng $\to$ Dus*
>   * Toko Baju / Konveksi: *Pcs $\to$ Lusin (isi 12) $\to$ Kodi (isi 20)*
>   * Toko ATK / Alat Tulis: *Pcs $\to$ Pack $\to$ Box*
>   * Toko Bahan Bangunan: *Batang $\to$ Ikat, Sak $\to$ Pallet*
> * Toko dengan tipe **`pharmacy`**: Otomatis tercentang secara default (*Tablet $\to$ Strip $\to$ Box*).

> [!IMPORTANT]
> **2. Arsitektur Modular & Aman Tanpa Regresi:**
> * Toko retail biasa yang tidak butuh multi-satuan atau SPG cukup biarkan addon tidak tercentang, alur kasir tetap sesederhana dan secepat sebelumnya.
> * Aplikasi Flutter kasir akan membaca kemampuan toko (*Store Capabilities*) saat login/ganti toko, lalu menampilkan UI yang relevan secara dinamis.

---

## Proposed Changes

### Component 0: Database & Web Store Management (Sistem Addon Toko)

#### 1. Database & Migrations
- **Tabel `stores`**:
  - Kolom `business_type` enum/string dibatasi 3 opsi: `'retail'`, `'fnb'`, `'pharmacy'`.
  - Migration `add_retail_and_pharmacy_addons_to_stores_table`:
    ```php
    Schema::table('stores', function (Blueprint $table) {
        if (!Schema::hasColumn('stores', 'addon_sales_person')) {
            $table->boolean('addon_sales_person')->default(false)->after('addon_multi_printer');
        }
        if (!Schema::hasColumn('stores', 'addon_multi_unit')) {
            $table->boolean('addon_multi_unit')->default(false)->after('addon_sales_person');
        }
        if (!Schema::hasColumn('stores', 'addon_fefo')) {
            $table->boolean('addon_fefo')->default(false)->after('addon_multi_unit');
        }
        if (!Schema::hasColumn('stores', 'addon_concoction')) {
            $table->boolean('addon_concoction')->default(false)->after('addon_fefo');
        }
    });
    ```

#### 2. Backend Laravel
- **Model [Store.php](file:///d:/laravel/rimspos/app/Models/Store.php)**:
  - Tambahkan field ke `$fillable` dan `$casts`:
    `'addon_sales_person' => 'boolean'`, `'addon_multi_unit' => 'boolean'`, `'addon_fefo' => 'boolean'`, `'addon_concoction' => 'boolean'`.
- **Controller [StoreController.php](file:///d:/laravel/rimspos/app/Http/Controllers/StoreController.php)**:
  - Validasi dan penyimpanan nilai addon saat simpan (`store`) dan perbarui (`update`).
- **Web View [stores/index.blade.php](file:///d:/laravel/rimspos/resources/views/stores/index.blade.php)**:
  - Pilihan Dropdown `business_type`:
    - `Retail (Toko Umum / Fashion / Grosir / Swalayan)`
    - `F&B (Restoran / Cafe / Bakery)`
    - `Pharmacy (Apotik / Toko Obat)`
  - Checkbox Addon yang fleksibel:
    - `addon_sales_person` (Pencatatan Sales Person / SPG) -> Dapat dicentang untuk Retail
    - `addon_multi_unit` (Multi-Satuan Kemasan Bertingkat) -> **Dapat dicentang untuk Retail maupun Pharmacy**
    - `addon_fefo` (Expired Date & FEFO) -> Dapat dicentang untuk Pharmacy atau Retail Khusus (Makanan/Kosmetik)
    - `addon_concoction` (Obat Racikan Farmasi) -> Khusus Pharmacy
  - Otomasi JavaScript:
    - Jika user pilih `Pharmacy`: otomatis centang `addon_multi_unit`, `addon_fefo`, `addon_concoction`.
    - Jika user pilih `Retail`: admin bebas memilih mencentang `addon_sales_person` (untuk toko baju) dan/atau `addon_multi_unit` (untuk grosir/sembako/ATK).

---

### Component 1: Fitur Sales Person (Pramuniaga Mandiri)

*Aktif jika `store.addon_sales_person == true`.*

#### 1. Database & Migrations
- **Tabel Baru `sales_persons`**:
  ```sql
  CREATE TABLE sales_persons (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      store_id BIGINT NOT NULL,          -- Multi-tenant
      code VARCHAR(30) NULL,             -- Contoh: SPG-01, SL-RINA
      name VARCHAR(100) NOT NULL,
      phone VARCHAR(20) NULL,
      commission_rate DECIMAL(5,2) NULL, -- Default komisi (%) jika ada
      user_id BIGINT NULL,               -- Opsional jika merangkap kasir login
      is_active BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP,
      updated_at TIMESTAMP
  );
  ```
- **Tabel `sales`**:
  - Tambah kolom `sales_person_id` (`unsignedBigInteger`, nullable, FK ke `sales_persons.id`).

#### 2. Backend Laravel
- **Model Baru [SalesPerson.php](file:///d:/laravel/rimspos/app/Models/SalesPerson.php)**:
  - Scope `HasStore`, relasi `hasMany(Sale::class)`.
- **Web Admin**:
  - Menu: **Master Data -> Data Sales Person / Pramuniaga** (hanya muncul jika toko mengaktifkan `addon_sales_person`).
- **API & Controller**:
  - `GET /api/pos/sales-persons?store_id=N`.
  - Simpan `sales_person_id` di `PosController@checkout`.

#### 3. Mobile POS (Flutter)
- Di [pos_screen.dart](file:///d:/learnflutter/rimspos_mobile/lib/screens/pos/pos_screen.dart):
  - Jika `store.addonSalesPerson == true`, tampilkan pemilih **"Pramuniaga / SPG"** sebelum checkout.

---

### Component 2: Diskon Promosi Terjadwal & Bertarget (Toko Baju, Retail & Apotik)

#### 1. Database & Migrations
- **Tabel Baru `discounts`**:
  - `id`, `store_id`, `name`, `discount_type` (`percentage`/`nominal`), `discount_value`, `max_discount_amount`, `min_purchase_amount`, `target_type` (`all`, `member_only`, `category`, `product`), `start_date`, `end_date`, `is_active`.

#### 2. Web Admin & API
- CRUD Pengaturan Diskon di Web Admin.
- Endpoint `GET /api/pos/discounts?store_id=N`.
- Tombol **"Pilih Promo"** di POS mobile untuk menerapkan diskon otomatis.

---

### Component 3: Multi-Satuan Dinamis (Satuan Bertingkat untuk Retail & Apotik)

*Aktif jika `store.addon_multi_unit == true`.*

#### 1. Database & Migrations
- **Tabel `products`**:
  - Tambah kolom `base_unit` VARCHAR(30) DEFAULT 'Pcs'.
- **Tabel Baru `product_units`**:
  ```sql
  CREATE TABLE product_units (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      product_id BIGINT NOT NULL,
      name VARCHAR(50) NOT NULL,          -- Lusin, Box, Dus, Kodi, Renteng, Karton
      multiplier INT NOT NULL,            -- Pengali ke satuan terkecil (12, 20, 24, 100, dll.)
      price DECIMAL(12, 2) NOT NULL,      -- Harga jual dalam satuan ini
      barcode VARCHAR(100) NULL,          -- Barcode fisik kemasan satuan ini
      is_default_purchase BOOLEAN DEFAULT FALSE,
      is_active BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP,
      updated_at TIMESTAMP
  );
  ```

#### 2. Web Admin & POS Mobile
- Form Produk di Web: jika toko punya `addon_multi_unit`, tampilkan tabel dinamis satuan konversi.
- Kasir POS Mobile: scan barcode kardus/pack otomatis masuk sesuai satuan, atau tap produk memunculkan pilihan satuan (Pcs / Lusin / Dus).
- Pengurangan stok otomatis mengalikan qty dengan `multiplier` ke Satuan Dasar.

---

### Component 4: Nomor Batch, Expired Date & Metode FEFO

*Aktif jika `store.addon_fefo == true`.*

#### 1. Database & Migrations
- **Tabel `stock_batches`**:
  - Tambah kolom `batch_number` (string 50, nullable).
  - Tambah kolom `expired_date` (date, nullable, indexed).

#### 2. Logika Pengeluaran Stok (Modular Strategy Pattern)
- `StockService.php` memilih strategi:
  - Jika `store.addon_fefo == true`: Urutkan berdasarkan `expired_date ASC, tanggal_masuk ASC`.
  - Jika tidak: Urutkan murni FIFO `tanggal_masuk ASC`.
- Web Dashboard: Peringatan produk yang mendekati kadaluarsa.

---

### Component 5: Fitur Penjualan Obat Racikan

*Aktif jika `store.addon_concoction == true`.*

#### 1. Alur di Kasir POS Mobile
- Tombol **"+ Obat Racikan"** hanya muncul di kasir jika `store.addonConcoction == true`.
- Dialog racikan: nama racikan, bentuk sediaan, signa aturan pakai, bahan obat dalam satuan dasar, serta biaya kemasan/jasa (embalase).
- Cetak struk transaksi sekaligus **etiket obat racikan** pada printer thermal.

---

## Verification Plan

### Automated Tests
1. **Test Addon Store Configuration**:
   - Pembuatan toko `retail` dengan `addon_multi_unit = true` (grosir/toko baju grosir) dan `addon_sales_person = true`.
   - Pembuatan toko `pharmacy` dengan semua addon aktif.
2. **Test Multi-Satuan di Retail**:
   - Toko Baju: Jual 1 Lusin kaos (multiplier 12) memotong 12 pcs kaos di stok dasar.
   - Apotik: Jual 1 Box (multiplier 100) memotong 100 tablet di stok dasar.
3. **Test Isolasi FEFO**:
   - Toko dengan `addon_fefo = true` memotong batch dengan tanggal kadaluarsa terdekat.
   - Toko retail biasa (`addon_fefo = false`) memotong batch berdasarkan FIFO tanpa memperhatikan expired_date.
4. **Test Sales Person**:
   - Transaksi mencatat `sales_person_id` dan rekap laporan omset per sales person akurat.

### Manual Verification
1. Masuk ke menu **Pengaturan -> Manage Toko** di web admin. Buat toko `Retail` dengan mengaktifkan checkbox `Multi-Satuan Bertingkat` dan `Sales Person`.
2. Login di Flutter POS, pastikan kasir toko retail tersebut bisa memilih satuan (misal Lusin/Kodi/Pcs) dan memilih pramuniaga saat checkout.
