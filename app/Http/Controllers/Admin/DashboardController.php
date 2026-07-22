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
            'subscribers' => \App\Models\Subscriber::where('status', 'subscribed')->count(),
            'pending_comments' => \App\Models\Comment::where('status', 'pending')->count(),
            'total_views' => \App\Models\PageView::count(),
        ];
        
        // 30 days traffic
        $trafficData = \App\Models\PageView::selectRaw('DATE(created_at) as date, count(*) as views')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('views', 'date');
            
        // Fill missing days
        $chartDates = [];
        $chartViews = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = $date;
            $chartViews[] = $trafficData->get($date, 0);
        }
        
        $topPosts = \App\Models\Post::withCount(['comments', 'revisions'])->orderByDesc('comments_count')->take(5)->get();

        $recentActivity = ActivityLog::with('user')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentActivity', 'chartDates', 'chartViews', 'topPosts'));
    }
}
