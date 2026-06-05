<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use App\Models\Sale;

class FirestoreService
{
    protected $projectId;
    protected $credentialsFile;

    public function __construct()
    {
        $credentials = config('firebase.projects.app.credentials');
        if (!file_exists($credentials)) {
            $credentials = base_path($credentials);
        }
        $this->credentialsFile = $credentials;

        if (file_exists($this->credentialsFile)) {
            $json = json_decode(file_get_contents($this->credentialsFile), true);
            $this->projectId = $json['project_id'] ?? 'rimspos';
        } else {
            $this->projectId = 'rimspos';
        }
    }

    /**
     * Get Google OAuth2 access token.
     */
    protected function getAccessToken(): ?string
    {
        try {
            if (!file_exists($this->credentialsFile)) {
                Log::error("Firebase credentials file not found: {$this->credentialsFile}");
                return null;
            }

            $scopes = ['https://www.googleapis.com/auth/datastore'];
            $creds = new ServiceAccountCredentials($scopes, $this->credentialsFile);
            $token = $creds->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error("Failed to generate Google OAuth2 token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync Store configuration to Firestore root stores collection.
     */
    public function syncStore(Store $store): bool
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $data = [
            'id' => (int)$store->id,
            'name' => (string)$store->name,
            'business_type' => (string)$store->business_type,
            'addon_self_service' => (bool)$store->addon_self_service,
            'addon_kds' => (bool)$store->addon_kds,
            'updated_at' => now()->toIso8601String(),
        ];

        $payload = $this->toFirestoreFields($data);
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/stores/{$store->id}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->patch($url, $payload);

            if ($response->successful()) {
                Log::info("Synced store #{$store->id} to Firestore successfully.");
                return true;
            }

            Log::error("Failed to sync store #{$store->id} to Firestore. Status: " . $response->status() . " Body: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Firestore API patch error for store #{$store->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync Sale order to Firestore self_service_orders sub-collection.
     */
    public function syncOrder(Sale $sale): bool
    {
        // Only sync self-service orders (invoice starts with 'QR-')
        if (!str_starts_with($sale->invoice_number, 'QR-')) {
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        // Load items with details freshly
        $sale->load(['items.variant.product', 'items.fnbDetail']);

        $items = [];
        foreach ($sale->items as $item) {
            $items[] = [
                'id' => (int)$item->id,
                'product_variant_id' => $item->product_variant_id ? (int)$item->product_variant_id : null,
                'name' => (string)$item->product_name,
                'qty' => (int)$item->qty,
                'price' => (float)$item->price,
                'subtotal' => (float)$item->subtotal,
                'notes' => (string)($item->notes ?? ''), // retrieve notes from sale_items column
            ];
        }

        $data = [
            'id' => (string)$sale->invoice_number,
            'sale_id' => (int)$sale->id,
            'table_number' => (string)$sale->table_number,
            'customer_name' => (string)$sale->customer_name,
            'status' => call_user_func(function() use ($sale) {
                if ($sale->status === 'void') {
                    return 'cancelled';
                }
                if ($sale->status === 'paid') {
                    return 'completed';
                }
                
                // If 'hold' (unpaid), check items' KDS status
                $sale->loadMissing('items.fnbDetail');
                $items = $sale->items;
                if ($items->isEmpty()) {
                    return ($sale->user_id !== null) ? 'confirmed' : 'pending';
                }
                
                $preparingCount = 0;
                $readyCount = 0;
                $totalItems = $items->count();
                
                foreach ($items as $item) {
                    $itemStatus = $item->kds_status;
                    if ($itemStatus === 'cooking' || $itemStatus === 'ready') {
                        $preparingCount++;
                    }
                    if ($itemStatus === 'ready' || $itemStatus === 'served') {
                        $readyCount++;
                    }
                }
                
                if ($readyCount === $totalItems && $totalItems > 0) {
                    return 'completed';
                }
                if ($preparingCount > 0) {
                    return 'preparing';
                }
                return ($sale->user_id !== null) ? 'confirmed' : 'pending';
            }),
            'items' => $items,
            'subtotal' => (float)$sale->subtotal,
            'discount' => (float)$sale->discount_total,
            'total' => (float)$sale->grand_total,
            'created_at' => $sale->created_at->toIso8601String(),
            'updated_at' => $sale->updated_at->toIso8601String(),
        ];
        // Catat $data ke log
        Log::info('Debug Firestore syncOrder:', ['data' => $data]);

        $payload = $this->toFirestoreFields($data);
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/stores/{$sale->store_id}/self_service_orders/{$sale->invoice_number}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->patch($url, $payload);

            if ($response->successful()) {
                Log::info("Synced order {$sale->invoice_number} (Store #{$sale->store_id}) to Firestore successfully.");
                return true;
            }

            Log::error("Failed to sync order {$sale->invoice_number} to Firestore. Status: " . $response->status() . " Body: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Firestore API patch error for order {$sale->invoice_number}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete self-service order document from Firestore.
     */
    public function deleteOrder(string $storeId, string $invoiceNumber): bool
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/stores/{$storeId}/self_service_orders/{$invoiceNumber}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->delete($url);

            if ($response->successful()) {
                Log::info("Deleted order {$invoiceNumber} from Firestore.");
                return true;
            }

            Log::error("Failed to delete order {$invoiceNumber} from Firestore. Status: " . $response->status());
            return false;
        } catch (\Throwable $e) {
            Log::error("Firestore API delete error for order {$invoiceNumber}: " . $e->getMessage());
            return false;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function toFirestoreFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->toFirestoreValue($value);
        }
        return ['fields' => $fields];
    }

    protected function toFirestoreValue($value): array
    {
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_int($value)) {
            return ['integerValue' => (string)$value];
        }
        if (is_float($value) || is_double($value)) {
            return ['doubleValue' => $value];
        }
        if (is_string($value)) {
            return ['stringValue' => $value];
        }
        if (is_null($value)) {
            return ['nullValue' => null];
        }
        if (is_array($value)) {
            if (empty($value)) {
                return ['arrayValue' => ['values' => []]];
            }
            // Check if sequential or associative
            if (array_keys($value) === range(0, count($value) - 1)) {
                $values = [];
                foreach ($value as $item) {
                    $values[] = $this->toFirestoreValue($item);
                }
                return ['arrayValue' => ['values' => $values]];
            } else {
                $fields = [];
                foreach ($value as $k => $v) {
                    $fields[$k] = $this->toFirestoreValue($v);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
        }
        return ['stringValue' => (string)$value];
    }
}
