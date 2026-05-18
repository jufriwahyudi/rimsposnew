<?php

namespace App\Console\Commands;

use App\Models\BroadcastMessageDetail;
use App\Models\SettingWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Http;

class ValidateNumberWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:wa {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim broadcast WhatsApp melalui watzap.id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $details = BroadcastMessageDetail::with('broadcast:id,type')->where('valid_nohp', 'N')
            ->where('no_hp', '!=', null)
            ->where('status', 'PENDING')
            ->whereHas('broadcast', function ($query) {
                $query->where('broadcast_on', '<=', now());
            })
            ->limit($limit)
            ->get();
        // $this->info(now());
        // exit;
        foreach ($details as $detail) {
            $noHp = $detail->no_hp;
            if ($noHp) {
                // Panggil API untuk validasi nomor WhatsApp
                $isValid = $this->validateWhatsAppNumber($noHp, $detail->broadcast->type);
                if ($isValid) {
                    $detail->valid_nohp = 'Y';
                    $this->info("Nomor valid: $noHp");
                    $this->sendMessage($detail, $detail->broadcast->type);
                } else {
                    $detail->valid_nohp = 'I';
                    $this->warn("Nomor tidak valid: $noHp");
                }
                $detail->save();
            } else {
                $this->warn("Nomor HP kosong untuk ID detail: {$detail->id}");
            }
        }
        $this->info('Validasi nomor WhatsApp selesai.');
    }

    private function validateWhatsAppNumber($number, $type)
    {
        try {
            $numberkey = SettingWhatsapp::get('watzap_number_key');
            if ($type === 'tagihan_catering') {
                $numberkey = SettingWhatsapp::get('watzap_number_key_catering');
            }
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post("https://api.watzap.id/v1/validate_number", [
                'api_key' => SettingWhatsapp::get('watzap_api_key'),
                'number_key' => $numberkey,
                'phone_no' => $number,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                // Cek status valid dari API (status == "200" dan ack == "successfully")
                return isset($data['status'], $data['ack']) && $data['status'] === "200" && $data['ack'] === "successfully";
            }
            return false;
        } catch (\Exception $e) {
            $this->error("Error validating number $number: " . $e->getMessage());
            return false;
        }
    }

    private function sendMessage(BroadcastMessageDetail $detail, $type)
    {
        try {
            $numberkey = SettingWhatsapp::get('watzap_number_key');
            if ($type === 'tagihan_catering') {
                $numberkey = SettingWhatsapp::get('watzap_number_key_catering');
            }
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post("https://api.watzap.id/v1/send_message", [
                'api_key' => SettingWhatsapp::get('watzap_api_key'),
                'number_key' => $numberkey,
                'phone_no' => $detail->no_hp,
                'message' => $detail->message,
                // 'wait_until_send' => "1",
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $detail->response_api = $data['status'] ?? null;
                $detail->response_message = json_encode($data) ?? null;
                if (isset($data['status']) && $data['status'] === "200") {
                    $detail->status = 'SENT';
                    $this->info("Pesan terkirim ke: {$detail->no_hp}");
                } else {
                    $detail->status = 'FAILED';
                    $this->warn("Gagal mengirim pesan ke: {$detail->no_hp}");
                }
            } else {
                $detail->status = 'FAILED';
                $this->warn("Gagal mengirim pesan ke: {$detail->no_hp}, HTTP error");
            }
            $detail->save();
        } catch (\Exception $e) {
            $this->error("Error sending message to {$detail->no_hp}: " . $e->getMessage());
            $detail->status = 'FAILED';
            $detail->save();
        }
    }
}
