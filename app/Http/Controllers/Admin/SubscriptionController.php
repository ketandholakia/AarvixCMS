<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = DB::table('subscriptions')
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->select('subscriptions.*', 'users.name', 'users.email')
            ->latest('subscriptions.created_at')
            ->paginate(15);
            
        return view('admin.subscriptions.index', compact('subscriptions'));
    }
}
