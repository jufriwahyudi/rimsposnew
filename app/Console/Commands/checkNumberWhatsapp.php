<?php

namespace App\Console\Commands;

use App\Models\SettingWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class checkNumberWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-number-whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $numbers = '085260032176';
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post("https://api.watzap.id/v1/validate_number", [
                'api_key' => SettingWhatsapp::get('watzap_api_key'),
                'number_key' => SettingWhatsapp::get('watzap_number_key'),
                'phone_no' => $numbers,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                // Cek status valid dari API (status == "200" dan ack == "successfully")
                $this->info(json_encode($data));
            }
            return false;
        } catch (\Exception $e) {
            $this->error("Error validating number $numbers: " . $e->getMessage());
            return false;
        }
    }
}
