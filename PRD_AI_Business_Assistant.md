# Product Requirements Document (PRD)
## Fitur: Koran Toko Digital — AI-Powered Daily Business Intelligence
### Versi: 1.0 | Status: Draft | Tanggal: 6 Juni 2026

---

## 1. Ringkasan Eksekutif (Executive Summary)

**Nama Fitur:** Koran Toko Digital  
**Tagline:** *"Laporan toko Anda, sudah siap saat Anda terbangun."*

**Latar Belakang:**  
Owner toko seringkali tidak memiliki waktu atau keahlian teknis untuk menganalisis data penjualan secara mandiri dari laporan yang tersedia di POS. Mereka membutuhkan wawasan bisnis (*business intelligence*) yang disajikan dalam bahasa yang mudah dipahami — bukan sekadar angka mentah.

**Solusi:**  
Sebuah sistem *automated daily reporting* yang bekerja di latar belakang setiap malam pukul 23:59, menarik ringkasan data operasional hari itu per toko, mengirimnya ke Gemini AI untuk dinarasikan, dan menyajikannya kepada *owner* dalam format "Koran Toko Digital" — laporan bergaya editorial yang mudah dicerna — keesokan paginya.

**Nilai Bisnis:**
- Meningkatkan keterlibatan (*engagement*) owner dengan aplikasi POS.
- Membantu owner dalam pengambilan keputusan berbasis data tanpa keahlian analitik khusus.
- Membedakan RIMSPOS dari kompetitor dengan fitur AI yang *actionable* dan kontekstual.

---

## 2. Pengguna Sasaran (Target Users)

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Owner / Pemilik Toko** | Pemilik bisnis yang memonitor performa toko | Ringkasan harian yang mudah dipahami, peringatan dini masalah bisnis |
| **Manajer Toko** | Pengelola operasional yang ingin review cepat | Akses laporan tanpa harus membuka menu analitik manual |

> Kasir dan peran lain **tidak memiliki akses** ke fitur ini.

---

## 3. Ruang Lingkup Fitur — Fase 1 (MVP)

### 3.1 Cakupan Data Analitik Per Toko (Per `store_id`)

Sistem mengumpulkan **6 kelompok data** untuk setiap toko pada hari berjalan:

| # | Kelompok Data | Sumber Tabel | Metrik yang Dihitung |
|---|---|---|---|
| 1 | **Ringkasan Omset** | `sales` | Total transaksi, total `grand_total`, rata-rata nilai transaksi, total diskon diberikan |
| 2 | **Produk Terlaris & Paling Sepi** | `sale_items` | Top 5 produk terjual (berdasarkan `qty`), bottom 5 produk terjual hari ini |
| 3 | **Performa Jam Sibuk** | `sales` | Distribusi transaksi per jam (untuk mengidentifikasi *peak hours*) |
| 4 | **Status Stok Kritis** | `stock_batches` (posisi: `store`) | Varian produk dengan `qty_sisa` ≤ *threshold* yang bisa dikonfigurasi (default: 5 unit) |
| 5 | **Ringkasan Pengeluaran** | `expenses` | Total pengeluaran hari ini per kategori (`expense_categories`) |
| 6 | **Perbandingan Hari Kemarin** | `sales` (H-1) | Perubahan omset (naik/turun dalam %) dibanding hari sebelumnya |

### 3.2 Konten Narasi AI (Gemini)

Dari data di atas, Gemini menghasilkan narasi dalam Bahasa Indonesia yang terdiri dari:

1. **Headline Utama** — Kalimat pembuka dramatis yang merangkum performa hari itu.
2. **Laporan Omset** — Narasi omset hari ini, perbandingan kemarin, dan tren.
3. **Bintang Hari Ini** — Sorotan produk terlaris beserta analisis singkat.
4. **Yang Perlu Diperhatikan** — Produk yang dijual paling sedikit & stok kritis (peringatan).
5. **Jam Terbang Toko** — Analisis jam ramai & sepi, rekomendasi operasional.
6. **Laporan Pengeluaran** — Ringkasan biaya operasional hari ini.
7. **Saran Aksi Besok (*Actionable Insights*)** — 3 poin rekomendasi konkret dari AI.

### 3.3 Tampilan (UI)

