# Panduan Penggunaan Modul Loyalty Points Member

Dokumentasi ini menjelaskan secara lengkap alur kerja, metode kalkulasi, konfigurasi, dan daftar endpoint API (URL) yang digunakan untuk modul **Loyalty Points (Poin Loyalitas) Member** di sistem POS RimsPOS (Web & Mobile).

---

## 1. Konsep Dasar & Alur Kerja

Program Loyalty Points memungkinkan pelanggan terdaftar (Member) untuk:
1. **Mengumpulkan Poin (Earn)** saat berbelanja di toko berdasarkan aturan yang dikonfigurasi.
2. **Menukarkan Poin (Redeem)** menjadi potongan nominal pembayaran saat checkout transaksi.
3. **Menerima Bonus Poin** saat pendaftaran pertama kali (*Welcome Points*) dan saat berbelanja di hari ulang tahun mereka (*Birthday Multiplier*).
4. **Pembatalan Poin (Void/Refund)** otomatis jika transaksi dibatalkan.

---

## 2. Pengaturan Loyalty Points (Settings)

Pengaturan ini dapat disesuaikan secara **Global** untuk semua cabang toko, atau di-**Override (Kustomisasi)** khusus untuk cabang toko tertentu saja.

| Field Konfigurasi | Deskripsi |
| :--- | :--- |
| `is_active` | Mengaktifkan atau menonaktifkan program poin loyalitas. |
| `is_override` | Jika dicentang, cabang toko bersangkutan menggunakan pengaturan mandiri yang terpisah dari pengaturan global. |
| `earning_method` | Metode kalkulasi perolehan poin: `transaction`, `product`, atau `hybrid`. |
| `earning_threshold` | Kelipatan belanja nominal (Rupiah) untuk mendapatkan poin (hanya berlaku pada metode `transaction` dan `hybrid`). Contoh: Rp 10.000. |
| `earning_points` | Jumlah poin yang diperoleh per kelipatan belanja. Contoh: 1 Poin. |
| `exclude_tax` | Poin tidak dihitung dari nilai pajak (PPN/PB1). |
| `exclude_service_charge` | Poin tidak dihitung dari biaya layanan (*Service Charge*). |
| `exclude_delivery_fee` | Poin tidak dihitung dari biaya pengiriman (*Delivery Fee*). |
| `exclude_promo_items` | Produk yang memiliki diskon/promo coret tidak akan menghasilkan poin. |
| `point_value` | Nilai tukar 1 Poin ke Rupiah. Contoh: 1 Poin = Rp 100. |
| `min_points_to_redeem` | Minimal saldo poin yang harus dimiliki member sebelum dapat melakukan penukaran. |
| `max_redeem_percentage` | Batas maksimal potongan pembayaran dari poin dalam persen (%) dari total bill. |
| `max_redeem_amount` | Batas maksimal potongan nominal rupiah (Rp) per transaksi (0 = Tanpa batas). |
| `expiration_type` | Tipe masa berlaku poin: `never` (tidak pernah kedaluwarsa), `duration` (berdasarkan jumlah bulan), atau `fixed_date` (kedaluwarsa pada tanggal tertentu setiap tahun). |
| `welcome_points` | Bonus poin instan saat member baru pertama kali didaftarkan. |
| `birthday_multiplier` | Faktor pengali poin jika member berbelanja tepat pada hari ulang tahunnya (Contoh: `2.00` untuk poin ganda). |

---

## 3. Metode Perolehan Poin (Earning)

Sistem menghitung poin secara otomatis saat checkout berdasarkan metode terpilih:

### A. Nominal Transaksi (`transaction`)
Poin dihitung dari total pembelanjaan bersih yang memenuhi syarat (*Eligible Spend*) dibagi kelipatan nominal belanja, lalu dikali jumlah poin.
* **Rumus:** `Floor(Eligible Spend / Earning Threshold) * Earning Points`
* **Contoh:** Belanja Rp 25.000 dengan threshold Rp 10.000 dan reward 1 Poin = Member mendapat **2 Poin**.

### B. Berdasarkan Produk (`product`)
Poin dihitung berdasarkan poin reward spesifik yang disematkan langsung pada masing-masing varian produk (`reward_points` di tabel `product_variants`).
* **Rumus:** Total akumulasi `(Reward Point per Item * Qty Item)` dari seluruh produk yang dibeli.

### C. Hybrid (`hybrid`)
Gabungan kedua metode di atas. Jika produk memiliki poin khusus, maka poin produk yang digunakan. Untuk produk biasa yang tidak memiliki poin khusus, perhitungannya menggunakan rasio nominal transaksi.

### Pengecualian (*Exclusions*) & Hari Ulang Tahun
* **Pajak & Biaya:** Jika `exclude_tax`, `exclude_service_charge`, atau `exclude_delivery_fee` aktif, nominal biaya-biaya tersebut akan dikurangkan dari nilai transaksi sebelum perhitungan poin.
* **Birthday Multiplier:** Jika hari ini adalah hari ulang tahun member (berdasarkan `birth_date` member), poin yang diperoleh akan dikalikan dengan `birthday_multiplier`.

---

## 4. Penukaran Poin (Redemption)

Pada halaman kasir (POS), pengguna dapat memasukkan jumlah poin yang ingin ditukarkan oleh member. Sistem memvalidasi batasan penukaran:
1. **Poin Cukup:** Poin yang didebit tidak boleh melebihi saldo member.
2. **Minimal Poin:** Poin harus memenuhi `min_points_to_redeem`.
3. **Maksimal Persentase:** Diskon dari poin tidak boleh melebihi `max_redeem_percentage` dari total tagihan.
4. **Maksimal Rupiah:** Potongan rupiah dari poin tidak boleh melebihi `max_redeem_amount`.

