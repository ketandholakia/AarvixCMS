<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 15;

    public function __construct(
        public int $webhookId,
        public string $event,
        public array $payload,
    ) {
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook || ! $webhook->is_active) {
            return;
        }

        $body = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ];
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($jsonBody === false) {
            Log::error("Webhook error [{$webhook->id}]: failed to encode payload as JSON");
            return;
        }

        $request = Http::timeout(10);

        if (! empty($webhook->secret)) {
            $signature = hash_hmac('sha256', $jsonBody, $webhook->secret);
            $request->withHeaders(['X-AarvixCMS-Signature' => $signature]);
        }

        try {
            $response = $request
                ->withBody($jsonBody, 'application/json')
                ->post($webhook->url);

            if ($response->failed()) {
                Log::warning("Webhook failed [{$webhook->id}]: HTTP {$response->status()} - {$response->body()}");
            }
        } catch (\Throwable $e) {
            Log::error("Webhook error [{$webhook->id}]: {$e->getMessage()}");
        }
    }
}
