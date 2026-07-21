<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'posts' => Post::count(),
            'pages' => Page::count(),
            'users' => User::count(),
        ];

        $recentActivity = ActivityLog::with('user')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentActivity'));
    }
}
