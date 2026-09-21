# Panduan Integrasi API Eksternal RIMS POS (RESTful API)

Dokumentasi ini ditujukan bagi pengembang sistem pihak ketiga (website e-commerce, ERP, aplikasi analitik, sistem loyalitas, atau aplikasi pihak ketiga lainnya) yang ingin membaca data toko, kategori, katalog produk, sisa stok, dan transaksi penjualan dari **RIMS POS**.

---

## 1. Konsep Dasar & Keamanan

* **Autentikasi**: Berbasis **API-KEY** melalui HTTP Header `X-API-KEY`.
* **Isolasi Data (Multi-Store)**: Setiap API Key terikat secara ketat pada satu cabang toko (`store_id`). Seluruh data yang dikembalikan terisolasi hanya untuk toko pemilik kunci tersebut.
* **Format Response**: Semua response dikirimkan dalam format **JSON** standar.
* **Rate Limit**: Default 60 request/menit (dapat dikonfigurasi per API Key).
* **IP Whitelist**: Jika diaktifkan pada pengaturan toko, request hanya akan diproses dari daftar alamat IP yang diizinkan.

---

## 2. Header Permintaan (Request Headers)

Setiap request ke endpoint integrasi **wajib** menyertakan header berikut:

```http
Accept: application/json
X-API-KEY: rims_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

> [!IMPORTANT]
> Jangan pernah membagikan atau mempublikasikan API Key Anda di repositori publik (seperti GitHub client-side code). Gunakan API Key di sisi backend / server-to-server.

---

## 3. Base URL

```text
Production / Local:
https://pos.domainanda.com/api/v1/external
http://localhost:8000/api/v1/external
```

---

## 4. Daftar Endpoint

| Method | Endpoint | Hak Akses (Ability) | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/store` | Bebas / Terautentikasi | Profil & informasi toko terasosiasi |
| `GET` | `/categories` | `categories:read` | Daftar kategori produk toko |
| `GET` | `/products` | `products:read` | Katalog produk (pagination, varian, harga & stok) |
| `GET` | `/products/{id}` | `products:read` | Detail satu produk spesifik |
| `GET` | `/stock` | `stock:read` | Ringkasan kondisi stok barang toko real-time |
| `GET` | `/sales` | `sales:read` | Riwayat transaksi penjualan (filter tanggal & status) |
| `GET` | `/sales/{id}` | `sales:read` | Detail lengkap faktur penjualan & rincian item |

---

## 5. Rincian Endpoint & Contoh Request / Response

### 5.1 Profil Toko (`GET /store`)

Mengembalikan informasi umum toko yang terhubung dengan API Key.