- **Halaman Daftar Koran** — Timeline daftar koran toko digital (seperti feed berita), diurutkan dari terbaru.
- **Halaman Detail Koran** — Konten narasi lengkap per tanggal, dengan badge status (berhasil/gagal/diproses).
- **Widget Dashboard** — Integrasi di halaman utama: tampilkan *teaser* edisi terbaru koran hari ini.

---

## 4. Alur Kerja Teknis (Data Flow)

```
[23:59 Laravel Scheduler]
        │
        ▼
[Artisan Command: newspaper:generate]
        │
        ├── Ambil semua store aktif (is_active = true)
        │
        └── Loop per store_id:
                │
                ├── [DataAggregatorService] ←── Query SQL Agregat (sales, sale_items, expenses, stock_batches)
                │         │
                │         └── Sanitasi: Strip customer_name, customer_phone, user_id
                │
                ├── Format ke JSON Payload
                │
                ├── [GeminiService::generateDailyNewspaper()] ←── POST ke Gemini API
                │         │
                │         └── System Prompt: gaya bahasa koran, Bahasa Indonesia
                │
                ├── Parse & validasi respons Gemini
                │
                └── [DigitalNewspaper::updateOrCreate()] ←── Simpan ke DB
                          │
                          └── store_id + date (unique key)
```

---

## 5. Spesifikasi Keamanan & Privasi (Security & Privacy)

### 5.1 Data yang DILARANG dikirim ke Gemini

| Data Sensitif | Alasan |
|---|---|
| `customer_name`, `customer_phone` | Identitas pribadi pelanggan |
| `user_id`, nama kasir | Identitas karyawan |
| Nomor invoice lengkap | Bisa digunakan untuk rekonstruksi data |
| `grand_total` per transaksi individual | Data per-transaksi |

### 5.2 Data yang BOLEH dikirim ke Gemini

Hanya statistik **agregat global** per toko:
- Total omset, jumlah transaksi, rata-rata transaksi (angka agregat)
- Nama produk + jumlah terjual (nama produk, bukan data pelanggan)
- Total pengeluaran per kategori
- Nama toko, tanggal laporan

### 5.3 Kontrol Akses

- Middleware: Hanya role dengan `role_type` = `owner` atau `admin` yang dapat mengakses halaman koran.
- Isolasi data: Setiap koran **hanya dapat dilihat oleh user yang terdaftar di `store_id` yang sama**.

---

## 6. Kebutuhan Teknis (Technical Requirements)

### 6.1 Backend — Laravel

| Komponen | Detail |
|---|---|
| **Migration** | Tabel `digital_newspapers` (store_id, date, content_json, status, generated_at, error_message) |
| **Model** | `DigitalNewspaper` dengan scope per toko (`HasStore`) |
| **Service** | `DataAggregatorService` — mengumpulkan & mensanitasi data agregat per toko |
| **Service** | Extend `GeminiService` dengan method `generateDailyNewspaper(array $data): string` |
| **Artisan Command** | `newspaper:generate` — dapat dijalankan manual via `php artisan newspaper:generate` |
| **Scheduler** | Tambahkan ke `console.php`: `Schedule::command('newspaper:generate')->dailyAt('23:59')->withoutOverlapping()` |
| **Controller** | `DigitalNewspaperController` dengan method `index()` dan `show()` |
| **API Route** | `GET /api/digital-newspapers` (list) dan `GET /api/digital-newspapers/{date}` (detail) |

### 6.2 Struktur JSON Payload ke Gemini

```json
{
  "store_name": "Toko Sejahtera",
  "report_date": "2026-06-05",
  "summary": {
    "total_transactions": 87,
    "total_revenue": 4250000,
    "avg_transaction_value": 48850,
    "total_discount_given": 125000,
    "revenue_vs_yesterday_pct": 12.5
  },
  "top_products": [
    { "name": "Indomie Goreng", "qty_sold": 45, "revenue": 225000 }
  ],
  "slow_products": [
    { "name": "Minyak Goreng 2L", "qty_sold": 1, "revenue": 38000 }
  ],
  "hourly_distribution": {
    "08": 5, "09": 12, "10": 18, "11": 14, "12": 9,
    "13": 6, "14": 8, "15": 11, "16": 4
  },
  "critical_stock": [
    { "name": "Aqua 600ml", "variant": "Default", "qty_remaining": 3 }
  ],
  "expenses": {
    "total": 350000,
    "by_category": [
      { "category": "Listrik", "amount": 200000 },
      { "category": "Transportasi", "amount": 150000 }
    ]
  }
}
```

