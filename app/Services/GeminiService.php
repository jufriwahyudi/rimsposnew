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

        $systemInstruction = "Anda adalah seorang Analis Bisnis Ritel Senior sekaligus Jurnalis Bisnis. Tugas Anda adalah menganalisis data performa harian toko POS (Point of Sales) yang dikirim dalam format JSON dan menyusun laporan narasi bisnis dalam Bahasa Indonesia bergaya editorial 'Koran Toko Digital'.\n\n"
            . "Laporan harus informatif, memotivasi, dan memberikan wawasan tindakan nyata (actionable insights). Gunakan gaya bahasa jurnalistik koran bisnis yang menarik.\n\n"
            . "Buatlah struktur laporan HTML (content_html) yang menarik dengan elemen-elemen berikut:\n"
            . "1. Headline Utama yang ringkas dan menarik.\n"
            . "2. Laporan Omset: Rangkuman omset hari ini, perbandingan dengan H-1 (persentase naik/turun), total transaksi, dan rata-rata nilai transaksi.\n"
            . "3. Bintang Hari Ini: Analisis produk terlaris hari ini dan kinerjanya.\n"
            . "4. Perlu Perhatian: Analisis produk yang paling sepi dan stok yang kritis (jika ada) untuk segera direstock.\n"
            . "5. Jam Terbang Toko: Analisis waktu transaksi paling ramai (peak hours) dan bagaimana memaksimalkannya.\n"
            . "6. Laporan Pengeluaran: Ringkasan biaya operasional toko hari ini.\n"
            . "7. Saran Aksi Besok: 3 rekomendasi taktis dan konkret untuk dijalankan esok hari.\n\n"
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
}