Saat checkout berhasil, saldo poin member langsung berkurang, dan detail transaksi mencatat `points_redeemed` serta `point_discount_amount`.

---

## 5. Daftar URL & Endpoint API

Berikut adalah seluruh rute yang digunakan untuk mengelola konfigurasi, member, dan checkout dengan program loyalty point:

### A. Pengaturan Loyalty Points (Backend Web)

* **Menampilkan Form Pengaturan**
  * **URL:** `GET /settings/points`
  * **Route Name:** `settings.points`
  * **Controller:** `App\Http\Controllers\PointSettingController@index`
  * **Deskripsi:** Menampilkan halaman konfigurasi loyalty point (global & override cabang).

* **Menyimpan/Memperbarui Pengaturan**
  * **URL:** `POST /settings/points`
  * **Route Name:** `settings.points.update`
  * **Request Payload (JSON):**
    ```json
    {
      "is_active": "1",
      "is_override": "0",
      "earning_method": "transaction",
      "earning_threshold": "10000",
      "earning_points": "1",
      "exclude_tax": "1",
      "exclude_service_charge": "1",
      "exclude_delivery_fee": "1",
      "point_value": "100",
      "min_points_to_redeem": "0",
      "max_redeem_percentage": "100",
      "max_redeem_amount": "0",
      "expiration_type": "never",
      "expiration_duration_months": "12",
      "expiration_fixed_date": "12-31",
      "welcome_points": "0",
      "birthday_multiplier": "1.00",
      "exclude_promo_items": "0"
    }
    ```
  * **Controller:** `App\Http\Controllers\PointSettingController@update`

---

### B. Manajemen Member & Riwayat Poin

* **Daftar & Pencarian Member**
  * **URL:** `GET /members`
  * **Route Name:** `members.index`
  * **Controller:** `App\Http\Controllers\MemberController@index`
  * **Keterangan:** Mendukung request AJAX Datatables untuk menampilkan daftar member dan saldo poin mereka (`total_points`).

* **Pendaftaran Member Baru**
  * **URL:** `POST /members`
  * **Route Name:** `members.store`
  * **Request Payload:**
    ```json
    {
      "name": "Nama Lengkap",
      "phone": "081234567890",
      "email": "member@example.com",
      "birth_date": "1995-12-31"
    }
    ```
  * **Keterangan:** Otomatis menambahkan *Welcome Points* ke member baru jika dikonfigurasi aktif.

* **Pembaruan Data Member**
  * **URL:** `PUT/PATCH /members/{member}`
  * **Route Name:** `members.update`
  * **Request Payload:** Sama seperti pendaftaran member.

* **Penghapusan Member**
  * **URL:** `DELETE /members/{member}`
  * **Route Name:** `members.destroy`

* **Melihat Riwayat Mutasi Poin Member**
  * **URL:** `GET /members/{member}/history`
  * **Route Name:** `members.history`
  * **Controller:** `App\Http\Controllers\MemberController@history`
  * **Deskripsi:** Menampilkan riwayat transaksi penambahan/pemotongan poin (*earn, redeem, adjust*) lengkap dengan nomor invoice penjualan terkait.

---

### C. Checkout POS (Point of Sale)

* **Checkout POS Web**
  * **URL:** `POST /pos/checkout`
  * **Route Name:** `pos.checkout`
  * **Request Payload (JSON):**
    ```json
    {
      "cart": {
        "member_id": "3",
        "points_to_redeem": 100,
        "payment_method": "cash",
        "paid_amount": 50000,
        "total": 40000,
        "subtotal": 40000,
        "discount_total": 0,
        "items": [
          {
            "product_id": 12,
            "variant_id": 25,
            "sku": "SKU-PROD-A",
            "price": 20000,
            "qty": 2,
            "discount_amount": 0,
            "subtotal": 40000
          }
        ]
      }
    }
    ```
  * **Deskripsi:** Memproses transaksi belanja. Jika `member_id` disertakan, poin belanja akan otomatis di-kalkulasi dan di-kreditkan. Jika `points_to_redeem` diisi > 0, saldo poin member didebit.

* **Checkout POS Mobile App (API)**
  * **URL:** `POST /api/pos/checkout` (atau rute API internal kasir)
  * **Controller:** `App\Http\Controllers\PosController@apiCheckout`
  * **Deskripsi:** Sama seperti checkout POS Web namun digunakan oleh aplikasi Flutter (Mobile) dengan format payload yang identik.

---

## 6. Pembatalan Transaksi (Void & Refund)

Ketika transaksi dibatalkan atau dibatalkan sepihak (*Void*):
* Rute Void: `POST /sales/{sale}/void` (`sales.void`)
* Rute Refund: `POST /sales/{sale}/refund` (`sales.refund`)

Sistem akan memanggil fungsi `revertPointsForVoid()` di `LoyaltyPointService` untuk memulihkan saldo:
1. Poin yang **diperoleh (earned)** dari transaksi tersebut akan **ditarik/dikurangkan** kembali dari saldo member.
2. Poin yang **ditukarkan (redeemed)** sebagai diskon pembayaran akan **dikembalikan (credit back)** ke saldo member.
3. Mutasi ini tercatat sebagai tipe penyesuaian `adjust` di riwayat poin member.
