# Product Requirement Document (PRD)
## Omni-Channel Multi-Outlet Laundry SaaS

| Metadata | Detail |
| :--- | :--- |
| **Project Name** | Omni-Channel Multi-Outlet Laundry SaaS |
| **Version** | 2.1 (Mature Edition — Stack Confirmed) |
| **Status** | Ready for Development |
| **Last Updated** | 30 Juni 2026 |
| **Author** | Product Owner |

---

## Daftar Isi

1. [Visi & Sasaran Bisnis](#1-visi--sasaran-bisnis)
2. [Aktor & Hierarki Akses](#2-aktor--hierarki-akses)
3. [Peta Dua Platform Utama](#3-peta-dua-platform-utama)
4. [Platform 1 — Web Application](#4-platform-1--web-application)
   - [4.1 SuperAdmin Panel (SaaS Management)](#41-superadmin-panel-saas-management)
   - [4.2 Owner Dashboard (Bisnis Management)](#42-owner-dashboard-bisnis-management)
   - [4.3 Customer Portal (Public Tracking)](#43-customer-portal-public-tracking)
5. [Platform 2 — Mobile/Tablet Flutter App](#5-platform-2--mobiletablet-flutter-app)
   - [5.1 Peran & Hak Akses dalam App](#51-peran--hak-akses-dalam-app)
   - [5.2 Modul Kasir (POS)](#52-modul-kasir-pos)
   - [5.3 Modul Produksi (Floor Worker)](#53-modul-produksi-floor-worker)
   - [5.4 Omni-Channel Service — UI Dinamis](#54-omni-channel-service--ui-dinamis)
   - [5.5 Notifikasi WhatsApp Redirect](#55-notifikasi-whatsapp-redirect)
6. [Arsitektur Backend & Modular System](#6-arsitektur-backend--modular-system)
7. [State Machine — Alur Kerja Pesanan](#7-state-machine--alur-kerja-pesanan)
8. [Kebutuhan Non-Fungsional](#8-kebutuhan-non-fungsional)
9. [Cetak Biru Skema Database](#9-cetak-biru-skema-database)
10. [Roadmap Pengembangan](#10-roadmap-pengembangan)

---

## 1. Visi & Sasaran Bisnis

### 1.1. Latar Belakang
Industri laundry di Indonesia didominasi oleh usaha skala kecil–menengah yang masih beroperasi secara manual atau dengan sistem kasir sederhana. Bisnis multi-cabang menghadapi tantangan besar dalam koordinasi operasional, pencegahan kecurangan, dan pemantauan performa lintas outlet secara real-time.

### 1.2. Visi Produk
Menjadi **platform SaaS laundry #1 di Indonesia** yang memungkinkan pemilik usaha mengelola operasional semua outlet dari satu ekosistem digital — mulai dari transaksi di lantai kasir hingga laporan keuangan konsolidasi.

### 1.3. Core Value Proposition (USP)

| # | Nilai | Deskripsi |
|---|---|---|
| 1 | **Loss Prevention & Anti-Fraud** | Pelacakan multi-keranjang (*multi-basket tracking*) mencegah pakaian hilang/tertukar. Rekonsiliasi bahan baku mendeteksi kecurangan karyawan secara otomatis. |
| 2 | **Omni-Channel Service** | Satu sistem mendukung 4 model bisnis: Kiloan, Satuan/Premium, Per Mesin (Lot), dan Self-Service. |
| 3 | **Cost-Efficient Bootstrap** | Notifikasi WhatsApp via redirect link tanpa biaya API gateway di fase awal. |
| 4 | **Multi-Outlet Real-Time** | Owner bisnis memantau performa semua cabang dari satu dashboard terpadu. |
| 5 | **Offline-First Mobile** | Operasional toko tetap berjalan saat internet terputus, sinkronisasi otomatis saat online. |

---

## 2. Aktor & Hierarki Akses

```
┌─────────────────────────────────────────────────────────────┐
│                    HIERARKI AKSES SISTEM                     │
│                                                              │
│  [SuperAdmin] ──── Mengelola seluruh platform SaaS           │
│       │                                                      │
│       ├── [Tenant / Owner Bisnis] ─── Mengelola 1+ Outlet    │
│       │         │                                            │
│       │         ├── [Outlet Manager] ─── 1 Outlet saja       │
│       │         │         │                                  │
│       │         │         ├── [Kasir] ─── Operasional POS    │
│       │         │         └── [Staff Produksi] ─ Floor Work  │
│       │         │                                            │
│       │         └── [Pelanggan] ─── Portal Publik (no login) │
└─────────────────────────────────────────────────────────────┘
```

| Aktor | Platform | Akses Utama |
| :--- | :--- | :--- |
| **SuperAdmin** | Web Only | Manajemen tenant, subscription plan, global analytics, billing, user system |
| **Owner Bisnis** | Web + (Mobile read-only opsional) | Dashboard omzet, laporan laba/rugi, manajemen outlet, manajemen karyawan |
| **Outlet Manager** | Web (terbatas) + Mobile | Laporan outlet sendiri, pengaturan outlet, approval |
| **Kasir** | Mobile Only | Input transaksi, pembayaran, notifikasi WhatsApp |
| **Staff Produksi** | Mobile Only | Scan keranjang, update status produksi |
| **Pelanggan** | Customer Portal (Web) | Tracking pesanan & kuota membership |

---

## 3. Peta Dua Platform Utama

```
┌──────────────────────────────────────────────────────────────────┐
│                      EKOSISTEM PLATFORM                          │
│                                                                  │
│  ┌─────────────────────────┐    ┌────────────────────────────┐   │
│  │      WEB APPLICATION     │    │  MOBILE/TABLET FLUTTER APP │   │
│  │   (Laravel + Filament)   │    │      (Android / iOS)       │   │
│  │                          │    │                            │   │
│  │  ┌──────────────────┐    │    │  ┌──────────────────────┐  │   │
│  │  │  SuperAdmin Panel │    │    │  │   Modul Kasir (POS)  │  │   │
│  │  │  - Kelola Tenant  │    │    │  │   - Buat Transaksi   │  │   │
│  │  │  - Kelola Plan    │    │    │  │   - Bayar & Cetak    │  │   │
│  │  │  - Global Stats   │    │    │  │   - Notif WhatsApp   │  │   │
│  │  └──────────────────┘    │    │  └──────────────────────┘  │   │
│  │                          │    │                            │   │
│  │  ┌──────────────────┐    │    │  ┌──────────────────────┐  │   │
│  │  │  Owner Dashboard  │    │    │  │  Modul Produksi      │  │   │
│  │  │  - Multi-Outlet   │    │    │  │  - Scan Keranjang    │  │   │
│  │  │  - Laporan & KPI  │    │    │  │  - Update Status     │  │   │
│  │  │  - Kelola Outlet  │    │    │  │  - Manajemen Mesin   │  │   │
│  │  └──────────────────┘    │    │  └──────────────────────┘  │   │
│  │                          │    │                            │   │
│  │  ┌──────────────────┐    │    └────────────────────────────┘   │
│  │  │  Customer Portal  │    │                                    │
│  │  │  (Publik/Token)   │    │       ↕ API (Laravel Sanctum)      │
│  │  └──────────────────┘    │                                    │
│  └─────────────────────────┘    ┌────────────────────────────┐   │
│                                 │   BACKEND: Laravel 11.x    │   │
│                                 │   + nwidart/laravel-modules │   │
│                                 └────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Platform 1 — Web Application

> **Stack**: Laravel 13.x + `nwidart/laravel-modules` + **Inertia.js + Vue 3 + Tailwind CSS**
> **Tujuan**: Pusat kontrol SuperAdmin dan dashboard analitik Owner Bisnis. **Bukan untuk operasional toko harian.**

---

### 4.1 SuperAdmin Panel (SaaS Management)

SuperAdmin adalah pemilik platform SaaS (Anda). Panel ini adalah *mission control* seluruh ekosistem.

#### 4.1.1. Manajemen Tenant

| Fitur | Deskripsi |
| :--- | :--- |
| **Daftar Semua Tenant** | Lihat semua bisnis laundry yang terdaftar beserta status subscription |
| **Detail Tenant** | Informasi lengkap: nama bisnis, PIC, email, jumlah outlet aktif, total transaksi |
| **Suspend / Aktifkan Tenant** | Toggle akses tenant ke seluruh sistem |
| **Impersonate Tenant** | Login *as* owner bisnis tertentu untuk debugging/support |
| **Reset Password Tenant** | Bantuan akses jika owner lupa password |
| **Hapus Tenant** | Soft delete + grace period sebelum data dihapus permanen |

#### 4.1.2. Manajemen Subscription & Pricing Plan

| Fitur | Deskripsi |
| :--- | :--- |
| **Master Plan** | Buat & kelola paket berlangganan (misal: Starter, Pro, Enterprise) |
| **Batas Fitur per Plan** | Konfigurasikan limit outlet, karyawan, dan fitur per plan |
| **Assign Plan ke Tenant** | Ubah plan tenant (upgrade/downgrade) secara manual |
| **Periode Trial** | Atur durasi masa percobaan gratis untuk tenant baru |
| **Riwayat Pembayaran** | Log semua transaksi subscription dari seluruh tenant |

**Contoh Struktur Plan:**

| Plan | Maks Outlet | Maks Karyawan | Harga/Bulan |
|---|---|---|---|
| **Starter** | 1 | 5 | Rp 99.000 |
| **Pro** | 5 | 25 | Rp 299.000 |
| **Enterprise** | Unlimited | Unlimited | Custom |

#### 4.1.3. Global Analytics Dashboard

| Metrik | Deskripsi |
| :--- | :--- |
| **Total Tenant Aktif** | Jumlah tenant berlangganan aktif |
| **Total Outlet** | Jumlah outlet operasional di seluruh platform |
| **Total Transaksi** | Volume transaksi agregat platform per hari/minggu/bulan |
| **MRR (Monthly Recurring Revenue)** | Proyeksi pendapatan langganan bulanan |
| **Churn Rate** | Tingkat kehilangan subscriber |
| **Tenant Growth Chart** | Grafik pertumbuhan tenant baru per periode |

#### 4.1.4. Pengumuman & Broadcast Notifikasi
- Kirim pengumuman (maintenance, fitur baru) ke seluruh tenant atau tenant tertentu.
- Pengumuman tampil sebagai banner alert di Owner Dashboard.

#### 4.1.5. Audit Log Platform
- Rekam seluruh aksi kritis SuperAdmin (suspend tenant, ubah plan, impersonate).
- Riwayat dapat difilter berdasarkan aktor, jenis aksi, dan rentang waktu.

---

### 4.2 Owner Dashboard (Bisnis Management)

Owner adalah pemilik usaha laundry. Satu akun Owner dapat memiliki **satu atau lebih outlet**. Dashboard ini **murni untuk monitoring dan konfigurasi**, bukan operasional harian.

#### 4.2.1. Overview Multi-Outlet

Halaman utama owner menampilkan **ringkasan performa konsolidasi** semua outlet yang dimiliki:

| Widget | Deskripsi |
| :--- | :--- |
| **Total Omzet Hari Ini** | Agregat pendapatan semua outlet hari ini |
| **Jumlah Transaksi Aktif** | Order yang sedang dalam proses di semua outlet |
| **Order Pending Pickup** | Cucian sudah selesai namun belum diambil pelanggan |
| **Outlet Status Card** | Kartu cepat tiap outlet: status online/offline app, jumlah kasir aktif, omzet hari ini |
| **Top Outlet by Revenue** | Ranking outlet berdasarkan omzet periode tertentu |

#### 4.2.2. Manajemen Outlet

| Fitur | Deskripsi |
| :--- | :--- |
| **Buat Outlet Baru** | Tambah cabang: nama, alamat, telepon, prefix invoice |
| **Konfigurasi Outlet** | Atur metode tracking (Stiker/QR), zona waktu, nomor rak default |
| **Nonaktifkan Outlet** | Suspend outlet tanpa menghapus data historis |
| **Lihat Detail Outlet** | Statistik dan laporan spesifik per outlet |

#### 4.2.3. Manajemen Karyawan

| Fitur | Deskripsi |
| :--- | :--- |
| **Daftar Karyawan** | Semua staff di seluruh outlet beserta peran (Kasir / Staff Produksi / Outlet Manager) |
| **Tambah Karyawan** | Buat akun + assign ke outlet + tentukan role |
| **Nonaktifkan Karyawan** | Blokir akses tanpa menghapus riwayat aktivitas |
| **Remote Revoke Token** | Cabut paksa session login Flutter karyawan (kasus perangkat hilang / resign) |
| **Log Aktivitas Karyawan** | Riwayat aksi karyawan: transaksi dibuat, status diubah, waktu login/logout |

#### 4.2.4. Manajemen Layanan & Harga

| Fitur | Deskripsi |
| :--- | :--- |
| **Master Layanan** | Kelola menu layanan tiap outlet (nama, jenis, harga, satuan) |
| **Dynamic Pricing** | Harga layanan yang sama dapat berbeda antar outlet |
| **Membership Package** | Buat paket prabayar kiloan (contoh: paket 20kg, 50kg, 100kg) |

#### 4.2.5. Laporan & Analitik

Semua laporan dapat difilter berdasarkan **Outlet**, **Rentang Tanggal**, dan **Jenis Layanan**.

**Laporan Keuangan:**

| Laporan | Deskripsi |
| :--- | :--- |
| **Omzet Harian/Bulanan** | Total pendapatan per hari dan per bulan, tren grafik |
| **Breakdown per Layanan** | Kontribusi omzet tiap jenis layanan (Kiloan vs Satuan, dll.) |
| **Laporan Laba/Rugi Estimasi** | Omzet dikurangi estimasi biaya bahan baku (dari data auto-deduct) |
| **Performa per Outlet** | Perbandingan omzet, volume transaksi antar-cabang |
| **Outstanding Payment** | Daftar order yang belum lunas pembayaran |

**Laporan Operasional:**

| Laporan | Deskripsi |
| :--- | :--- |
| **Rekonsiliasi Stok Bahan Baku** | Grafik perbandingan stok sistem (auto-deduct) vs stok riil opname — untuk mendeteksi kecurangan |
| **Produktivitas Mesin** | Persentase utilisasi mesin per outlet per periode |
| **Order Completion Rate** | Rasio order selesai tepat waktu vs terlambat |
| **Membership Tracker** | Rekapitulasi pemakaian kuota seluruh pelanggan membership |

#### 4.2.6. Manajemen Inventaris (Stok Opname)
- Input stok fisik bahan baku hasil pengecekan lapangan (detergen, parfum, plastik, dll.).
- Sistem menampilkan **selisih antara stok opname vs kalkulasi auto-deduct** sebagai *variance indicator*.
- Alert otomatis jika stok bahan baku di bawah threshold minimum.

---

### 4.3 Customer Portal (Public Tracking Web)

> **Stack**: Laravel 13.x + Inertia.js + Vue 3 + Tailwind CSS (Mobile-Responsive)
> **Akses**: Tanpa login — hanya via token unik di URL

Halaman web publik yang dikirim ke pelanggan melalui link WhatsApp untuk memantau status cucian.

| Fitur | Deskripsi |
| :--- | :--- |
| **Progress Bar Status** | Visual step-by-step status cucian (Antrean → Dicuci → Dikeringkan → Setrika → Siap Diambil) |
| **Detail Order** | Nomor invoice, jenis layanan, berat/jumlah item, estimasi selesai |
| **Foto Kondisi Awal** | Foto barang saat diterima (untuk layanan Satuan/Premium) |
| **Lokasi Rak Pengambilan** | Nomor rak yang ditampilkan otomatis saat status `Siap Diambil` |
| **Membership Quota** | Grafik kuota sisa (contoh: Sisa: 12.4 kg / 50 kg) |
| **Riwayat Transaksi Membership** | Log pemotongan kuota sebelumnya |

**URL Format**: `https://app.laundry.id/track/{cryptographic_secure_token}`

---

## 5. Platform 2 — Mobile/Tablet Flutter App

> **Stack**: Flutter (Android/iOS) — Tablet-first, Mobile-compatible
> **Tujuan**: **Seluruh kegiatan operasional toko harian** berjalan di aplikasi ini. Staff di outlet tidak perlu membuka web.
> **Offline-First**: Hive/Isar local database + background sync ke API.

---

### 5.1 Peran & Hak Akses dalam App

Satu perangkat Flutter dapat digunakan oleh satu karyawan. Role menentukan tampilan dan fitur yang tersedia:

| Role | Akses Fitur Flutter |
| :--- | :--- |
| **Kasir** | POS (buat order), lihat daftar order aktif, terima pembayaran, cetak struk, kirim WhatsApp |
| **Staff Produksi** | Scan keranjang, update status produksi, lihat antrian cucian |
| **Outlet Manager** | Semua akses Kasir + Staff Produksi + lihat laporan harian outlet |

---

### 5.2 Modul Kasir (POS)

#### 5.2.1. Pembuatan Transaksi Baru

Alur lengkap transaksi kasir:

```
[Pilih / Buat Pelanggan]
       ↓
[Pilih Jenis Layanan → UI Berubah Dinamis]
       ↓
[Input Detail Order (Berat / Pcs / Mesin / Token)]
       ↓
[Tentukan Jumlah Keranjang]
       ↓
[Cetak Label Stiker / Scan QR Keranjang]
       ↓
[Kalkulasi Harga Otomatis]
       ↓
[Pilih Metode Pembayaran]
       ↓
[Selesaikan & Kirim Notifikasi WhatsApp]
```

#### 5.2.2. Manajemen Pelanggan
- Cari pelanggan by nama / nomor HP.
- Buat profil pelanggan baru langsung dari layar POS.
- Lihat riwayat order dan sisa kuota membership pelanggan.

#### 5.2.3. Penerimaan Pembayaran
| Metode | Detail |
| :--- | :--- |
| **Tunai (Cash)** | Input nominal, tampilkan kembalian |
| **QRIS** | Tampilkan QR dinamis untuk pembayaran langsung |
| **Kuota Deposit / Membership** | Potong saldo kuota pelanggan secara otomatis |
| **Partial Payment** | Pembayaran DP dengan sisa dilunasi saat pengambilan (opsional) |

#### 5.2.4. Cetak Struk & Label
- Integrasi printer thermal Bluetooth via SDK ESC/POS.
- **Struk Transaksi**: Nomor invoice, rincian layanan, total harga, metode bayar, link tracking.
- **Label Keranjang**: Kode unik per keranjang (format: `ORD-001 [1/3]`) dicetak setelah order dibuat.

#### 5.2.5. Daftar Order Aktif
- Tampilan real-time semua order yang sedang dalam proses di outlet.
- Filter by status, tanggal, atau jenis layanan.
- Tap untuk lihat detail order dan riwayat perubahan status.

---

### 5.3 Modul Produksi (Floor Worker)

#### 5.3.1. Dashboard Antrian Produksi
- Tampilkan daftar semua keranjang aktif yang perlu diproses.
- Dikelompokkan berdasarkan status: **Menunggu Cuci**, **Sedang Dicuci**, **Dikeringkan**, **Setrika**.

#### 5.3.2. Scan & Update Status Keranjang
- Staff mengarahkan kamera ke **stiker label** atau **QR keranjang permanen**.
- Setelah scan berhasil, muncul tombol update status ke tahap berikutnya.
- Setiap update mencatat: `timestamp`, `user_id` staff, `status_baru`.

#### 5.3.3. Manajemen Mesin (Modul Machine)

| Fitur | Deskripsi |
| :--- | :--- |
| **Status Mesin Real-Time** | Grid visual mesin: `Idle` (hijau), `Running` (kuning), `Maintenance` (merah) |
| **Lock Mesin ke Order** | Untuk layanan Per Mesin (Lot): pilih mesin Idle → otomatis terkunci ke order |
| **Release Mesin** | Bebaskan mesin setelah siklus selesai |
| **Catat Maintenance** | Tandai mesin sebagai sedang dalam perbaikan + catatan |
| **Riwayat Pemakaian Mesin** | Log siklus mesin per hari |

#### 5.3.4. Safe Pickup Validation
- Ketika kasir mencoba menyelesaikan order (`Selesai`), sistem **memvalidasi semua keranjang**.
- Jika ada keranjang yang statusnya belum `Siap Diambil` → **order diblokir, muncul alert**.
- Staff produksi wajib menyelesaikan dan menscan semua keranjang sebelum order bisa ditutup.

---

### 5.4 Omni-Channel Service — UI Dinamis

Formulir input transaksi berubah secara otomatis sesuai jenis layanan:

#### **A. Layanan Kiloan**
```
┌────────────────────────────────────┐
│  Layanan: KILOAN                   │
│  Berat: [___4.65___] kg            │
│  Keranjang: [  1  ] [  2  ] [  3  ]│
│  Harga/kg: Rp 8.000                │
│  Total Estimasi: Rp 37.200         │
└────────────────────────────────────┘
```
- Input berat desimal (contoh: `4.65 kg`).
- Tentukan jumlah keranjang → otomatis cetak label per keranjang.

#### **B. Layanan Satuan / Premium (Sepatu, Tas, Jas, dll.)**
```
┌────────────────────────────────────┐
│  Layanan: SATUAN - Sepatu          │
│  Jumlah: [  2  ] pcs               │
│  Foto Kondisi Awal: [📷 Ambil Foto]│
│  Catatan: [Noda kopi di sol kanan] │
│  Total: Rp 50.000                  │
└────────────────────────────────────┘
```
- Input jumlah satuan.
- **Wajib foto** kondisi fisik barang via kamera perangkat sebagai bukti digital → mencegah komplain palsu pelanggan.
- Field catatan noda/cacat awal.

#### **C. Layanan Per Mesin (Lot/Load)**
```
┌────────────────────────────────────┐
│  Layanan: PER MESIN                │
│  Pilih Mesin:                      │
│  [🟢 Mesin 01] [🟡 Mesin 02 (busy)]│
│  [🟢 Mesin 03] [🔴 Mesin 04 (mtc)] │
│  Durasi: 45 menit                  │
│  Total: Rp 35.000                  │
└────────────────────────────────────┘
```
- Tampilkan grid status mesin real-time.
- Hanya mesin `Idle` (hijau) yang bisa dipilih.
- Mesin terpilih otomatis terkunci ke order ini.

#### **D. Layanan Self-Service**
```
┌────────────────────────────────────┐
│  Layanan: SELF-SERVICE             │
│  Metode: [🪙 Koin Fisik] [📱 QRIS] │
│  --- Koin Fisik ---                │
│  Jumlah Token: [  3  ] koin        │
│  Total: Rp 15.000                  │
│  --- QRIS ---                      │
│  [QR Code Dinamis Tampil di Sini]  │
└────────────────────────────────────┘
```
- Pilih antara koin fisik atau QRIS mandiri.
- QRIS dinamis generate otomatis sesuai nominal.

---

### 5.5 Notifikasi WhatsApp Redirect

Implementasi tanpa biaya API gateway menggunakan package `url_launcher` Flutter.

**Mekanisme**: Flutter menyusun teks → URL encode → buka `wa.me/{phone}?text={encoded_text}` di perangkat kasir.

**Template Notifikasi:**

| Trigger | Template Pesan |
| :--- | :--- |
| **Order Masuk** | *"Halo {Nama}! 👋 Terima kasih telah mempercayakan cucian Anda ke {Nama Laundry}. Nota #{INV-001} sudah kami terima. Pantau status cucian Anda secara real-time di: {Link Tracking} — Tim {Nama Laundry}"* |
| **Siap Diambil** | *"Kabar gembira! 🎉 Cucian Anda pada nota #{INV-001} sudah selesai dan siap diambil. Silakan ambil di: Rak nomor {No. Rak}. Kami tunggu! — {Nama Laundry}"* |
| **Pengingat Pickup** | *"Halo {Nama}, kami ingatkan cucian Anda (Nota #{INV-001}) sudah 2 hari siap diambil di Rak {No. Rak}. Jangan lupa ya! 😊"* |

---

## 6. Arsitektur Backend & Modular System

### 6.1. Tech Stack Backend

| Komponen | Teknologi |
| :--- | :--- |
| **Framework** | Laravel 13.x (PHP 8.3+) |
| **Modular** | `nwidart/laravel-modules` |
| **Frontend Web** | Inertia.js + Vue 3 (Composition API) + Tailwind CSS v4 |
| **API Auth** | Laravel Sanctum |
| **Database** | MySQL 8.x / PostgreSQL |
| **Cache & Queue** | Redis |
| **File Storage** | Local + Laravel Storage (foto kondisi barang) |

### 6.2. Struktur Modul Backend

```plaintext
Modules/
├── Auth/
│   ├── Registrasi & login tenant (Owner)
│   ├── Login karyawan via Sanctum token
│   ├── Role-Based Access Control (RBAC)
│   └── Remote Token Revocation
│
├── Tenant/
│   ├── Manajemen data bisnis (nama, logo, kontak)
│   ├── Pembuatan & konfigurasi outlet
│   ├── Subscription plan tracking
│   └── Broadcast notifikasi dari SuperAdmin
│
├── Customer/
│   ├── Database pelanggan per outlet
│   ├── Membership prabayar (deposit kuota kg)
│   ├── Log pemotongan kuota
│   └── Cryptographic tracking token generator
│
├── Service/
│   ├── Master menu layanan (Kiloan, Satuan, Per Mesin, Self-Service)
│   ├── Dynamic pricing per outlet
│   └── Paket membership (50kg, 100kg, dll.)
│
├── Order/
│   ├── Core transaction engine
│   ├── State Machine workflow (Draft→Selesai)
│   ├── Multi-Basket tracking (order_baskets)
│   ├── Unique invoice & tracking token generator
│   └── Safe Pickup validation logic
│
├── Inventory/
│   ├── Master bahan baku per outlet
│   ├── Input stok opname fisik
│   ├── Auto-Deduct algorithm (pasca order Selesai)
│   └── Rekonsiliasi: sistem vs opname
│
├── Machine/
│   ├── Registrasi mesin cuci & pengering
│   ├── Status control (Idle, Running, Maintenance)
│   ├── Lock/Release mesin ke order
│   └── Log siklus penggunaan
│
└── Report/
    ├── Laporan omzet harian/bulanan per outlet
    ├── Laporan konsolidasi multi-outlet (Owner)
    ├── Laporan laba/rugi estimasi
    ├── Dashboard KPI SuperAdmin (MRR, churn)
    └── Export PDF/CSV
```

### 6.3. Multi-Tenancy Architecture

- **Strategi**: Single Database (Shared) dengan Row-Level Isolation.
- **Implementasi**: Eloquent Global Scope via Trait `BelongsToOutlet` dan `BelongsToTenant`.
- **Scoping**: Setiap query otomatis difilter berdasarkan `outlet_id` dari token Sanctum user yang sedang aktif.

```php
// Contoh implementasi Trait BelongsToOutlet
trait BelongsToOutlet
{
    public static function bootBelongsToOutlet(): void
    {
        static::addGlobalScope('outlet', function (Builder $builder) {
            $outletId = auth()->user()?->active_outlet_id;
            if ($outletId) {
                $builder->where('outlet_id', $outletId);
            }
        });
    }
}
```

---

## 7. State Machine — Alur Kerja Pesanan

### 7.1. Alur Status Order (Level Transaksi)

```
DRAFT → ANTREAN → DICUCI → DIKERINGKAN → SETRIKA → SIAP DIAMBIL → SELESAI
```

| Status | Trigger | PIC | Catatan |
| :--- | :--- | :--- | :--- |
| `draft` | Order dibuat kasir, belum bayar | Kasir | Bisa diedit atau dibatalkan |
| `queued` | Pembayaran diterima / DP masuk | Kasir | Label/stiker dicetak |
| `washing` | Scan keranjang masuk mesin | Staff Produksi | Timestamp + user_id dicatat |
| `drying` | Scan keranjang keluar mesin cuci | Staff Produksi | — |
| `ironing` | Scan keranjang masuk setrika | Staff Produksi | — |
| `ready` | Semua keranjang selesai setrika, rak ditentukan | Staff Produksi | Notif WhatsApp otomatis terkirim |
| `completed` | Pelanggan mengambil, kasir konfirmasi | Kasir | Auto-deduct inventaris dieksekusi |

### 7.2. Alur Status per Keranjang (Multi-Basket)

```
ANTREAN → DICUCI → DIKERINGKAN → SETRIKA → SIAP DIAMBIL
```

- Setiap `order_basket` memiliki statusnya sendiri.
- **Contoh skenario**: Order 1 punya 3 keranjang. Keranjang A & B sudah `Siap Diambil`, keranjang C masih `Setrika` → order **belum bisa** diselesaikan.
- Kasir melihat peringatan: *"1 keranjang (C) masih dalam proses setrika."*

### 7.3. Validasi Safe Pickup

```php
// Pseudo-code validasi sebelum status → 'completed'
$order = Order::with('baskets')->find($orderId);

$allBasketReady = $order->baskets->every(
    fn($basket) => $basket->status === 'ready'
);

if (!$allBasketReady) {
    throw new OrderNotReadyException('Masih ada keranjang yang belum selesai.');
}
```

---

## 8. Kebutuhan Non-Fungsional

### 8.1. Performa
| Metrik | Target |
| :--- | :--- |
| API Response Time | < 500ms (95th percentile) |
| Sinkronisasi Offline → Online | < 30 detik setelah koneksi pulih |
| Concurrent Users per Tenant | Minimal 50 karyawan aktif bersamaan |
| Uptime SLA | 99.5% per bulan |

### 8.2. Keamanan
| Aspek | Implementasi |
| :--- | :--- |
| **Autentikasi API** | Laravel Sanctum — token hashed, expiry configurable |
| **Data Isolation** | Global Scope Eloquent — strict per outlet/tenant |
| **Customer Privacy** | Tracking token = cryptographic random (tidak sequential/guessable) |
| **Remote Revoke** | Owner/SuperAdmin dapat mencabut token karyawan kapan saja |
| **HTTPS Enforcement** | Seluruh komunikasi API wajib HTTPS |
| **Input Validation** | Server-side validation + rate limiting pada endpoint publik |

### 8.3. Flutter App — Offline-First Capability

```
OFFLINE MODE:
  ├── Local DB (Hive/Isar): simpan transaksi pending
  ├── Local DB: simpan data master (layanan, pelanggan, mesin)
  └── Queue: antri request untuk sinkronisasi

SAAT ONLINE:
  ├── Background sync: kirim semua transaksi pending ke API
  ├── Konflik handling: server sebagai sumber kebenaran (server-wins)
  └── Notifikasi user jika ada konflik yang perlu review manual
```

### 8.4. Flutter App — Integrasi Hardware
| Hardware | Integrasi |
| :--- | :--- |
| **Printer Thermal Struk** | Bluetooth ESC/POS SDK |
| **Printer Thermal Label** | Bluetooth ESC/POS (untuk label keranjang) |
| **Scanner QR/Barcode** | Kamera native Flutter (`mobile_scanner` package) |
| **Kamera Foto Barang** | `image_picker` / kamera native |

### 8.5. Skalabilitas
- Backend Laravel dapat di-deploy di server tunggal (VPS) untuk fase awal.
- Arsitektur modular memudahkan migrasi ke microservices jika diperlukan di masa depan.
- Redis Queue untuk proses berat: auto-deduct, laporan besar, export PDF.

---

## 9. Cetak Biru Skema Database

### 9.1. Layer SaaS (Tenant & Subscription)

```php
// Tabel: subscription_plans
Schema::create('subscription_plans', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Starter, Pro, Enterprise
    $table->integer('max_outlets');
    $table->integer('max_employees');
    $table->decimal('price_monthly', 12, 2);
    $table->json('features')->nullable(); // JSON list fitur yang diaktifkan
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Tabel: users (Owner/Tenant)
// Menggunakan tabel users default Laravel dengan tambahan:
Schema::table('users', function (Blueprint $table) {
    $table->enum('role', ['superadmin', 'owner', 'outlet_manager', 'cashier', 'staff'])->default('cashier');
    $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans');
    $table->foreignId('active_outlet_id')->nullable()->constrained('outlets');
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamp('subscription_ends_at')->nullable();
    $table->boolean('is_active')->default(true);
});
```

### 9.2. Layer Operasional

```php
// Modul Tenant: outlets
Schema::create('outlets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
    $table->string('name');
    $table->string('address');
    $table->string('phone_number');
    $table->string('invoice_prefix')->default('INV');
    $table->enum('tracking_method', ['sticker', 'qr_permanent'])->default('sticker');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Modul Customer: customers
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->string('name');
    $table->string('phone_number');
    $table->string('membership_token')->unique(); // Token untuk portal kuota/membership
    $table->timestamps();
});

// Modul Customer: customer_memberships (Paket Prabayar)
Schema::create('customer_memberships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('service_id')->constrained('services'); // Referensi ke paket membership
    $table->decimal('quota_kg', 8, 2); // Total kuota yang dibeli
    $table->decimal('used_kg', 8, 2)->default(0); // Kuota terpakai
    $table->decimal('remaining_kg', 8, 2); // Sisa kuota (bisa computed)
    $table->date('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Modul Service: services
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->string('name');
    $table->decimal('price', 12, 2);
    $table->enum('unit', ['kg', 'pcs', 'load', 'coin']);
    $table->enum('type', ['kiloan', 'satuan', 'per_mesin', 'self_service', 'membership_package']);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Modul Machine: machines
Schema::create('machines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->string('name'); // Mesin 01, Mesin 02, dll.
    $table->enum('type', ['washer', 'dryer']);
    $table->enum('status', ['idle', 'running', 'maintenance'])->default('idle');
    $table->text('maintenance_note')->nullable();
    $table->timestamps();
});

// Modul Order: orders
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('cashier_id')->constrained('users'); // Siapa yang membuat order
    $table->string('invoice_number')->unique();
    $table->string('tracking_token')->unique(); // Token acak untuk customer portal
    $table->enum('status', ['draft', 'queued', 'washing', 'drying', 'ironing', 'ready', 'completed'])->default('draft');
    $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
    $table->enum('payment_method', ['cash', 'qris', 'quota_deposit'])->nullable();
    $table->decimal('total_price', 12, 2);
    $table->decimal('paid_amount', 12, 2)->default(0);
    $table->text('notes')->nullable();
    $table->timestamps();
});

// Modul Order: order_items (Detail item dalam 1 order)
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignId('service_id')->constrained('services');
    $table->foreignId('machine_id')->nullable()->constrained('machines'); // Untuk layanan Per Mesin
    $table->decimal('quantity', 8, 2); // Berat (kg) atau jumlah (pcs) atau load
    $table->decimal('unit_price', 12, 2);
    $table->decimal('subtotal', 12, 2);
    $table->json('photos')->nullable(); // Path foto kondisi awal (untuk layanan Satuan)
    $table->text('condition_notes')->nullable();
    $table->timestamps();
});

// Modul Order: order_baskets (Multi-Basket Tracking)
Schema::create('order_baskets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    $table->string('basket_code'); // Kode stiker unik atau kode QR permanen keranjang
    $table->enum('status', ['queued', 'washing', 'drying', 'ironing', 'ready'])->default('queued');
    $table->string('rack_location')->nullable(); // Nomor rak, diisi saat status = 'ready'
    $table->timestamps();
});

// Modul Order: order_status_logs (Audit Trail)
Schema::create('order_status_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignId('basket_id')->nullable()->constrained('order_baskets')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users'); // Siapa yang mengubah status
    $table->string('from_status');
    $table->string('to_status');
    $table->timestamp('changed_at');
    $table->timestamps();
});

// Modul Inventory: inventory_items
Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->string('name'); // Detergen, Parfum, Plastik, dll.
    $table->string('unit'); // ml, gram, pcs
    $table->decimal('stock_quantity', 10, 2)->default(0); // Stok sistem (hasil auto-deduct)
    $table->decimal('min_stock_alert', 10, 2)->default(0); // Threshold alert stok minimum
    $table->decimal('deduct_per_kg', 8, 4)->nullable(); // Rumus auto-deduct per kg cucian
    $table->timestamps();
});

// Modul Inventory: inventory_opnames (Stok Opname Fisik)
Schema::create('inventory_opnames', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outlet_id')->constrained('outlets');
    $table->foreignId('inventory_item_id')->constrained('inventory_items');
    $table->foreignId('user_id')->constrained('users'); // Siapa yang melakukan opname
    $table->decimal('actual_quantity', 10, 2); // Stok riil hasil hitung fisik
    $table->decimal('system_quantity', 10, 2); // Stok sistem saat opname
    $table->decimal('variance', 10, 2); // Selisih (actual - system)
    $table->text('notes')->nullable();
    $table->date('opname_date');
    $table->timestamps();
});
```

---

## 10. Roadmap Pengembangan

### Phase 1 — Foundation (Month 1–2)
- [ ] Setup Laravel 13 + nwidart/laravel-modules + Inertia.js + Vue 3 + Tailwind CSS
- [ ] Modul Auth (RBAC, Sanctum) — halaman Login/Register dengan Inertia
- [ ] Modul Tenant (Outlet CRUD, Subscription Plan)
- [ ] Modul Service & Customer (Master Data)
- [ ] Modul Order — Core State Machine
- [ ] SuperAdmin Panel (Vue/Inertia) — Manajemen Tenant & Plan
- [ ] Flutter App — Struktur dasar + Login + POS Kiloan

### Phase 2 — Core Operations (Month 3–4)
- [ ] Modul Machine (Status, Lock/Release)
- [ ] Multi-Basket Tracking + Scan Keranjang
- [ ] Layanan Satuan + Kamera Foto Kondisi
- [ ] Layanan Per Mesin + Self-Service
- [ ] Printer Thermal Integration (ESC/POS)
- [ ] WhatsApp Redirect Notifications
- [ ] Customer Portal — Tracking Web

### Phase 3 — Analytics & Anti-Fraud (Month 5–6)
- [ ] Modul Inventory + Auto-Deduct
- [ ] Dashboard Rekonsiliasi Stok Opname
- [ ] Owner Dashboard — Laporan & KPI
- [ ] Multi-Outlet Konsolidasi Report
- [ ] Offline-First Flutter (Hive/Isar sync)
- [ ] Export Laporan PDF/CSV

### Phase 4 — Polish & Scale (Month 7+)
- [ ] Push Notification (Firebase FCM)
- [ ] WhatsApp API Gateway Integration (upgrade dari redirect)
- [ ] Membership Auto-Renewal
- [ ] Mobile App: Owner view (read-only laporan)
- [ ] Performance optimization & load testing
- [ ] SaaS Billing automation

---

> **Dokumen ini adalah spesifikasi hidup.** Perubahan fitur atau prioritas dapat diperbarui seiring dengan feedback pengguna dan kondisi pasar. Semua perubahan major wajib memperbarui versi dokumen ini.
