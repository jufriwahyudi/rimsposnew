<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Parses the spoken voice command text into a structured JSON.
     * Return format: ['product_name' => string, 'quantity' => int]
     */
    public function parseVoiceCommand(string $text): array
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($key)) {
            Log::warning('Gemini API key is not configured. Using fallback regex parser.');
            return $this->fallbackRegexParser($text);
        }

        // Direct REST API URL for Gemini API
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $systemInstruction = "You are a voice command parser for a retail POS system. Your task is to analyze the cashier's transcript and extract:\n"
            . "1. \"product_name\": The product description, name, size, or brand (string).\n"
            . "2. \"quantity\": The requested quantity. Default to 1 if not specified or not readable (integer).\n"
            . "Examples:\n"
            . "- Input: \"cari wardah whitening 30ml jumlah tiga\"\n"
            . "  Output JSON: { \"product_name\": \"wardah whitening 30ml\", \"quantity\": 3 }\n"
            . "- Input: \"cari bakso malang jumlah 2\"\n"
            . "  Output JSON: { \"product_name\": \"bakso malang\", \"quantity\": 2 }\n"
            . "- Input: \"bakso tok 2\"\n"
            . "  Output JSON: { \"product_name\": \"bakso tok\", \"quantity\": 2 }\n"
            . "- Input: \"wardah whitening\"\n"
            . "  Output JSON: { \"product_name\": \"wardah whitening\", \"quantity\": 1 }\n"
            . "Constraints:\n"
            . "- You must ONLY return a valid JSON object matching the requested schema.\n"
            . "- Do not output any markdown formatting (like ```json), commentary, or extra text.";

        // Use responseSchema parameter to enforce structured JSON output in Gemini
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $text]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_name' => [
                            'type' => 'STRING',
                            'description' => 'The product description or brand (string).'
                        ],
                        'quantity' => [
                            'type' => 'INTEGER',
                            'description' => 'The quantity requested. Defaults to 1.'
                        ]
                    ],
                    'required' => ['product_name', 'quantity']
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidates = $data['candidates'] ?? [];
                if (!empty($candidates)) {
                    $jsonText = $candidates[0]['content']['parts'][0]['text'] ?? '';
                    $parsed = json_decode(trim($jsonText), true);
                    if (is_array($parsed) && isset($parsed['product_name'])) {
                        return [
                            'product_name' => (string) $parsed['product_name'],
                            'quantity' => isset($parsed['quantity']) ? (int) $parsed['quantity'] : 1
                        ];
                    }
                }
            } elseif ($response->status() === 429) {
                // Quota exceeded — log warning only (not error), do NOT retry
                Log::warning('Gemini API quota exceeded (429). Using fallback regex parser. Check https://ai.dev/rate-limit for your usage.');
            } else {
                Log::error('Gemini API request failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService: ' . $e->getMessage());
        }

        // Return fallback if Gemini API call failed or couldn't parse
        return $this->fallbackRegexParser($text);
    }

    /**
     * Simple regex parser fallback for offline or configuration-free usage.
     */
    protected function fallbackRegexParser(string $text): array
    {
        $textLower = strtolower($text);
        
        // Remove "cari " prefix if present
        if (str_starts_with($textLower, 'cari ')) {
            $textLower = substr($textLower, 5);
        }

        // Try extracting quantity text words: "satu" -> 1, "dua" -> 2, "tiga" -> 3, etc.
        $wordNumbers = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10
        ];

        $quantity = 1;
        $productName = trim($text);
        if (str_starts_with(strtolower($productName), 'cari ')) {
            $productName = substr($productName, 5);
        }

        // Match "jumlah (\d+|kata_angka)"
        if (preg_match('/jumlah\s+(\w+)/i', $textLower, $matches)) {
            $qtyWord = $matches[1];
            if (is_numeric($qtyWord)) {
                $quantity = (int)$qtyWord;
            } elseif (isset($wordNumbers[$qtyWord])) {
                $quantity = $wordNumbers[$qtyWord];
            }
            
            // Remove "jumlah [qty]" from product name
            $productName = trim(preg_replace('/jumlah\s+\w+/i', '', $productName));
        }
        // Match "(\d+)\s*(pcs|buah|biji)"
        elseif (preg_match('/(\d+)\s*(pcs|buah|biji)/i', $textLower, $matches)) {
            $quantity = (int)$matches[1];
            $productName = trim(preg_replace('/' . $matches[0] . '/i', '', $productName));
        }

        return [
            'product_name' => $productName,
            'quantity' => $quantity
        ];
    }

    /**
     * Generates a daily newspaper narrative from store aggregation data.
     * Return format: ['headline' => string, 'content_html' => string]
     */
    public function generateDailyNewspaper(array $storeData): array
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($key)) {
            Log::warning('Gemini API key is not configured. Returning fallback daily newspaper.');
            return $this->fallbackDailyNewspaper($storeData);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $businessType = $storeData['business_type'] ?? 'retail';
        $isFnb = $businessType === 'fnb';

        $roleLabel = $isFnb
            ? 'Analis Bisnis F&B (Food & Beverage) Senior'
            : 'Analis Bisnis Ritel Senior';

        $contextNote = $isFnb
            ? "Toko ini adalah bisnis F&B (Food & Beverage) seperti restoran, kafe, atau kedai makanan/minuman. Gunakan istilah-istilah yang relevan dengan dunia F&B: menu, pesanan, food cost, bahan baku, peak dining hours, dll."
            : "Toko ini adalah bisnis Ritel (toko fisik yang menjual barang). Gunakan istilah-istilah yang relevan dengan dunia ritel: stok barang, inventory turnover, display produk, restock, dll.";

        $sectionProducts = $isFnb
            ? "3. Menu Andalan Hari Ini: Analisis menu terlaris hari ini dan kinerjanya.\n"
            . "4. Perlu Perhatian: Analisis menu yang kurang diminati dan bahan baku yang stoknya kritis (jika ada) untuk segera dipersiapkan.\n"
            . "5. Jam Ramai Pesanan: Analisis waktu pesanan paling ramai (peak hours) dan bagaimana memaksimalkan kapasitas dapur/layanan.\n"
            : "3. Bintang Hari Ini: Analisis produk terlaris hari ini dan kinerjanya.\n"
            . "4. Perlu Perhatian: Analisis produk yang paling sepi dan stok yang kritis (jika ada) untuk segera direstock.\n"
            . "5. Jam Terbang Toko: Analisis waktu transaksi paling ramai (peak hours) dan bagaimana memaksimalkannya.\n";

        $systemInstruction = "Anda adalah seorang {$roleLabel} sekaligus Jurnalis Bisnis. Tugas Anda adalah menganalisis data performa harian toko POS (Point of Sales) yang dikirim dalam format JSON dan menyusun laporan narasi bisnis dalam Bahasa Indonesia bergaya editorial 'Koran Toko Digital'.\n\n"
            . "KONTEKS BISNIS: {$contextNote}\n\n"
            . "Laporan harus informatif, memotivasi, dan memberikan wawasan tindakan nyata (actionable insights). Gunakan gaya bahasa jurnalistik koran bisnis yang menarik.\n\n"
            . "Buatlah struktur laporan HTML (content_html) yang menarik dengan elemen-elemen berikut:\n"
            . "1. Headline Utama yang ringkas dan menarik.\n"
            . "2. Laporan Omset: Rangkuman omset hari ini, perbandingan dengan H-1 (persentase naik/turun), total transaksi, dan rata-rata nilai transaksi.\n"
            . $sectionProducts
            . "6. Laporan Pengeluaran: Ringkasan biaya operasional toko hari ini.\n"
            . "7. Saran Aksi Besok: 3 rekomendasi taktis dan konkret untuk dijalankan esok hari yang relevan dengan bisnis {$businessType}.\n\n"
            . "PENTING:\n"
            . "- Kembalikan hasil hanya dalam format JSON yang valid sesuai schema.\n"
            . "- Jangan menyertakan tag markdown ```json di output teks Anda.\n"
            . "- HTML dalam 'content_html' harus menggunakan tag semantic yang bersih seperti <h4>, <h5>, <p>, <strong>, <ul>, <li>, dan class-class CSS bootstrap standar (seperti text-success, text-danger, badge bg-light-danger, dsb.) jika diperlukan.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => json_encode($storeData, JSON_PRETTY_PRINT)]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'headline' => [
                            'type' => 'STRING',
                            'description' => 'Catchy headline in Indonesian summarizing the store performance for the day.'
                        ],
                        'content_html' => [
                            'type' => 'STRING',
                            'description' => 'The complete narrative report body in HTML format.'
                        ]
                    ],
                    'required' => ['headline', 'content_html']
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(20)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidates = $data['candidates'] ?? [];
                if (!empty($candidates)) {
                    $jsonText = $candidates[0]['content']['parts'][0]['text'] ?? '';
                    $parsed = json_decode(trim($jsonText), true);
                    if (is_array($parsed) && isset($parsed['headline']) && isset($parsed['content_html'])) {
                        return [
                            'headline' => (string) $parsed['headline'],
                            'content_html' => (string) $parsed['content_html']
                        ];
                    }
                }
            } else {
                Log::error('Gemini API daily newspaper request failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService generateDailyNewspaper: ' . $e->getMessage());
        }

        return $this->fallbackDailyNewspaper($storeData);
    }

    /**
     * Fallback daily newspaper when Gemini API is unavailable.
     */
    protected function fallbackDailyNewspaper(array $storeData): array
    {
        $date = \Carbon\Carbon::parse($storeData['report_date'])->format('d-m-Y');
        $storeName = $storeData['store_name'];
        $summary = $storeData['summary'];
        $revenue = number_format($summary['total_revenue'], 0, ',', '.');
        $transactions = $summary['total_transactions'];
        $avgTx = number_format($summary['avg_transaction_value'], 0, ',', '.');
        $vsYesterday = $summary['revenue_vs_yesterday_pct'];
        $trend = $vsYesterday >= 0 ? "meningkat sebesar <span class='text-success'>+{$vsYesterday}%</span>" : "menurun sebesar <span class='text-danger'>{$vsYesterday}%</span>";

        $headline = "Laporan Toko {$storeName} Edisi {$date}: Omset Hari Ini Rp {$revenue}";

        $topList = '';
        foreach ($storeData['top_products'] as $item) {
            $topList .= "<li><strong>{$item['name']}</strong> (Terjual: {$item['qty_sold']} unit)</li>";
        }

        $stockList = '';
        if (empty($storeData['critical_stock'])) {
            $stockList = "<li>Stok aman terkendali.</li>";
        } else {
            foreach ($storeData['critical_stock'] as $item) {
                $stockList .= "<li><span class='text-danger'>{$item['name']} - {$item['variant']}</span> (Sisa: {$item['qty_remaining']} unit)</li>";
            }
        }

        $contentHtml = "
            <h4>Edisi Koran Harian Toko: {$storeName} ({$date})</h4>
            <p>Hari ini toko mencatatkan total omset penjualan sebesar <strong>Rp {$revenue}</strong> dari <strong>{$transactions}</strong> transaksi. Rata-rata belanja pelanggan adalah <strong>Rp {$avgTx}</strong>. Dibandingkan kemarin, omset hari ini {$trend}.</p>

            <h5>Produk Terlaris</h5>
            <ul>
                {$topList}
            </ul>

            <h5>Perhatian Stok Kritis</h5>
            <ul>
                {$stockList}
            </ul>

            <h5>Rekomendasi</h5>
            <ol>
                <li>Periksa item dengan stok kritis dan segera buat Purchase Order (PO) baru.</li>
                <li>Fokuskan promosi pada produk terlaris untuk meningkatkan penjualan besok.</li>
                <li>Monitor jam-jam sibuk untuk optimalisasi jumlah staf di toko.</li>
            </ol>
        ";

        return [
            'headline' => $headline,
            'content_html' => trim($contentHtml)
        ];
    }

    /**
     * Generates a daily newspaper narrative using DeepSeek v4 API.
     * Return format: ['headline' => string, 'content_html' => string]
     */
    public function generateDailyNewspaperWithDeepSeek(array $storeData): array
    {
        $key = config('services.deepseek.key');
        $model = config('services.deepseek.model', 'deepseek-v4');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');

        if (empty($key)) {
            Log::warning('DeepSeek API key is not configured. Using fallback daily newspaper.');
            return $this->fallbackDailyNewspaperWithDeepSeek($storeData);
        }

        $url = "{$baseUrl}/chat/completions";

        $businessType = $storeData['business_type'] ?? 'retail';
        $isFnb = $businessType === 'fnb';

        $roleLabel = $isFnb
            ? 'Analis Bisnis F&B (Food & Beverage) Senior'
            : 'Analis Bisnis Ritel Senior';

        $contextNote = $isFnb
            ? "Toko ini adalah bisnis F&B (Food & Beverage) seperti restoran, kafe, atau kedai makanan/minuman. Gunakan istilah-istilah yang relevan dengan dunia F&B: menu, pesanan, food cost, bahan baku, peak dining hours, dll."
            : "Toko ini adalah bisnis Ritel (toko fisik yang menjual barang). Gunakan istilah-istilah yang relevan dengan dunia ritel: stok barang, inventory turnover, display produk, restock, dll.";

        $sectionProducts = $isFnb
            ? "3. **Menu Andalan Hari Ini**: Analisis menu terlaris hari ini — cantumkan nama menu, jumlah terjual, dan kontribusinya terhadap omset. Jelaskan mengapa menu tersebut bisa laris (contoh: faktor cuaca, promo, tren).\n"
            . "4. **Perlu Perhatian Khusus**:\n"
            . "   - Menu yang paling sepi peminat dan dugaan penyebabnya (harga, rasa, kurang promosi?).\n"
            . "   - Bahan baku dengan stok kritis yang harus segera diorder.\n"
            . "5. **Jam Sibuk & Pola Pesanan**: Analisis distribusi transaksi per jam — kapan peak dan off-peak? Bagaimana memaksimalkan kapasitas dapur dan layanan di jam sibuk serta menarik pelanggan di jam sepi?\n"
            : "3. **Bintang Hari Ini**: Analisis produk terlaris hari ini — cantumkan nama produk, jumlah terjual, dan kontribusinya terhadap omset. Jelaskan mengapa produk tersebut bisa laris (contoh: faktor musiman, promo, tren).\n"
            . "4. **Perlu Perhatian Khusus**:\n"
            . "   - Produk yang paling sepi peminat dan dugaan penyebabnya (harga, display, kurang promosi?).\n"
            . "   - Stok yang kritis dan harus segera direstock.\n"
            . "5. **Jam Terbang Toko**: Analisis distribusi transaksi per jam — kapan peak dan off-peak? Berikan insight tentang bagaimana memaksimalkan konversi di jam sibuk dan strategi menarik traffic di jam sepi.\n";

        $systemInstruction = "Anda adalah seorang {$roleLabel} dengan pengalaman 15+ tahun sekaligus Jurnalis Bisnis profesional. Tugas Anda adalah menganalisis data performa harian toko POS (Point of Sales) yang dikirim dalam format JSON dan menyusun laporan narasi bisnis dalam Bahasa Indonesia bergaya editorial 'Koran Toko Digital'.\n\n"
            . "KONTEKS BISNIS: {$contextNote}\n\n"
            . "Laporan HARUS informatif, tajam, memotivasi, dan memberikan wawasan tindakan nyata (actionable insights). Gunakan gaya bahasa jurnalistik koran bisnis profesional — lugas namun engaging. JANGAN menggunakan template yang generik; setiap edisi harus terasa unik berdasarkan data aktual.\n\n"
            . "STRUKTUR LAPORAN HTML (content_html):\n"
            . "1. **Headline Utama** yang catchy, spesifik, dan mencerminkan insight utama hari ini. Jangan headline generik seperti 'Laporan Harian'.\n"
            . "2. **Laporan Omset**: Rangkuman omset hari ini (sebut angka), perbandingan dengan H-1 (persentase naik/turun — sebut angkanya), total transaksi, dan rata-rata nilai transaksi. Beri interpretasi singkat: apa arti angka ini bagi kesehatan bisnis?\n"
            . $sectionProducts
            . "6. **Laporan Pengeluaran**: Ringkasan biaya operasional toko hari ini. Jika ada kategori pengeluaran yang dominan atau tidak biasa, soroti dan beri catatan.\n"
            . "7. **Saran Aksi & Rekomendasi Strategis** (minimal 5 butir, maksimal 8 butir):\n"
            . "   - Setiap rekomendasi HARUS menyebutkan ANGKA atau DATA SPESIFIK dari laporan sebagai dasar argumen.\n"
            . "   - HARUS mencakup kategori yang BERVARIASI, pilih dari: manajemen inventori/stok, strategi pemasaran/promosi, efisiensi operasional/staffing, pricing & diskon, customer experience & loyalty, pengelolaan biaya, dan prediksi tren.\n"
            . "   - JANGAN gunakan saran generik. Setiap butir harus terasa dibuat khusus untuk toko ini berdasarkan data hari ini.\n"
            . "   - Gunakan bahasa profesional khas konsultan bisnis — langsung, taktis, dan actionable.\n"
            . "   - Contoh variasi (JANGAN dikutip mentah, ini hanya ilustrasi gaya):\n"
            . "     • \"Stok [Produk X] tersisa [N] unit dengan kontribusi [Y]% ke omset — segera buat PO hari ini juga karena lead time restock bisa memakan 2-3 hari.\"\n"
            . "     • \"Naikkan margin [kategori] sebesar 5-8% pada jam [peak hours] karena data menunjukkan price elasticity rendah di waktu tersebut.\"\n"
            . "     • \"Luncurkan program 'Early Bird' berupa diskon [X]% untuk transaksi sebelum jam [Y] guna mengisi jam sepi yang hanya menyumbang [Z]% transaksi.\"\n\n"
            . "PENTING:\n"
            . "- Kembalikan hasil HANYA dalam format JSON yang valid: {\"headline\": \"...\", \"content_html\": \"...\"}\n"
            . "- JANGAN menyertakan tag markdown ```json di output.\n"
            . "- HTML dalam 'content_html' harus menggunakan tag semantic yang bersih seperti <h4>, <h5>, <p>, <strong>, <ul>, <li>, <ol>, dan class CSS Bootstrap standar (text-success, text-danger, badge bg-light-danger, bg-warning, dll) jika diperlukan.\n"
            . "- Pastikan rekomendasi benar-benar beragam — jika ada N butir, minimal harus mencakup 3 kategori berbeda.";

        $userMessage = "Berikut adalah data performa toko hari ini dalam format JSON:\n\n"
            . json_encode($storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Buatlah laporan Koran Toko Digital yang tajam dan profesional berdasarkan data di atas.";

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemInstruction
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'response_format' => [
                'type' => 'json_object'
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$key}",
            ])->timeout(45)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';

                if (!empty($content)) {
                    $parsed = json_decode(trim($content), true);
                    if (is_array($parsed) && isset($parsed['headline']) && isset($parsed['content_html'])) {
                        Log::channel('newspaper')->info('DeepSeek API successfully generated newspaper.');
                        return [
                            'headline' => (string) $parsed['headline'],
                            'content_html' => (string) $parsed['content_html']
                        ];
                    }

                    Log::warning('DeepSeek returned invalid JSON structure.', ['content' => $content]);
                }
            } elseif ($response->status() === 429) {
                Log::warning('DeepSeek API rate limited (429). Using fallback.');
            } else {
                Log::error('DeepSeek API request failed: HTTP ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in generateDailyNewspaperWithDeepSeek: ' . $e->getMessage());
        }

        return $this->fallbackDailyNewspaperWithDeepSeek($storeData);
    }

    /**
     * Dynamic fallback daily newspaper when DeepSeek API is unavailable.
     * Generates data-driven recommendations instead of hardcoded text.
     */
    protected function fallbackDailyNewspaperWithDeepSeek(array $storeData): array
    {
        $date = \Carbon\Carbon::parse($storeData['report_date'])->format('d-m-Y');
        $storeName = $storeData['store_name'];
        $summary = $storeData['summary'];
        $revenue = number_format($summary['total_revenue'], 0, ',', '.');
        $transactions = $summary['total_transactions'];
        $avgTx = number_format($summary['avg_transaction_value'], 0, ',', '.');
        $totalDiscount = number_format($summary['total_discount_given'], 0, ',', '.');
        $vsYesterday = $summary['revenue_vs_yesterday_pct'];
        $trendIcon = $vsYesterday >= 0 ? '📈' : '📉';
        $trend = $vsYesterday >= 0
            ? "meningkat sebesar <span class='text-success'>+{$vsYesterday}%</span>"
            : "menurun sebesar <span class='text-danger'>{$vsYesterday}%</span>";

        // Dynamic headline based on revenue trend
        if ($vsYesterday > 20) {
            $headline = "{$trendIcon} Lompatan Omset Toko {$storeName}! Naik {$vsYesterday}% Tembus Rp {$revenue}";
        } elseif ($vsYesterday > 0) {
            $headline = "{$trendIcon} Positif: Omset {$storeName} Naik {$vsYesterday}% ke Rp {$revenue} — Edisi {$date}";
        } elseif ($vsYesterday >= -10) {
            $headline = "Stabil: Toko {$storeName} Catat Omset Rp {$revenue} — Edisi {$date}";
        } else {
            $headline = "{$trendIcon} Waspada: Omset {$storeName} Turun {$vsYesterday}% — Perlu Strategi Baru (Edisi {$date})";
        }

        // Top products
        $topList = '';
        foreach ($storeData['top_products'] as $item) {
            $topList .= "<li><strong>{$item['name']}</strong> — Terjual: {$item['qty_sold']} unit, Revenue: Rp " . number_format($item['revenue'], 0, ',', '.') . "</li>";
        }

        // Slow products
        $slowList = '';
        if (!empty($storeData['slow_products'])) {
            foreach ($storeData['slow_products'] as $item) {
                $slowList .= "<li><strong>{$item['name']}</strong> (Terjual: {$item['qty_sold']} unit)</li>";
            }
        } else {
            $slowList = "<li>Semua produk terjual minimal 1 unit hari ini.</li>";
        }

        // Critical stock
        $stockList = '';
        if (empty($storeData['critical_stock'])) {
            $stockList = "<li><span class='text-success'>✅ Stok aman terkendali — tidak ada item dengan stok kritis.</span></li>";
        } else {
            foreach ($storeData['critical_stock'] as $item) {
                $stockList .= "<li><span class='text-danger'>⚠️ {$item['name']} - {$item['variant']}</span> (Sisa: {$item['qty_remaining']} unit)</li>";
            }
        }

        // Peak hours
        $peakHoursList = '';
        $hourlyDist = $storeData['hourly_distribution'] ?? [];
        if (!empty($hourlyDist)) {
            arsort($hourlyDist);
            $peakHours = array_slice(array_keys($hourlyDist), 0, 3);
            sort($peakHours);
            $peakHoursStr = implode(':00 - ', $peakHours) . ':00';
            $peakHoursList = "<p>Jam tersibuk hari ini: <strong>{$peakHoursStr}</strong>.</p>";
        }

        // Expenses
        $expensesTotal = number_format($storeData['expenses']['total'] ?? 0, 0, ',', '.');
        $expenseCategories = $storeData['expenses']['by_category'] ?? [];
        $expenseList = '';
        if (!empty($expenseCategories)) {
            foreach ($expenseCategories as $cat) {
                $expenseList .= "<li><strong>{$cat['category']}</strong>: Rp " . number_format($cat['amount'], 0, ',', '.') . "</li>";
            }
        } else {
            $expenseList = "<li>Tidak ada pengeluaran tercatat hari ini.</li>";
        }

        // ═══════════════════════════════════════════
        // DYNAMIC RECOMMENDATIONS ENGINE
        // Builds recommendations based on actual data
        // ═══════════════════════════════════════════

        $recommendations = [];

        // 1. Critical stock → specific restock recommendation
        if (!empty($storeData['critical_stock'])) {
            $topCritical = $storeData['critical_stock'][0];
            $recText = "🚨 <strong>Prioritas Restock:</strong> Stok <em>{$topCritical['name']} ({$topCritical['variant']})</em> hanya tersisa <strong>{$topCritical['qty_remaining']} unit</strong> — segera buat Purchase Order (PO) hari ini untuk menghindari kehilangan penjualan (lost sales).";

            if (count($storeData['critical_stock']) > 1) {
                $others = [];
                for ($i = 1; $i < min(count($storeData['critical_stock']), 3); $i++) {
                    $item = $storeData['critical_stock'][$i];
                    $others[] = "{$item['name']} ({$item['qty_remaining']} unit)";
                }
                $recText .= " Jangan lupakan juga: " . implode(', ', $others) . ".";
            }
            $recommendations[] = $recText;
        }

        // 2. Revenue vs yesterday — different recommendation based on direction
        if ($vsYesterday < -15) {
            $recommendations[] = "📉 <strong>Investigasi Penurunan Omset:</strong> Omset turun signifikan {$vsYesterday}% vs kemarin. Bandingkan data traffic pengunjung dan jumlah transaksi hari ini vs H-1. Jika transaksi turun, fokus pada promosi untuk menarik traffic. Jika rata-rata transaksi yang turun, fokus pada upsell dan bundling.";
        } elseif ($vsYesterday > 15) {
            $topProduct = !empty($storeData['top_products']) ? $storeData['top_products'][0]['name'] : 'produk unggulan';
            $recommendations[] = "🔥 <strong>Pertahankan Momentum:</strong> Omset naik {$vsYesterday}%! Analisis faktor pendorong — apakah karena promo, hari spesial, atau produk tertentu seperti <em>{$topProduct}</em>? Dokumentasikan strategi yang berhasil agar bisa direplikasi di masa mendatang.";
        }

        // 3. Peak hours staffing optimization
        if (!empty($hourlyDist)) {
            arsort($hourlyDist);
            $peakHourKeys = array_keys($hourlyDist);
            $topPeak = (int) $peakHourKeys[0];
            $peakPct = $transactions > 0 ? round(($hourlyDist[$peakHourKeys[0]] / $transactions) * 100) : 0;

            if ($peakPct > 30) {
                $recommendations[] = "⏰ <strong>Optimasi Jadwal Staf:</strong> Jam <strong>{$topPeak}:00 - " . ($topPeak + 1) . ":00</strong> menyumbang <strong>{$peakPct}%</strong> total transaksi. Pastikan jumlah kasir dan staf lantai maksimal di jam ini. Pertimbangkan sistem shift yang menempatkan staf terbaik pada periode peak.";
            } else {
                $recommendations[] = "⏰ <strong>Distribusi Transaksi Merata:</strong> Tidak ada konsentrasi transaksi ekstrem di jam tertentu. Ini bagus untuk workload staf, tetapi pertimbangkan untuk menciptakan 'mini peak' dengan promo time-based (Happy Hour diskon 10% jam 14:00-16:00) untuk mendorong traffic tambahan.";
            }

            // Low hour strategy
            $slowestHour = array_keys($hourlyDist);
            $slowestHour = !empty($slowestHour) ? (int) $slowestHour[0] : null;
            if ($slowestHour !== null && isset($hourlyDist[(string) $slowestHour]) && $hourlyDist[(string) $slowestHour] <= 2) {
                $recommendations[] = "🕐 <strong>Isi Jam Sepi:</strong> Transaksi jam {$slowestHour}:00 hanya <strong>{$hourlyDist[(string)$slowestHour]}</strong> transaksi. Luncurkan flash sale atau diskon khusus khusus di jam ini untuk meningkatkan traffic. Bisa juga manfaatkan waktu ini untuk stocktaking atau briefing staf.";
            }
        }

        // 4. Top product strategy
        if (!empty($storeData['top_products'])) {
            $topP = $storeData['top_products'][0];
            $topRevShare = $summary['total_revenue'] > 0 ? round(($topP['revenue'] / $summary['total_revenue']) * 100) : 0;
            if ($topRevShare > 30) {
                $recommendations[] = "⭐ <strong>Jaga Ketersediaan Bintang:</strong> <em>{$topP['name']}</em> menyumbang <strong>{$topRevShare}%</strong> total omset! Pastikan stok produk ini selalu tersedia. Tempatkan di posisi display premium (eye-level) dan pertimbangkan untuk membuat paket bundling dengan produk pelengkap.";
            } else {
                $recommendations[] = "⭐ <strong>Promosikan Produk Bintang:</strong> <em>{$topP['name']}</em> adalah produk terlaris hari ini dengan {$topP['qty_sold']} unit terjual. Pasang di etalase utama dan tawarkan sebagai rekomendasi pertama ke setiap pelanggan yang datang.";
            }
        }

        // 5. Slow moving products strategy
        if (!empty($storeData['slow_products']) && $storeData['slow_products'][0]['qty_sold'] <= 1) {
            $slowNames = [];
            foreach (array_slice($storeData['slow_products'], 0, 3) as $sp) {
                $slowNames[] = $sp['name'];
            }
            $slowStr = implode(', ', $slowNames);
            $recommendations[] = "🔻 <strong>Aksi Produk Sepi:</strong> <em>{$slowStr}</em> hampir tidak bergerak. Evaluasi penyebabnya: apakah harga terlalu tinggi, display kurang menarik, atau sudah tidak tren? Coba strategi bundling (gratis produk sepi dengan pembelian produk laris) atau berikan diskon terbatas.";
        }

        // 6. Expense ratio warning
        $totalExpenses = $storeData['expenses']['total'] ?? 0;
        if ($summary['total_revenue'] > 0 && $totalExpenses > 0) {
            $expenseRatio = round(($totalExpenses / $summary['total_revenue']) * 100);
            if ($expenseRatio > 30) {
                $topExpCat = !empty($expenseCategories) ? $expenseCategories[0]['category'] : 'operasional';
                $recommendations[] = "💰 <strong>Efisiensi Biaya:</strong> Rasio pengeluaran terhadap omset mencapai <strong>{$expenseRatio}%</strong> — di atas ambang sehat 30%. Kategori terbesar adalah <em>{$topExpCat}</em>. Review apakah ada pos biaya yang bisa dinegosiasikan atau ditunda tanpa mengorbankan layanan.";
            } elseif ($expenseRatio < 10) {
                $recommendations[] = "💰 <strong>Biaya Operasional Efisien:</strong> Rasio pengeluaran hanya <strong>{$expenseRatio}%</strong> dari omset — sangat baik. Pertimbangkan untuk mengalokasikan sebagian margin untuk investasi promosi atau peningkatan fasilitas toko.";
            }
        }

        // 7. Average transaction value strategy
        $avgTxValue = $summary['avg_transaction_value'];
        if ($avgTxValue < 50000 && $transactions > 10) {
            $targetAvg = (int) ($avgTxValue * 1.3);
            $targetAvgFormatted = number_format($targetAvg, 0, ',', '.');
            $recommendations[] = "🛒 <strong>Tingkatkan Nilai Transaksi:</strong> Rata-rata belanja pelanggan Rp " . number_format($avgTxValue, 0, ',', '.') . " — masih ada ruang untuk naik. Terapkan strategi upsell di kasir (produk kecil margin tinggi) dan bundling 'beli 2 hemat' dengan target rata-rata Rp {$targetAvgFormatted} per transaksi.";
        }

        // 8. Discount analysis
        if ($summary['total_discount_given'] > 0 && $summary['total_revenue'] > 0) {
            $discountPct = round(($summary['total_discount_given'] / $summary['total_revenue']) * 100, 1);
            if ($discountPct > 10) {
                $recommendations[] = "🏷️ <strong>Evaluasi Diskon:</strong> Total diskon yang diberikan mencapai <strong>{$discountPct}%</strong> dari omset (Rp " . number_format($summary['total_discount_given'], 0, ',', '.') . "). Pastikan setiap diskon menghasilkan konversi yang sepadan. Jika tidak, pertimbangkan untuk menaikkan minimum pembelian agar diskon lebih efektif.";
            }
        }

        // Ensure we have at least 5 recommendations
        if (count($recommendations) < 5) {
            $fillerRecs = [
                "📊 <strong>Pantau Stok Harian:</strong> Jadwalkan pengecekan stok 30 menit sebelum toko buka untuk memastikan semua produk unggulan tersedia di rak. Gunakan data penjualan kemarin sebagai panduan prioritas restock pagi.",
                "🤝 <strong>Program Loyalitas Pelanggan:</strong> Mulai kumpulkan data pelanggan tetap — tawarkan membership sederhana dengan benefit seperti diskon khusus member atau poin reward untuk mendorong repeat purchase.",
                "📱 <strong>Optimasi Promosi Digital:</strong> Bagikan produk terlaris hari ini ke media sosial toko (WhatsApp Story / Instagram). Sertakan testimoni singkat atau foto produk untuk meningkatkan daya tarik.",
                "🎯 <strong>Target Penjualan Besok:</strong> Berdasarkan data hari ini, tetapkan target omset spesifik untuk besok (misalnya naik 10% dari hari ini). Briefing staf pagi hari agar seluruh tim satu visi.",
                "📋 <strong>Catat Insight Harian:</strong> Dokumentasikan pembelajaran hari ini (apa yang berhasil, apa yang tidak) dalam logbook toko. Data ini sangat berharga untuk analisis tren mingguan dan bulanan.",
            ];
            $needed = 5 - count($recommendations);
            for ($i = 0; $i < $needed && $i < count($fillerRecs); $i++) {
                $recommendations[] = $fillerRecs[$i];
            }
        }

        // Build recommendation HTML (max 6)
        $recommendations = array_slice($recommendations, 0, 6);
        $recHtml = '';
        foreach ($recommendations as $i => $rec) {
            $num = $i + 1;
            $recHtml .= "<li>{$rec}</li>\n";
        }

        $contentHtml = "
            <h4>📰 Edisi Koran Harian Toko: {$storeName} ({$date})</h4>
            <p>Hari ini toko mencatatkan total omset penjualan sebesar <strong>Rp {$revenue}</strong> dari <strong>{$transactions}</strong> transaksi. Rata-rata belanja pelanggan adalah <strong>Rp {$avgTx}</strong>. Dibandingkan kemarin, omset hari ini {$trend}.</p>
            {$peakHoursList}

            <h5>🏆 Produk Terlaris</h5>
            <ul>
                {$topList}
            </ul>

            <h5>🔻 Produk Perlu Dorongan</h5>
            <ul>
                {$slowList}
            </ul>

            <h5>⚠️ Status Stok Kritis</h5>
            <ul>
                {$stockList}
            </ul>

            <h5>💸 Pengeluaran Operasional</h5>
            <p>Total pengeluaran hari ini: <strong>Rp {$expensesTotal}</strong></p>
            <ul>
                {$expenseList}
            </ul>

            <h5>🎯 Rekomendasi Strategis</h5>
            <ol>
                {$recHtml}
            </ol>

            <p class='text-muted mt-4'><small>🤖 Laporan ini dibuat otomatis oleh sistem — data bersumber dari transaksi Point of Sales.</small></p>
        ";

        return [
            'headline' => $headline,
            'content_html' => trim($contentHtml)
        ];
    }
}
