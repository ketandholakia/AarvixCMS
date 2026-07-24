<?php

namespace App\Http\Controllers\Admin;

use App\AI\Enums\AiStatus;
use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use App\Models\ActivityLog;

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

        $aiStats = null;
        $aiChartDates = [];
        $aiChartRequests = [];
        $aiChartTokens = [];
        $recentAiRequests = collect();

        if (auth()->user()?->hasPermission('view_ai_usage')) {
            $aiRequests = AiRequest::query()->where('created_at', '>=', now()->subDays(30));

            $requestCount = (clone $aiRequests)->count();
            $successCount = (clone $aiRequests)->where('status', AiStatus::Succeeded->value)->count();
            $failureCount = (clone $aiRequests)->whereIn('status', [
                AiStatus::Rejected->value,
                AiStatus::RateLimited->value,
                AiStatus::TimedOut->value,
                AiStatus::Failed->value,
            ])->count();

            $aiStats = [
                'requests_count' => $requestCount,
                'success_rate' => $requestCount > 0 ? round(($successCount / $requestCount) * 100, 1) : 0.0,
                'failure_count' => $failureCount,
                'total_tokens' => (int) (clone $aiRequests)->sum('total_tokens'),
                'estimated_cost' => (string) (clone $aiRequests)->sum('estimated_cost'),
                'average_latency_ms' => (int) round((float) ((clone $aiRequests)->avg('latency_ms') ?? 0)),
            ];

            $usageByDate = AiUsageDaily::query()
                ->selectRaw('usage_date, sum(requests_count) as requests_count, sum(total_tokens) as total_tokens')
                ->where('usage_date', '>=', now()->subDays(29)->toDateString())
                ->groupBy('usage_date')
                ->orderBy('usage_date')
                ->get()
                ->keyBy('usage_date');

            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $aiChartDates[] = $date;
                $aiChartRequests[] = (int) ($usageByDate[$date]->requests_count ?? 0);
                $aiChartTokens[] = (int) ($usageByDate[$date]->total_tokens ?? 0);
            }

            $recentAiRequests = AiRequest::with('user')->latest()->take(5)->get();
        }

        return view('admin.dashboard', compact(
            'stats',
            'recentActivity',
            'chartDates',
            'chartViews',
            'topPosts',
            'aiStats',
            'aiChartDates',
            'aiChartRequests',
            'aiChartTokens',
            'recentAiRequests'
        ));
    }
}
