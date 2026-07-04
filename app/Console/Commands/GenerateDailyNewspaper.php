<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\DigitalNewspaper;
use App\Services\DataAggregatorService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateDailyNewspaper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newspaper:generate {--date= : The date to generate for (YYYY-MM-DD)} {--store= : Generate for a specific store ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the digital daily newspaper for active stores';

    /**
     * Execute the console command.
     */
    public function handle(DataAggregatorService $aggregator, GeminiService $gemini)
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $storeIdOption = $this->option('store');

        $this->info("Starting digital newspaper generation for date: {$date}");
        Log::channel('newspaper')->info("Starting digital newspaper generation for date: {$date}");

        if ($storeIdOption) {
            $stores = Store::where('id', $storeIdOption)->get();
            if ($stores->isEmpty()) {
                $this->error("Store with ID {$storeIdOption} not found.");
                return 1;
            }
        } else {
            $stores = Store::where('is_active', true)->get();
        }

        foreach ($stores as $store) {
            $this->info("Processing store: {$store->name} (ID: {$store->id})");
            Log::channel('newspaper')->info("Processing store: {$store->name} (ID: {$store->id})");

            // Create or update newspaper record
            $newspaper = DigitalNewspaper::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'report_date' => $date,
                ],
                [
                    'status' => 'pending',
                    'generated_at' => now(),
                    'error_message' => null,
                ]
            );

            try {
                // 1. Aggregate data
                $aggregatedData = $aggregator->aggregateForNewspaper($store->id, $date);

                // Save raw payload for debugging
                $newspaper->update([
                    'raw_payload' => $aggregatedData
                ]);

                // 2. Generate daily newspaper using DeepSeek v4
                $result = $gemini->generateDailyNewspaperWithDeepSeek($aggregatedData);

                // 3. Update status to success
                $newspaper->update([
                    'headline' => $result['headline'],
                    'content_html' => $result['content_html'],
                    'status' => 'success',
                ]);

                $this->info("Successfully generated newspaper for store: {$store->name}");
                Log::channel('newspaper')->info("Successfully generated newspaper for store: {$store->name}");

            } catch (\Throwable $e) {
                Log::channel('newspaper')->error("Failed to generate newspaper for store {$store->id}: " . $e->getMessage(), [
                    'exception' => $e
                ]);

                $newspaper->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $this->error("Failed to generate newspaper for store: {$store->name}. Error: " . $e->getMessage());
            }
        }

        $this->info("Finished digital newspaper generation.");
        Log::channel('newspaper')->info("Finished digital newspaper generation.");

        return 0;
    }
}
