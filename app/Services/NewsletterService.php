<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class NewsletterService
{
    /**
     * Send a welcome email when a user subscribes.
     */
    public function sendWelcomeEmail(Subscriber $subscriber)
    {
        // In a real application, you would queue a mailable here
        // e.g. Mail::to($subscriber->email)->queue(new WelcomeNewsletter($subscriber));
        Log::info("Newsletter Welcome Email sent to: {$subscriber->email}");
    }

    /**
     * Broadcast a post to all active subscribers.
     */
    public function broadcastPost(Post $post)
    {
        $subscribers = Subscriber::where('status', 'subscribed')->get();
        
        foreach ($subscribers as $subscriber) {
            // Queue mail
            // Mail::to($subscriber->email)->queue(new BroadcastPost($post, $subscriber));
            Log::info("Broadcasted Post ID {$post->id} to {$subscriber->email}");
        }
        
        return count($subscribers);
    }
}
