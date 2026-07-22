<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch an event to all matching active webhooks.
     */
    public function dispatch(string $event, array $payload = [])
    {
        $webhooks = Webhook::where('is_active', true)->get();

        foreach ($webhooks as $webhook) {
            // Check if this webhook is configured for this event
            $events = $webhook->events ?? [];
            
            // If events array is empty, we assume it triggers for everything (or you can require exact match)
            // Let's require the event to be in the array, or '*' to trigger on all.
            if (empty($events) || (!in_array($event, $events) && !in_array('*', $events))) {
                continue;
            }

            $this->sendPayload($webhook, $event, $payload);
        }
    }

    /**
     * Send the payload to a specific webhook asynchronously.
     */
    protected function sendPayload(Webhook $webhook, string $event, array $payload)
    {
        $body = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $request = Http::timeout(10)->asJson();

        // Optional HMAC signature
        if (!empty($webhook->secret)) {
            $signature = hash_hmac('sha256', json_encode($body), $webhook->secret);
            $request->withHeaders(['X-AarvixCMS-Signature' => $signature]);
        }

        // Ideally this should be queued. We'll use a queued closure in Laravel 11.
        dispatch(function () use ($request, $webhook, $body) {
            try {
                $response = $request->post($webhook->url, $body);
                
                if ($response->failed()) {
                    Log::warning("Webhook failed [{$webhook->id}]: HTTP {$response->status()} - {$response->body()}");
                }
            } catch (\Exception $e) {
                Log::error("Webhook error [{$webhook->id}]: {$e->getMessage()}");
            }
        });
    }
}
