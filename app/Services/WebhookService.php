<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;
use Illuminate\Support\Facades\Bus;

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
        Bus::dispatch(new SendWebhookJob($webhook->id, $event, $payload));
    }
}
