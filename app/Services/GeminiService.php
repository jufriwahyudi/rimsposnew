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
     * Generates a daily newspaper narrative using DeepSeek v4/Flash API.
     * Return format: ['headline' => string, 'content_html' => string, 'sections_json' => array, 'ai_provider' => string]
     */
    public function generateDailyNewspaperWithDeepSeek(array $storeData): array
    {
        $key = config('services.deepseek.key');
        $model = config('services.deepseek.model', 'deepseek-flash');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');

        if (empty($key)) {
            Log::channel('newspaper')->warning('DeepSeek API key is not configured. Trying Gemini or fallback.');
            return $this->generateDailyNewspaperWithGemini($storeData);
        }

        $url = "{$baseUrl}/chat/completions";
        $businessType = $storeData['business_type'] ?? 'retail';
        $isFnb = $businessType === 'fnb';

        $roleLabel = $isFnb
            ? 'Senior Food & Beverage (F&B) Business Consultant & Restaurant Critic'
            : 'Senior Retail Business Consultant & Merchandising Strategist';

        $fnbGuidance = $isFnb ? "
ATURAN UTAMA F&B:
1. PENTING - HPP/FOOD COST: Periksa 'data_quality.food_cost_tracked'. Jika bernilai false, toko BELUM mencatat HPP di sistem POS. DILARANG KERAS mengarang laba kotor, margin rupiah, atau persentase food cost fiktif! Fokuslah pada omset kotor vs bersih, volume penjualan, kontribusi kategori menu, dan basket size.
2. NAMA KASIR: Tetap gunakan alias seperti 'Kasir A' atau 'Kasir B' sesuai data, jangan gunakan nama asli.
3. KATEGORI & MENU: Bedah kategori yang mendominasi omset (sebut % dan nominal), hero items (menu terlaris), dan menu lambat yang perlu dievaluasi.
4. JAM EMAS & MEJA: Analisis jam-jam paling ramai (peak dining hours) serta perbandingan Dine-In vs Takeaway untuk strategi mise-en-place barista/dapur.
5. BASKET & BUNDLING: Manfaatkan data 'basket_pairs' (menu yang sering dibeli bersamaan) untuk merancang paket combo menu yang konkret.
6. ALARM OPERASIONAL: Soroti retur, void items, rasio biaya operasional, dan kepatuhan kasir (Cash vs QRIS).
" : "
ATURAN UTAMA RITEL:
1. Analisis performa omset, transaksi, dan perbandingan dengan H-1 serta H-7.
2. Analisis kategori produk terlaris dan produk slow-moving.
3. Analisis jam kunjungan ramai dan jam sepi toko.
4. Soroti stok kritis yang perlu segera direstock.
5. Alarm biaya operasional dan kasir.
";

        $systemInstruction = "Anda adalah seorang {$roleLabel} dengan pengalaman 15+ tahun.
Tugas Anda adalah menganalisis data performa harian toko POS (format JSON) dan menyusun laporan narasi bisnis editorial dalam Bahasa Indonesia bergaya 'Koran Toko Digital'.
{$fnbGuidance}
Kembalikan HANYA format JSON valid tanpa tag markdown ```json.
Format JSON yang WAJIB dipenuhi:
{
  \"headline\": \"Judul berita editorial yang tajam, spesifik, dan memikat (maks 15 kata)\",
  \"executive_summary\": \"Paragraf pembuka: Rangkuman omset bersih, perbandingan vs kemarin dan minggu lalu, rata-rata belanja, serta sentimen performa harian secara menyeluruh.\",
  \"category_and_menu\": \"Analisis mendalam kategori menu/produk mana yang jadi tulang punggung omset (sebutkan % kontribusi dan nominalnya), menu andalan (star items), dan menu lambat yang perlu dievaluasi.\",
  \"peak_hours_and_dining\": \"Analisis jam-jam emas (rush hours), perbandingan Dine-In vs Takeaway (jika F&B), serta kesiapan staf sebelum jam sibuk.\",
  \"basket_and_combos\": \"Ulasan kombinasi menu yang sering dibeli bersamaan (basket pairs) dan usulan paket combo/bundling taktis untuk mengerek average transaction value.\",
  \"operational_and_finance\": \"Audit operasional kasir, kepatuhan pembayaran (Cash vs QRIS), rasio pengeluaran harian, catatan void/refund, dan kesiapan stok.\",
  \"tactical_recommendations\": [
     \"Rekomendasi taktis 1 yang spesifik dengan angka nyata dari data\",
     \"Rekomendasi taktis 2\",
     \"Rekomendasi taktis 3\",
     \"Rekomendasi taktis 4\",
     \"Rekomendasi taktis 5\",
     \"Rekomendasi taktis 6\"
  ]
}";

        $userMessage = "Berikut data penjualan harian toko untuk dianalisis:\n\n"
            . json_encode($storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.6,
            'max_tokens' => 8192,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$key}",
            ])->timeout(60)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';

                if (!empty($content)) {
                    $parsed = json_decode(trim($content), true);
                    if (is_array($parsed) && !empty($parsed['headline']) && !empty($parsed['executive_summary'])) {
                        Log::channel('newspaper')->info('DeepSeek successfully generated structured newspaper.');
                        $contentHtml = $this->compileSectionsToHtml($parsed, $storeData);
                        return [
                            'headline' => (string) $parsed['headline'],
                            'content_html' => $contentHtml,
                            'sections_json' => $parsed,
                            'ai_provider' => 'deepseek',
                        ];
                    }
                    Log::channel('newspaper')->warning('DeepSeek returned invalid JSON structure: ' . substr($content, 0, 300));
                }
            } else {
                Log::channel('newspaper')->error('DeepSeek API request failed: HTTP ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::channel('newspaper')->error('Exception calling DeepSeek: ' . $e->getMessage());
        }

        // DeepSeek failed -> Try Gemini fallback
        return $this->generateDailyNewspaperWithGemini($storeData);
    }

    /**
     * Secondary fallback using Google Gemini API.
     */
    public function generateDailyNewspaperWithGemini(array $storeData): array
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($key)) {
            Log::channel('newspaper')->warning('Gemini key not configured. Using rich offline fallback.');
            return $this->fallbackDailyNewspaperWithDeepSeek($storeData);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $businessType = $storeData['business_type'] ?? 'retail';
        $isFnb = $businessType === 'fnb';

        $systemInstruction = "Anda adalah Senior POS Business Consultant. Analisis data performa harian toko berikut dan susun koran digital harian dalam format JSON.\n"
            . "Format JSON WAJIB:\n"
            . "{\n"
            . "  \"headline\": \"Judul berita (maks 15 kata)\",\n"
            . "  \"executive_summary\": \"Ringkasan omset bersih, perbandingan kemarin dan minggu lalu, dan performa umum.\",\n"
            . "  \"category_and_menu\": \"Analisis kategori menu terlaris dan menu lambat.\",\n"
            . "  \"peak_hours_and_dining\": \"Analisis jam ramai dan perbandingan dine-in vs takeaway.\",\n"
            . "  \"basket_and_combos\": \"Kombinasi menu dan ide paket bundling.\",\n"
            . "  \"operational_and_finance\": \"Catatan kasir, metode bayar, pengeluaran, dan stok.\",\n"
            . "  \"tactical_recommendations\": [\"Rekomendasi 1\", \"Rekomendasi 2\", \"Rekomendasi 3\", \"Rekomendasi 4\", \"Rekomendasi 5\"]\n"
            . "}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => json_encode($storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
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
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidates = $data['candidates'] ?? [];
                if (!empty($candidates)) {
                    $jsonText = $candidates[0]['content']['parts'][0]['text'] ?? '';
                    $parsed = json_decode(trim($jsonText), true);
                    if (is_array($parsed) && !empty($parsed['headline']) && !empty($parsed['executive_summary'])) {
                        Log::channel('newspaper')->info('Gemini successfully generated structured newspaper.');
                        $contentHtml = $this->compileSectionsToHtml($parsed, $storeData);
                        return [
                            'headline' => (string) $parsed['headline'],
                            'content_html' => $contentHtml,
                            'sections_json' => $parsed,
                            'ai_provider' => 'gemini',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::channel('newspaper')->error('Exception calling Gemini: ' . $e->getMessage());
        }

        // Final fallback: Rich offline generator
        return $this->fallbackDailyNewspaperWithDeepSeek($storeData);
    }

    /**
     * Compiles structured JSON sections into clean, beautiful editorial HTML.
     */
    protected function compileSectionsToHtml(array $sections, array $storeData): string
    {
        $isFnb = ($storeData['business_type'] ?? '') === 'fnb';
        $html = '';

        // 1. Executive Summary
        if (!empty($sections['executive_summary'])) {
            $html .= "<div class='newspaper-section mb-4'>"
                . "<p class='lead fw-normal text-dark'>" . $this->formatText($sections['executive_summary']) . "</p>"
                . "</div>";
        }

        // 2. Category & Menu
        if (!empty($sections['category_and_menu'])) {
            $title = $isFnb ? 'Menu & Kategori Andalan' : 'Kategori & Produk Andalan';
            $icon = $isFnb ? 'fa-utensils' : 'fa-box-open';
            $html .= "<div class='newspaper-section mb-4'>"
                . "<h4><i class='fas {$icon} me-2 text-primary'></i>{$title}</h4>"
                . "<p>" . $this->formatText($sections['category_and_menu']) . "</p>"
                . "</div>";
        }

        // 3. Peak Hours & Dining
        if (!empty($sections['peak_hours_and_dining'])) {
            $title = $isFnb ? 'Jam Emas & Pola Meja' : 'Jam Terbang & Pola Kunjungan';
            $icon = 'fa-clock';
            $html .= "<div class='newspaper-section mb-4'>"
                . "<h4><i class='fas {$icon} me-2 text-warning'></i>{$title}</h4>"
                . "<p>" . $this->formatText($sections['peak_hours_and_dining']) . "</p>"
                . "</div>";
        }

        // 4. Basket & Combos
        if (!empty($sections['basket_and_combos'])) {
            $title = $isFnb ? 'Rekomendasi Bundling & Menu Sepi' : 'Peluang Bundling & Cross-Selling';
            $html .= "<div class='newspaper-section mb-4'>"
                . "<h4><i class='fas fa-tags me-2 text-info'></i>{$title}</h4>"
                . "<p>" . $this->formatText($sections['basket_and_combos']) . "</p>"
                . "</div>";
        }

        // 5. Operational & Finance Audit
        if (!empty($sections['operational_and_finance'])) {
            $html .= "<div class='newspaper-section mb-4'>"
                . "<h4><i class='fas fa-shield-alt me-2 text-danger'></i>Alarm Operasional & Keuangan</h4>"
                . "<p>" . $this->formatText($sections['operational_and_finance']) . "</p>"
                . "</div>";
        }

        // 6. Tactical Recommendations
        if (!empty($sections['tactical_recommendations']) && is_array($sections['tactical_recommendations'])) {
            $html .= "<div class='newspaper-section mb-4'>"
                . "<h4><i class='fas fa-bullseye me-2 text-success'></i>Rekomendasi Taktis Esok Hari</h4>"
                . "<ol class='ps-3'>";
            foreach ($sections['tactical_recommendations'] as $rec) {
                $html .= "<li class='mb-2'>" . $this->formatText($rec) . "</li>";
            }
            $html .= "</ol></div>";
        }

        return trim($html);
    }

    /**
     * Formats markdown bolding safely.
     */
    protected function formatText(string $text): string
    {
        $escaped = e($text);
        return preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $escaped);
    }

    /**
     * Rich dynamic fallback daily newspaper when AI APIs are unavailable.
     * Generates structured sections based on actual store data.
     */
    protected function fallbackDailyNewspaperWithDeepSeek(array $storeData): array
    {
        $date = \Carbon\Carbon::parse($storeData['report_date'])->locale('id')->isoFormat('dddd, D MMMM Y');
        $storeName = $storeData['store_name'];
        $businessType = $storeData['business_type'] ?? 'retail';
        $isFnb = $businessType === 'fnb';

        $summary = $storeData['summary'];
        $netRevenue = (float)($summary['net_revenue'] ?? $summary['total_revenue'] ?? 0);
        $revenueFormatted = 'Rp ' . number_format($netRevenue, 0, ',', '.');
        $transactions = (int)($summary['total_transactions'] ?? 0);
        $avgTx = 'Rp ' . number_format($summary['avg_transaction_value'] ?? 0, 0, ',', '.');
        $vsYesterday = (float)($summary['revenue_vs_yesterday_pct'] ?? 0);
        $vsLastWeek = (float)($summary['revenue_same_day_last_week'] ?? 0);
        $vsLastWeekPct = (float)($summary['revenue_vs_last_week_pct'] ?? 0);

        // Headline
        $trendIcon = $vsYesterday >= 0 ? '📈' : '📊';
        if ($vsYesterday > 15) {
            $headline = "{$trendIcon} Lompatan Omset {$storeName}! Naik {$vsYesterday}% Tembus {$revenueFormatted}";
        } elseif ($vsYesterday >= 0) {
            $headline = "{$trendIcon} Tren Positif: {$storeName} Bukukan Omset {$revenueFormatted} (+{$vsYesterday}%)";
        } elseif ($vsYesterday >= -15) {
            $headline = "Stabil: {$storeName} Catatkan Omset Bersih {$revenueFormatted} — Edisi {$date}";
        } else {
            $headline = "Waspada: Omset {$storeName} Terkoreksi {$vsYesterday}% Menjadi {$revenueFormatted}";
        }

        // Section 1: Executive Summary
        $trendText = $vsYesterday >= 0 ? "meningkat +{$vsYesterday}%" : "turun {$vsYesterday}%";
        $weekTrendText = $vsLastWeekPct >= 0 ? "tumbuh +{$vsLastWeekPct}%" : "terkoreksi {$vsLastWeekPct}%";
        $execSummary = "Pada edisi {$date}, {$storeName} mencatatkan omset bersih sebesar {$revenueFormatted} dari total {$transactions} transaksi. Dibandingkan kemarin, omset {$trendText}, sementara dibandingkan hari yang sama pekan lalu {$weekTrendText}. Rata-rata nilai belanja per pelanggan tercatat di angka {$avgTx}.";

        // Section 2: Category & Menu
        $catParts = [];
        foreach (array_slice($storeData['categories'] ?? [], 0, 3) as $cat) {
            $catParts[] = "{$cat['category']} ({$cat['share_pct']}%, Rp " . number_format($cat['revenue'], 0, ',', '.') . ")";
        }
        $catSummaryStr = !empty($catParts) ? implode(', ', $catParts) : 'belum terkategori';

        $topMenuParts = [];
        foreach (array_slice($storeData['top_products'] ?? [], 0, 3) as $tp) {
            $topMenuParts[] = "{$tp['name']} ({$tp['qty_sold']} terjual)";
        }
        $topMenuStr = !empty($topMenuParts) ? implode(', ', $topMenuParts) : 'belum ada transaksi';

        $slowMenuParts = [];
        foreach (array_slice($storeData['slow_products'] ?? [], 0, 3) as $sp) {
            $slowMenuParts[] = "{$sp['name']} ({$sp['qty_sold']} terjual)";
        }
        $slowMenuStr = !empty($slowMenuParts) ? implode(', ', $slowMenuParts) : 'semua item bergerak';

        $categoryAndMenu = "Kategori penopang omset utama hari ini adalah {$catSummaryStr}. Produk andalan dengan volume penjualan tertinggi dipimpin oleh {$topMenuStr}. Sementara itu, produk dengan pergerakan paling lambat tercatat pada {$slowMenuStr}.";

        // Section 3: Peak Hours & Dining
        $hourlyDist = $storeData['hourly_distribution'] ?? [];
        $peakHourStr = 'pukul 12:00 - 14:00';
        if (!empty($hourlyDist)) {
            arsort($hourlyDist);
            $topHours = array_slice(array_keys($hourlyDist), 0, 2);
            $peakHourStr = 'pukul ' . implode(':00 dan ', $topHours) . ':00';
        }

        $dineData = $storeData['dine_in_takeaway'] ?? [];
        $dineStr = '';
        if ($isFnb && !empty($dineData['dine_in_count'])) {
            $dineStr = " Pola kunjungan didominasi oleh pesanan Dine-In sebanyak {$dineData['dine_in_count']} transaksi ({$dineData['dine_in_pct']}%), sedangkan pesanan Takeaway tercatat {$dineData['takeaway_count']} transaksi ({$dineData['takeaway_pct']}%).";
        }

        $peakHoursAndDining = "Jam transaksi paling padat terjadi pada {$peakHourStr}.{$dineStr} Pastikan tim operasional melakukan persiapan mise-en-place sebelum jam sibuk tersebut.";

        // Section 4: Basket & Combos
        $basketPairs = $storeData['basket_pairs'] ?? [];
        $basketStr = '';
        if (!empty($basketPairs)) {
            $pairsFormatted = [];
            foreach (array_slice($basketPairs, 0, 3) as $bp) {
                $pairsFormatted[] = "{$bp['combo']} ({$bp['pair_count']} kali dipesan bersamaan)";
            }
            $basketStr = "Kombinasi menu yang paling sering dipesan bersamaan adalah: " . implode('; ', $pairsFormatted) . ". Paket ini sangat potensial dijadikan menu bundling resmi di daftar menu.";
        } else {
            $basketStr = "Belum terdeteksi kombinasi pesanan berulang yang dominan hari ini. Dorong tim kasir untuk aktif menawarkan menu pendamping (cross-selling).";
        }

        // Section 5: Operational & Finance
        $totalExp = (float)($storeData['expenses']['total'] ?? 0);
        $expFormatted = 'Rp ' . number_format($totalExp, 0, ',', '.');
        $expRatio = $netRevenue > 0 ? round(($totalExp / $netRevenue) * 100, 1) : 0;
        $voidCount = (int)($storeData['void_metrics']['void_items_count'] ?? 0);
        $voidStr = $voidCount > 0 ? "Tercatat {$voidCount} item dibatalkan (void) hari ini." : "Nihil pembatalan pesanan (void), operasional berjalan bersih.";

        $operationalAndFinance = "Pengeluaran operasional hari ini tercatat {$expFormatted} ({$expRatio}% dari omset). {$voidStr} Disiplin kasir dan rekonsiliasi kas register perlu terus dipertahankan.";

        // Section 6: Tactical Recommendations
        $recommendations = [
            "Siapkan bahan baku dan kapasitas layanan 30 menit sebelum jam sibuk ({$peakHourStr}) untuk mencegah antrean menumpuk.",
            "Tampilkan paket bundling favorit pelanggan di papan menu utama guna mengerek rata-rata transaksi di atas {$avgTx}.",
            "Evaluasi menu yang lambat bergerak ({$slowMenuStr}) melalui promosi terbatas atau penataan ulang posisi menu.",
            "Jaga rasio pengeluaran operasional tetap di bawah 20% dari total omset harian.",
            "Pastikan stok bahan baku utama dicek setiap pagi sebelum jam operasional dimulai."
        ];

        $sections = [
            'headline' => $headline,
            'executive_summary' => $execSummary,
            'category_and_menu' => $categoryAndMenu,
            'peak_hours_and_dining' => $peakHoursAndDining,
            'basket_and_combos' => $basketStr,
            'operational_and_finance' => $operationalAndFinance,
            'tactical_recommendations' => $recommendations,
        ];

        $contentHtml = $this->compileSectionsToHtml($sections, $storeData);

        return [
            'headline' => $headline,
            'content_html' => $contentHtml,
            'sections_json' => $sections,
            'ai_provider' => 'fallback',
        ];
    }
}
