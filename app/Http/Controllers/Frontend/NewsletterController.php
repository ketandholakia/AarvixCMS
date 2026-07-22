<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\NewsletterService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        // Honeypot check
        if (!empty($request->input('hp_contact'))) {
            return back()->with('newsletter_success', 'Thank you for subscribing!');
        }

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ]);

        $subscriber = Subscriber::where('email', $data['email'])->first();

        if ($subscriber) {
            if ($subscriber->status === 'unsubscribed') {
                $subscriber->update(['status' => 'pending']); // or 'subscribed' if no double opt-in
            }
            // Already subscribed, just show success
        } else {
            $data['status'] = 'subscribed'; // Or 'pending' if you want double opt-in
            $data['ip_address'] = $request->ip();
            $subscriber = Subscriber::create($data);
        }

        // Send welcome email / confirmation via service
        app(NewsletterService::class)->sendWelcomeEmail($subscriber);

        return back()->with('newsletter_success', 'Thank you for subscribing to our newsletter!');
    }
}