### 6.3 Struktur Tabel `digital_newspapers`

```sql
CREATE TABLE digital_newspapers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id        BIGINT UNSIGNED NOT NULL,
    report_date     DATE NOT NULL,
    status          ENUM('pending','success','failed') DEFAULT 'pending',
    headline        VARCHAR(255) NULL,
    content_html    LONGTEXT NULL,      -- narasi lengkap hasil Gemini (HTML/Markdown)
    raw_payload     JSON NULL,          -- payload JSON yang dikirim ke Gemini (untuk debug)
    generated_at    TIMESTAMP NULL,
    error_message   TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    UNIQUE KEY uq_store_date (store_id, report_date),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);
```

### 6.4 Frontend (Mobile - Flutter)

- **Layar Koran** (`digital_newspaper_screen.dart`) — list edisi koran menggunakan `ListView`.
- **Layar Detail** (`newspaper_detail_screen.dart`) — tampilkan `content_html` dengan `flutter_html` atau render Markdown.
- **Widget Dashboard** — card *teaser* di POS home screen yang menampilkan `headline` terbaru.

---

## 7. Penanganan Error & Resiliensi (Error Handling)

| Skenario | Penanganan |
|---|---|
| Gemini API timeout / error 5xx | Retry 1 kali setelah 30 detik; jika masih gagal, set `status = 'failed'`, simpan `error_message` |
| Gemini API quota exceeded (429) | Set `status = 'failed'`, catat di log, jangan retry (hindari loop quota) |
| Tidak ada transaksi hari itu (toko tutup) | Tetap generate laporan dengan narasi "Toko tutup hari ini — tidak ada transaksi." |
| Toko baru, belum punya data | Skip toko jika `store.is_active = false` |
| Koneksi DB gagal saat query | Catch exception, set `status = 'failed'`, lanjut ke toko berikutnya |

---

## 8. Monitoring & Observabilitas

- **Log Channel Khusus:** Buat log channel `newspaper` di `config/logging.php` untuk memisahkan log fitur ini.
- **Log Entry:** Setiap eksekusi command mencatat: store yang diproses, durasi, status (success/failed), dan error jika ada.
- **DB Status Field:** Field `status` di tabel `digital_newspapers` memungkinkan monitoring dari UI (misal: admin bisa lihat toko mana yang koran-nya gagal digenerate).

---

## 9. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Command `php artisan newspaper:generate` dapat dijalankan manual dan menghasilkan record di DB.
- [ ] Scheduler terdaftar dan berjalan setiap pukul 23:59 tanpa tumpang tindih (`withoutOverlapping`).
- [ ] Data sensitif (nama pelanggan, nama kasir) TIDAK muncul dalam `raw_payload`.
- [ ] Jika Gemini gagal, `status = 'failed'` tersimpan di DB dan tidak melempar exception yang menghentikan proses toko lain.
- [ ] Halaman daftar koran hanya dapat diakses oleh role owner/admin.
- [ ] Koran dengan `store_id` berbeda TIDAK dapat diakses silang oleh user toko lain.
- [ ] Waktu eksekusi total untuk semua toko aktif tidak melebihi 10 menit.

---

## 10. Metrik Keberhasilan (Success Metrics)

| Metrik | Target |
|---|---|
| Tingkat keberhasilan generate koran | ≥ 95% dari toko aktif per malam |
| Waktu buka layar koran (Flutter) | < 2 detik (data sudah ada di DB) |
| Owner membuka koran setiap pagi | ≥ 60% DAU (*Daily Active Users*) owner dalam 30 hari pertama |
| Kualitas narasi Gemini (0-5 bintang, self-rating oleh owner) | Rata-rata ≥ 4.0 |

---

## 11. Peta Jalan Selanjutnya (Future Roadmap)

- **Fase 2:** Tambah fitur "Tanya Toko" (chat interaktif) berbasis data agregat 30 hari.
- **Fase 2:** Push notification FCM pagi hari saat koran sudah siap.
- **Fase 3:** Koran mingguan & bulanan (rekap periode).
- **Fase 3:** Komparasi antar toko (multi-store analytics) untuk pemilik bisnis dengan banyak gerai.
