<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class PageCacheMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests for unauthenticated users
        if (!$request->isMethod('GET') || auth()->check()) {
            return $next($request);
        }

        $key = 'page_cache_' . md5($request->fullUrl());

        if (Cache::has($key)) {
            return response(Cache::get($key));
        }

        $response = $next($request);

        // Only cache successful HTML responses
        if ($response->status() === 200 && str_contains($response->headers->get('Content-Type') ?? '', 'text/html')) {
            // Cache for 24 hours (we will invalidate manually on content change)
            Cache::put($key, $response->getContent(), now()->addDay());
        }

        return $response;
    }
}