**Contoh Request (cURL):**
```bash
curl -X GET "http://localhost:8000/api/v1/external/store" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Informasi profil toko berhasil diambil.",
  "data": {
    "id": 1,
    "name": "Kopi Alazca Cabang Utama",
    "code": "ALZ01",
    "business_type": "fnb",
    "address": "Jl. Merdeka No. 45",
    "city": "Banda Aceh",
    "phone": "081234567890",
    "printer_type": "80mm",
    "logo_url": "http://localhost:8000/storage/stores/logo.png",
    "qris_url": "http://localhost:8000/storage/stores/qris.png",
    "addons": {
      "self_service": true,
      "kds": true,
      "multi_printer": false,
      "multi_unit": false,
      "fefo": false,
      "concoction": false,
      "sales_person": false
    },
    "subscription": {
      "status": "active",
      "starts_at": "2026-01-01T00:00:00+07:00",
      "expires_at": "2027-01-01T23:59:59+07:00",
      "is_expired": false
    }
  },
  "meta": {
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

### 5.2 Kategori Produk (`GET /categories`)

Mengambil daftar kategori produk yang aktif pada toko tersebut.

**Contoh Request (cURL):**
```bash
curl -X GET "http://localhost:8000/api/v1/external/categories" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Daftar kategori berhasil diambil.",
  "data": [
    {
      "id": 3,
      "name": "Minuman Kopi",
      "slug": "minuman-kopi",
      "icon": "bi-cup-hot",
      "sort_order": 1,
      "products_count": 14
    }
  ],
  "meta": {
    "total": 1,
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

### 5.3 Katalog Produk (`GET /products`)

Mengambil daftar produk lengkap beserta varian, harga jual, barcode, dan stok saat ini.

**Query Parameters:**
* `search` *(opsional)*: Kata kunci nama produk, kode produk, atau SKU.
* `category_id` *(opsional)*: Filter ID kategori spesifik.
* `per_page` *(opsional)*: Jumlah per halaman (default 20, maks 100).
* `page` *(opsional)*: Nomor halaman paginasi.

**Contoh Request (cURL):**
```bash
curl -X GET "http://localhost:8000/api/v1/external/products?search=espresso&per_page=10" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Daftar produk berhasil diambil.",
  "data": [
    {
      "id": 42,
      "kode_produk": "PRD-0042",
      "nama_produk": "Espresso Single Origin",
      "deskripsi": "Kopi arabika gayo roasted medium",
      "product_type": "standard",
      "image_url": "http://localhost:8000/storage/products/espresso.jpg",
      "category": {
        "id": 3,
        "name": "Minuman Kopi"
      },
      "units": [],
      "variants": [
        {
          "id": 78,
          "variant_name": "Hot 120ml",
          "sku": "ESP-HOT",
          "barcode": "899123456789",
          "harga_jual": 22000,
          "track_stock": true,
          "stock_store": 45,
          "stock_warehouse": 100,
          "stock_total": 145,
          "is_available": true,
          "is_sold_out": false,
          "image_url": null,
          "barcodes": ["899123456789"]
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1,
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

### 5.4 Detail Produk (`GET /products/{id}`)

Mengambil rincian produk tunggal berdasarkan ID produk.

**Contoh Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/external/products/42" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

---

### 5.5 Stok Real-Time (`GET /stock`)

Mengambil daftar sisa stok per varian barang yang mengaktifkan pelacakan stok (`track_stock = true`).

**Query Parameters:**
* `search` *(opsional)*: Cari nama produk / varian / barcode.
* `low_stock` *(opsional)*: `true` / `1` untuk menyaring hanya barang yang stoknya menipis.
* `threshold` *(opsional)*: Ambang batas stok menipis (default: 5).
* `per_page` *(opsional)*: Jumlah per halaman (default 50, maks 150).

**Contoh Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/external/stock?low_stock=true&threshold=3" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Data stok toko berhasil diambil.",
  "data": [
    {
      "variant_id": 78,
      "product_id": 42,
      "product_name": "Espresso Single Origin",
      "variant_name": "Hot 120ml",
      "sku": "ESP-HOT",
      "barcode": "899123456789",
      "stock_store": 2,
      "stock_warehouse": 0,
      "stock_total": 2,
      "is_sold_out": false
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1,
    "last_page": 1,
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

### 5.6 Riwayat Transaksi Penjualan (`GET /sales`)

Mengambil daftar penjualan yang tercatat di toko POS.

**Query Parameters:**
* `start_date` *(opsional)*: Format `YYYY-MM-DD` (contoh: `2026-09-01`).
* `end_date` *(opsional)*: Format `YYYY-MM-DD` (contoh: `2026-09-21`).
* `status` *(opsional)*: Filter status nota (`paid`, `unpaid`, `void`, `draft`).
* `payment_method` *(opsional)*: Filter metode pembayaran (`cash`, `qris`, `transfer`, dll.).
* `per_page` *(opsional)*: Jumlah data per halaman (default 20, maks 100).

**Contoh Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/external/sales?start_date=2026-09-20&status=paid" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Riwayat transaksi penjualan berhasil diambil.",
  "data": [
    {
      "id": 1024,
      "no_faktur": "INV-20260921-0012",
      "sale_date": "2026-09-21T11:45:00+07:00",
      "status": "paid",
      "payment_method": "qris",
      "payment_status": "paid",
      "customer": {
        "id": 15,
        "name": "Budi Santoso",
        "phone": "081987654321"
      },
      "subtotal": 50000,
      "discount_total": 5000,
      "tax": 0,
      "tip_amount": 0,
      "grand_total": 45000,
      "total_paid": 45000,
      "change_amount": 0,
      "items_count": 2
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

### 5.7 Detail Faktur Penjualan (`GET /sales/{id}`)

Mengambil rincian item, harga satuan, dan diskon untuk satu faktur transaksi.

**Contoh Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/external/sales/1024" \
  -H "Accept: application/json" \
  -H "X-API-KEY: rims_live_a1b2c3d4e5f6..."
```

**Contoh Response:**
```json
{
  "status": "success",
  "message": "Detail transaksi penjualan berhasil diambil.",
  "data": {
    "id": 1024,
    "no_faktur": "INV-20260921-0012",
    "sale_date": "2026-09-21T11:45:00+07:00",
    "status": "paid",
    "payment_method": "qris",
    "payment_status": "paid",
    "notes": "Pesanan Meja 4",
    "customer": {
      "id": 15,
      "name": "Budi Santoso",
      "phone": "081987654321",
      "email": "budi@gmail.com"
    },
    "subtotal": 50000,
    "discount_total": 5000,
    "tax": 0,
    "tip_amount": 0,
    "grand_total": 45000,
    "total_paid": 45000,
    "change_amount": 0,
    "items": [
      {
        "id": 2048,
        "product_id": 42,
        "product_name": "Espresso Single Origin",
        "variant_id": 78,
        "variant_name": "Hot 120ml",
        "qty": 2,
        "price": 25000,
        "discount": 5000,
        "subtotal": 45000,
        "notes": "Gula pisah",
        "status": "sold"
      }
    ]
  },
  "meta": {
    "timestamp": "2026-09-21T12:00:00+07:00"
  }
}
```

---

## 6. Penanganan Error (HTTP Status Codes)

| Kode HTTP | Penjelasan | Contoh Pesan Response |
| :---: | :--- | :--- |
| **401** | Kunci API hilang, tidak valid, atau kedaluwarsa | `{"status": "error", "message": "API Key diperlukan. Sertakan header X-API-KEY pada permintaan."}` |
| **403** | Akses IP ditolak atau hak akses (ability) tidak mencukupi | `{"status": "error", "message": "Akses ditolak. Alamat IP [x.x.x.x] tidak terdaftar dalam IP Whitelist."}` |
| **404** | Data yang diminta (ID produk/faktur) tidak ditemukan atau bukan milik toko ini | `{"status": "error", "message": "Produk tidak ditemukan."}` |
| **429** | Melewati batas kuota panggilan per menit (Rate Limit Exceeded) | `{"status": "error", "message": "Too Many Requests."}` |
| **500** | Terjadi kendala internal server | `{"status": "error", "message": "Internal Server Error."}` |

---

## 7. Cara Membuat API Key di Web Admin RIMS POS

1. Masuk ke halaman **Pengaturan > Manage Toko** (`/stores`).
2. Klik tombol **"Kelola"** (ikon speedometer) pada baris toko yang ingin diintegrasikan.
3. Buka tab **"Integrasi API Key"**.
4. Klik tombol **"Buat API Key Baru"**:
   - Beri nama deskriptif (contoh: *Integrasi Website Toko Online*).
   - Tentukan hak akses yang diizinkan (default: *products:read*, *categories:read*, *stock:read*, *sales:read*).
   - *(Opsional)* Masukkan daftar IP Whitelist server pemanggil.
   - *(Opsional)* Tentukan batas masa aktif atau biarkan kosong untuk selamanya.
5. Tekan **Generate Kunci API**.
6. Salin API Key yang muncul pada layar dan simpan di server pihak ketiga Anda.
