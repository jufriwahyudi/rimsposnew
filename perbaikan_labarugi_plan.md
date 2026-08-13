# Rencana Implementasi: Integrasi Resep Bahan Baku ke Laporan Laba Rugi

Rencana ini bertujuan untuk menyertakan biaya modal bahan baku mentah (HPP resep) ke dalam Laporan Laba Rugi (Profit & Loss) dan ekspor Excel. HPP produk dengan tipe `RECIPE` akan dihitung secara dinamis dari total takaran bahan baku dikali harga estimasi beli (HPP bahan baku).

---

## Terobosan Rencana & Dampak Perubahan
1. **Dynamic COGS (HPP) Calculation**:
   * Jika produk yang terjual bertipe `RECIPE`, HPP item tersebut dihitung dengan menjumlahkan takaran resep dikali harga beli bahan baku:
     $$\text{HPP Item} = \sum (\text{takaran\_resep} \times \text{HPP\_bahan\_baku}) \times \text{jumlah\_beli}$$
   * Jika bertipe `SINGLE`, perhitungan tetap menggunakan fallback lama (`cost_price_manual` atau FIFO batch).
2. **Eager Loading Optimization**:
   * Menambahkan relasi `recipes.ingredient` pada eager loading `items` di query penjualan agar performa query tetap cepat dan terhindar dari masalah N+1 database queries.

---

## Rencana Perubahan File

### 1. Backend Controller

#### [MODIFY] [LaporanController.php](file:///d:/laravel/rimspos/app/Http/Controllers/LaporanController.php)
* Memperbarui query eager loading pada method `getLabaRugi()` (baris 717) untuk memuat relasi resep dan bahan bakunya:
  ```php
  'items' => fn($q) => $q->with(['batches', 'variant.product.recipes.ingredient', 'variant.product.tenant', 'fnbDetail'])
  ```
* Memperbarui logika perulangan HPP penjualan (baris 731-751) untuk mendeteksi `product_type === 'RECIPE'` dan menjumlahkan estimasi HPP bahan baku pendukungnya.

---

### 2. Excel Export Class

#### [MODIFY] [LaporanLabaRugiExport.php](file:///d:/laravel/rimspos/app/Exports/LaporanLabaRugiExport.php)
* Memperbarui query eager loading pada method `array()` (baris 27) untuk menyertakan relasi resep:
  ```php
  'items' => fn($q) => $q->with(['batches', 'variant.product.recipes.ingredient', 'variant.product.tenant', 'fnbDetail'])
  ```
* Memperbarui alur kalkulasi loop `$hpp` (baris 39-59) agar menggunakan HPP resep untuk produk bertipe `RECIPE`, serupa dengan logika di `LaporanController`.

---

## Rencana Verifikasi

### Pengujian Otomatis
* Menjalankan unit tests untuk memastikan keutuhan sistem tidak terganggu.
  ```bash
  php artisan test --filter=IngredientInventoryServiceTest
  ```

### Pengujian Manual
1. Buat bahan baku baru dengan HPP per unit di Master Bahan Baku.
2. Buat produk menu jual bertipe `RECIPE` dan tambahkan bahan baku tersebut ke resepnya di Menu Resep.
3. Catat transaksi penjualan lunas untuk menu resep tersebut di kasir.
4. Buka halaman **Laporan Laba Rugi**, pilih periode transaksi, dan verifikasi nilai **HPP / Modal** yang tertera sudah mengakomodasi biaya bahan baku dikalikan kuantitas terjual.
5. Ekspor laporan ke Excel dan verifikasi bahwa angka HPP di file Excel sama dengan tampilan di halaman web.
