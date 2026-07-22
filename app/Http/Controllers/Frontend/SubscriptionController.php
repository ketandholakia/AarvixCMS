<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function pricing()
    {
        return view('frontend.pricing');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string',
        ]);

        return $request->user()
            ->newSubscription('default', $request->price_id)
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.cancel'),
            ]);
    }

    public function success(Request $request)
    {
        return view('frontend.subscription_success');
    }

    public function cancel(Request $request)
    {
        return view('frontend.subscription_cancel');
    }
}
